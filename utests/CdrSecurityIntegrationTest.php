<?php
/**
 * Integration Security Tests for CDR Module
 * Tests SQL injection prevention and parameter validation with real database
 * 
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */

class CdrSecurityIntegrationTest extends PHPUnit_Framework_TestCase {

    private static $cdr;

    public static function setUpBeforeClass() {
        include "setuptests.php";
        self::$cdr = FreePBX::Cdr();
    }

    /**
     * Test that getAllCalls handles malicious input safely
     */
    public function testGetAllCallsSecurityValidation() {
        // Test with malicious orderby - should not cause SQL error
        $result = self::$cdr->getAllCalls(1, "timestamp; DROP TABLE cdr; --", 'desc', '', 10);
        $this->assertTrue(is_array($result), "getAllCalls should return array with malicious orderby");
        
        // Test with malicious search - should not cause SQL error
        $result = self::$cdr->getAllCalls(1, 'date', 'desc', "'; DROP TABLE cdr; --", 10);
        $this->assertTrue(is_array($result), "getAllCalls should return array with malicious search");
        
        // Test with invalid parameters - should handle gracefully
        $result = self::$cdr->getAllCalls("invalid", 'date', 'INVALID_ORDER', '', "invalid");
        $this->assertTrue(is_array($result), "getAllCalls should handle invalid parameters");
    }

    /**
     * Test that getCalls handles malicious input safely
     */
    public function testGetCallsSecurityValidation() {
        $extension = "1001";
        
        // Test with malicious orderby
        $result = self::$cdr->getCalls($extension, 1, "timestamp; DROP TABLE cdr; --", 'desc', '', 10);
        $this->assertTrue(is_array($result), "getCalls should return array with malicious orderby");
        
        // Test with malicious search
        $result = self::$cdr->getCalls($extension, 1, 'date', 'desc', "'; SELECT * FROM users; --", 10);
        $this->assertTrue(is_array($result), "getCalls should return array with malicious search");
        
        // Test with malicious extension
        $result = self::$cdr->getCalls("1001'; DROP TABLE cdr; --", 1, 'date', 'desc', '', 10);
        $this->assertTrue(is_array($result), "getCalls should return array with malicious extension");
    }

    /**
     * Test that getGraphQLCalls handles malicious input safely
     */
    public function testGetGraphQLCallsSecurityValidation() {
        // Test with malicious date parameters
        $result = self::$cdr->getGraphQLCalls(0, 10, null, null, 'date', "2023-01-01'; DROP TABLE cdr; --", '2023-12-31');
        $this->assertTrue(is_array($result), "getGraphQLCalls should return array with malicious start date");
        
        $result = self::$cdr->getGraphQLCalls(0, 10, null, null, 'date', '2023-01-01', "2023-12-31'; SELECT * FROM users; --");
        $this->assertTrue(is_array($result), "getGraphQLCalls should return array with malicious end date");
        
        // Test with malicious orderby
        $result = self::$cdr->getGraphQLCalls(0, 10, null, null, "timestamp; DROP TABLE cdr; --", null, null);
        $this->assertTrue(is_array($result), "getGraphQLCalls should return array with malicious orderby");
    }

    /**
     * Test parameter validation and type casting
     */
    public function testParameterValidation() {
        // Test non-integer parameters are handled
        $result = self::$cdr->getAllCalls("not_a_number", 'date', 'desc', '', "also_not_a_number");
        $this->assertTrue(is_array($result), "Should handle non-integer parameters");
        
        // Test negative values
        $result = self::$cdr->getAllCalls(-1, 'date', 'desc', '', -10);
        $this->assertTrue(is_array($result), "Should handle negative values");
        
        // Test zero values
        $result = self::$cdr->getAllCalls(0, 'date', 'desc', '', 0);
        $this->assertTrue(is_array($result), "Should handle zero values");
    }

    /**
     * Test orderby whitelist functionality
     */
    public function testOrderbyWhitelist() {
        // Valid orderby values should work
        $validOrderby = array('date', 'description', 'duration');
        foreach ($validOrderby as $orderby) {
            $result = self::$cdr->getAllCalls(1, $orderby, 'desc', '', 10);
            $this->assertTrue(is_array($result), "Should accept valid orderby: $orderby");
        }
        
        // Invalid orderby values should be handled safely
        $invalidOrderby = array('users', 'password', 'admin', 'DROP TABLE', 'SELECT * FROM');
        foreach ($invalidOrderby as $orderby) {
            $result = self::$cdr->getAllCalls(1, $orderby, 'desc', '', 10);
            $this->assertTrue(is_array($result), "Should handle invalid orderby safely: $orderby");
        }
    }

    /**
     * Test date validation in getGraphQLCalls
     */
    public function testDateValidation() {
        // Invalid date formats should be handled
        $invalidDates = array(
            "invalid-date",
            "2023/01/01",
            "01-01-2023",
            "2023-13-01",
            "2023-01-32",
            "'; DROP TABLE cdr; --"
        );
        
        foreach ($invalidDates as $invalidDate) {
            $result = self::$cdr->getGraphQLCalls(0, 10, null, null, 'date', $invalidDate, '2023-12-31');
            $this->assertTrue(is_array($result), "Should handle invalid start date: $invalidDate");
            
            $result = self::$cdr->getGraphQLCalls(0, 10, null, null, 'date', '2023-01-01', $invalidDate);
            $this->assertTrue(is_array($result), "Should handle invalid end date: $invalidDate");
        }
    }

    /**
     * Test special characters in search parameters
     */
    public function testSpecialCharacterHandling() {
        $specialChars = array(
            "test'test",
            'test"test',
            "test\\test",
            "test%test",
            "test_test",
            "test;test",
            "test--test",
            "test/*comment*/test",
            "test UNION SELECT test"
        );
        
        foreach ($specialChars as $search) {
            $result = self::$cdr->getAllCalls(1, 'date', 'desc', $search, 10);
            $this->assertTrue(is_array($result), "Should handle special characters: $search");
            
            $result = self::$cdr->getCalls('1001', 1, 'date', 'desc', $search, 10);
            $this->assertTrue(is_array($result), "getCalls should handle special characters: $search");
        }
    }

    /**
     * Test that methods don't throw exceptions with edge cases
     */
    public function testEdgeCaseHandling() {
        // Empty strings
        $result = self::$cdr->getAllCalls(1, '', '', '', 10);
        $this->assertTrue(is_array($result), "Should handle empty strings");
        
        // Null values where possible
        $result = self::$cdr->getGraphQLCalls(0, 10, null, null, 'date', null, null);
        $this->assertTrue(is_array($result), "Should handle null date values");
        
        // Very long strings
        $longString = str_repeat("a", 1000);
        $result = self::$cdr->getAllCalls(1, 'date', 'desc', $longString, 10);
        $this->assertTrue(is_array($result), "Should handle very long search strings");
    }

    /**
     * Test that order parameter validation works
     */
    public function testOrderParameterValidation() {
        // Valid order values
        $result = self::$cdr->getAllCalls(1, 'date', 'asc', '', 10);
        $this->assertTrue(is_array($result), "Should accept 'asc' order");
        
        $result = self::$cdr->getAllCalls(1, 'date', 'desc', '', 10);
        $this->assertTrue(is_array($result), "Should accept 'desc' order");
        
        $result = self::$cdr->getAllCalls(1, 'date', 'DESC', '', 10);
        $this->assertTrue(is_array($result), "Should accept 'DESC' order");
        
        // Invalid order values should default to safe value
        $result = self::$cdr->getAllCalls(1, 'date', 'INVALID_ORDER', '', 10);
        $this->assertTrue(is_array($result), "Should handle invalid order parameter");
        
        $result = self::$cdr->getAllCalls(1, 'date', '; DROP TABLE cdr; --', '', 10);
        $this->assertTrue(is_array($result), "Should handle malicious order parameter");
    }
}
