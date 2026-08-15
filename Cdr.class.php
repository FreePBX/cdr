<?php
// vim: set ai ts=4 sw=4 ft=php:
//
// This is the main interface to the CDR Database.
//
// It is used by multiple modules. Please don't alter it without
// running complete unit tests!
//
// The License for this FreePBX module can be found in the license file inside the
// module directory
//
// Copyright 2015, 2016 Sangoma Technologies Corporation

namespace FreePBX\modules;

class Cdr extends \FreePBX_Helpers implements \BMO {

	/** Public variable for access to the raw PDO handle */
	public $cdrdb;

	/** Cache of the FreePBX BMO Object */
	private $FreePBX;

	/** CDR Table name, set in __construct */
	private $db_table;

	public function __construct($freepbx = null) {
		if ($freepbx == null) {
			throw new \Exception(_("Not given a FreePBX Object"));
		}

		$this->FreePBX = $freepbx;

		// Variables to try. If the key is blank/unset, use the value instead.
		$vars = array(
			"CDRDBHOST" => "AMPDBHOST",
			"CDRDBPORT" => "AMPDBPORT",
			"CDRDBUSER" => "AMPDBUSER",
			"CDRDBPASS" => "AMPDBPASS",
			"CDRDBTYPE" => "AMPDBTYPE",
			// This is removed if unset
			"CDRDBSOCK" => "AMPDBSOCK",
			// Note - no default, we check later.
			"CDRDBNAME" => "CDRDBNAME",
			"CDRDBTABLENAME" => "CDRDBTABLENAME",
			"CDRUSEGMT" => "CDRUSEGMT",
		);

		$cdr = array();
		foreach ($vars as $conf => $default) {
			$tmp = \FreePBX::Config()->get($conf);
			// Is our config blank for this setting?
			if (!$tmp) {
				// How about the default?
				$defvalue = \FreePBX::Config()->get($default);
				if ($defvalue) {
					$cdr[$conf] = $defalue;
				} else {
					// Well that's blank. Is it part of FreePBX::$conf? (That's the parsed output of /etc/freepbx.conf)
					if (empty(\FreePBX::$conf[$default])) {
						// No. Set it to blank.
						$cdr[$conf] = "";
					} else {
						$cdr[$conf] = \FreePBX::$conf[$default];
					}
				}
			} else {
				// We have a setting
				$cdr[$conf] = $tmp;
			}
		}

		// If CDRDBNAME is blank, set it to asteriskcdrdb
		if (!$cdr['CDRDBNAME']) {
			$dsnarray = array("dbname" => "asteriskcdrdb");
		} else {
			$dsnarray = array("dbname" => $cdr['CDRDBNAME']);
        }

		// If we don't have a type (bogus install, possibly?), assume mysql
		if (!$cdr['CDRDBTYPE']) {
			$engine = "mysql";
		} else {
			// The db 'type' name can be wrong. Remap it to the correct one if it is
			if ($cdr['CDRDBTYPE'] == "postgres") {
				$engine = "pgsql";
			} else {
				$engine = $cdr['CDRDBTYPE'];
			}
		}

		// If we have a socket, we don't want host and port.
		if ($cdr['CDRDBSOCK']) {
			$dsnarray['unix_socket'] = $cdr['CDRDBSOCK'];
		} else {
			$dsnarray['host'] = $cdr['CDRDBHOST'];
			// Do we have a port?
			if ($cdr['CDRDBPORT']) {
				$dsnarray['port'] = $cdr['CDRDBPORT'];
			}
		}

		// If there's no cdrdbtablename, set it to cdr
		if (!$cdr['CDRDBTABLENAME']) {
			$this->db_table = "cdr";
		} else {
			$this->db_table = $cdr['CDRDBTABLENAME'];
		}

		// If this is sqlite, ignore everything we've just done.
		if (strpos($engine, "sqlite") === 0) {
			// This is our raw parsed variables from /etc/freepbx.conf
			$ampconf = \FreePBX::$amp_conf;
			if (isset($amp_conf['cdrdatasource'])) {
				$dsn = "$engine:".$amp_conf['cdrdatasource'];
			} elseif (!empty($amp_conf['datasource'])) {
				$dsn = "$engine:".$amp_conf['datasource'];
			} else {
				throw new \Exception(_("Datasource set to sqlite, but no cdrdatasource or datasource provided"));
			}
			$user = "";
			$pass = "";
		} else {
			// Not SQLite.
			$user = $cdr["CDRDBUSER"];
			$pass = $cdr["CDRDBPASS"];

			// Note - http_build_query() is a simple shortcut to change a key=>value array
			// to a string.
			$dsn = "$engine:".http_build_query($dsnarray, '', ';');
		}
		// Now try to get a DB handle using our DSN
		try {
			$this->cdrdb = new \Database($dsn, $user, $pass);
		} catch(\Exception $e) {
			throw new \Exception(_('Unable to connect to CDR Database'));
		}
		//Set the CDR session timezone to GMT if CDRUSEGMT is true
		if (isset($cdr["CDRUSEGMT"]) && $cdr["CDRUSEGMT"]) {
			$sql = "SET time_zone = '+00:00'";
			$sth = $this->cdrdb->prepare($sql);
			$sth->execute();
		}
	}

	public function getCdrDbHandle() {
		// Simply returns the DB Handle created in __construct
		return $this->cdrdb;
	}

	public function ucpDelGroup($id,$display,$data) {
	}

	/* UCP template to get the user assigned vm extension details
	* @defaultexten is the default_extensionof the userman userid
	* @userid is userman user id
	* @widget is an array we need to replace few item based on the userid
	*/
	public function getWidgetListByModule($defaultexten, $userid,$widget) {
		// if the widget_type_id is not defaultextension and widget_type_id is not in extensions
		// then return only the defaultexten details
		$widgets = array();
		$widget_type_id = $widget['widget_type_id'];// this will be an extension number
		$enabled = $this->FreePBX->Ucp->getCombinedSettingByID($userid,'Cdr','enable');
		if (!$enabled) {
			return false;
		}
		$extensions = $this->FreePBX->Ucp->getCombinedSettingByID($userid,'Cdr','assigned');
		$extensions = is_array($extensions)?$extensions:[];
		if(in_array($widget_type_id,$extensions)){
			// nothing to do return the same widget
			return $widget;
		}else {// sent the default extension
			$data = $this->FreePBX->Core->getDevice($defaultexten);
			if(empty($data) || empty($data['description'])) {
				$data = $this->FreePBX->Core->getUser($defaultexten);
				$name = $data['name'];
			} else {
				$name = $data['description'];
			}
			$widget['widget_type_id'] = $defaultexten;
			$widget['name'] = $name;
			return $widget;
		}
	return false;
	}


	public function ucpAddGroup($id, $display, $data) {
		$this->ucpUpdateGroup($id,$display,$data);
	}

	public function ucpUpdateGroup($id,$display,$data) {
		if($display == 'userman' && isset($_POST['type']) && $_POST['type'] == 'group') {
			if(!empty($_POST['cdr_enable']) && $_POST['cdr_enable'] == "yes") {
				$this->FreePBX->Ucp->setSettingByGID($id,'Cdr','enable',true);
			} else {
				$this->FreePBX->Ucp->setSettingByGID($id,'Cdr','enable',false);
			}
			if(!empty($_POST['ucp_cdr'])) {
				$this->FreePBX->Ucp->setSettingByGID($id,'Cdr','assigned',$_POST['ucp_cdr']);
			} else {
				$this->FreePBX->Ucp->setSettingByGID($id,'Cdr','assigned',array('self'));
			}
			if(!empty($_REQUEST['cdr_download']) && $_REQUEST['cdr_download'] == 'yes') {
				$this->FreePBX->Ucp->setSettingByGID($id,'Cdr','download',true);
			} else {
				$this->FreePBX->Ucp->setSettingByGID($id,'Cdr','download',false);
			}
			if(!empty($_REQUEST['cdr_playback']) && $_REQUEST['cdr_playback'] == 'yes') {
				$this->FreePBX->Ucp->setSettingByGID($id,'Cdr','playback',true);
			} else {
				$this->FreePBX->Ucp->setSettingByGID($id,'Cdr','playback',false);
			}
		}
	}

	/**
	* Hook functionality from userman when a user is deleted
	* @param {int} $id      The userman user id
	* @param {string} $display The display page name where this was executed
	* @param {array} $data    Array of data to be able to use
	*/
	public function ucpDelUser($id, $display, $ucpStatus, $data) {

	}

	/**
	* Hook functionality from userman when a user is added
	* @param {int} $id      The userman user id
	* @param {string} $display The display page name where this was executed
	* @param {array} $data    Array of data to be able to use
	*/
	public function ucpAddUser($id, $display, $ucpStatus, $data) {
		$this->ucpUpdateUser($id, $display, $ucpStatus, $data);
	}

	/**
	* Hook functionality from userman when a user is updated
	* @param {int} $id      The userman user id
	* @param {string} $display The display page name where this was executed
	* @param {array} $data    Array of data to be able to use
	*/
	public function ucpUpdateUser($id, $display, $ucpStatus, $data) {
		if($display == 'userman' && isset($_POST['type']) && $_POST['type'] == 'user') {
			if(!empty($_POST['cdr_enable']) && $_POST['cdr_enable'] == "yes") {
				$this->FreePBX->Ucp->setSettingByID($id,'Cdr','enable',true);
			} elseif(!empty($_POST['cdr_enable']) && $_POST['cdr_enable'] == "no") {
				$this->FreePBX->Ucp->setSettingByID($id,'Cdr','enable',false);
			} elseif(!empty($_POST['cdr_enable']) && $_POST['cdr_enable'] == "inherit") {
				$this->FreePBX->Ucp->setSettingByID($id,'Cdr','enable',null);
			}
			if(!empty($_POST['ucp_cdr'])) {
				$this->FreePBX->Ucp->setSettingByID($id,'Cdr','assigned',$_POST['ucp_cdr']);
			} else {
				$this->FreePBX->Ucp->setSettingByID($id,'Cdr','assigned',null);
			}
			if(!empty($_REQUEST['cdr_download']) && $_REQUEST['cdr_download'] == 'yes') {
				$this->FreePBX->Ucp->setSettingByID($id,'Cdr','download',true);
			} elseif(!empty($_POST['cdr_download']) && $_POST['cdr_download'] == "no") {
				$this->FreePBX->Ucp->setSettingByID($id,'Cdr','download',false);
			} elseif(!empty($_POST['cdr_download']) && $_POST['cdr_download'] == "inherit") {
				$this->FreePBX->Ucp->setSettingByID($id,'Cdr','download',null);
			}
			if(!empty($_REQUEST['cdr_playback']) && $_REQUEST['cdr_playback'] == 'yes') {
				$this->FreePBX->Ucp->setSettingByID($id,'Cdr','playback',true);
			} elseif(!empty($_POST['cdr_playback']) && $_POST['cdr_playback'] == "no") {
				$this->FreePBX->Ucp->setSettingByID($id,'Cdr','playback',false);
			} elseif(!empty($_POST['cdr_playback']) && $_POST['cdr_playback'] == "inherit") {
				$this->FreePBX->Ucp->setSettingByID($id,'Cdr','playback',null);
			}
		}
	}

	public function ucpConfigPage($mode, $user, $action) {
		if(empty($user)) {
			$enable = ($mode == 'group') ? true : null;
			$download = ($mode == 'group') ? true : null;
			$playback = ($mode == 'group') ? true : null;
		} else {
			if($mode == 'group') {
				$enable = $this->FreePBX->Ucp->getSettingByGID($user['id'],'Cdr','enable');
				$download = $this->FreePBX->Ucp->getSettingByGID($user['id'],'Cdr','download');
				$playback = $this->FreePBX->Ucp->getSettingByGID($user['id'],'Cdr','playback');
				$cdrassigned = $this->FreePBX->Ucp->getSettingByGID($user['id'],'Cdr','assigned');
			} else {
				$enable = $this->FreePBX->Ucp->getSettingByID($user['id'],'Cdr','enable');
				$download = $this->FreePBX->Ucp->getSettingByID($user['id'],'Cdr','download');
				$playback = $this->FreePBX->Ucp->getSettingByID($user['id'],'Cdr','playback');
				$cdrassigned = $this->FreePBX->Ucp->getSettingByID($user['id'],'Cdr','assigned');
			}
		}

		$cdrassigned = !empty($cdrassigned) ? $cdrassigned : array();

		$ausers = array();
		if($action == "showgroup" || $action == "addgroup") {
			$ausers['self'] = _("User Primary Extension");
		}
		if($action == "addgroup") {
			$cdrassigned = array('self');
		}
		foreach(core_users_list() as $list) {
			$ausers[$list[0]] = sprintf("%s <%s>", $list[1], $list[0]);
		}
		$html[0] = array(
			"title" => _("Call History"),
			"rawname" => "cdrreports",
			"content" => load_view(dirname(__FILE__)."/views/ucp_config.php",array("mode"  => $mode, "enable" => $enable, "cdrassigned" => $cdrassigned, "ausers" => $ausers, "playback" => $playback,"download" => $download))
		);
		return $html;
	}

	public function doConfigPageInit($page) {
	}

	public function install() {
		$new = !$this->getConfig('newinstall');
		if($new) {
			$this->setConfig('newinstall',true);
		}
		// check fwconsole cdr job is enabled then move to cron
		$alljob = $this->FreePBX->Job->getAll();
		foreach($alljob as $j ){
			if($j['modulename'] == 'cdr' && $j['jobname'] =='cleanTransientCDRData'){
				$this->FreePBX->Job->remove('cdr', 'cleanTransientCDRData'); 
				$this->addcronEntryForCDR();
				out('Removed Job and added cron');
			}
		}
	}
	public function uninstall() {

	}
	public function backup(){

	}
	public function restore($backup){

	}
	public function genConfig() {

	}

	public function getDbTable() {
		return $this->db_table;
	}

	public static function myDialplanHooks()
	{
		return 900;
	}

	public function doDialplanHook(&$ext, $engine, $priority)
	{
		$transientcdr = $this->FreePBX->Config()->get('TRANSIENTCDR');
		if ($transientcdr) {
			$setupCDRTrigger = $this->getConfig('setupCDRTrigger');
			$this->createCdrTrigger();
		} else {
			$new = $this->getConfig('newinstall');
			$setupCDRTrigger = $this->getConfig('setupCDRTrigger');
			if($new && $setupCDRTrigger) {
				$this->removeCdrTrigger();
				$this->removecronEntry();
			}
		}
	}

	public function ajaxRequest($req, &$setting) {
		$setting['authenticate'] = true;
		$setting['allowremote'] = false;
		switch($req) {
			case "gethtml5":
			case "playback":
			case "download":
			case "getJSON":
			case "export_csv":
			case "getCelEvents":
			case "getGraphData":
				return true;
			break;
		}
		return false;
	}

	public function ajaxCustomHandler() {
		switch($_REQUEST['command']) {
			case "playback":
			case "download":
				$media = $this->FreePBX->Media();
				$media->getHTML5File($_REQUEST['file']);
			break;
		}
	}

	public function ajaxHandler() {
		switch($_REQUEST['command']) {
			case "gethtml5":
				$media = $this->FreePBX->Media();
				$info = $this->getRecordByID($_POST['uid'],'cdr');
				if(!empty($info['recordingfile'])) {
					$media->load($info['recordingfile']);
					$files = $media->generateHTML5();
					$final = array();
					foreach($files as $format => $name) {
						$final[$format] = "ajax.php?module=cdr&command=playback&file=".$name;
					}
					return array("status" => true, "files" => $final);
				}
				return array("status" => false);
			break;
			case "getJSON":
				return $this->getCdrData();
			break;
			case "export_csv":
				return $this->exportCsv();
			break;
			case "getCelEvents":
				return $this->getCelEvents();
			break;
			case "getGraphData":
				return $this->getGraphData();
			break;
		}
	}

	public function getRecordByID($rid,$tblname = '') {
		if($tblname) {
			$this->db_table = $tblname;
		} else {
			$this->checkCdrTrigger();
		}
		$sql = "SELECT * FROM ".$this->db_table." WHERE recordingfile != '' AND (uniqueid = :uid OR linkedid = :uid) LIMIT 1";
		$sth = $this->cdrdb->prepare($sql);
		try {
			$sth->execute(array("uid" => str_replace("_",".",(string) $rid)));
			$recording = $sth->fetch(\PDO::FETCH_ASSOC);
		} catch(\Exception $e) {
			return [];
		}
		if(!is_array($recording)) {
			$recording = [];
		}
		$recording['recordingfile'] = (isset($recording['recordingfile'])) ? $this->processPath($recording['recordingfile']) : '';
		return $recording;
	}

	/**
	 * Get CDR record by record ID and extension
	 * @param int $rid           The record ID
	 * @param string $ext           The extension
	 * @param bool $generateMedia Whether to generate HTML assets or not
	 */
	public function getRecordByIDExtension($rid,$ext) {
		$sql = "SELECT * FROM ".$this->db_table." WHERE recordingfile != '' AND uniqueid = :uid AND (src = :ext OR dst = :ext OR src = :vmext OR dst = :vmext OR cnum = :ext OR cnum = :vmext OR dstchannel LIKE :chan OR channel LIKE :chan)";
		$sth = $this->cdrdb->prepare($sql);
		try {
			$sth->execute(array("uid" => str_replace("_",".",$rid), "ext" => $ext, "vmext" => "vmu".$ext, "chan" => '%/'.$ext.'-%'));
			$recording = $sth->fetch(\PDO::FETCH_ASSOC);
		} catch(\Exception $e) {
			return false;
		}
		if(!is_array($recording)) {
			$recording = array();
		}
		$recording['recordingfile'] = isset($recording['recordingfile']) ? $this->processPath($recording['recordingfile']) : '';
		return $recording;
	}

	public function getAllCalls($page=1,$orderby='date',$order='desc',$search='',$limit=100) {
		$start = ($limit * ($page - 1));
		$end = $limit;
		
		// Parameter validation and sanitization
		$page = (int)$page;
		$limit = (int)$limit;
		$start = ($limit * ($page - 1));
		$end = $limit;
		
		// Whitelist for orderby
		$allowed_orderby = array('clid', 'duration', 'timestamp');
		switch($orderby) {
			case 'description':
				$orderby = 'clid';
			break;
			case 'duration':
				$orderby = 'duration';
			break;
			case 'date':
			default:
				$orderby = 'timestamp';
			break;
		}
		
		// Order validation
		$order = (strtolower($order) == 'desc') ? 'DESC' : 'ASC';
		
		if(!empty($search)) {
			$sql = "SELECT *, UNIX_TIMESTAMP(calldate) As timestamp FROM ".$this->db_table." WHERE (clid LIKE :search OR src LIKE :search OR dst LIKE :search) ORDER BY ".$orderby." ".$order." LIMIT :start, :end";
			$sth = $this->cdrdb->prepare($sql);
			$sth->bindValue(':search', '%'.$search.'%', \PDO::PARAM_STR);
			$sth->bindValue(':start', $start, \PDO::PARAM_INT);
			$sth->bindValue(':end', $end, \PDO::PARAM_INT);
			$sth->execute();
		} else {
			$sql = "SELECT *, UNIX_TIMESTAMP(calldate) As timestamp FROM ".$this->db_table." ORDER BY ".$orderby." ".$order." LIMIT :start, :end";
			$sth = $this->cdrdb->prepare($sql);
			$sth->bindValue(':start', $start, \PDO::PARAM_INT);
			$sth->bindValue(':end', $end, \PDO::PARAM_INT);
			$sth->execute();
		}
		$calls = $sth->fetchAll(\PDO::FETCH_ASSOC);
		return $calls;
	}

	/**
	 * Get all CDR call records
	 * @param int  $extension 		The extension
	 * @param integer $page      	The page number to start at
	 * @param string  $orderby   	Order the results by
	 * @param string  $order    	Order ASC or DESC
	 * @param string  $search   	The search string to use
	 * @param integer $limit    	The number of results to return
	 * @param bool    $fromAPI  	Uses the transient_cdr table instead, which only stores last two months of CDR data.  Used when queries to regular cdr table take too long because the table has too much data.
	 * @param string  $webrtcPrefix 	
	 */
	public function getCalls($extension, $page = 1, $orderby = 'date', $order = 'desc', $search = '', $limit = 100, $fromAPI = false, $webrtcPrefix = '') {
		if($fromAPI) {
			//set the $db_table variable to 'transient_cdr' if cdrTrigger is created
			$this->checkCdrTrigger();
		}
		$defaultExtension = $extension;
		if (!empty($webrtcPrefix)) {
			$extension = $webrtcPrefix . $extension;
		}
		
		// Parameter validation and sanitization
		$page = (int)$page;
		$limit = (int)$limit;
		$start = ($limit * ($page - 1));
		$end = $limit;
		
		// Whitelist for orderby
		switch($orderby) {
			case 'description':
				$orderby = 'clid';
			break;
			case 'duration':
				$orderby = 'duration';
			break;
			case 'date':
			default:
				$orderby = 'timestamp';
			break;
		}
		
		// Order validation
		$order = (strtolower($order) == 'desc') ? 'DESC' : 'ASC';
		
		if(!empty($search)) {
			$sql = "SELECT *, UNIX_TIMESTAMP(calldate) As timestamp FROM ".$this->db_table." WHERE (dstchannel LIKE :chan OR dstchannel LIKE :dst_channel OR channel LIKE :chan OR src = :extension OR dst = :extension OR src = :extensionv OR dst = :extensionv OR cnum = :extension OR cnum = :extensionv) AND (clid LIKE :search OR src LIKE :search OR dst LIKE :search) ORDER BY ".$orderby." ".$order." LIMIT :start, :end";
			$sth = $this->cdrdb->prepare($sql);
			$sth->bindValue(':chan', '%/'.$extension.'-%', \PDO::PARAM_STR);
			$sth->bindValue(':dst_channel', '%-'.$defaultExtension.'@%', \PDO::PARAM_STR);
			$sth->bindValue(':extension', $extension, \PDO::PARAM_STR);
			$sth->bindValue(':search', '%'.$search.'%', \PDO::PARAM_STR);
			$sth->bindValue(':extensionv', 'vmu'.$extension, \PDO::PARAM_STR);
			$sth->bindValue(':start', $start, \PDO::PARAM_INT);
			$sth->bindValue(':end', $end, \PDO::PARAM_INT);
			$sth->execute();
		} else {
			$sql = "SELECT *, UNIX_TIMESTAMP(calldate) As timestamp FROM ".$this->db_table." WHERE (dstchannel LIKE :chan OR dstchannel LIKE :dst_channel OR channel LIKE :chan OR src = :extension OR dst = :extension OR src = :extensionv OR dst = :extensionv OR cnum = :extension OR cnum = :extensionv) ORDER BY ".$orderby." ".$order." LIMIT :start, :end";
			$sth = $this->cdrdb->prepare($sql);
			$sth->bindValue(':chan', '%/'.$extension.'-%', \PDO::PARAM_STR);
			$sth->bindValue(':dst_channel', '%-'.$defaultExtension.'@%', \PDO::PARAM_STR);
			$sth->bindValue(':extension', $extension, \PDO::PARAM_STR);
			$sth->bindValue(':extensionv', 'vmu'.$extension, \PDO::PARAM_STR);
			$sth->bindValue(':start', $start, \PDO::PARAM_INT);
			$sth->bindValue(':end', $end, \PDO::PARAM_INT);
			$sth->execute();
		}
		$calls = $sth->fetchAll(\PDO::FETCH_ASSOC);
		$scribeModuleStatus = false;
		if ($this->FreePBX->Modules->checkStatus("scribe") && $this->FreePBX->Scribe->isLicensed()) {
			$scribeModuleStatus = true;
		}
		foreach($calls as &$call) {
			if(empty($call['dst']) && preg_match('/\/(.*)\-/',$call['dstchannel'],$matches)) {
				$call['dst'] = $matches[1];
			}
			if(empty($call['src']) && preg_match('/\/(.*)\-/',$call['channel'],$matches)) {
				$call['src'] = $matches[1];
			}
			//This Check $fromAPI to avoid to send the unwanted data to DPMA Call Log API only.
			if(!$fromAPI) {
			if($call['duration'] > 59) {
				$min = floor($call['duration'] / 60);
				if($min > 59) {
					$call['niceDuration'] = sprintf(_('%s hour, %s min, %s sec'),gmdate("H", $call['duration']), gmdate("i", $call['duration']), gmdate("s", $call['duration']));
				} else {
					$call['niceDuration'] = sprintf(_('%s min, %s sec'),gmdate("i", $call['duration']), gmdate("s", $call['duration']));
				}
			} else {
				$call['niceDuration'] = sprintf(_('%s sec'),$call['duration']);
			}
			$call['niceUniqueid'] = str_replace(".","_",$call['uniqueid']);
			$call['recordingformat'] = !empty($call['recordingfile']) ? strtolower(pathinfo($call['recordingfile'],PATHINFO_EXTENSION)) : '';
			$call['recordingfile'] = $this->processPath($call['recordingfile']);
			$call['requestingExtension'] = $extension;
			}
			$recordingfile = isset($call['recordingfile']) ? $call['recordingfile']:'';
			if($scribeModuleStatus) {
				$url = \FreePBX::Scribe()->getUcpTranscriptionUrl($extension,$call['uniqueid'],'callrecording',$recordingfile);
				if($url) {
					$call['converttotext'] = $url;
				} else {
					$call['converttotext'] = '';
				}
			}
		}
		return $calls;
	}

	/**
	* Get the Number of Pages by limit for extension
	* @param {int} $extension The Extension to lookup
	* @param {int} $limit=100 The limit of results per page
	*/
	public function getPages($extension,$search='',$limit=100) {
		if(!empty($search)) {
			$sql = "SELECT count(*) as count FROM ".$this->db_table." WHERE (dstchannel LIKE :chan OR channel LIKE :chan OR src = :extension OR dst = :extension OR src = :extensionv OR dst = :extensionv OR cnum = :extension) AND (clid LIKE :search OR src LIKE :search OR dst LIKE :search)";
			$sth = $this->cdrdb->prepare($sql);
			$sth->execute(array(':chan' => '%/'.$extension.'-%', ':extension' => $extension, ':search' => '%'.$search.'%',':extensionv' => 'vmu'.$extension));
		} else {
			$sql = "SELECT count(*) as count FROM ".$this->db_table." WHERE (dstchannel LIKE :chan OR channel LIKE :chan OR src = :extension OR dst = :extension OR src = :extensionv OR dst = :extensionv OR cnum = :extension)";
			$sth = $this->cdrdb->prepare($sql);
			$sth->execute(array(':chan' => '%/'.$extension.'-%', ':extension' => $extension, ':extensionv' => 'vmu'.$extension));
		}
		$res = $sth->fetch(\PDO::FETCH_ASSOC);
		$total = $res['count'];
		if(!empty($total)) {
			return ceil($total/$limit);
		} else {
			return false;
		}
	}

	public function getTotalCalls($extension,$search='') {
		if(!empty($search)) {
			$sql = "SELECT count(*) as count FROM ".$this->db_table." WHERE (dstchannel LIKE :chan OR channel LIKE :chan OR src = :extension OR dst = :extension OR src = :extensionv OR dst = :extensionv OR cnum = :extension) AND (clid LIKE :search OR src LIKE :search OR dst LIKE :search)";
			$sth = $this->cdrdb->prepare($sql);
			$sth->execute(array(':chan' => '%/'.$extension.'-%', ':extension' => $extension, ':search' => '%'.$search.'%',':extensionv' => 'vmu'.$extension));
		} else {
			$sql = "SELECT count(*) as count FROM ".$this->db_table." WHERE (dstchannel LIKE :chan OR channel LIKE :chan OR src = :extension OR dst = :extension OR src = :extensionv OR dst = :extensionv OR cnum = :extension)";
			$sth = $this->cdrdb->prepare($sql);
			$sth->execute(array(':chan' => '%/'.$extension.'-%', ':extension' => $extension, ':extensionv' => 'vmu'.$extension));
		}
		$res = $sth->fetch(\PDO::FETCH_ASSOC);
		$total = $res['count'];
		if(!empty($total)) {
			return $total;
		} else {
			return 0;
		}
	}

	/**
	 * Tear apart the file name to get our correct path
	 * @param  string $recordingFile The recording file
	 * @return string                The full path
	 */
	public function processPath($recordingFile) {
		if(empty($recordingFile)) {
			return '';
		}
		$spool = $this->FreePBX->Config->get('ASTSPOOLDIR');
		$mixmondir = $this->FreePBX->Config->get('MIXMON_DIR');
		$rec_parts = explode('-',$recordingFile);
		$fyear = substr($rec_parts[3],0,4);
		$fmonth = substr($rec_parts[3],4,2);
		$fday = substr($rec_parts[3],6,2);
		$monitor_base = $mixmondir ? $mixmondir : $spool . '/monitor';
		$recordingFile = "$monitor_base/$fyear/$fmonth/$fday/" . $recordingFile;
		//check to make sure the file size is bigger than 44 bytes (header size)
		if(file_exists($recordingFile) && is_readable($recordingFile) && filesize($recordingFile) > 44) {
			return $recordingFile;
		}
		return '';
	}

	public function getTotal() {
		$sql = "SELECT count(*) as count FROM ".$this->getDbTable();
		$sth = $this->cdrdb->prepare($sql);
		$sth->execute();
		return $sth->fetchColumn();
	}

	public function getGraphQLCalls($after, $first, $before, $last, $orderby, $startDate, $endDate) {
		// Parameter validation and sanitization
		switch($orderby) {
				case 'duration':
						$orderby = 'duration';
				break;
				case 'date':
				default:
						$orderby = 'timestamp';
				break;
		}
		$first = !empty($first) ? (int) $first : 5;
		$after = !empty($after) ? (int) $after : 0;
		
		$whereClause = "";
		$params = array();
		
		if((isset($startDate) && !empty($startDate)) && (isset($endDate) && !empty($endDate))){
			// Date validation to prevent SQL injection
			$startDate = preg_replace('/[^0-9\-]/', '', $startDate);
			$endDate = preg_replace('/[^0-9\-]/', '', $endDate);
			$whereClause = " WHERE DATE(calldate) BETWEEN :startDate AND :endDate";
			$params[':startDate'] = $startDate;
			$params[':endDate'] = $endDate;
		}
		
		$sql = "SELECT *, UNIX_TIMESTAMP(calldate) As timestamp FROM ".$this->getDbTable()." ".$whereClause." ORDER BY ".$orderby." DESC LIMIT :limitValue OFFSET :afterValue";
		$sth = $this->cdrdb->prepare($sql);
		
		// Bind date parameters if present
		foreach($params as $key => $value) {
			$sth->bindValue($key, $value, \PDO::PARAM_STR);
		}
		
		$sth->bindValue(':limitValue', (int) trim($first), \PDO::PARAM_INT);
		$sth->bindValue(':afterValue', (int) trim($after), \PDO::PARAM_INT);
		$sth->execute();
		$calls = $sth->fetchAll(\PDO::FETCH_ASSOC);
		return $calls;
	}

	public function getGraphQLRecordByID($rid) {
		$sql = "SELECT *, UNIX_TIMESTAMP(calldate) As timestamp FROM ".$this->getDbTable()." WHERE uniqueid = :uid";
		$sth = $this->cdrdb->prepare($sql);
		try {
				$sth->execute(array("uid" => str_replace("_",".",$rid)));
				$recording = $sth->fetch(\PDO::FETCH_ASSOC);
		} catch(\Exception $e) {
				return array();
		}
		return $recording;
	}
	
	/**
	 * This function will check whether the cdrTrigger is created or not.
	 * If the trigger exists, it will set the $db_table variable to 'transient_cdr'.
	 * So when 'pbx.users.callLogs.getList' method is called, it will fetch the call logs from 'transient_cdr' instead of 'cdr' table
	 */
	private function checkCdrTrigger() {
		$query = "SHOW TRIGGERS WHERE `Trigger` = 'cdrTrigger'";
		$res = $this->cdrdb->prepare($query);
		$res->execute();
		$result = $res->fetch(\PDO::FETCH_ASSOC);
		if (!empty($result)) {
			if($this->FreePBX->Config()->get('TRANSIENTCDR')) {
				$this->db_table = 'transient_cdr';
			} else {
				$query = "SHOW TABLES LIKE 'replicate_cdr'";
				$res = $this->cdrdb->prepare($query);
				$res->execute();
				$result = $res->fetch(\PDO::FETCH_ASSOC);
				if (!empty($result)) {
					$this->db_table = 'replicate_cdr';
				}else {
					$this->db_table = 'cdr';
				}
			}
		}
	}


	public function getReplicationStatus() {
		$data = [];
		$data['enable'] = $this->FreePBX->Config()->get('TRANSIENTCDR');
		$data['tablename'] = 'transient_cdr';
		return $data;
	}

	public function setupCDRTriggerProcess() {
		try {
			$query = "SHOW TABLES LIKE 'transient_cdr'";
			$res = $this->cdrdb->prepare($query);
			$res->execute();
			$result = $res->fetch(\PDO::FETCH_ASSOC);
			if (empty($result)) {
				$query = "CREATE TABLE IF NOT EXISTS transient_cdr ENGINE=MyISAM SELECT * FROM cdr LIMIT 0;";
				$res = $this->cdrdb->prepare($query);
				try {
					$res->execute();
				} catch (\Exception $e) {}
				// Add Indexes
				$squery = "ALTER TABLE `transient_cdr` ADD INDEX `calldate` (`calldate`), ADD INDEX `dst` (`dst`), ADD INDEX `uniqueid` (`uniqueid`), ADD INDEX `did` (`did`), ADD INDEX `linkedid` (`linkedid`),ADD INDEX `src` (`src`),ADD INDEX `channel` (`channel`),ADD INDEX `dstchannel` (`dstchannel`),ADD INDEX `cnum` (`cnum`)";
				$sres = $this->cdrdb->prepare($squery);
				try {
					$sres->execute();
				} catch (\Exception $e) {}
			}
			$this->createCdrTrigger();
			$this->addcronEntryForCDR();
		} catch (\Exception $e) {
			dbug($e->getMessage());
		}
		$this->setConfig('setupCDRTrigger',true);
	}


	public function copydatafromCDR($month = 2,$fromtable='cdr') {
		$sql = "SELECT * FROM ".$fromtable."  WHERE calldate > (NOW() - INTERVAL ".(int)$month." MONTH) limit 1";
		$res = $this->cdrdb->prepare($sql);
		$res->execute();
		$result = $res->fetch(\PDO::FETCH_ASSOC);
		if (!empty($result)) {
			$sql_query = "INSERT INTO transient_cdr SELECT * FROM ".$fromtable."  WHERE calldate > (NOW() - INTERVAL ".(int)$month." MONTH)";
			$res = $this->cdrdb->prepare($sql_query);
			try {
				$res->execute();
			} catch (\Exception $e) {}
		}
	}

	public function createCdrTrigger() {
		$query = "SHOW TRIGGERS WHERE `Trigger` = 'cdrTrigger'";
		$res = $this->cdrdb->prepare($query);
		$res->execute();
		$result = $res->fetch(\PDO::FETCH_ASSOC);
		if (empty($result)) {
			$sql = "CREATE TRIGGER `cdrTrigger` AFTER INSERT ON `cdr`
				FOR EACH ROW
				BEGIN
					INSERT INTO transient_cdr(calldate, clid, src, dst, dcontext, channel, dstchannel, lastapp, lastdata, duration, billsec, disposition, amaflags, accountcode, uniqueid, userfield, did, recordingfile, cnum, cnam, outbound_cnum, outbound_cnam, dst_cnam, linkedid, peeraccount, sequence) values (new.calldate, new.clid, new.src, new.dst, new.dcontext, new.channel, new.dstchannel, new.lastapp, new.lastdata, new.duration, new.billsec, new.disposition, new.amaflags, new.accountcode, new.uniqueid, new.userfield, new.did, new.recordingfile, new.cnum, new.cnam, new.outbound_cnum, new.outbound_cnam, new.dst_cnam, new.linkedid, new.peeraccount, new.sequence);
				END";
			$res = $this->cdrdb->prepare($sql);
			try {
				$res->execute();
			} catch (\Exception $e) {}
		}
	}

	private function addcronEntryForCDR() {
		$AMPSBIN = $this->FreePBX->Config->get("AMPSBIN");
		$crons = $this->FreePBX->Cron->getAll();
		foreach($crons as $cron) {
			if(preg_match("/fwconsole cdr  --purnedata /",$cron)) {
				$this->FreePBX->Cron->remove($cron);
			}
		}
		$this->FreePBX->Cron->addLine("1 0 * * * [ -e ".$AMPSBIN."/fwconsole ] && sleep $((RANDOM\%30)) && ".$AMPSBIN."/fwconsole cdr  --purnedata >> /var/log/asterisk/freepbx.log 2>&1");
	}

	public function removeCDRTriggerSetup() {
		$this->removeCdrTrigger();
		$this->dropTransientCDRTable();
		$this->removecronEntry();
	}

	public function removeCdrTrigger() {
		$query = "SHOW TRIGGERS WHERE `Trigger` = 'cdrTrigger'";
		$res = $this->cdrdb->prepare($query);
		$res->execute();
		$result = $res->fetch(\PDO::FETCH_ASSOC);
		if (!empty($result)) {
			$query = "drop trigger cdrTrigger";
			$res = $this->cdrdb->prepare($query);
			$res->execute();
		}
	}

	private function dropTransientCDRTable() {
		$query = "SHOW TABLES LIKE 'transient_cdr'";
		$res = $this->cdrdb->prepare($query);
		$res->execute();
		$result = $res->fetch(\PDO::FETCH_ASSOC);
		if (!empty($result)) {
			$squery = "DROP table transient_cdr";
			$sres = $this->cdrdb->prepare($squery);
			try {
				$sres->execute();
			} catch (\Exception $e) { }
		}
	}

	private function removecronEntry() {
		$this->FreePBX->Job->remove('cdr', 'cleanTransientCDRData');
		$crons = $this->FreePBX->Cron->getAll();
		foreach($crons as $cron) {
			if(preg_match("/fwconsole cdr  --purnedata /",$cron)) {
				$this->FreePBX->Cron->remove($cron);
			}
		}
	}

	public function cleanTransientCDRData($date) {
		$table_name = 'transient_cdr';
		$col = 'calldate';
		$query = "SHOW TABLES LIKE 'transient_cdr'";
		$res = $this->cdrdb->prepare($query);
		$res->execute();
		$result = $res->fetch(\PDO::FETCH_ASSOC);
		if (!empty($result)) {
			$sql = "DELETE FROM " . $table_name . " WHERE " . $col . " < :date;";
			$res = $this->cdrdb->prepare($sql);
			$res->execute(array(':date' => $date . "%"));

			$query = "OPTIMIZE TABLE transient_cdr";
			$res = $this->cdrdb->prepare($query);
			$res->execute();
		}
	}

	/**
	 * Get CDR data for bootstrap table with advanced search
	 * @return array CDR data formatted for bootstrap table
	 */
	public function getCdrData() {
		// Build WHERE clause based on search parameters
		$where_conditions = array();
		$params = array();
		
		// The Quick Date Range Picker (startdate/enddate) and the Advanced Search
		// Options From/To Date fields (from_*/to_*) are two separate UI controls
		// that both filter on calldate. Only one of them should ever apply: if
		// they were both ANDed together, a stale value left over in the other
		// control (e.g. the picker's default "last 30 days") would silently
		// narrow or empty out results the user never asked to restrict.
		$hasAdvancedDateFilter = !empty($_REQUEST['from_day']) || !empty($_REQUEST['from_month']) || !empty($_REQUEST['from_year'])
			|| !empty($_REQUEST['to_day']) || !empty($_REQUEST['to_month']) || !empty($_REQUEST['to_year']);

		if ($hasAdvancedDateFilter) {
			$from_date = $this->buildDateFromComponents($_REQUEST, 'from');
			if ($from_date) {
				$where_conditions[] = "calldate >= :from_date";
				$params[':from_date'] = $from_date;
			}

			$to_date = $this->buildDateFromComponents($_REQUEST, 'to');
			if ($to_date) {
				$where_conditions[] = "calldate <= :to_date";
				$params[':to_date'] = $to_date;
			}
		} elseif (!empty($_REQUEST['startdate']) && !empty($_REQUEST['enddate'])) {
			$where_conditions[] = "calldate BETWEEN :startdate AND :enddate";
			$params[':startdate'] = $_REQUEST['startdate'];
			$params[':enddate'] = $_REQUEST['enddate'];
		}
		
		// Search fields with modifiers
		$search_fields = array(
			'cnum' => 'src',
			'cnam' => 'cnam', 
			'outbound_cnum' => 'outbound_cnum',
			'did' => 'did',
			'dst' => 'dst',
			'dst_cnam' => 'dst_cnam',
			'userfield' => 'userfield',
			'accountcode' => 'accountcode'
		);
		
		foreach ($search_fields as $param => $field) {
			if (!empty($_REQUEST[$param])) {
				$modifier = !empty($_REQUEST[$param . '_modifier']) ? $_REQUEST[$param . '_modifier'] : 'contains';
				$condition = $this->buildSearchCondition($field, $_REQUEST[$param], $modifier);
				if ($condition) {
					$where_conditions[] = $condition['sql'];
					$params = array_merge($params, $condition['params']);
				}
			}
		}
		
		// Duration range filter
		if (!empty($_REQUEST['duration_min'])) {
			$where_conditions[] = "duration >= :duration_min";
			$params[':duration_min'] = (int)$_REQUEST['duration_min'];
		}
		if (!empty($_REQUEST['duration_max'])) {
			$where_conditions[] = "duration <= :duration_max";
			$params[':duration_max'] = (int)$_REQUEST['duration_max'];
		}
		
		// Disposition filter
		if (!empty($_REQUEST['disposition'])) {
			$where_conditions[] = "disposition = :disposition";
			$params[':disposition'] = $_REQUEST['disposition'];
		}
		
		// Report type filter
		if (!empty($_REQUEST['report_type'])) {
			$report_types = explode(',', $_REQUEST['report_type']);
			$type_conditions = array();
			
			foreach ($report_types as $type) {
				switch ($type) {
					case 'inbound':
						$type_conditions[] = "(did IS NOT NULL AND did != '')";
						break;
					case 'outbound':
						$type_conditions[] = "(did IS NULL OR did = '') AND src NOT LIKE 's%'";
						break;
					case 'internal':
						$type_conditions[] = "src LIKE 's%' OR (src REGEXP '^[0-9]+$' AND dst REGEXP '^[0-9]+$' AND (did IS NULL OR did = ''))";
						break;
				}
			}
			
			if (!empty($type_conditions)) {
				$where_conditions[] = '(' . implode(' OR ', $type_conditions) . ')';
			}
		}
		
		// Basic search filter (from bootstrap table search). Match every
		// column shown in the grid (views/cdr_grid.php), not just the first
		// few, so the quick search box behaves like users expect instead of
		// silently missing hits on DID, account code, etc.
		//
		// Duration and Call Date are stored raw (seconds / full datetime)
		// but displayed formatted client-side (assets/js/cdr.js), so typing
		// what's on screen (e.g. "00:03") would never match the raw column.
		// Rebuild the same formatting in SQL and match against that too:
		// - duration: mirrors the niceDuration logic below (00:SS / MM:SS /
		//   HH:MM:SS depending on length).
		// - calldate: raw stored value plus the French d/m/Y display format
		//   used by toLocaleString() in this fr_FR deployment.
		if (!empty($_REQUEST['search'])) {
			$search = '%' . $_REQUEST['search'] . '%';
			$duration_display = "CASE
				WHEN duration > 3599 THEN CONCAT(LPAD(FLOOR(duration/3600),2,'0'),':',LPAD(FLOOR((duration % 3600)/60),2,'0'),':',LPAD(duration % 60,2,'0'))
				WHEN duration > 59 THEN CONCAT(LPAD(FLOOR(duration/60),2,'0'),':',LPAD(duration % 60,2,'0'))
				ELSE CONCAT('00:',LPAD(duration % 60,2,'0'))
			END";
			$where_conditions[] = "(src LIKE :search OR dst LIKE :search OR clid LIKE :search OR cnum LIKE :search OR cnam LIKE :search
				OR outbound_cnum LIKE :search OR did LIKE :search OR dst_cnam LIKE :search
				OR userfield LIKE :search OR accountcode LIKE :search OR lastapp LIKE :search OR disposition LIKE :search
				OR ($duration_display) LIKE :search
				OR calldate LIKE :search
				OR DATE_FORMAT(calldate, '%d/%m/%Y') LIKE :search
				OR DATE_FORMAT(calldate, '%d/%m/%Y %H:%i:%s') LIKE :search
				OR DATE_FORMAT(calldate, '%H:%i:%s') LIKE :search)";
			$params[':search'] = $search;
		}
		
		// Build WHERE clause
		$where_clause = '';
		if (!empty($where_conditions)) {
			$where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
		}
		
		// Group by handling
		$group_by = '';
		$select_fields = "calldate, clid, src, dst, dcontext, channel, dstchannel, lastapp, lastdata, 
				duration, billsec, disposition, amaflags, accountcode, uniqueid, userfield, did,
				recordingfile, cnum, cnam, outbound_cnum, outbound_cnam, dst_cnam, linkedid, peeraccount, sequence,
				UNIX_TIMESTAMP(calldate) as timestamp";
		
		if (!empty($_REQUEST['group_by'])) {
			$group_field = $_REQUEST['group_by'];
			switch ($group_field) {
				case 'date':
					$group_by = 'GROUP BY DATE(calldate)';
					$select_fields = "DATE(calldate) as call_date, COUNT(*) as call_count, SUM(duration) as total_duration, " . $select_fields;
					break;
				case 'hour':
					$group_by = 'GROUP BY DATE(calldate), HOUR(calldate)';
					$select_fields = "DATE(calldate) as call_date, HOUR(calldate) as call_hour, COUNT(*) as call_count, SUM(duration) as total_duration, " . $select_fields;
					break;
				case 'day_of_week':
					$group_by = 'GROUP BY DAYOFWEEK(calldate)';
					$select_fields = "DAYNAME(calldate) as day_name, COUNT(*) as call_count, SUM(duration) as total_duration, " . $select_fields;
					break;
				case 'month':
					$group_by = 'GROUP BY YEAR(calldate), MONTH(calldate)';
					$select_fields = "YEAR(calldate) as call_year, MONTHNAME(calldate) as call_month, COUNT(*) as call_count, SUM(duration) as total_duration, " . $select_fields;
					break;
				default:
					if (in_array($group_field, array('accountcode', 'userfield', 'src', 'dst', 'did', 'disposition', 'lastapp', 'channel'))) {
						$group_by = "GROUP BY $group_field";
						$select_fields = "$group_field, COUNT(*) as call_count, SUM(duration) as total_duration, " . $select_fields;
					}
					break;
			}
		}
		
		// Order and limit
		// Whitelist for sort column to prevent SQL injection (FREEI-2806)
		$allowed_orderby = array('calldate', 'clid', 'src', 'dst', 'duration', 'billsec', 'disposition', 'cnum', 'cnam', 'did', 'accountcode', 'outbound_cnum', 'outbound_cnam', 'dst_cnam', 'userfield', 'lastapp');
		$order = (!empty($_REQUEST['sort']) && in_array($_REQUEST['sort'], $allowed_orderby, true)) ? $_REQUEST['sort'] : 'calldate';
		$order_dir = !empty($_REQUEST['order']) && $_REQUEST['order'] == 'asc' ? 'ASC' : 'DESC';
		
		// Result limit
		$limit = 100; // default
		if (!empty($_REQUEST['result_limit'])) {
			$limit = (int)$_REQUEST['result_limit'];
			if ($limit == 0) $limit = 999999; // No limit
		} elseif (!empty($_REQUEST['limit'])) {
			$limit = (int)$_REQUEST['limit'];
		}
		
		$offset = !empty($_REQUEST['offset']) ? (int)$_REQUEST['offset'] : 0;
		
		// Main query
		$sql = "SELECT $select_fields
				FROM " . $this->db_table . " 
				$where_clause 
				$group_by
				ORDER BY $order $order_dir 
				LIMIT $limit OFFSET $offset";
		
		$sth = $this->cdrdb->prepare($sql);
		$sth->execute($params);
		$calls = $sth->fetchAll(\PDO::FETCH_ASSOC);
		
		// Count total records
		$count_sql = "SELECT COUNT(*) as total FROM " . $this->db_table . " $where_clause";
		if (!empty($group_by)) {
			$count_sql = "SELECT COUNT(*) as total FROM (SELECT 1 FROM " . $this->db_table . " $where_clause $group_by) as grouped";
		}
		$count_sth = $this->cdrdb->prepare($count_sql);
		$count_sth->execute($params);
		$total = $count_sth->fetchColumn();
		
		// Format data for bootstrap table
		$ret = array();
		foreach ($calls as $call) {
			// Process recording file path
			$call['recordingfile'] = $this->processPath($call['recordingfile']);
			
			// Format duration
			if ($call['duration'] > 59) {
				$min = floor($call['duration'] / 60);
				if ($min > 59) {
					$call['niceDuration'] = sprintf('%02d:%02d:%02d', 
						floor($call['duration'] / 3600), 
						floor(($call['duration'] % 3600) / 60), 
						$call['duration'] % 60);
				} else {
					$call['niceDuration'] = sprintf('%02d:%02d', 
						floor($call['duration'] / 60), 
						$call['duration'] % 60);
				}
			} else {
				$call['niceDuration'] = sprintf('00:%02d', $call['duration']);
			}
			
			$call['niceUniqueid'] = str_replace('.', '_', $call['uniqueid']);
			$ret[] = $call;
		}
		
		return array(
			'total' => $total,
			'rows' => $ret
		);
	}
	
	/**
	 * Build date from component fields
	 */
	private function buildDateFromComponents($request, $prefix) {
		$day = !empty($request[$prefix . '_day']) ? $request[$prefix . '_day'] : '01';
		$month = !empty($request[$prefix . '_month']) ? $request[$prefix . '_month'] : '01';
		$year = !empty($request[$prefix . '_year']) ? $request[$prefix . '_year'] : date('Y');
		$hour = !empty($request[$prefix . '_hour']) ? $request[$prefix . '_hour'] : '00';
		
		if ($prefix == 'to' && empty($request[$prefix . '_hour'])) {
			$hour = '23';
			$minute = '59';
			$second = '59';
		} else {
			$minute = '00';
			$second = '00';
		}
		
		return "$year-$month-$day $hour:$minute:$second";
	}
	
	/**
	 * Build search condition based on modifier
	 */
	private function buildSearchCondition($field, $value, $modifier) {
		$param_name = ':search_' . $field . '_' . uniqid();
		
		switch ($modifier) {
			case 'not':
				return array(
					'sql' => "$field NOT LIKE $param_name",
					'params' => array($param_name => '%' . $value . '%')
				);
			case 'begins':
				return array(
					'sql' => "$field LIKE $param_name",
					'params' => array($param_name => $value . '%')
				);
			case 'ends':
				return array(
					'sql' => "$field LIKE $param_name",
					'params' => array($param_name => '%' . $value)
				);
			case 'exactly':
				return array(
					'sql' => "$field = $param_name",
					'params' => array($param_name => $value)
				);
			case 'contains':
			default:
				return array(
					'sql' => "$field LIKE $param_name",
					'params' => array($param_name => '%' . $value . '%')
				);
		}
	}
	
	/**
	 * Export CDR data as CSV - matching original CDR module format exactly
	 */
	public function exportCsv() {
		// Get the same data as the grid
		$data = $this->getCdrData();
		
		// Set headers for CSV download
		header('Content-Type: text/csv; charset=utf-8');
		header('Content-Disposition: attachment; filename=cdr_export_' . date('Y-m-d_H-i-s') . '.csv');
		
		$output = fopen('php://output', 'w');
		
		// CSV headers - matching original CDR module format exactly
		$headers = array(
			'calldate', 'clid', 'src', 'dst', 'dcontext', 'channel', 'dstchannel', 'lastapp', 'lastdata',
			'duration', 'billsec', 'disposition', 'amaflags', 'accountcode', 'uniqueid', 'userfield', 'did',
			'cnum', 'cnam', 'outbound_cnum', 'outbound_cnam', 'dst_cnam', 'recordingfile', 'linkedid', 'peeraccount', 'sequence'
		);
		
		fputcsv($output, $headers);
		
		// CSV data - matching original CDR module format exactly
		foreach ($data['rows'] as $row) {
			$csv_row = array(
				$row['calldate'], $row['clid'], $row['src'], $row['dst'], $row['dcontext'],
				$row['channel'], $row['dstchannel'], $row['lastapp'], $row['lastdata'],
				$row['duration'], $row['billsec'], $row['disposition'], $row['amaflags'],
				$row['accountcode'], $row['uniqueid'], $row['userfield'], $row['did'],
				$row['cnum'], $row['cnam'], $row['outbound_cnum'], $row['outbound_cnam'],
				$row['dst_cnam'], basename($row['recordingfile']), $row['linkedid'], 
				$row['peeraccount'], $row['sequence']
			);
			fputcsv($output, $csv_row);
		}
		
		fclose($output);
		exit;
	}

	/**
	 * Get CEL events for a specific call
	 * @return array CEL events data
	 */
	public function getCelEvents() {
		if (empty($_REQUEST['uniqueid'])) {
			return array('status' => false, 'message' => _('No uniqueid provided'));
		}
		
		$uniqueid = $_REQUEST['uniqueid'];
		
		// Check if CEL is enabled
		$cel_config = $this->FreePBX->Config()->get('CEL_ENABLED');
		$cel_enabled = !empty($cel_config) && $cel_config;
		if (!$cel_enabled) {
			return array('status' => false, 'message' => _('CEL is not enabled'));
		}
		
		try {
			// Query CEL table for events related to this call
			$sql = "SELECT eventtime, eventtype, channame, appname, appdata, amaflags, accountcode, uniqueid, linkedid, peer 
					FROM asteriskcdrdb.cel 
					WHERE uniqueid = :uniqueid OR linkedid = :uniqueid 
					ORDER BY eventtime ASC";
			
			$sth = $this->cdrdb->prepare($sql);
			$sth->execute(array(':uniqueid' => $uniqueid));
			$events = $sth->fetchAll(\PDO::FETCH_ASSOC);
			
			if (empty($events)) {
				return array('status' => false, 'message' => _('No CEL events found for this call'));
			}
			
			// Format the events for display
			foreach ($events as &$event) {
				// Format the event time
				if ($event['eventtime']) {
					$event['eventtime'] = date('Y-m-d H:i:s', strtotime($event['eventtime']));
				}
				
				// Clean up empty fields
				$event['channame'] = $event['channame'] ?: '';
				$event['appname'] = $event['appname'] ?: '';
				$event['appdata'] = $event['appdata'] ?: '';
			}
			
			return array(
				'status' => true,
				'events' => $events
			);
			
		} catch (\Exception $e) {
			return array('status' => false, 'message' => 'Error retrieving CEL events: ' . $e->getMessage());
		}
	}

	/**
	 * Get graph data for CanvasJS charts
	 * @return array Graph data formatted for CanvasJS
	 */
	public function getGraphData() {
		if (empty($_REQUEST['params'])) {
			return array('status' => false, 'message' => _('No parameters provided'));
		}
		
		$params_json = $_REQUEST['params'];
		$params = json_decode($params_json, true);
		
		if (!$params || empty($params['graph_type'])) {
			return array('status' => false, 'message' => _('Invalid parameters or missing graph type'));
		}
		
		$graph_type = $params['graph_type'];
		
		// Build WHERE clause using the same logic as getCdrData
		$where_conditions = array();
		$sql_params = array();
		
		// Date range filters
		if (!empty($params['startdate']) && !empty($params['enddate'])) {
			$where_conditions[] = "calldate BETWEEN :startdate AND :enddate";
			$sql_params[':startdate'] = $params['startdate'];
			$sql_params[':enddate'] = $params['enddate'];
		}
		
		// Advanced date/time filters
		if (!empty($params['from_day']) || !empty($params['from_month']) || !empty($params['from_year'])) {
			$from_date = $this->buildDateFromComponents($params, 'from');
			if ($from_date) {
				$where_conditions[] = "calldate >= :from_date";
				$sql_params[':from_date'] = $from_date;
			}
		}
		
		if (!empty($params['to_day']) || !empty($params['to_month']) || !empty($params['to_year'])) {
			$to_date = $this->buildDateFromComponents($params, 'to');
			if ($to_date) {
				$where_conditions[] = "calldate <= :to_date";
				$sql_params[':to_date'] = $to_date;
			}
		}
		
		// Other filters (disposition, report type, etc.)
		if (!empty($params['disposition'])) {
			$where_conditions[] = "disposition = :disposition";
			$sql_params[':disposition'] = $params['disposition'];
		}
		
		// Build WHERE clause
		$where_clause = '';
		if (!empty($where_conditions)) {
			$where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
		}
		
		try {
			$chartData = array();
			
			switch ($graph_type) {
				case 'calls_by_hour':
					$sql = "SELECT HOUR(calldate) as hour, COUNT(*) as call_count 
							FROM " . $this->db_table . " 
							$where_clause 
							GROUP BY HOUR(calldate) 
							ORDER BY hour";
					break;
					
				case 'calls_by_day':
					$sql = "SELECT DATE(calldate) as call_date, COUNT(*) as call_count 
							FROM " . $this->db_table . " 
							$where_clause 
							GROUP BY DATE(calldate) 
							ORDER BY call_date DESC 
							LIMIT 30";
					break;
					
				case 'calls_by_disposition':
					$sql = "SELECT disposition, COUNT(*) as call_count 
							FROM " . $this->db_table . " 
							$where_clause 
							GROUP BY disposition 
							ORDER BY call_count DESC";
					break;
					
				case 'duration_by_hour':
					$sql = "SELECT HOUR(calldate) as hour, SUM(duration) as total_duration 
							FROM " . $this->db_table . " 
							$where_clause 
							GROUP BY HOUR(calldate) 
							ORDER BY hour";
					break;
					
				case 'calls_by_source':
					$sql = "SELECT src, COUNT(*) as call_count 
							FROM " . $this->db_table . " 
							$where_clause 
							GROUP BY src 
							ORDER BY call_count DESC 
							LIMIT 10";
					break;
					
				case 'calls_by_destination':
					$sql = "SELECT dst, COUNT(*) as call_count 
							FROM " . $this->db_table . " 
							$where_clause 
							GROUP BY dst 
							ORDER BY call_count DESC 
							LIMIT 10";
					break;
					
				default:
					return array('status' => false, 'message' => _('Invalid graph type'));
			}
			
			$sth = $this->cdrdb->prepare($sql);
			$sth->execute($sql_params);
			$chartData = $sth->fetchAll(\PDO::FETCH_ASSOC);
			
			if (empty($chartData)) {
				return array('status' => false, 'message' => _('No data found for the selected criteria'));
			}
			
			return array(
				'status' => true,
				'chartData' => $chartData
			);
			
		} catch (\Exception $e) {
			return array('status' => false, 'message' => _('Error retrieving graph data: ') . $e->getMessage());
		}
	}

}
