<?php
/**
* This is the User Control Panel Object.
*
* Copyright (C) 2013 Schmooze Com, INC
* Copyright (C) 2013 Andrew Nagy <andrew.nagy@schmoozecom.com>
*
* This program is free software: you can redistribute it and/or modify
* it under the terms of the GNU Affero General Public License as
* published by the Free Software Foundation, either version 3 of the
* License, or (at your option) any later version.
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU Affero General Public License for more details.
*
* You should have received a copy of the GNU Affero General Public License
* along with this program.  If not, see <http://www.gnu.org/licenses/>.
*
* @package   FreePBX UCP BMO
* @author   Andrew Nagy <andrew.nagy@schmoozecom.com>
* @license   AGPL v3
*/
namespace UCP\Modules;
use \UCP\Modules as Modules;

class Cdr extends Modules{
	protected $module = 'Cdr';
	private $activeConferences = array();
	private $limit = 15;
	private $break = 5;

	function __construct($Modules) {
		$this->Modules = $Modules;
		$this->cdr = $this->UCP->FreePBX->Cdr;
		$this->user = $this->UCP->User->getUser();
		if($this->UCP->Session->isMobile || $this->UCP->Session->isTablet) {
			$this->limit = 7;
		}
	}

	function getDisplay() {
		$view = !empty($_REQUEST['view']) ? $_REQUEST['view'] : 'history';
		$ext = !empty($_REQUEST['sub']) ? $_REQUEST['sub'] : '';
		if(!$this->_checkExtension($ext)) {
			return _('Not Authorized');
		}
		$page = !empty($_REQUEST['page']) ? $_REQUEST['page'] : 1;
		$order = !empty($_REQUEST['order']) && ($_REQUEST['order'] == 'asc') ? 'asc' : 'desc';
		$orderby = !empty($_REQUEST['orderby']) ? $_REQUEST['orderby'] : 'date';
		$search = !empty($_REQUEST['search']) ? $_REQUEST['search'] : '';

		$totalPages = $this->cdr->getPages($ext,$search,$this->limit);
		$displayvars = array(
			'ext' => $ext,
			'activeList' => $view,
			'calls' => $this->postProcessCalls($this->cdr->getCalls($ext,$page,$orderby,$order,$search,$this->limit),$ext),
		);
		$html = '';
		$html = "<script>var extension = '".$ext."';var showPlayback = ".json_encode($this->_checkPlayback($ext)).";var showDownload = ".json_encode($this->_checkDownload($ext))."; var supportedHTML5 = '".implode(",",$this->UCP->FreePBX->Media->getSupportedHTML5Formats())."';</script>";
		switch($view) {
			case 'settings':
				$html .= $this->load_view(__DIR__.'/views/settings.php',$displayvars);
			break;
			case 'history':
			default:
				$searchl = !empty($search) ? '&amp;search='.urlencode($search) : '';
				$link = '?display=dashboard&mod=cdr&sub='.$ext.'&view=history&order='.$order.'&orderby='.$orderby.$searchl;
				$displayvars['pagnation'] = $this->UCP->Template->generatePagnation($totalPages,$page,$link,$this->break);
				$displayvars['search'] = $search;
				$displayvars['desktop'] = (!$this->UCP->Session->isMobile && !$this->UCP->Session->isTablet);
				$displayvars['order'] = !empty($_REQUEST['order']) ? $_REQUEST['order'] : 'desc';
				$displayvars['orderby'] = !empty($_REQUEST['orderby']) ? $_REQUEST['orderby'] : 'date';
				$displayvars['showDownload'] = $this->_checkDownload($ext);
				$displayvars['showPlayback'] = $this->_checkPlayback($ext);
				$html .= $this->load_view(__DIR__.'/views/view.php',$displayvars);
			break;
		}
		return $html;
	}

	function poll($data) {
		return array('status' => false);
	}

	/**
	* Determine what commands are allowed
	*
	* Used by Ajax Class to determine what commands are allowed by this class
	*
	* @param string $command The command something is trying to perform
	* @param string $settings The Settings being passed through $_POST or $_PUT
	* @return bool True if pass
	*/
	function ajaxRequest($command, $settings) {
		switch($command) {
			case 'grid':
				return true;
			break;
			case 'download':
				return $this->_checkDownload($_REQUEST['ext']);
			break;
			case 'gethtml5':
			case 'playback':
				return $this->_checkPlayback($_REQUEST['ext']);
			break;
			default:
				return false;
			break;
		}
	}

	/**
	* The Handler for all ajax events releated to this class
	*
	* Used by Ajax Class to process commands
	*
	* @return mixed Output if success, otherwise false will generate a 500 error serverside
	*/
	function ajaxHandler() {
		$return = array("status" => false, "message" => "");
		switch($_REQUEST['command']) {
			case "grid":
				$limit = $_REQUEST['limit'];
				$ext = $_REQUEST['extension'];
				$order = $_REQUEST['order'];
				$orderby = !empty($_REQUEST['sort']) ? $_REQUEST['sort'] : "date";
				$search = !empty($_REQUEST['search']) ? $_REQUEST['search'] : "";
				$pages = $this->cdr->getPages($ext,$search,$limit);
				$total = $pages * $limit;
				$offset = $_REQUEST['offset'];
				$page = ($offset / $limit) + 1;
				$data = $this->postProcessCalls($this->cdr->getCalls($ext,$page,$orderby,$order,$search,$limit),$ext);
				return array(
					"total" => $total,
					"rows" => $data
				);
			break;
			case 'gethtml5':
				$media = $this->UCP->FreePBX->Media();
				$record = $this->UCP->FreePBX->Cdr->getRecordByIDExtension($_REQUEST['id'],$_REQUEST['ext']);
				if(!file_exists($record['recordingfile'])) {
					return array("status" => false, "message" => _("File does not exist"));
				}
				$media->load($record['recordingfile']);
				$files = $media->generateHTML5();
				$final = array();
				foreach($files as $format => $name) {
					$final[$format] = "index.php?quietmode=1&module=cdr&command=playback&file=".$name."&ext=".$_REQUEST['ext'];
				}
				return array("status" => true, "files" => $final);
			break;
			default:
				return false;
			break;
		}
		return $return;
	}

	/**
	* The Handler for quiet events
	*
	* Used by Ajax Class to process commands in which custom processing is needed
	*
	* @return mixed Output if success, otherwise false will generate a 500 error serverside
	*/
	function ajaxCustomHandler() {
		switch($_REQUEST['command']) {
			case "download":
				$msgid = $_REQUEST['msgid'];
				$ext = $_REQUEST['ext'];
				$this->downloadFile($msgid,$ext);
				return true;
			case "playback":
				$media = $this->UCP->FreePBX->Media();
				$media->getHTML5File($_REQUEST['file']);
				return true;
			break;
			default:
				return false;
			break;
		}
		return false;
	}


	public function getMenuItems() {
		$user = $this->UCP->User->getUser();
		$enabled = $this->UCP->getCombinedSettingByID($user['id'],'Cdr','enable');
		if(!$enabled) {
			return array();
		}
		$extensions = $this->UCP->getCombinedSettingByID($user['id'],'Cdr','assigned');
		$menu = array();
		if(!empty($extensions)) {
			$menu = array(
				"rawname" => "cdr",
				"name" => _("Call History"),
				"badge" => false
			);
			foreach($extensions as $e) {
				$data = $this->UCP->FreePBX->Core->getDevice($e);
				if(empty($data) || empty($data['description'])) {
					$data = $this->UCP->FreePBX->Core->getUser($e);
					$name = $data['name'];
				} else {
					$name = $data['description'];
				}
				$menu["menu"][] = array(
					"rawname" => $e,
					"name" => $e . (!empty($name) ? " - " . $name : ""),
					"badge" => false
				);
			}
		}
		return !empty($menu["menu"]) ? $menu : array();
	}

	private function postProcessCalls($calls,$self) {
		foreach($calls as &$call) {
			$app = strtolower($call['lastapp']);
			switch($app) {
				case 'dial':
					switch($call['disposition']) {
						case 'ANSWERED':
							if($call['src'] == $self) {
								$call['icons'][] = 'fa-arrow-right out';
								$device = $this->UCP->FreePBX->Core->getDevice($call['dst']);
								$call['text'] = !empty($device['description']) ? htmlentities('"'.$device['description'].'"' . " <".$call['dst'].">",ENT_COMPAT | ENT_HTML401, "UTF-8") : $call['dst'];
							} elseif($call['dst'] == $self) {
								$call['icons'][] = 'fa-arrow-left in';
								$call['text'] = htmlentities($call['clid'],ENT_COMPAT | ENT_HTML401, "UTF-8");
							} elseif($call['cnum'] == $self) {
								$call['icons'][] = 'fa-arrow-right out';
								$call['text'] = htmlentities($call['dst'],ENT_COMPAT | ENT_HTML401, "UTF-8");
							} else {
								$call['icons'][] = 'fa-arrow-left in';
								$call['text'] = htmlentities($call['src'],ENT_COMPAT | ENT_HTML401, "UTF-8");
							}
						break;
						case 'NO ANSWER':
							//Remove the recording reference as these are almost always errors (from what I've seen)
							$call['recordingfile'] = '';
							if($call['src'] == $self) {
								$device = $this->UCP->FreePBX->Core->getDevice($call['dst']);
								$call['icons'][] = 'fa-arrow-right out';
								$call['icons'][] = 'fa-ban';
								$call['text'] = !empty($device['description']) ? htmlentities('"'.$device['description'].'"' . " <".$call['dst'].">",ENT_COMPAT | ENT_HTML401, "UTF-8") : $call['dst'];
							} elseif($call['dst'] == $self) {
								$call['icons'][] = 'fa-ban';
								$call['icons'][] = 'fa-arrow-left in';
								$call['text'] = htmlentities($call['clid'],ENT_COMPAT | ENT_HTML401, "UTF-8");
							} elseif($call['cnum'] == $self) {
								$call['icons'][] = 'fa-arrow-right out';
								$call['text'] = htmlentities($call['dst'],ENT_COMPAT | ENT_HTML401, "UTF-8");
							} else {
								$call['text'] = htmlentities($call['src'],ENT_COMPAT | ENT_HTML401, "UTF-8");
							}
						break;
						case 'BUSY':
							if($call['src'] == $self) {
								$device = $this->UCP->FreePBX->Core->getDevice($call['dst']);
								$call['icons'][] = 'fa-arrow-right out';
								$call['icons'][] = 'fa-clock-o';
								$call['text'] = !empty($device['description']) ? htmlentities('"'.$device['description'].'"' . " <".$call['dst'].">",ENT_COMPAT | ENT_HTML401, "UTF-8") : $call['dst'];
							} elseif($call['dst'] == $self) {
								$call['icons'][] = 'fa-ban';
								$call['icons'][] = 'fa-clock-o';
								$call['text'] = $call['clid'];
							} elseif($call['cnum'] == $self) {
								$call['icons'][] = 'fa-arrow-right out';
								$call['text'] = htmlentities($call['dst'],ENT_COMPAT | ENT_HTML401, "UTF-8");
							} else {
								$call['text'] = htmlentities($call['src'],ENT_COMPAT | ENT_HTML401, "UTF-8");
							}
						break;
					}
					if(!empty($call['text']) && preg_match('/LC\-(\d*)/i',$call['text'],$matches)) {
						$device = $this->UCP->FreePBX->Core->getDevice($matches[1]);
						$call['text'] = !empty($device['description']) ? htmlentities('"'.$device['description'].'"' . " <".$matches[1].">",ENT_COMPAT | ENT_HTML401, "UTF-8") : $matches[1];
					}
				break;
				case 'voicemail':
					if($call['src'] == $self) {
						$call['icons'][] = 'fa-arrow-right out';
						$call['icons'][] = 'fa-envelope';
						$call['text'] = htmlentities($call['dst'],ENT_COMPAT | ENT_HTML401, "UTF-8");
					} elseif($call['dst'] == $self) {
						$call['icons'][] = 'fa-envelope';
						$call['icons'][] = 'fa-arrow-left in';
						$call['text'] = htmlentities($call['clid'],ENT_COMPAT | ENT_HTML401, "UTF-8");
					} else {
						$call['icons'][] = 'fa-envelope';
						$call['text'] = htmlentities($call['src'],ENT_COMPAT | ENT_HTML401, "UTF-8");
					}
					if(preg_match('/^vmu(\d*)/i',$call['text'],$matches)) {
						$device = $this->UCP->FreePBX->Core->getDevice($matches[1]);
						$desc = !empty($device['description']) ? htmlentities('"'.$device['description'].'"' . " <".$matches[1].">",ENT_COMPAT | ENT_HTML401, "UTF-8") : $matches[1];
						$call['text'] = $desc . ' ' . _('Voicemail');
					} else {
						$id = trim($call['text']);
						$device = $this->UCP->FreePBX->Core->getDevice($id);
						$desc = !empty($device['description']) ? htmlentities('"'.$device['description'].'"' . " <".$id.">",ENT_COMPAT | ENT_HTML401, "UTF-8") : $id;
						$call['text'] = $desc . ' ' . _('Voicemail');
					}
				break;
				case 'confbridge':
				case 'meetme':
					if($call['src'] == $self) {
						$call['icons'][] = 'fa-arrow-right out';
						$call['icons'][] = 'fa-users';
						$conference = $this->UCP->FreePBX->Conferences->getConference($call['dst']);
						$call['text'] = _('Conference') . ' ' . (!empty($conference['description']) ? htmlentities('"'.$conference['description'].'"' . " <".$call['dst'].">",ENT_COMPAT | ENT_HTML401, "UTF-8") : $call['dst']);
					} elseif($call['dst'] == $self) {
						$call['icons'][] = 'fa-users';
						$call['icons'][] = 'fa-arrow-left in';
						$call['text'] = _('Conference') . ' ' . htmlentities($call['clid'],ENT_COMPAT | ENT_HTML401, "UTF-8");
					} else {
						$call['icons'][] = 'fa-users';
						$call['text'] = $call['src'];
					}
				break;
				case 'hangup':
					switch($call['dst']) {
						case 'STARTMEETME':
							$call['icons'][] = 'fa-users';
							$call['text'] = $call['src'] . ' ' . _('kicked from conference');
						break;
						case 'denied':
							$call['icons'][] = 'fa-ban';
							$call['text'] = $call['src'] . ' ' . _('denied by COS');
						break;
						default:
							if($call['src'] == $self) {
								$call['icons'][] = 'fa-arrow-right out';
								$call['icons'][] = 'fa-ban';
								$call['text'] = htmlentities($call['clid'],ENT_COMPAT | ENT_HTML401, "UTF-8");
							} elseif($call['dst'] == $self) {
								$call['icons'][] = 'fa-ban';
								$call['icons'][] = 'fa-arrow-left in';
								$call['text'] = htmlentities($call['clid'],ENT_COMPAT | ENT_HTML401, "UTF-8");
							} else {
								$call['text'] = _('Unknown') . ' (' . $call['uniqueid'] . ')';
							}
						break;
					}
				break;
				case 'playback':
					if($call['src'] == $self) {
						$call['icons'][] = 'fa-arrow-right out';
						$call['text'] = htmlentities($call['dst'],ENT_COMPAT | ENT_HTML401, "UTF-8");
					} else {
						$call['text'] = htmlentities($call['src'],ENT_COMPAT | ENT_HTML401, "UTF-8");
					}
				break;
				default:
					if($call['src'] == $self) {
						$call['icons'][] = 'fa-arrow-right out';
						$call['text'] = htmlentities($call['dst'],ENT_COMPAT | ENT_HTML401, "UTF-8");
					} elseif($call['dst'] == $self) {
						$call['icons'][] = 'fa-arrow-left in';
						$call['text'] = htmlentities($call['src'],ENT_COMPAT | ENT_HTML401, "UTF-8");
					} else {
						$call['text'] = _('Unknown') . ' (' . $call['uniqueid'] . ')';
					}
			}
			if(empty($call['text'])) {
				$call['text'] = _('Unknown') . ' (' . $call['uniqueid'] . ')';
			} else {
				$call['text'] = preg_replace("/&lt;(.*)&gt;/i","&lt;<span class='clickable' data-type='number' data-primary='phone'>$1</span>&gt;",$call['text']);
			}
			$call['formattedTime'] = date('m/d/y h:i:sa',$call['timestamp']);
		}
		return $calls;
	}

	/**
	 * Download a file to listen to on your desktop
	 * @param  string $msgid The message id
	 * @param  int $ext   Extension wanting to listen to
	 */
	private function downloadFile($msgid,$ext) {
		if(!$this->_checkExtension($ext)) {
			header("HTTP/1.0 403 Forbidden");
			echo _("Forbidden");
			exit;
		}
		$record = $this->UCP->FreePBX->Cdr->getRecordByIDExtension($msgid,$ext);
		if(!file_exists($record['recordingfile'])) {
			header("HTTP/1.0 404 Not Found");
			echo _("Not Found");
			exit;
		}
		header("Content-length: " . filesize($record['recordingfile']));
		header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
		header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
		header('Content-Disposition: attachment;filename="' . basename($record['recordingfile']).'"');
		readfile($record['recordingfile']);
	}

	private function _checkExtension($extension) {
		$enabled = $this->UCP->getCombinedSettingByID($this->user['id'],'Cdr','enable');
		if(!$enabled) {
			return false;
		}
		$extensions = $this->UCP->getCombinedSettingByID($this->user['id'],'Cdr','assigned');
		return in_array($extension,$extensions);
	}

	private function _checkDownload($extension) {
		$enabled = $this->UCP->getCombinedSettingByID($this->user['id'],'Cdr','enable');
		if(!$enabled) {
			return false;
		}
		if($this->_checkExtension($extension)) {
			$dl = $this->UCP->getCombinedSettingByID($this->user['id'],'Cdr','download');
			return is_null($dl) ? true : $dl;
		}
		return false;
	}

	private function _checkPlayback($extension) {
		$enabled = $this->UCP->getCombinedSettingByID($this->user['id'],'Cdr','enable');
		if(!$enabled) {
			return false;
		}
		if($this->_checkExtension($extension)) {
			$pb = $this->UCP->getCombinedSettingByID($this->user['id'],'Cdr','playback');
			return is_null($pb) ? true : $pb;
		}
		return false;
	}
}
