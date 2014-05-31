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
	private $break = 10;

	function __construct($Modules) {
		$this->Modules = $Modules;
		$this->cdr = $this->UCP->FreePBX->Cdr;
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
		$html = $this->loadLESS();

		$totalPages = $this->cdr->getPages($ext,$search,$this->limit);
		$displayvars = array(
			'ext' => $ext,
			'activeList' => $view,
			'calls' => $this->postProcessCalls($this->cdr->getCalls($ext,$page,$orderby,$order,$search,$this->limit),$ext),
		);
		//$html .= $this->load_view(__DIR__.'/views/nav.php',$displayvars);
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
				$displayvars['order'] = !empty($_REQUEST['order']) ? $_REQUEST['order'] : 'desc';
				$displayvars['orderby'] = !empty($_REQUEST['orderby']) ? $_REQUEST['orderby'] : 'date';
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
			case 'listen':
				return true;
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
			case "listen":
				$msgid = $_REQUEST['msgid'];
				$format = $_REQUEST['format'];
				$ext = $_REQUEST['ext'];
				$this->readRemoteFile($msgid,$ext,$format);
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
		$extensions = $this->UCP->getSetting($user['username'],'Voicemail','assigned');
		$menu = array();
		if(!empty($extensions)) {
			$menu = array(
				"rawname" => "cdr",
				"name" => "Call History",
				"badge" => false
			);
			foreach($extensions as $e) {
				$data = $this->UCP->FreePBX->Core->getDevice($e);
				$menu["menu"][] = array(
					"rawname" => $e,
					"name" => $e . " - " . $data['description'],
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
								$call['text'] = !empty($device['description']) ? htmlentities('"'.$device['description'].'"' . " <".$call['dst'].">") : $call['dst'];
							} elseif($call['dst'] == $self) {
								$call['icons'][] = 'fa-arrow-left in';
								$call['text'] = htmlentities($call['clid']);
							} else {
								$call['text'] = $call['src'];
							}
						break;
						case 'NO ANSWER':
							if($call['src'] == $self) {
								$device = $this->UCP->FreePBX->Core->getDevice($call['dst']);
								$call['icons'][] = 'fa-arrow-right out';
								$call['icons'][] = 'fa-ban';
								$call['text'] = !empty($device['description']) ? htmlentities('"'.$device['description'].'"' . " <".$call['dst'].">") : $call['dst'];
							} elseif($call['dst'] == $self) {
								$call['icons'][] = 'fa-ban';
								$call['icons'][] = 'fa-arrow-left in';
								$call['text'] = $call['clid'];
							} else {
								$call['text'] = $call['src'];
							}
						break;
						case 'BUSY':
							if($call['src'] == $self) {
								$device = $this->UCP->FreePBX->Core->getDevice($call['dst']);
								$call['icons'][] = 'fa-arrow-right out';
								$call['icons'][] = 'fa-clock-o';
								$call['text'] = !empty($device['description']) ? htmlentities('"'.$device['description'].'"' . " <".$call['dst'].">") : $call['dst'];
							} elseif($call['dst'] == $self) {
								$call['icons'][] = 'fa-ban';
								$call['icons'][] = 'fa-clock-o';
								$call['text'] = $call['clid'];
							} else {
								$call['text'] = $call['src'];
							}
						break;
					}
					if(preg_match('/LC\-(\d*)/i',$call['text'],$matches)) {
						$device = $this->UCP->FreePBX->Core->getDevice($matches[1]);
						$call['text'] = !empty($device['description']) ? htmlentities('"'.$device['description'].'"' . " <".$matches[1].">") : $matches[1];
					}
				break;
				case 'voicemail':
					if($call['src'] == $self) {
						$call['icons'][] = 'fa-arrow-right out';
						$call['icons'][] = 'fa-envelope';
						$call['text'] = $call['dst'];
					} elseif($call['dst'] == $self) {
						$call['icons'][] = 'fa-envelope';
						$call['icons'][] = 'fa-arrow-left in';
						$call['text'] = htmlentities($call['clid']);
					} else {
						$call['icons'][] = 'fa-envelope';
						$call['text'] = $call['src'];
					}
					if(preg_match('/^vmu(\d*)/i',$call['text'],$matches)) {
						$device = $this->UCP->FreePBX->Core->getDevice($matches[1]);
						$desc = !empty($device['description']) ? htmlentities('"'.$device['description'].'"' . " <".$matches[1].">") : $matches[1];
						$call['text'] = $desc . ' ' . _('Voicemail');
					} else {
						$id = trim($call['text']);
						$device = $this->UCP->FreePBX->Core->getDevice($id);
						$desc = !empty($device['description']) ? htmlentities('"'.$device['description'].'"' . " <".$id.">") : $id;
						$call['text'] = $desc . ' ' . _('Voicemail');
					}
				break;
				case 'confbridge':
				case 'meetme':
					if($call['src'] == $self) {
						$call['icons'][] = 'fa-arrow-right out';
						$call['icons'][] = 'fa-users';
						$conference = $this->UCP->FreePBX->Conferences->getConference($call['dst']);
						$call['text'] = _('Conference') . ' ' . (!empty($conference['description']) ? htmlentities('"'.$conference['description'].'"' . " <".$call['dst'].">") : $call['dst']);
					} elseif($call['dst'] == $self) {
						$call['icons'][] = 'fa-users';
						$call['icons'][] = 'fa-arrow-left in';
						$call['text'] = _('Conference') . ' ' . htmlentities($call['clid']);
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
					}
				break;
				case 'playback':
					if($call['src'] == $self) {
						$call['icons'][] = 'fa-arrow-right out';
						$call['text'] = $call['dst'];
					} else {
						$call['text'] = $call['src'];
					}
				break;
				default:
			}
			if(empty($call['text'])) {
				$call['text'] = _('Unknown') . ' (' . $call['uniqueid'] . ')';
			}
		}
		return $calls;
	}

	private function readRemoteFile($msgid,$ext,$format) {
		if(!$this->_checkExtension($ext)) {
			header("HTTP/1.0 403 Forbidden");
			echo _("Forbidden");
			exit;
		}

		$record = $this->UCP->FreePBX->Cdr->getRecordByIDExtension($msgid,$ext);
		if(!empty($record) && !empty($record['recordings']['format'][$format]) && !empty($record['recordings']['format'][$format]['length'])) {
			$ct = null;
			switch($format) {
				case "WAV":
				case "wav":
					$ct = "audio/x-wav, audio/wav";
				break;
				case "oga":
				case "ogg":
					$ct = "audio/ogg";
				break;
				default:
					if($_REQUEST['type'] == 'download') {
						switch($format) {
							case "ulaw":
								$ctype="audio/basic";
							case "alaw":
								$ctype="audio/x-alaw-basic";
							case "sln":
								$ctype="audio/x-wav";
							case "gsm":
								$ctype="audio/x-gsm";
							case "g729":
								$ctype="audio/x-g729";
							break;
							default:
								header("HTTP/1.0 404 Not Found");
								echo _("File Not Found");
								exit;
							break;
						}
					} else {
						header("HTTP/1.0 404 Not Found");
						echo _("File Not Found");
						exit;
					}
				break;
			}

			header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
			header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
			$size   = $record['recordings']['format'][$format]['length']; // File size
			$length = $size;           // Content length
			$start  = 0;               // Start byte
			$end    = $size - 1;       // End byte

			header('Content-Description: File Transfer');
			header("Content-Transfer-Encoding: binary");
			header('Content-Type: '.$ct);
			header("Accept-Ranges: 0-".$size);
			if (isset($_SERVER['HTTP_RANGE'])) {
				$c_start = $start;
				$c_end   = $end;

				list(, $range) = explode('=', $_SERVER['HTTP_RANGE'], 2);
				if (strpos($range, ',') !== false) {
					header('HTTP/1.1 416 Requested Range Not Satisfiable');
					header("Content-Range: bytes $start-$end/$size");
					exit;
				}
				if ($range == '-') {
					$c_start = $size - substr($range, 1);
				}else{
					$range  = explode('-', $range);
					$c_start = $range[0];
					$c_end   = (isset($range[1]) && is_numeric($range[1])) ? $range[1] : $size;
				}
				$c_end = ($c_end > $end) ? $end : $c_end;
				if ($c_start > $c_end || $c_start > $size - 1 || $c_end >= $size) {
					header('HTTP/1.1 416 Requested Range Not Satisfiable');
					header("Content-Range: bytes $start-$end/$size");
					exit;
				}
				$start  = $c_start;
				$end    = $c_end;
				$length = $end - $start + 1;
				header('HTTP/1.1 206 Partial Content');
			} else {
				header("HTTP/1.1 200 OK");
			}

			header("Content-Range: bytes $start-$end/$size");
			header('Content-length: ' . $size);
			header('Content-Disposition: attachment;filename="' . $record['recordings']['format'][$format]['filename'].'"');
			$buffer = 1024 * 8;
			$wstart = $start;
			ob_end_clean();
			ob_start();
			while(true) {
				$content = $this->UCP->FreePBX->Cdr->readRecordingBinaryByRecordingIDExtension($msgid,$ext,$format,$wstart,$buffer);
				if(!$content) {
					break;
				}
				echo $content;
				ob_flush();
				flush();
				$wstart = $wstart + $buffer;
				set_time_limit(0);
			}
			exit;
		} else {
			header("HTTP/1.0 404 Not Found");
			echo _("File Not Found");
			exit;
		}
	}

	private function _checkExtension($extension) {
		$user = $this->UCP->User->getUser();
		$extensions = $this->UCP->getSetting($user['username'],'Voicemail','assigned');
		return in_array($extension,$extensions);
	}
}
