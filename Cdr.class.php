<?php
// vim: set ai ts=4 sw=4 ft=php:
class Cdr implements BMO {
	private $validFiles = array();
	private $db_table = 'cdr';

	public function __construct($freepbx = null) {
		if ($freepbx == null) {
			throw new Exception("Not given a FreePBX Object");
		}

		$amp_conf = FreePBX::$conf;
		$this->FreePBX = $freepbx;
		$this->db = $freepbx->Database;
		$config = $this->FreePBX->Config;
		$db_name = $config->get('CDRDBNAME');
		$db_host = $config->get('CDRDBHOST');
		$db_port = $config->get('CDRDBPORT');
		$db_user = $config->get('CDRDBUSER');
		$db_pass = $config->get('CDRDBPASS');
		$db_table = $config->get('CDRDBTABLENAME');
		$dbt = $config->get('CDRDBTYPE');

		$db_hash = array('mysql' => 'mysql', 'postgres' => 'pgsql');
		$dbt = !empty($dbt) ? $dbt : 'mysql';
		$db_type = $db_hash[$dbt];
		$this->db_table = !empty($db_table) ? $db_table : "cdr";
		$db_name = !empty($db_name) ? $db_name : "asteriskcdrdb";
		$db_host = !empty($db_host) ? $db_host : "localhost";
		$db_port = empty($db_port) ? '' :  ';port=' . $db_port;
		$db_user = empty($db_user) ? $amp_conf['AMPDBUSER'] : $db_user;
		$db_pass = empty($db_pass) ? $amp_conf['AMPDBPASS'] : $db_pass;
		try {
			$this->cdrdb = new \Database($db_type.':host='.$db_host.$db_port.';dbname='.$db_name,$db_user,$db_pass);
		} catch(\Exception $e) {
			die('Unable to connect to CDR Database using string:'.$db_type.':host='.$db_host.$db_port.';dbname='.$db_name.','.$db_user.','.$db_pass);
		}
	}

	public function ucpDelGroup($id,$display,$data) {
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
			$ausers[$list[0]] = $list[1] . " &#60;".$list[0]."&#62;";
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

	}
	public function uninstall() {

	}
	public function backup(){

	}
	public function restore($backup){

	}
	public function genConfig() {

	}

	public function ajaxRequest($req, &$setting) {
		$setting['authenticate'] = false;
		$setting['allowremote'] = false;
		switch($req) {
			case "gethtml5":
			case "playback":
			case "download":
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
				$info = $this->getRecordByID($_POST['uid']);
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
		}
	}

	public function getRecordByID($rid) {
		$sql = "SELECT * FROM ".$this->db_table." WHERE uniqueid = :uid";
		$sth = $this->cdrdb->prepare($sql);
		try {
			$sth->execute(array("uid" => str_replace("_",".",$rid)));
			$recording = $sth->fetch(PDO::FETCH_ASSOC);
		} catch(\Exception $e) {
			return array();
		}
		$recording['recordingfile'] = $this->processPath($recording['recordingfile']);
		return $recording;
	}

	/**
	 * Get CDR record by record ID and extension
	 * @param int $rid           The record ID
	 * @param string $ext           The extension
	 * @param bool $generateMedia Whether to generate HTML assets or not
	 */
	public function getRecordByIDExtension($rid,$ext) {
		$sql = "SELECT * FROM ".$this->db_table." WHERE uniqueid = :uid AND (src = :ext OR dst = :ext OR src = :vmext OR dst = :vmext OR cnum = :ext OR cnum = :vmext OR dstchannel LIKE :dstchannel)";
		$sth = $this->cdrdb->prepare($sql);
		try {
			$sth->execute(array("uid" => str_replace("_",".",$rid), "ext" => $ext, "vmext" => "vmu".$ext, ':dstchannel' => '%/'.$ext.'-%'));
			$recording = $sth->fetch(PDO::FETCH_ASSOC);
		} catch(\Exception $e) {
			return false;
		}
		$recording['recordingfile'] = $this->processPath($recording['recordingfile']);
		return $recording;
	}

	/**
	 * Get all CDR call records
	 * @param int  $extension The extension
	 * @param integer $page      The page number to start at
	 * @param string  $orderby   Order the results by
	 * @param string  $order     Order ASC or DESC
	 * @param string  $search    The search string to use
	 * @param integer $limit     The number of results to return
	 */
	public function getCalls($extension,$page=1,$orderby='date',$order='desc',$search='',$limit=100) {
		$start = ($limit * ($page - 1));
		$end = $limit;
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
		$order = ($order == 'desc') ? 'desc' : 'asc';
		if(!empty($search)) {
			$sql = "SELECT *, UNIX_TIMESTAMP(calldate) As timestamp FROM ".$this->db_table." WHERE (dstchannel LIKE :dstchannel OR src = :extension OR dst = :extension OR src = :extensionv OR dst = :extensionv OR cnum = :extension OR cnum = :extensionv) AND (clid LIKE :search OR src LIKE :search OR dst LIKE :search) ORDER by $orderby $order LIMIT $start,$end";
			$sth = $this->cdrdb->prepare($sql);
			$sth->execute(array(':dstchannel' => '%/'.$extension.'-%', ':extension' => $extension, ':search' => '%'.$search.'%', ':extensionv' => 'vmu'.$extension));
		} else {
			$sql = "SELECT *, UNIX_TIMESTAMP(calldate) As timestamp FROM ".$this->db_table." WHERE (dstchannel LIKE :dstchannel OR src = :extension OR dst = :extension OR src = :extensionv OR dst = :extensionv OR cnum = :extension OR cnum = :extensionv) ORDER by $orderby $order LIMIT $start,$end";
			$sth = $this->cdrdb->prepare($sql);
			$sth->execute(array(':dstchannel' => '%/'.$extension.'-%', ':extension' => $extension, ':extensionv' => 'vmu'.$extension));
		}
		$calls = $sth->fetchAll(PDO::FETCH_ASSOC);
		foreach($calls as &$call) {
			if(empty($call['dst']) && preg_match('/\/(.*)\-/',$call['dstchannel'],$matches)) {
				$call['dst'] = $matches[1];
			}
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
			$sql = "SELECT count(*) as count FROM ".$this->db_table." WHERE (src = :extension OR dst = :extension OR src = :extensionv OR dst = :extensionv OR cnum = :extension) AND (clid LIKE :search OR src LIKE :search OR dst LIKE :search)";
			$sth = $this->cdrdb->prepare($sql);
			$sth->execute(array(':extension' => $extension, ':search' => '%'.$search.'%',':extensionv' => 'vmu'.$extension));
		} else {
			$sql = "SELECT count(*) as count FROM ".$this->db_table." WHERE (src = :extension OR dst = :extension OR src = :extensionv OR dst = :extensionv OR cnum = :extension)";
			$sth = $this->cdrdb->prepare($sql);
			$sth->execute(array(':extension' => $extension,':extensionv' => 'vmu'.$extension));
		}
		$res = $sth->fetch(PDO::FETCH_ASSOC);
		$total = $res['count'];
		if(!empty($total)) {
			return ceil($total/$limit);
		} else {
			return false;
		}
	}

	/**
	 * Tear apart the file name to get our correct path
	 * @param  string $recordingFile The recording file
	 * @return string                The full path
	 */
	private function processPath($recordingFile) {
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
}
