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

	function __construct($Modules) {
		$this->Modules = $Modules;
		$this->cdr = $this->UCP->FreePBX->Cdr;
	}

	function getDisplay() {
		$view = !empty($_REQUEST['view']) ? $_REQUEST['view'] : 'history';
		$ext = !empty($_REQUEST['sub']) ? $_REQUEST['sub'] : '';
		$page = !empty($_REQUEST['page']) ? $_REQUEST['page'] : 1;
		$html = $this->loadLESS();
		$displayvars = array(
			'ext' => $ext,
			'activeList' => $view,
			'calls' => $this->cdr->getCalls($ext,$page),
			'totalPages' => $this->cdr->getPages($ext),
			'activePage' => $page
		);
		dbug($this->cdr->getCalls($ext,$page));
		$html .= $this->load_view(__DIR__.'/views/nav.php',$displayvars);
		switch($view) {
			case 'settings':
				$html .= $this->load_view(__DIR__.'/views/settings.php',$displayvars);
			break;
			case 'history':
			default:
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
				$menu["menu"][] = array(
					"rawname" => $e,
					"name" => $e,
					"badge" => false
				);
			}
		}
		return !empty($menu["menu"]) ? $menu : array();
	}
}
