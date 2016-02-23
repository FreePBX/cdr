<?php
if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }
//	License for all code of this FreePBX module can be found in the license file inside the module directory
//  Portions Copyright (C) 2011 Mikael Carlsson
//	Copyright 2013 Schmooze Com Inc.
//
// Update cdr database with did field
//
global $db;
global $amp_conf;

// Retrieve database and table name if defined, otherwise use FreePBX default
$db_name = !empty($amp_conf['CDRDBNAME'])?$amp_conf['CDRDBNAME']:"asteriskcdrdb";
$db_table_name = !empty($amp_conf['CDRDBTABLENAME'])?$amp_conf['CDRDBTABLENAME']:"cdr";

// if CDRDBHOST and CDRDBTYPE are not empty then we assume an external connection and don't use the default connection
//
if (!empty($amp_conf["CDRDBHOST"]) && !empty($amp_conf["CDRDBTYPE"])) {
	$db_hash = array('mysql' => 'mysql', 'postgres' => 'pgsql');
	$db_type = $db_hash[$amp_conf["CDRDBTYPE"]];
	$db_host = $amp_conf["CDRDBHOST"];
	$db_port = empty($amp_conf["CDRDBPORT"]) ? '' :  ':' . $amp_conf["CDRDBPORT"];
	$db_user = empty($amp_conf["CDRDBUSER"]) ? $amp_conf["AMPDBUSER"] : $amp_conf["CDRDBUSER"];
	$db_pass = empty($amp_conf["CDRDBPASS"]) ? $amp_conf["AMPDBPASS"] : $amp_conf["CDRDBPASS"];
	$datasource = $db_type . '://' . $db_user . ':' . $db_pass . '@' . $db_host . $db_port . '/' . $db_name;
	$dbcdr = DB::connect($datasource); // attempt connection
	if(DB::isError($dbcdr)) {
		die_freepbx($dbcdr->getDebugInfo());
	}
} else {
	$dbcdr = $db;
}

if (! function_exists("out")) {
        function out($text) {
                echo $text."<br />";
        }
}

// Remove this section in FreePBX 14
$sql = "SHOW KEYS FROM `$db_name`.`$db_table_name` WHERE Key_name='did'";
$check = $dbcdr->getOne($sql);
if (empty($check)) {
	$sql = "ALTER TABLE `$db_name`.`$db_table_name` ADD INDEX `did` (`did` ASC)";
	$result = $dbcdr->query($sql);
	if(DB::IsError($result)) {
		out(_("Unable to add index to did field in the cdr table"));
		freepbx_log(FPBX_LOG_ERROR, "Failed to add index to did field in the cdr table");
	} else {
		out(_("Adding index to did field in the cdr table"));
	}
}

// Remove this section in FreePBX 14
$pdo = \FreePBX::Database();
$cid_fields = array('cnum', 'cnam', 'outbound_cnum', 'outbound_cnam', 'dst_cnam');

foreach($cid_fields as $cf) {
	outn(_("Checking if field $cf is present in cdr table.."));
	try {
		$sql = "SELECT $cf FROM `$db_name`.`$db_table_name`";
		$confs = $pdo->query($sql, DB_FETCHMODE_ASSOC);
		// If we didn't throw an exception, we're done.
		out(_("OK!"));
		continue;
	} catch (\Exception $e) {
		out(_("Adding!"));
		$sql = "ALTER TABLE `$db_name`.`$db_table_name` ADD $cf VARCHAR ( 80 ) NOT NULL default ''";
		$pdo->query($sql);
	}
}


