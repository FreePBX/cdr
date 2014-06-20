<?php
// vim: set ai ts=4 sw=4 ft=php:
class Cdr implements BMO {
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
		$db_name = !empty($db_name)?$db_name:"asteriskcdrdb";
		$db_host = !empty($db_host)?$db_host:"localhost";
		$db_port = empty($db_port) ? '' :  ':' . $db_port;
		$db_user = empty($db_user) ? $amp_conf['AMPDBUSER'] : $db_user;
		$db_pass = empty($db_pass) ? $amp_conf['AMPDBPASS'] : $db_pass;
		try {
			$this->cdrdb = new Database($db_type.':host='.$db_host.$db_port.';dbname='.$db_name,$db_user,$db_pass);
		} catch(\Exception $e) {
			die('Unable to connect to CDR Database using string:'.$db_type.':host='.$db_host.$db_port.';dbname='.$db_name.','.$db_user.','.$db_pass);
		}
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

	public function getRecordByIDExtension($rid,$ext) {
		$sql = "SELECT * FROM cdr WHERE uniqueid = ? AND (src = ? OR dst = ?)";
		$sth = $this->cdrdb->prepare($sql);
		try {
			$sth->execute(array(str_replace("_",".",$rid),$ext,$ext));
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
			if(file_exists($file)) {
				$format = strtolower(pathinfo($file,PATHINFO_EXTENSION));
				$recording['recordingformat'] = $format;
				$recording['recordings']['format'][$format] = array(
					'path' => dirname($file),
					'filename' => basename($file),
					'length' => filesize($file)
				);
			}
		}
		return $recording;
	}

	public function readRecordingBinaryByRecordingIDExtension($msgid,$ext,$format,$start=0,$buffer=8192) {
		$record = $this->getRecordByIDExtension($msgid,$ext);
		$fpath = $record['recordings']['format'][$format]['path']."/".$record['recordings']['format'][$format]['filename'];
		if(!empty($record) && !empty($record['recordings']['format'][$format]) && file_exists($fpath)) {
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
			$sql = "SELECT *, UNIX_TIMESTAMP(calldate) As timestamp FROM cdr WHERE (src = :extension OR dst = :extension OR src = :extensionv OR dst = :extensionv) AND (clid LIKE :search OR src LIKE :search OR dst LIKE :search) ORDER by $orderby $order LIMIT $start,$end";
			$sth = $this->cdrdb->prepare($sql);
			$sth->execute(array(':extension' => $extension, ':search' => '%'.$search.'%', ':extensionv' => 'vmu'.$extension));
		} else {
			$sql = "SELECT *, UNIX_TIMESTAMP(calldate) As timestamp FROM cdr WHERE src = :extension OR dst = :extension OR src = :extensionv OR dst = :extensionv ORDER by $orderby $order LIMIT $start,$end";
			$sth = $this->cdrdb->prepare($sql);
			$sth->execute(array(':extension' => $extension, ':extensionv' => 'vmu'.$extension));
		}
		$calls = $sth->fetchAll(PDO::FETCH_ASSOC);
		foreach($calls as &$call) {
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
				if(!file_exists($file)) {
					$call['recordingfile'] = "";
					$call['recordingformat'] = "";
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
			$sql = "SELECT count(*) as count FROM cdr WHERE (src = :extension OR dst = :extension OR src = :extensionv OR dst = :extensionv) AND (clid LIKE :search OR src LIKE :search OR dst LIKE :search)";
			$sth = $this->cdrdb->prepare($sql);
			$sth->execute(array(':extension' => $extension, ':search' => '%'.$search.'%',':extensionv' => 'vmu'.$extension));
		} else {
			$sql = "SELECT count(*) as count FROM cdr WHERE src = :extension OR dst = :extension OR src = :extensionv OR dst = :extensionv";
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
