<?php
//	License for all code of this FreePBX module can be found in the license file inside the module directory
//  Portions Copyright (C) 2011 Igor Okunev
//  Portions Copyright (C) 2011 Mikael Carlsson
//	Copyright 2013 Schmooze Com Inc.
//
if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }

global $amp_conf, $db;

// Handle legacy actions for backward compatibility
$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';

// Handle specific actions
switch ($action) {
	case 'cdr_play':
	case 'cdr_audio':
		include_once("$action.php");
		exit;
		break;
	case 'download_audio':
		// Handle audio download
		$sql = 'SELECT recordingfile FROM asteriskcdrdb.cdr WHERE uniqueid = ? AND recordingfile IS NOT NULL LIMIT 1';
		$stmt = $db->prepare($sql);
		$stmt->execute(array($_REQUEST["cdr_file"]));
		$file = $stmt->fetch(\PDO::FETCH_ASSOC);
		$file = (string) $file["recordingfile"] ?? null;

		if ($file) {
			$rec_parts = explode('-', $file);
			$fyear = substr($rec_parts[3], 0, 4);
			$fmonth = substr($rec_parts[3], 4, 2);
			$fday = substr($rec_parts[3], 6, 2);
			$monitor_base = $amp_conf['MIXMON_DIR'] ? $amp_conf['MIXMON_DIR'] : $amp_conf['ASTSPOOLDIR'] . '/monitor';
			$file = pathinfo($file, PATHINFO_EXTENSION) == 'wav49' ? pathinfo($file, PATHINFO_FILENAME) . '.WAV' : $file;
			$file = "$monitor_base/$fyear/$fmonth/$fday/" . $file;
			
			if (file_exists($file)) {
				header('Content-Type: application/octet-stream');
				header('Content-Disposition: attachment; filename="' . basename($file) . '"');
				header('Content-Length: ' . filesize($file));
				readfile($file);
			}
		}
		exit;
		break;
	case 'cel_show':
		// Handle CEL display - show call events
		if (isset($amp_conf['CEL_ENABLED']) && $amp_conf['CEL_ENABLED']) {
			$uid = $_REQUEST['uid'] ?? '';
			if ($uid) {
				// Query CEL events for this call
				$sql = 'SELECT * FROM asteriskcdrdb.cel WHERE uniqueid = ? OR linkedid = ? ORDER BY eventtime ASC';
				$stmt = $db->prepare($sql);
				$stmt->execute(array($uid, $uid));
				$cel_events = $stmt->fetchAll(\PDO::FETCH_ASSOC);
				
				// Display CEL events
				echo '<div class="container-fluid">';
				echo '<div class="row"><div class="col-sm-12">';
				echo '<div class="fpbx-container"><div class="display full-border">';
				echo '<h1>' . _('Call Events for') . ' ' . htmlspecialchars($uid) . '</h1>';
				echo '<a href="?display=cdr" class="btn btn-default"><i class="fa fa-arrow-left"></i> ' . _('Back to CDR') . '</a><br><br>';
				
				if (!empty($cel_events)) {
					echo '<table class="table table-striped table-bordered">';
					echo '<thead><tr>';
					echo '<th>' . _('Event Time') . '</th>';
					echo '<th>' . _('Event Type') . '</th>';
					echo '<th>' . _('Caller Name') . '</th>';
					echo '<th>' . _('Caller Number') . '</th>';
					echo '<th>' . _('Extension') . '</th>';
					echo '<th>' . _('Context') . '</th>';
					echo '<th>' . _('Channel') . '</th>';
					echo '<th>' . _('Application') . '</th>';
					echo '<th>' . _('App Data') . '</th>';
					echo '</tr></thead><tbody>';
					
					foreach ($cel_events as $event) {
						echo '<tr>';
						echo '<td>' . htmlspecialchars($event['eventtime']) . '</td>';
						echo '<td><span class="label label-info">' . htmlspecialchars($event['eventtype']) . '</span></td>';
						echo '<td>' . htmlspecialchars($event['cid_name']) . '</td>';
						echo '<td>' . htmlspecialchars($event['cid_num']) . '</td>';
						echo '<td>' . htmlspecialchars($event['exten']) . '</td>';
						echo '<td>' . htmlspecialchars($event['context']) . '</td>';
						echo '<td>' . htmlspecialchars($event['channame']) . '</td>';
						echo '<td>' . htmlspecialchars($event['appname']) . '</td>';
						echo '<td>' . htmlspecialchars($event['appdata']) . '</td>';
						echo '</tr>';
					}
					
					echo '</tbody></table>';
				} else {
					echo '<div class="alert alert-info">' . _('No call events found for this call.') . '</div>';
				}
				
				echo '</div></div></div></div>';
			}
		} else {
			echo '<div class="alert alert-warning">' . _('CEL (Call Event Logging) is not enabled.') . '</div>';
		}
		exit;
		break;
	default:
		break;
}

// Load the modern CDR grid view
echo load_view(__DIR__ . '/views/cdr_grid.php', array(
	'amp_conf' => $amp_conf
));

// assets/js/cdr.js is already auto-included (and cache-busted via
// load_version) by the framework's framework_include_js() for every module
// page, so it does not need to be echoed here.
?>
