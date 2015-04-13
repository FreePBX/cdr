<?php
// vim: set ai ts=4 sw=4 ft=php:
class Cdr implements BMO {
	//supported playback formats
	public $supportedFormats = array(
		"oga" => "ogg",
		"wav" => "wav"
	);

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
		$dbt = $config->get('CDRDBTYPE');

		$db_hash = array('mysql' => 'mysql', 'postgres' => 'pgsql');
		$dbt = !empty($dbt) ? $dbt : 'mysql';
		$db_type = $db_hash[$dbt];
		$db_name = !empty($db_name) ? $db_name : "asteriskcdrdb";
		$db_host = !empty($db_host) ? $db_host : "localhost";
		$db_port = empty($db_port) ? '' :  ':' . $db_port;
		$db_user = empty($db_user) ? $amp_conf['AMPDBUSER'] : $db_user;
		$db_pass = empty($db_pass) ? $amp_conf['AMPDBPASS'] : $db_pass;
		try {
			$this->cdrdb = new \Database($db_type.':host='.$db_host.$db_port.';dbname='.$db_name,$db_user,$db_pass);
		} catch(\Exception $e) {
			die('Unable to connect to CDR Database using string:'.$db_type.':host='.$db_host.$db_port.';dbname='.$db_name.','.$db_user.','.$db_pass);
		}
	}

	public function processUCPAdminDisplay($user) {
		if(!empty($_POST['ucp_cdr'])) {
			$this->FreePBX->Ucp->setSetting($user['username'],'Cdr','assigned',$_POST['ucp_cdr']);
		} else {
			$this->FreePBX->Ucp->setSetting($user['username'],'Cdr','assigned',array());
		}
		if(!empty($_REQUEST['cdr_download']) && $_REQUEST['cdr_download'] == 'yes') {
			$this->FreePBX->Ucp->setSetting($user['username'],'Cdr','download',true);
		} else {
			$this->FreePBX->Ucp->setSetting($user['username'],'Cdr','download',false);
		}
		if(!empty($_REQUEST['cdr_playback']) && $_REQUEST['cdr_playback'] == 'yes') {
			$this->FreePBX->Ucp->setSetting($user['username'],'Cdr','playback',true);
		} else {
			$this->FreePBX->Ucp->setSetting($user['username'],'Cdr','playback',false);
		}
	}

	/**
	* get the Admin display in UCP
	* @param array $user The user array
	*/
	public function getUCPAdminDisplay($user, $action) {
		$download = $this->FreePBX->Ucp->getSetting($user['username'],'Cdr','download');
		$playback = $this->FreePBX->Ucp->getSetting($user['username'],'Cdr','playback');
		$download = is_null($download) ? true : $download;
		$playback = is_null($playback) ? true : $playback;
		$cdrassigned = $this->FreePBX->Ucp->getSetting($user['username'],'Cdr','assigned');
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
			"title" => _("CDR Reports"),
			"rawname" => "cdrreports",
			"content" => load_view(dirname(__FILE__)."/views/ucp_config.php",array("cdrassigned" => $cdrassigned, "ausers" => $ausers, "playback" => $playback,"download" => $download))
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

	/**
	 * Get CDR record by record ID and extension
	 * @param int $rid           The record ID
	 * @param string $ext           The extension
	 * @param bool $generateMedia Whether to generate HTML assets or not
	 */
	public function getRecordByIDExtension($rid,$ext, $generateMedia = false) {
		$sql = "SELECT * FROM cdr WHERE uniqueid = :uid AND (src = :ext OR dst = :ext OR src = :vmext OR dst = :vmext OR cnum = :ext OR cnum = :vmext OR dstchannel LIKE :dstchannel)";
		$sth = $this->cdrdb->prepare($sql);
		try {
			$sth->execute(array("uid" => str_replace("_",".",$rid), "ext" => $ext, "vmext" => "vmu".$ext, ':dstchannel' => '%/'.$ext.'-%'));
			$recording = $sth->fetch(PDO::FETCH_ASSOC);
		} catch(\Exception $e) {
			return false;
		}
		if(!empty($recording['recordingfile'])) {
			$spool = $this->FreePBX->Config->get('ASTSPOOLDIR');
			$mixmondir = $this->FreePBX->Config->get('MIXMON_DIR');
			$rec_parts = explode('-',$recording['recordingfile']);
			$fyear = substr($rec_parts[3],0,4);
			$fmonth = substr($rec_parts[3],4,2);
			$fday = substr($rec_parts[3],6,2);
			$monitor_base = $mixmondir ? $mixmondir : $spool . '/monitor';
			$file = "$monitor_base/$fyear/$fmonth/$fday/" . $recording['recordingfile'];
			if($this->queryAudio($file)) {
				if($generateMedia) {
					$this->generateAdditionalMediaFormats($file, false);
				}
				$sha = sha1_file($file);
				$filename = pathinfo($file,PATHINFO_FILENAME);
				$basename = dirname($file);
				foreach($this->supportedFormats as $format => $extension) {
					$mf = $basename."/".$filename."_".$sha.".".$extension;
					if($this->queryAudio($mf)) {
						$recording['recordings']['format'][$format] = array(
							"filename" => basename($mf),
							"path" => dirname($mf),
							"length" => filesize($mf)
						);
					}
				}
				$recording['recordings']['format'][$format] = array(
					'path' => dirname($file),
					'filename' => basename($file),
					'length' => filesize($file)
				);
			}
		}
		return $recording;
	}

	/**
	 * Read Message Binary Data by message ID
	 * Used during playback to intercommunicate with UCP
	 * @param string  $msgid  The message ID
	 * @param int  $ext    The extension
	 * @param string  $format The format of the file to use
	 * @param int $start  The starting byte position
	 * @param int $buffer The buffer size to pass
	 */
	public function readRecordingBinaryByRecordingIDExtension($msgid,$ext,$format,$start=0,$buffer=8192) {
		$record = $this->getRecordByIDExtension($msgid,$ext);
		$fpath = $record['recordings']['format'][$format]['path']."/".$record['recordings']['format'][$format]['filename'];
		if(!empty($record) && !empty($record['recordings']['format'][$format]) && $this->queryAudio($fpath)) {
			$end = $record['recordings']['format'][$format]['length'] - 1;
			$fp = fopen($fpath, "rb");
			fseek($fp, $start);
			if(!feof($fp) && ($p = ftell($fp)) <= $end) {
				if ($p + $buffer > $end) {
					$buffer = $end - $p + 1;
				}
				$contents = fread($fp, $buffer);
				fclose($fp);
				return $contents;
			}
			fclose($fp);
		}
		return false;
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
			$sql = "SELECT *, UNIX_TIMESTAMP(calldate) As timestamp FROM cdr WHERE (dstchannel LIKE :dstchannel OR src = :extension OR dst = :extension OR src = :extensionv OR dst = :extensionv OR cnum = :extension OR cnum = :extensionv) AND (clid LIKE :search OR src LIKE :search OR dst LIKE :search) ORDER by $orderby $order LIMIT $start,$end";
			$sth = $this->cdrdb->prepare($sql);
			$sth->execute(array(':dstchannel' => '%/'.$extension.'-%', ':extension' => $extension, ':search' => '%'.$search.'%', ':extensionv' => 'vmu'.$extension));
		} else {
			$sql = "SELECT *, UNIX_TIMESTAMP(calldate) As timestamp FROM cdr WHERE (dstchannel LIKE :dstchannel OR src = :extension OR dst = :extension OR src = :extensionv OR dst = :extensionv OR cnum = :extension OR cnum = :extensionv) ORDER by $orderby $order LIMIT $start,$end";
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
			if(!empty($call['recordingfile'])) {
				$spool = $this->FreePBX->Config->get('ASTSPOOLDIR');
				$mixmondir = $this->FreePBX->Config->get('MIXMON_DIR');
				$rec_parts = explode('-',$call['recordingfile']);
				$fyear = substr($rec_parts[3],0,4);
				$fmonth = substr($rec_parts[3],4,2);
				$fday = substr($rec_parts[3],6,2);
				$monitor_base = $mixmondir ? $mixmondir : $spool . '/monitor';
				$file = "$monitor_base/$fyear/$fmonth/$fday/" . $call['recordingfile'];
				if($this->queryAudio($file)) {
					$this->generateAdditionalMediaFormats($file);
					$sha = sha1_file($file);
					$filename = pathinfo($file,PATHINFO_FILENAME);
					$basename = dirname($file);
					foreach($this->supportedFormats as $format => $extension) {
						$mf = $basename."/".$filename."_".$sha.".".$extension;
						if($this->queryAudio($mf)) {
							$call['format'][$format] = array(
								"filename" => basename($mf),
								"path" => dirname($mf),
								"length" => filesize($mf)
							);
						}
					}
				} else {
					$call['format'] = array();
					$call['recordingfile'] = "";
					$call['recordingformat'] = "";
				}
			}
		}
		return $calls;
	}

	/**
	 * Generate Media Formats for use in HTML5 playback
	 * @param string $file       The filename
	 * @param bool $background Whether to background this process or stall PHP
	 */
	private function generateAdditionalMediaFormats($file,$background = true) {
		$b = ($background) ? '&' : ''; //this is so very important
		$path = dirname($file);
		$filename = pathinfo($file,PATHINFO_FILENAME);
		if(!$this->queryAudio($file)) {
			return false;
		}
		$sha1 = sha1_file($file);
		foreach($this->supportedFormats as $format) {
			switch($format) {
				case "ogg":
				if(!file_exists($path . "/" . $filename . "_".$sha1.".ogg")) {
					exec("sox $file " . $path . "/" . $filename . "_".$sha1.".ogg > /dev/null 2>&1 ".$b);
				}
				break;
			}
		}
		return true;
	}

	/**
	* Query the audio file and make sure it's actually audio
	* @param string $file The full file path to check
	*/
	public function queryAudio($file) {
		if(!file_exists($file) || !is_readable($file)) {
			return false;
		}
		if(in_array($file,$this->validFiles)) {
			return true;
		}
		//TODO: do this part during retrieve conf and remove the files from the hard drive and db if invalid!
		$last = exec('sox '.$file.' -n stat 2>&1',$output,$ret);
		if(preg_match('/not sound/',$last)) {
			return false;
		}
		$data = array();
		foreach($output as $o) {
			$parts = explode(":",$o);
			$key = preg_replace("/\W/","",$parts[0]);
			$data[$key] = trim($parts[1]);
		}
		$this->validFiles[] = $file;
		return $data;
	}

	/**
	* Get the Number of Pages by limit for extension
	* @param {int} $extension The Extension to lookup
	* @param {int} $limit=100 The limit of results per page
	*/
	public function getPages($extension,$search='',$limit=100) {
		if(!empty($search)) {
			$sql = "SELECT count(*) as count FROM cdr WHERE (src = :extension OR dst = :extension OR src = :extensionv OR dst = :extensionv OR cnum = :extension) AND (clid LIKE :search OR src LIKE :search OR dst LIKE :search)";
			$sth = $this->cdrdb->prepare($sql);
			$sth->execute(array(':extension' => $extension, ':search' => '%'.$search.'%',':extensionv' => 'vmu'.$extension));
		} else {
			$sql = "SELECT count(*) as count FROM cdr WHERE (src = :extension OR dst = :extension OR src = :extensionv OR dst = :extensionv OR cnum = :extension)";
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
}
