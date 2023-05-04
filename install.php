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
	outn(_('Adding index to did field...'));
	$sql = "ALTER TABLE `$db_name`.`$db_table_name` ADD INDEX `did` (`did` ASC)";
	$result = $dbcdr->query($sql);
	if(DB::IsError($result)) {
		out(_("Unable to add index to did field in the cdr table"));
		freepbx_log(FPBX_LOG_ERROR, "Failed to add index to did field in the cdr table");
	} else {
		out(_("Adding index to did field in the cdr table"));
	}
	out(_('Done'));
}

$sql = "SHOW KEYS FROM `$db_name`.`$db_table_name` WHERE Key_name='recordingfile'";
$check = $dbcdr->getOne($sql);
if (empty($check)) {
	outn(_('Adding index to recordingfile field...'));
	$sql = "ALTER TABLE `$db_name`.`$db_table_name` ADD INDEX `recordingfile` (`recordingfile` ASC)";
	$result = $dbcdr->query($sql);
	if(DB::IsError($result)) {
		out(_("Unable to add index to recordingfile field in the cdr table"));
		freepbx_log(FPBX_LOG_ERROR, "Failed to add index to recordingfile field in the cdr table");
	} else {
		out(_("Adding index to recordingfile field in the cdr table"));
	}
	out(_('Done'));
}

// Remove this section in FreePBX 14
$db_name = FreePBX::Config()->get('CDRDBNAME');
$db_host = FreePBX::Config()->get('CDRDBHOST');
$db_port = FreePBX::Config()->get('CDRDBPORT');
$db_user = FreePBX::Config()->get('CDRDBUSER');
$db_pass = FreePBX::Config()->get('CDRDBPASS');
$db_table = FreePBX::Config()->get('CDRDBTABLENAME');
$dbt = FreePBX::Config()->get('CDRDBTYPE');

global $amp_conf;

$db_hash = array('mysql' => 'mysql', 'postgres' => 'pgsql');
$dbt = !empty($dbt) ? $dbt : 'mysql';
$db_type = $db_hash[$dbt];
$db_table_name = !empty($db_table) ? $db_table : "cdr";
$db_name = !empty($db_name) ? $db_name : "asteriskcdrdb";
$db_host = empty($db_host) ?  $amp_conf['AMPDBHOST'] : $db_host;
$db_port = empty($db_port) ? '' :  ';port=' . $db_port;
$db_user = empty($db_user) ? $amp_conf['AMPDBUSER'] : $db_user;
$db_pass = empty($db_pass) ? $amp_conf['AMPDBPASS'] : $db_pass;

$pdo = new \Database($db_type.':host='.$db_host.$db_port.';dbname='.$db_name,$db_user,$db_pass);
$cid_fields = array('cnum', 'cnam', 'outbound_cnum', 'outbound_cnam', 'dst_cnam');

foreach($cid_fields as $cf) {
	outn(_("Checking if field $cf is present in cdr table.."));
	try {
		$sql = "SELECT $cf FROM `$db_name`.`$db_table_name` limit 1";
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

$alterclauses = array();
/*Add standard fields: linkedid, peeraccount, sequence*/
$stdfields=array('linkedid'=>array('VARCHAR',32,'\'\''),'peeraccount'=>array('VARCHAR',80,'\'\''),'sequence'=>array('INT',11,0));
foreach($stdfields as $name => $type) {
    try {
        outn(_("Checking if field $name is present in cdr table.."));
        $sql = "SELECT $name FROM `$db_name`.`$db_table_name` LIMIT 1";
        $confs = $pdo->query($sql, DB_FETCHMODE_ASSOC);
        out(_("OK!"));
        continue;
    } catch (\Exception $e) {
        out(_("Adding!"));
        $alterclauses[] = ' ADD `'.$name.'` '.$type[0].'('.$type[1].') NOT NULL DEFAULT '.$type[2];
    }
}

if (count($alterclauses)) {
    $sql = "ALTER TABLE `$db_name`.`$db_table_name`";
    $sql .= implode(",", $alterclauses);
    $result = $pdo->query($sql);
    if(DB::IsError($result)) {
        out($sql);
        out(_("ERROR failed to update database"));
    } else {
        out(_("OK!"));
    }
}

$freepbx_conf = freepbx_conf::create();
$webroot = \FreePBX::Config()->get('AMPWEBROOT');
$cdrConfFile = $webroot.'/admin/modules/cdr/etc/cdr.conf';
//check for existing batch conf
if(isset($amp_conf['CDR_BATCH_ENABLE'])) {
	$enable = $amp_conf['CDR_BATCH_ENABLE'];
	$batch = $amp_conf['CDR_BATCH'];
	$size = $amp_conf['CDR_BATCH_SIZE'];
	$time = $amp_conf['CDR_BATCH_TIME'];
	$scheduleOnly = $amp_conf['CDR_BATCH_SCHEDULE_ONLY'];
	$safeShutDown = $amp_conf['CDR_BATCH_SAFE_SHUT_DOWN'];
} else if (file_exists($amp_conf['ASTETCDIR'] . '/cdr.conf') && (filesize($cdrConfFile) != filesize($amp_conf['ASTETCDIR']. '/cdr.conf') && md5_file($cdrConfFile) != md5_file($amp_conf['ASTETCDIR']. '/cdr.conf'))){
	$cdrfile = fopen($amp_conf['ASTETCDIR'] . '/cdr.conf', "r");
	$additionalConfLines = [];
	while(($line = fgets($cdrfile)) !== false) {
		if(substr($line,0,1) == ";" || empty($line)) {
			continue;
		}
		$bvals = explode("=",$line);
		switch ($bvals[0]) {
			case 'enable':
				$enable = $bvals[1];
				break;
			case 'batch':
				$batch = $bvals[1];
				break;
			case 'size':
				$size = $bvals[1];
				break;
			case 'time':
				$time = $bvals[1];
				break;
			case 'scheduleronly':
				$scheduleOnly = $bvals[1];
				break;
			case 'safeshutdown':
				$safeShutDown = $bvals[1];
				break;
			default:
				$additionalConfLines[] = $line;
				break;
		}
	}
	writeCustomFiles($additionalConfLines);
	fclose($cdrfile);
} else {
	$enable = 0;
	$batch = 0;
	$size = 200;
	$time = 300;
	$scheduleOnly = 0;
	$safeShutDown = 0;
}
$set['value'] = ($enable == 1 || trim($enable) == 'yes') ? 1 : 0;
$set['defaultval'] = 0;
$set['readonly'] = 0;
$set['hidden'] = 0;
$set['level'] = 3;
$set['module'] = 'cdr';
$set['category'] = 'CDR Batch Mode';
$set['emptyok'] = 0;
$set['sortorder'] = 1;
$set['name'] = 'Enable CDR Batch Mode';
$set['description'] = 'Define whether or not to use CDR logging. Default is "no"';
$set['type'] = CONF_TYPE_BOOL;
$freepbx_conf->define_conf_setting('CDR_BATCH_ENABLE',$set);

$set['value'] = ($batch == 1 || trim($batch) == 'yes') ? 1 : 0;
$set['defaultval'] = 0;
$set['readonly'] = 0;
$set['hidden'] = 0;
$set['level'] = 3;
$set['module'] = 'cdr';
$set['category'] = 'CDR Batch Mode';
$set['emptyok'] = 0;
$set['sortorder'] = 2;
$set['name'] = 'CDR Batch';
$set['description'] = 'Use of batch mode may result in data loss after unsafe asterisk termination ie. software crash, power failure, kill -9, etc. Default is "no"';
$set['type'] = CONF_TYPE_BOOL;
$freepbx_conf->define_conf_setting('CDR_BATCH',$set);

$set['value'] = ($size) ? $size : 200;
$set['defaultval'] = '200';
$set['options'] = array(0,300);
$set['readonly'] = 0;
$set['hidden'] = 0;
$set['level'] = 1;
$set['module'] = 'cdr';
$set['category'] = 'CDR Batch Mode';
$set['emptyok'] = 0;
$set['sortorder'] = 3;
$set['name'] = "CDR Batch Size";
$set['description'] = "Define the maximum number of CDRs to accumulate in the buffer before posting them to the backend engines.  'batch' must be set to 'yes'.  Default is 200.";
$set['type'] = CONF_TYPE_INT;
$freepbx_conf->define_conf_setting('CDR_BATCH_SIZE',$set);

$set['value'] = ($time) ? $time : 300;
$set['defaultval'] = '300';
$set['options'] = array(0,300);
$set['readonly'] = 0;
$set['hidden'] = 0;
$set['level'] = 1;
$set['module'] = 'cdr';
$set['category'] = 'CDR Batch Mode';
$set['emptyok'] = 0;
$set['sortorder'] = 4;
$set['name'] = "CDR Batch Time";
$set['description'] = "Define the maximum time to accumulate CDRs in the buffer before posting them to the backend engines. If this time limit is reached, then it will post the records, regardless of the value defined for 'size'. 'batch' must be set to 'yes'.  Note that time is in seconds. Default is 300 (5 minutes).";
$set['type'] = CONF_TYPE_INT;
$freepbx_conf->define_conf_setting('CDR_BATCH_TIME',$set);

$set['value'] = ($scheduleOnly == 1 || trim($scheduleOnly) == 'yes') ? 1 : 0;
$set['defaultval'] = 0;
$set['readonly'] = 0;
$set['hidden'] = 0;
$set['level'] = 3;
$set['module'] = 'cdr';
$set['category'] = 'CDR Batch Mode';
$set['emptyok'] = 0;
$set['sortorder'] = 5;
$set['name'] = 'CDR Schedule Only';
$set['description'] = 'The CDR engine uses the internal asterisk scheduler to determine when to post records. Posting can either occur inside the scheduler thread, or a new thread can be spawned for the submission of every batch. For small batches, it might be acceptable to just use the scheduler thread, so set this to "yes". For large batches, say anything over size=10, a new thread is recommended, so set this to "no".  Default is "no".';
$set['type'] = CONF_TYPE_BOOL;
$freepbx_conf->define_conf_setting('CDR_BATCH_SCHEDULE_ONLY',$set);

$set['value'] = ($safeShutDown == 1 || trim($safeShutDown) == 'yes') ? 1 : 0;
$set['defaultval'] = 0;
$set['readonly'] = 0;
$set['hidden'] = 0;
$set['level'] = 3;
$set['module'] = 'cdr';
$set['category'] = 'CDR Batch Mode';
$set['emptyok'] = 0;
$set['sortorder'] = 6;
$set['name'] = 'CDR Batch Safe ShutDown';
$set['description'] = "When shutting down asterisk, you can block until the CDRs are submitted. If you don't, then data will likely be lost.  You can always check the size of the CDR batch buffer with the CLI 'cdr status command. To enable blocking on submission of CDR data during asterisk shutdown, set this to 'yes'. Default is 'no'.";
$set['type'] = CONF_TYPE_BOOL;
$freepbx_conf->define_conf_setting('CDR_BATCH_SAFE_SHUT_DOWN',$set);