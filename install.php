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
out(_("Checking if field did is present in cdr table.."));
$sql = "SELECT did FROM `$db_name`.`$db_table_name`";
$confs = $dbcdr->getRow($sql, DB_FETCHMODE_ASSOC);
if (DB::IsError($confs)) { // no error... Already there
  out(_("Adding did field to cdr"));
  out(_("This might take a while......"));
  $sql = "ALTER TABLE `$db_name`.`$db_table_name` ADD did VARCHAR ( 50 ) NOT NULL DEFAULT ''";
  $results = $dbcdr->query($sql);
  if(DB::IsError($results)) {
    die($results->getMessage());
  }
  out(_("Added field did to cdr"));
} else {
  out(_("did field already present."));
}

out(_("Checking if field recordingfile is present in cdr table.."));
$sql = "SELECT recordingfile FROM `$db_name`.`$db_table_name`";
$confs = $dbcdr->getRow($sql, DB_FETCHMODE_ASSOC);
if (DB::IsError($confs)) { // no error... Already there
    out(_("Adding recordingfile field to cdr"));
    $sql = "ALTER TABLE `$db_name`.`$db_table_name` ADD recordingfile VARCHAR ( 255 ) NOT NULL DEFAULT ''";
    $results = $dbcdr->query($sql);
    if(DB::IsError($results)) {
        out(_('Unable to add recordingfile field to cdr table'));
        freepbx_log(FPBX_LOG_ERROR,"failed to add recordingfile field to cdr table");
    } else {
        out(_("Added field recordingfile to cdr"));
    }
} else {
      out(_("recordingfile field already present."));
}

$cid_fields = array('cnum', 'cnam', 'outbound_cnum', 'outbound_cnam', 'dst_cnam');
foreach($cid_fields as $cf) {
	out(_("Checking if field $cf is present in cdr table.."));
	$sql = "SELECT $cf FROM `$db_name`.`$db_table_name`";
	$confs = $dbcdr->getRow($sql, DB_FETCHMODE_ASSOC);
	if (DB::IsError($confs)) { // no error... Already there
    	out(_("Adding $cf field to cdr"));
	$sql = "ALTER TABLE `$db_name`.`$db_table_name` ADD $cf VARCHAR ( 40 ) NOT NULL DEFAULT ''";
    	$results = $dbcdr->query($sql);
    	if(DB::IsError($results)) {
        	out(_("Unable to add $cf field to cdr table"));
        	freepbx_log(FPBX_LOG_ERROR,"failed to add $cf field to cdr table");
    	} else {
        	out(_("Added field $cf to cdr"));
					// TODO: put onetime notification about old src field searches and query that could be
					// done if user wants to get that into cnum field.
    	}
	} else {
      	out(_("$cf field already present."));
	}
}

$sql = "SHOW KEYS FROM `$db_name`.`$db_table_name` WHERE Key_name='uniqueid'";
$check = $dbcdr->getOne($sql);
if (empty($check)) {
	$sql = "ALTER TABLE `$db_name`.`$db_table_name` ADD INDEX `uniqueid` (`uniqueid` ASC)";
	$result = $dbcdr->query($sql);
	if(DB::IsError($result)) {
		out(_("Unable to add index to uniqueid field in cdr table"));
		freepbx_log(FPBX_LOG_ERROR, "Failed to add index to uniqueid field in the cdr table");
	} else {
		out(_("Adding index to uniqueid field in the cdr table"));
	}
}

$sql = "SHOW KEYS FROM `$db_name`.`$db_table_name` WHERE Key_name='did'";
$check = $dbcdr->getOne($sql);
if (empty($check)) {
	$sql = "ALTER TABLE `$db_name`.`$db_table_name` ADD INDEX `did` (`did` ASC)";
	$result = $dbcdr->query($sql);
	if(DB::IsError($result)) {
		out(_("Unable to add index todid field in cdr table"));
		freepbx_log(FPBX_LOG_ERROR, "Failed to add index to did field in the cdr table");
	} else {
		out(_("Adding index to did field in the cdr table"));
	}
}

$info = FreePBX::Modules()->getInfo("cdr");
if(version_compare_freepbx($info['cdr']['dbversion'], "12.0.13", "<=")) {
	if(FreePBX::Modules()->checkStatus('ucp') && FreePBX::Modules()->checkStatus('userman')) {
		$users = FreePBX::Userman()->getAllUsers();
		foreach($users as $user) {
			$exts = FreePBX::Ucp()->getSetting($user['username'],'Settings','assigned');
			if(!empty($exts)) {
				FreePBX::Ucp()->setSetting($user['username'],'Cdr','assigned',$exts);
			}
		}
	} elseif(FreePBX::Modules()->checkStatus('ucp',MODULE_STATUS_NEEDUPGRADE)) {
		out(_("Please upgrade UCP before this module so that settings can be properly migrated"));
		return false;
	} elseif(FreePBX::Modules()->checkStatus('userman',MODULE_STATUS_NEEDUPGRADE)) {
		out(_("Please upgrade Usermanager before this module so that settings can be properly migrated"));
		return false;
	}
}
