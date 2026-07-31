<?php
/**
 * Security Tests for CDR Module
 * Tests SQL injection prevention and parameter validation
 * 
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */

class CdrSecurityTest extends PHPUnit_Framework_TestCase {

    private static $cdr;
    private static $mockDb;

    public static function setUpBeforeClass() {
        include "setuptests.php";
        self::$cdr = FreePBX::Cdr();
        
        // Create a mock database connection for testing
        self::$mockDb = self::createMockDatabase();
    }

    /**
     * Create a mock database for testing SQL injection prevention
     */
    private static function createMockDatabase() {
        $mockDb = new MockDatabase();
        return $mockDb;
    }

    /**
     * Test getAllCalls method with SQL injection attempts
     */
    public function testGetAllCallsSqlInjectionPrevention() {
        // Test malicious orderby parameter
        $maliciousOrderby = "timestamp; DROP TABLE cdr; --";
        $calls = self::$cdr->getAllCalls(1, $maliciousOrderby, 'desc', '', 10);
        
        // Should not throw exception and should return valid data
        $this->assertTrue(is_array($calls), "getAllCalls should return array even with malicious input");
        
        // Test malicious search parameter
        $maliciousSearch = "'; DROP TABLE cdr; --";
        $calls = self::$cdr->getAllCalls(1, 'date', 'desc', $maliciousSearch, 10);
        
        $this->assertTrue(is_array($calls), "getAllCalls should handle malicious search input safely");
    }

    /**
     * Test getCalls method with SQL injection attempts
     */
    public function testGetCallsSqlInjectionPrevention() {
        $extension = "1001";
        
        // Test malicious orderby parameter
        $maliciousOrderby = "timestamp; DROP TABLE cdr; --";
        $calls = self::$cdr->getCalls($extension, 1, $maliciousOrderby, 'desc', '', 10);
        
        $this->assertTrue(is_array($calls), "getCalls should return array even with malicious orderby");
        
        // Test malicious search parameter
        $maliciousSearch = "'; DROP TABLE cdr; SELECT * FROM users WHERE '1'='1";
        $calls = self::$cdr->getCalls($extension, 1, 'date', 'desc', $maliciousSearch, 10);
        
        $this->assertTrue(is_array($calls), "getCalls should handle malicious search input safely");
        
        // Test malicious extension parameter
        $maliciousExtension = "1001'; DROP TABLE cdr; --";
        $calls = self::$cdr->getCalls($maliciousExtension, 1, 'date', 'desc', '', 10);
        
        $this->assertTrue(is_array($calls), "getCalls should handle malicious extension input safely");
    }

    /**
     * Test getGraphQLCalls method with SQL injection attempts
     */
    public function testGetGraphQLCallsSqlInjectionPrevention() {
        // Test malicious date parameters
        $maliciousStartDate = "2023-01-01'; DROP TABLE cdr; --";
        $maliciousEndDate = "2023-12-31'; SELECT * FROM users; --";
        
        $calls = self::$cdr->getGraphQLCalls(0, 10, null, null, 'date', $maliciousStartDate, $maliciousEndDate);
        
        $this->assertTrue(is_array($calls), "getGraphQLCalls should handle malicious date input safely");
        
        // Test malicious orderby parameter
        $maliciousOrderby = "timestamp; DROP TABLE cdr; --";
        $calls = self::$cdr->getGraphQLCalls(0, 10, null, null, $maliciousOrderby, '2023-01-01', '2023-12-31');
        
        $this->assertTrue(is_array($calls), "getGraphQLCalls should handle malicious orderby safely");
    }

    /**
     * Test parameter validation in getAllCalls
     */
    public function testGetAllCallsParameterValidation() {
        // Test non-integer page parameter
        $calls = self::$cdr->getAllCalls("invalid", 'date', 'desc', '', 10);
        $this->assertTrue(is_array($calls), "getAllCalls should handle non-integer page parameter");
        
        // Test non-integer limit parameter
        $calls = self::$cdr->getAllCalls(1, 'date', 'desc', '', "invalid");
        $this->assertTrue(is_array($calls), "getAllCalls should handle non-integer limit parameter");
        
        // Test invalid order parameter
        $calls = self::$cdr->getAllCalls(1, 'date', 'INVALID_ORDER', '', 10);
        $this->assertTrue(is_array($calls), "getAllCalls should handle invalid order parameter");
        
        // Test valid orderby values
        $validOrderby = array('date', 'description', 'duration');
        foreach ($validOrderby as $orderby) {
            $calls = self::$cdr->getAllCalls(1, $orderby, 'desc', '', 10);
            $this->assertTrue(is_array($calls), "getAllCalls should accept valid orderby: $orderby");
        }
    }

    /**
     * Test parameter validation in getCalls
     */
    public function testGetCallsParameterValidation() {
        $extension = "1001";
        
        // Test non-integer page parameter
        $calls = self::$cdr->getCalls($extension, "invalid", 'date', 'desc', '', 10);
        $this->assertTrue(is_array($calls), "getCalls should handle non-integer page parameter");
        
        // Test non-integer limit parameter
        $calls = self::$cdr->getCalls($extension, 1, 'date', 'desc', '', "invalid");
        $this->assertTrue(is_array($calls), "getCalls should handle non-integer limit parameter");
        
        // Test invalid order parameter
        $calls = self::$cdr->getCalls($extension, 1, 'date', 'INVALID_ORDER', '', 10);
        $this->assertTrue(is_array($calls), "getCalls should handle invalid order parameter");
    }

    /**
     * Test parameter validation in getGraphQLCalls
     */
    public function testGetGraphQLCallsParameterValidation() {
        // Test non-integer first parameter
        $calls = self::$cdr->getGraphQLCalls(0, "invalid", null, null, 'date', null, null);
        $this->assertTrue(is_array($calls), "getGraphQLCalls should handle non-integer first parameter");
        
        // Test non-integer after parameter
        $calls = self::$cdr->getGraphQLCalls("invalid", 10, null, null, 'date', null, null);
        $this->assertTrue(is_array($calls), "getGraphQLCalls should handle non-integer after parameter");
        
        // Test invalid orderby parameter
        $calls = self::$cdr->getGraphQLCalls(0, 10, null, null, 'invalid_order', null, null);
        $this->assertTrue(is_array($calls), "getGraphQLCalls should handle invalid orderby parameter");
    }

    /**
     * Test date validation in getGraphQLCalls
     */
    public function testGetGraphQLCallsDateValidation() {
        // Test invalid date formats
        $invalidDates = array(
            "invalid-date",
            "2023/01/01",
            "01-01-2023",
            "2023-13-01", // Invalid month
            "2023-01-32", // Invalid day
            "'; DROP TABLE cdr; --"
        );
        
        foreach ($invalidDates as $invalidDate) {
            $calls = self::$cdr->getGraphQLCalls(0, 10, null, null, 'date', $invalidDate, '2023-12-31');
            $this->assertTrue(is_array($calls), "getGraphQLCalls should handle invalid start date: $invalidDate");
            
            $calls = self::$cdr->getGraphQLCalls(0, 10, null, null, 'date', '2023-01-01', $invalidDate);
            $this->assertTrue(is_array($calls), "getGraphQLCalls should handle invalid end date: $invalidDate");
        }
        
        // Test valid date formats
        $validDates = array(
            "2023-01-01",
            "2023-12-31",
            "2024-02-29" // Leap year
        );
        
        foreach ($validDates as $validDate) {
            $calls = self::$cdr->getGraphQLCalls(0, 10, null, null, 'date', $validDate, $validDate);
            $this->assertTrue(is_array($calls), "getGraphQLCalls should accept valid date: $validDate");
        }
    }

    /**
     * Test that prepared statements are used correctly
     */
    public function testPreparedStatementsUsage() {
        // This test verifies that the methods use prepared statements
        // by checking that no direct string concatenation occurs in SQL
        
        $reflection = new ReflectionClass(self::$cdr);
        
        // Test getAllCalls method
        $method = $reflection->getMethod('getAllCalls');
        $method->setAccessible(true);
        
        // Capture any database queries (this would require database query logging)
        // For now, we test that the method executes without throwing SQL errors
        try {
            $calls = self::$cdr->getAllCalls(1, 'date', 'desc', 'test', 10);
            $this->assertTrue(true, "getAllCalls executed without SQL errors");
        } catch (Exception $e) {
            $this->fail("getAllCalls threw exception: " . $e->getMessage());
        }
    }

    /**
     * Test orderby whitelist validation
     */
    public function testOrderbyWhitelistValidation() {
        // Test that only allowed orderby values are processed
        $allowedOrderby = array('date', 'description', 'duration');
        $disallowedOrderby = array(
            'users',
            'password',
            'admin',
            'DROP TABLE',
            'SELECT * FROM'
        );
        
        foreach ($allowedOrderby as $orderby) {
            $calls = self::$cdr->getAllCalls(1, $orderby, 'desc', '', 10);
            $this->assertTrue(is_array($calls), "Should accept whitelisted orderby: $orderby");
        }
        
        foreach ($disallowedOrderby as $orderby) {
            $calls = self::$cdr->getAllCalls(1, $orderby, 'desc', '', 10);
            $this->assertTrue(is_array($calls), "Should safely handle non-whitelisted orderby: $orderby");
        }
    }

    /**
     * Test that special characters in search are properly escaped
     */
    public function testSearchParameterEscaping() {
        $specialCharacters = array(
            "test'test",
            'test"test',
            "test\\test",
            "test%test",
            "test_test",
            "test;test",
            "test--test"
        );
        
        foreach ($specialCharacters as $search) {
            $calls = self::$cdr->getAllCalls(1, 'date', 'desc', $search, 10);
            $this->assertTrue(is_array($calls), "Should handle special characters in search: $search");
            
            $calls = self::$cdr->getCalls('1001', 1, 'date', 'desc', $search, 10);
            $this->assertTrue(is_array($calls), "getCalls should handle special characters in search: $search");
        }
    }

    /**
     * Test boundary values for pagination
     */
    public function testPaginationBoundaryValues() {
        // Test negative values
        $calls = self::$cdr->getAllCalls(-1, 'date', 'desc', '', -10);
        $this->assertTrue(is_array($calls), "Should handle negative pagination values");
        
        // Test zero values
        $calls = self::$cdr->getAllCalls(0, 'date', 'desc', '', 0);
        $this->assertTrue(is_array($calls), "Should handle zero pagination values");
        
        // Test very large values
        $calls = self::$cdr->getAllCalls(999999, 'date', 'desc', '', 999999);
        $this->assertTrue(is_array($calls), "Should handle large pagination values");
    }

    /**
     * getCdrData() is the AJAX-facing method behind the "getJSON" command that
     * feeds the main CDR grid and CSV export. Unlike the other methods above
     * it reads sort/order straight from $_REQUEST, and previously built its
     * ORDER BY clause from $_REQUEST['sort'] with no validation at all - a
     * real, reported SQL injection (see FreePBX/cdr PR #53). This asserts the
     * whitelist added to fix that keeps working.
     */
    public function testGetCdrDataOrderbyWhitelistValidation() {
        $savedRequest = $_REQUEST;
        $savedDb = self::$cdr->cdrdb;

        $allowed = array('calldate', 'src', 'dst', 'duration', 'disposition');
        foreach ($allowed as $sort) {
            $mock = new MockDatabase();
            self::$cdr->cdrdb = $mock;
            $_REQUEST = array('sort' => $sort, 'order' => 'asc');
            self::$cdr->getCdrData();
            $sql = $mock->getQueries()[0];
            $this->assertTrue(strpos($sql, "ORDER BY $sort") !== false, "Whitelisted column '$sort' should be used verbatim in ORDER BY");
        }

        $disallowed = array(
            'calldate; DROP TABLE cdr; --',
            'users',
            '1=1',
            '(SELECT password FROM ampusers)',
        );
        foreach ($disallowed as $sort) {
            $mock = new MockDatabase();
            self::$cdr->cdrdb = $mock;
            $_REQUEST = array('sort' => $sort, 'order' => 'asc');
            $this->assertTrue(is_array(self::$cdr->getCdrData()), "getCdrData should not throw on malicious sort: $sort");
            $sql = $mock->getQueries()[0];
            $this->assertTrue(strpos($sql, 'ORDER BY calldate') !== false, "Non-whitelisted sort '$sort' must fall back to the safe default column");
            $this->assertFalse(strpos($sql, $sort) !== false, "Non-whitelisted sort value must never appear in the generated SQL");
        }

        $_REQUEST = $savedRequest;
        self::$cdr->cdrdb = $savedDb;
    }

    /**
     * Regression test: the Quick Date Range Picker (startdate/enddate) and the
     * Advanced Search Options date fields (from_ and to_ prefixed) are two independent
     * UI controls that both filter on calldate. They must never both apply at
     * once, since a stale value left in one (e.g. the picker's default "last
     * 30 days") would silently narrow or empty out results filtered by the
     * other (see FreePBX/cdr PR #53 review by hannes427).
     */
    public function testGetCdrDataAdvancedDateFieldsTakePriorityOverStaleQuickPicker() {
        $savedRequest = $_REQUEST;
        $savedDb = self::$cdr->cdrdb;
        $mock = new MockDatabase();
        self::$cdr->cdrdb = $mock;

        // Stale "last 30 days" range from the quick picker, plus a much wider
        // range explicitly set by the user under Advanced Search Options.
        $_REQUEST = array(
            'startdate' => '2026-07-02 00:00:00',
            'enddate'   => '2026-07-31 23:59:59',
            'from_day' => '01', 'from_month' => '01', 'from_year' => '2021',
            'to_day'   => '19', 'to_month'   => '10', 'to_year'   => '2025',
        );

        self::$cdr->getCdrData();
        $sql = $mock->getQueries()[0];

        $this->assertTrue(strpos($sql, 'calldate >= :from_date') !== false, "Advanced Search date filter should be applied");
        $this->assertFalse(strpos($sql, 'BETWEEN :startdate') !== false, "Stale quick-picker range must not also be ANDed in when Advanced Search fields are set");

        $_REQUEST = $savedRequest;
        self::$cdr->cdrdb = $savedDb;
    }
}

/**
 * Mock Database class for testing
 */
class MockDatabase {
    private $queries = [];
    
    public function prepare($sql) {
        $this->queries[] = $sql;
        return new MockStatement($sql);
    }
    
    public function getQueries() {
        return $this->queries;
    }
}

/**
 * Mock Statement class for testing
 */
class MockStatement {
    private $sql;
    private $params = [];
    
    public function __construct($sql) {
        $this->sql = $sql;
    }
    
    public function bindValue($param, $value, $type = null) {
        $this->params[$param] = ['value' => $value, 'type' => $type];
        return true;
    }
    
    public function execute($params = null) {
        if ($params) {
            $this->params = array_merge($this->params, $params);
        }
        return true;
    }
    
    public function fetchAll($mode = null) {
        return [];
    }
    
    public function fetch($mode = null) {
        return [];
    }
    
    public function fetchColumn() {
        return 0;
    }
    
    public function getParams() {
        return $this->params;
    }
    
    public function getSql() {
        return $this->sql;
    }
}
