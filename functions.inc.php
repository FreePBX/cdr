<?php
//This file is part of FreePBX.
//
//    FreePBX is free software: you can redistribute it and/or modify
//    it under the terms of the GNU General Public License as published by
//    the Free Software Foundation, either version 2 of the License, or
//    (at your option) any later version.
//
//    FreePBX is distributed in the hope that it will be useful,
//    but WITHOUT ANY WARRANTY; without even the implied warranty of
//    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//    GNU General Public License for more details.
//
//    You should have received a copy of the GNU General Public License
//    along with FreePBX.  If not, see <http://www.gnu.org/licenses/>.
//
//    cdr module for FreePBX 2.7+
//    Copyright (C) 2010, 2011 Anthony Joseph Messina
//    Portions Copyright (C) 2011 Igor Okunev
//    Portions Copyright (C) 2011 Mikael Carlsson
//    Portions Copyright (C) 2006 Seth Sargent, Steven Ward

// NOTE: This function should probably be in a FreePBX library
// php function empty() treats 0 as empty, that is why I need the function below
// to be able to search for any number starting with 0
function is_blank($value) {
	return empty($value) && !is_numeric($value);
}

function cdr_formatFiles($uniqueid, $id) {
	global $system_monitor_dir, $system_audio_format, $REC_CRYPT_PASSWORD;
	// If we don't have any uniqueid in the cdr database, set a default of all zeros
	// otherwise we get a false positive for every cdr record.
	if($uniqueid == '') $uniqueid = '0000000000.0000';
	// We only support recordings with uniqueid =  date-time-uniqueid.wav
	$recorded_file = glob($system_monitor_dir . '/*' . $uniqueid . '.' . $system_audio_format);
// TODO: Skip searching for extension, deal with that later.
	if (count($recorded_file)>0) {
		$recorded_file = basename($recorded_file[0],".$system_audio_format");
	} else {
		$recorded_file = $uniqueid;
	}

	if (file_exists("$system_monitor_dir/$recorded_file.$system_audio_format")) {
		$crypt = new Crypt();
		// Encrypt the complete file
		$audio = urlencode($crypt->encrypt($system_monitor_dir."/".$recorded_file.".".$system_audio_format,$REC_CRYPT_PASSWORD));
		$recurl=$_SERVER['PHP_SELF']."?display=cdr&action=cdr_play&recordingpath=$audio";
		$playbackRow = $id +1;
		echo "<td><a href=\"#\" onClick=\"javascript:cdr_play($playbackRow,'$recurl'); return false;\"><img src=\"images/cdr_sound.png\" alt=\"Call recording\" /></a></td>";
	} else {
		echo "<td></td>";
	}
}

/* CDR Table Display Functions */
function cdr_formatCallDate($calldate) {
	echo "<td>".$calldate."</td>";
}

function cdr_formatUniqueID($uniqueid) {
	$system = explode('-', $uniqueid, 2);
	echo "<td>" . fpbx_label($system[0], _("UniqueID") . ": " . $uniqueid) . "</td>";
}

function cdr_formatChannel($channel) {
	$chan_type = explode('/', $channel, 2);
	echo "<td>" . fpbx_label($chan_type[0], _("Channel") . ": " . $channel) . "</td>";
}

function cdr_formatSrc($src, $clid) {
	if (empty($src)) {
		echo "<td class=\"record_col\">UNKNOWN</td>";
	} else {
		$clid = htmlspecialchars($clid);
		echo "<td>" . fpbx_label($src, _("CallerID") . ": " . $clid) . "</td>";
	}
}

function cdr_formatDID($did) {
	echo "<td>" . fpbx_label($did, _("DID") . ": " . $did) . "</td>";
}

function cdr_formatApp($app, $lastdata) {
	echo "<td>" . fpbx_label($app, _("Application") . ": " . $app . "(" . $lastdata . ")") . "</td>";
}

function cdr_formatDst($dst, $dcontext) {
	echo "<td>" . fpbx_label($dst, _("Destination Context") . ": " . $dcontext) . "</td>";
}

function cdr_formatDisposition($disposition, $amaflags) {
	switch ($amaflags) {
		case 0:
			$amaflags = 'DOCUMENTATION';
			break;
		case 1:
			$amaflags = 'IGNORE';
			break;
		case 2:
			$amaflags = 'BILLING';
			break;
		case 3:
		default:
			$amaflags = 'DEFAULT';
	}
	echo "<td>" . fpbx_label($disposition, _("AMA Flag") . ": " . $amaflags) . "</td>";
}

function cdr_formatDuration($duration, $billsec) {
	$duration = sprintf('%02d', intval($duration/60)).':'.sprintf('%02d', intval($duration%60));
	$billduration = sprintf('%02d', intval($billsec/60)).':'.sprintf('%02d', intval($billsec%60));
	echo "<td>" . fpbx_label($duration, _("Billing Duration") . ": " . $billduration) . "</td>";
}

function cdr_formatUserField($userfield) {
	echo "<td>".$userfield."</td>";
}

function cdr_formatAccountCode($accountcode) {
	echo "<td>".$accountcode."</td>";
}

/* Asterisk RegExp parser */
function cdr_asteriskregexp2sqllike( $source_data, $user_num ) {
        $number = $user_num;
        if ( strlen($number) < 1 ) {
                $number = $_POST[$source_data];
        }
        if ( '__' == substr($number,0,2) ) {
                $number = substr($number,1);
        } elseif ( '_' == substr($number,0,1) ) {
                $number_chars = preg_split('//', substr($number,1), -1, PREG_SPLIT_NO_EMPTY);
                $number = '';
                foreach ($number_chars as $chr) {
                        if ( $chr == 'X' ) {
                                $number .= '[0-9]';
                        } elseif ( $chr == 'Z' ) {
                                $number .= '[1-9]';
                        } elseif ( $chr == 'N' ) {
                                $number .= '[2-9]';
                        } elseif ( $chr == '.' ) {
                                $number .= '.+';
                        } elseif ( $chr == '!' ) {
                                $_POST[ $source_data .'_neg' ] = 'true';
                        } else {
                                $number .= $chr;
                        }
                }
                $_POST[ $source_data .'_mod' ] = 'asterisk-regexp';
        }
        return $number;
}

function cdr_download($data, $name) {
    $filesize = strlen($data);
    $mimetype = "application/octet-stream";
	
    // Make sure there's not anything else left
    cdr_ob_clean_all();
    // Start sending headers
    header("Pragma: public"); // required
    header("Expires: 0");
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    header("Cache-Control: private",false); // required for certain browsers
    header("Content-Transfer-Encoding: binary");
    header("Content-Type: " . $mimetype);
    header("Content-Length: " . $filesize);
    header("Content-Disposition: attachment; filename=\"" . $name . "\";" );
    // Send data
    echo $data;
    die();
}

function cdr_export_csv($csvdata) {
	global $db;
	// Searching for more than 10,000 records take more than 30 seconds.
	// php default timeout is 30 seconds, hard code it to 3000 seconds for now (which is WAY overkill).
	// TODO: make this value a setting in Advanced Settings
	set_time_limit(3000);
	$fname		= "cdr__" .  (string) time() . $_SERVER["SERVER_NAME"] . ".csv";
	$csv_header ="calldate,clid,src,dst,dcontext,channel,dstchannel,lastapp,lastdata,duration,billsec,disposition,amaflags,accountcode,uniqueid,userfield\n";
	$data 		= $csv_header;
	
	foreach ($csvdata as $csv) {
		$csv_line[0] 	= $csv['calldate'];
		$csv_line[1] 	= $csv['clid'];
		$csv_line[2] 	= $csv['src'];
		$csv_line[3] 	= $csv['dst'];
		$csv_line[4] 	= $csv['dcontext'];
		$csv_line[5]	= $csv['channel'];
		$csv_line[6] 	= $csv['dstchannel'];
		$csv_line[7] 	= $csv['lastapp'];
		$csv_line[8]	= $csv['lastdata'];
		$csv_line[9]	= $csv['duration'];
		$csv_line[10]	= $csv['billsec'];
		$csv_line[11]	= $csv['disposition'];
		$csv_line[12]	= $csv['amaflags'];
		$csv_line[13]	= $csv['accountcode'];
		$csv_line[14]	= $csv['uniqueid'];
		$csv_line[15]	= $csv['userfield'];

		for ($i = 0; $i < count($csv_line); $i++) {
			/* If the string contains a comma, enclose it in double-quotes. */
			if (strpos($csv_line[$i], ",") !== FALSE) {
				$csv_line[$i] = "\"" . $csv_line[$i] . "\"";
			}
			if ($i != count($csv_line) - 1) {
				$data .= $csv_line[$i] . ",";
			} else {
				$data .= $csv_line[$i];
			}
		}
		$data .= "\n";
		unset($csv_line);
	}
	cdr_download($data, $fname);
	return;
}

function cdr_ob_clean_all () {
    $ob_active = ob_get_length () !== false;
    while($ob_active) {
        ob_end_clean();
        $ob_active = ob_get_length () !== false;
    }
    return true;
}

?>
