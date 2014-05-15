<?php
if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }

include_once("crypt.php");
$crypt = new Crypt();

$REC_CRYPT_PASSWORD = (isset($amp_conf['AMPPLAYKEY']) && trim($amp_conf['AMPPLAYKEY']) != "")?trim($amp_conf['AMPPLAYKEY']):'TheWindCriesMary';
$path = $crypt->decrypt($_REQUEST['recordingpath'],$REC_CRYPT_PASSWORD);
if(!empty($path)) {
	// Gather relevent info about file
	$size = filesize($path);
	$name = basename($path);
	$extension = strtolower(substr(strrchr($name,"."),1));
	// This will set the Content-Type to the appropriate setting for the file
	$ctype ='';
	switch( $extension ) {
		case "WAV":
		case "wav":
			$ctype="audio/x-wav";
			break;
		case "ulaw":
			$ctype="audio/basic";
			break;
		case "alaw":
			$ctype="audio/x-alaw-basic";
			break;
		case "sln":
			$ctype="audio/x-wav";
			break;
		case "gsm":
			$ctype="audio/x-gsm";
			break;
		case "g729":
			$ctype="audio/x-g729";
			break;
		default: //not downloadable
			// echo ("<b>404 File not found! foo</b>");
			// TODO: what to do if none of the above work?
		break ;
	}

	$length = $size;           // Content length
	$start  = 0;               // Start byte
	$end    = $size - 1;       // End byte
	header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
	header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
	header('Content-Description: File Transfer');
	header("Content-Transfer-Encoding: binary");
	header('Content-Type: '.$ctype);
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
	header('Content-Disposition: attachment;filename="' . $name.'"');
	$buffer = 1024 * 8;
	$wstart = $start;
	ob_end_clean();
	ob_start();
	set_time_limit(0);
	while(true) {
		$fp = fopen($path, "rb");
		fseek($fp, $wstart);
		if(!feof($fp) && ($p = ftell($fp)) <= $end) {
			if ($p + $buffer > $end) {
				$buffer = $end - $p + 1;
			}
			$contents = fread($fp, $buffer);
			fclose($fp);
			echo $contents;
			ob_flush();
			flush();
		} else {
			break;
		}
		$wstart = $wstart + $buffer;
	}
}
