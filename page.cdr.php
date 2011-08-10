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
//    Portions Copyright (C) Mikael Carlsson
//
global $amp_conf;
$dispnum = "cdr";
$db_result_limit = 100;	
// Check if cdr database and/or table is set, if not, use our default settings
$db_name = !empty($amp_conf['CDRDBNAME'])?$amp_conf['CDRDBNAME']:"asteriskcdrdb";
$db_table_name = !empty($amp_conf['CDRDBTABLENAME'])?$amp_conf['CDRTABLENAME']:"cdr";
$system_monitor_dir = isset($amp_conf['ASTSPOOLDIR'])?$amp_conf['ASTSPOOLDIR']."/monitor":"/var/spool/asterisk/monitor";

/* $system_fax_archive_dir is the directory where sent/received fax images are stored */
$system_fax_archive_dir = isset($amp_conf['ASTSPOOLDIR'])?$amp_conf['ASTSPOOLDIR']:"/fax-gw/archive";

/* audio file format */
$system_audio_format = 'wav';

$h_step = 30;	

?>

	<div id="maincdr">
	<br><br>
	<table class="cdr">
	<tr><td>
		<form method="post" enctype="application/x-www-form-urlencoded">
		<fieldset>
		<legend class="title"><?php echo _("Call Detail Record Search")?></legend>
			<table width="100%">
			<tr>
				<th><?php echo _("Order By")?></th>
				<th><?php echo _("Search conditions")?></th>
				<th>&nbsp;</th>
			</tr>
			<tr>
				<?php $calldate_tooltip = _("Select time span for your report. You can select Date, Month, Year, Hour and Minute to narrow your search");?>
				<td><input <?php if (empty($_POST['order']) || $_POST['order'] == 'calldate') { echo 'checked="checked"'; } ?> type="radio" name="order" value="calldate" />&nbsp;<?php echo "<a href=\"#\" class=\"info\">"._("Call Date")."<span>".$calldate_tooltip."</span></a>"?>:</td>
				<td><?php echo _("From")?>:
				<input type="text" name="startday" id="startday" size="2" maxlength="2" value="<?php if (isset($_POST['startday'])) { echo htmlspecialchars($_POST['startday']); } else { echo '01'; } ?>" />
				<select name="startmonth" id="startmonth">
<?php
				$months = array('01' => _('January'), '02' => _('February'), '03' => _('March'), '04' => _('April'), '05' => _('May'), '06' => _('June'), '07' => _('July'), '08' => _('August'), '09' => _('September'), '10' => _('October'), '11' => _('November'), '12' => _('December'));
				foreach ($months as $i => $month) {
					if ((is_blank($_POST['startmonth']) && date('m') == $i) || (isset($_POST['startmonth']) && $_POST['startmonth'] == $i)) {
						echo "<option value=\"$i\" selected=\"selected\">$month</option>\n";
					} else {
						echo "<option value=\"$i\">$month</option>\n";
					}
				}
?>
				</select>
				<select name="startyear" id="startyear">
<?php
				for ( $i = 2000; $i <= date('Y'); $i++) {
					if ((empty($_POST['startyear']) && date('Y') == $i) || (isset($_POST['startyear']) && $_POST['startyear'] == $i)) {
						echo "<option value=\"$i\" selected=\"selected\">$i</option>\n";
					} else {
						echo "<option value=\"$i\">$i</option>\n";
					}
				}
?>
				</select>
				<input type="text" name="starthour" id="starthour" size="2" maxlength="2" value="<?php if (isset($_POST['starthour'])) { echo htmlspecialchars($_POST['starthour']); } else { echo '00'; } ?>" />:
				<input type="text" name="startmin" id="startmin" size="2" maxlength="2" value="<?php if (isset($_POST['startmin'])) { echo htmlspecialchars($_POST['startmin']); } else { echo '00'; } ?>" /><?php echo _("To")?>:
				<input type="text" name="endday" id="endday" size="2" maxlength="2" value="<?php if (isset($_POST['endday'])) { echo htmlspecialchars($_POST['endday']); } else { echo '31'; } ?>" />
				<select name="endmonth" id="endmonth">
<?php
				foreach ($months as $i => $month) {
				if ((is_blank($_POST['endmonth']) && date('m') == $i) || (isset($_POST['endmonth']) && $_POST['endmonth'] == $i)) {
	                echo "<option value=\"$i\" selected=\"selected\">$month</option>\n";
					} else {
	               		echo "<option value=\"$i\">$month</option>\n";
	        		}
				}
?>
</select>
<select name="endyear" id="endyear">
<?php
for ( $i = 2000; $i <= date('Y'); $i++) {
        if ((empty($_POST['endyear']) && date('Y') == $i) || (isset($_POST['endyear']) && $_POST['endyear'] == $i)) {
                echo "        <option value=\"$i\" selected=\"selected\">$i</option>\n";
        } else {
                echo "        <option value=\"$i\">$i</option>\n";
        }
}
?>
</select>
	<input type="text" name="endhour" id="endhour" size="2" maxlength="2" value="<?php if (isset($_POST['endhour'])) { echo htmlspecialchars($_POST['endhour']); } else { echo '23'; } ?>" />:
	<input type="text" name="endmin" id="endmin" size="2" maxlength="2" value="<?php if (isset($_POST['endmin'])) { echo htmlspecialchars($_POST['endmin']); } else { echo '59'; } ?>" />
	</td>
<td rowspan="10" valign='top' align='right'>
<fieldset>
<legend class="title"><?php echo _("Extra options")?></legend>
<table>
<tr>
<td><?php echo _("Report type")?> : </td>
<td>
<input <?php if ( (empty($_POST['need_html']) && empty($_POST['need_chart']) && empty($_POST['need_chart_cc']) && empty($_POST['need_csv'])) || ( ! empty($_POST['need_html']) &&  $_POST['need_html'] == 'true' ) ) { echo 'checked="checked"'; } ?> type="checkbox" name="need_html" value="true" /> : <?php echo _("CDR search")?><br />
<input <?php if ( ! empty($_POST['need_csv']) && $_POST['need_csv'] == 'true' ) { echo 'checked="checked"'; } ?> type="checkbox" name="need_csv" value="true" /> : <?php echo _("CSV file")?><br/>
<input <?php if ( ! empty($_POST['need_chart']) && $_POST['need_chart'] == 'true' ) { echo 'checked="checked"'; } ?> type="checkbox" name="need_chart" value="true" /> : <?php echo _("Call Graph")?><br />
<input <?php if ( ! empty($_POST['need_chart_cc']) && $_POST['need_chart_cc'] == 'true' ) { echo 'checked="checked"'; } ?> type="checkbox" name="need_chart_cc" value="true" /> : <?php echo _("Concurrent Calls")?><br />
</td>
</tr>
<tr>
<td><label for="Result limit"><?php echo _("Result limit")?> : </label></td>
<td>
<input value="<?php
if (isset($_POST['limit']) ) {
        echo htmlspecialchars($_POST['limit']);
} else {
        echo $db_result_limit;
} ?>" name="limit" size="6" />
</td>
</tr>
</table>
</fieldset>
</td>
</tr>
<tr>
<?php $channel_tooltip = _("Select Source Channel to search for. You can enter just the channel type like SIP, IAX2 or Local. You can also include the destination for the channel, like SIP/1234.");?>
<td><input <?php if (isset($_POST['order']) && $_POST['order'] == 'channel') { echo 'checked="checked"'; } ?> type="radio" name="order" value="channel" />&nbsp;<label for="channel"><?php echo "<a href=\"#\" class=\"info\">"._("Src Channel")."<span>$channel_tooltip</span></a>"?>:</label></td>
<td><input type="text" name="channel" id="channel" value="<?php if (isset($_POST['channel'])) { echo htmlspecialchars($_POST['channel']); } ?>" />
<?php echo _("Not")?>:<input <?php if ( isset($_POST['channel_neg'] ) && $_POST['channel_neg'] == 'true' ) { echo 'checked="checked"'; } ?> type="checkbox" name="channel_neg" value="true" />
<?php echo _("Begins With")?>:<input <?php if (empty($_POST['channel_mod']) || $_POST['channel_mod'] == 'begins_with') { echo 'checked="checked"'; } ?> type="radio" name="channel_mod" value="begins_with" />
<?php echo _("Contains")?>:<input <?php if (isset($_POST['channel_mod']) && $_POST['channel_mod'] == 'contains') { echo 'checked="checked"'; } ?> type="radio" name="channel_mod" value="contains" />
<?php echo _("Ends With")?>:<input <?php if (isset($_POST['channel_mod']) && $_POST['channel_mod'] == 'ends_with') { echo 'checked="checked"'; } ?> type="radio" name="channel_mod" value="ends_with" />
<?php echo _("Exactly")?>:<input <?php if (isset($_POST['channel_mod']) && $_POST['channel_mod'] == 'exact') { echo 'checked="checked"'; } ?> type="radio" name="channel_mod" value="exact" />
</td>
</tr>
<tr>
<?php $source_tooltip = _("Search for source calls. You can enter multiple sources separated by a comma. This field support Asterisk regular expression. Example<br>");?>
<?php $source_tooltip .= _("<b>_2XXN, _562., _.0075</b> = search for any match of these numbers<br>");?>
<?php $source_tooltip .= _("<b>_!2XXN, _562., _.0075</b> = Search for any match <b>except</b> for these numbers");?>
<?php $source_tooltip .= _("<br>Asterisk pattern matching<br>");?>
<?php $source_tooltip .= _("<b>X</b> = matches any digit from 0-9<br>");?>
<?php $source_tooltip .= _("<b>Z</b> = matches any digit from 1-9<br>");?>
<?php $source_tooltip .= _("<b>N</b> = matches any digit from 2-9<br>");?>
<?php $source_tooltip .= _("<b>[1237-9]</b> = matches any digit or letter in the brackets<br>(in this example, 1,2,3,7,8,9)<br>");?>
<?php $source_tooltip .= _("<b>.</b> = wildcard, matches one or more characters<br>");?>
<td><input <?php if (isset($_POST['order']) && $_POST['order'] == 'src') { echo 'checked="checked"'; } ?> type="radio" name="order" value="src" />&nbsp;<label for="src"><?php echo "<a href=\"#\" class=\"info\">"._("Source")."<span>$source_tooltip</span></a>"?>:</label></td>
<td><input type="text" name="src" id="src" value="<?php if (isset($_POST['src'])) { echo htmlspecialchars($_POST['src']); } ?>" />
<?php echo _("Not")?>:<input <?php if ( isset($_POST['src_neg'] ) && $_POST['src_neg'] == 'true' ) { echo 'checked="checked"'; } ?> type="checkbox" name="src_neg" value="true" />
<?php echo _("Begins With")?>:<input <?php if (empty($_POST['src_mod']) || $_POST['src_mod'] == 'begins_with') { echo 'checked="checked"'; } ?> type="radio" name="src_mod" value="begins_with" />
<?php echo _("Contains")?>:<input <?php if (isset($_POST['src_mod']) && $_POST['src_mod'] == 'contains') { echo 'checked="checked"'; } ?> type="radio" name="src_mod" value="contains" />
<?php echo _("Ends With")?>:<input <?php if (isset($_POST['src_mod']) && $_POST['src_mod'] == 'ends_with') { echo 'checked="checked"'; } ?> type="radio" name="src_mod" value="ends_with" />
<?php echo _("Exactly")?>:<input <?php if (isset($_POST['src_mod']) && $_POST['src_mod'] == 'exact') { echo 'checked="checked"'; } ?> type="radio" name="src_mod" value="exact" />
</td>
</tr>
<tr>
<?php $callerid_tooltip = _("Search for CallerID. If your CallerID begins with \" then you must supply this in your search if you select Begins With.");?>
<td><input <?php if (isset($_POST['order']) && $_POST['order'] == 'clid') { echo 'checked="checked"'; } ?> type="radio" name="order" value="clid" />&nbsp;<label for="clid"><?php echo "<a href=\"#\" class=\"info\">"._("CallerID")."<span>$callerid_tooltip</span></a>"?></label></td>
<td><input type="text" name="clid" id="clid" value="<?php if (isset($_POST['clid'])) { echo htmlspecialchars($_POST['clid']); } ?>" />
<?php echo _("Not")?>:<input <?php if ( isset($_POST['clid_neg'] ) && $_POST['clid_neg'] == 'true' ) { echo 'checked="checked"'; } ?> type="checkbox" name="clid_neg" value="true" />
<?php echo _("Begins With")?>:<input <?php if (empty($_POST['clid_mod']) || $_POST['clid_mod'] == 'begins_with') { echo 'checked="checked"'; } ?> type="radio" name="clid_mod" value="begins_with" />
<?php echo _("Contains")?>:<input <?php if (isset($_POST['clid_mod']) && $_POST['clid_mod'] == 'contains') { echo 'checked="checked"'; } ?> type="radio" name="clid_mod" value="contains" />
<?php echo _("Ends With")?>:<input <?php if (isset($_POST['clid_mod']) && $_POST['clid_mod'] == 'ends_with') { echo 'checked="checked"'; } ?> type="radio" name="clid_mod" value="ends_with" />
<?php echo _("Exactly")?>:<input <?php if (isset($_POST['clid_mod']) && $_POST['clid_mod'] == 'exact') { echo 'checked="checked"'; } ?> type="radio" name="clid_mod" value="exact" />
</td>
</tr>
<tr>
<?php $did_tooltip = _("Search for a DID.");?>
<td><input <?php if (isset($_POST['order']) && $_POST['order'] == 'did') { echo 'checked="checked"'; } ?> type="radio" name="order" value="did" />&nbsp;<label for="did"><?php echo "<a href=\"#\" class=\"info\">"._("DID")."<span>$did_tooltip</span></a>"?></label></td>
<td><input type="text" name="did" id="clid" value="<?php if (isset($_POST['did'])) { echo htmlspecialchars($_POST['did']); } ?>" />
<?php echo _("Not")?>:<input <?php if ( isset($_POST['did_neg'] ) && $_POST['did_neg'] == 'true' ) { echo 'checked="checked"'; } ?> type="checkbox" name="did_neg" value="true" />
<?php echo _("Begins With")?>:<input <?php if (empty($_POST['did_mod']) || $_POST['did_mod'] == 'begins_with') { echo 'checked="checked"'; } ?> type="radio" name="did_mod" value="begins_with" />
<?php echo _("Contains")?>:<input <?php if (isset($_POST['did_mod']) && $_POST['did_mod'] == 'contains') { echo 'checked="checked"'; } ?> type="radio" name="did_mod" value="contains" />
<?php echo _("Ends With")?>:<input <?php if (isset($_POST['did_mod']) && $_POST['did_mod'] == 'ends_with') { echo 'checked="checked"'; } ?> type="radio" name="did_mod" value="ends_with" />
<?php echo _("Exactly")?>:<input <?php if (isset($_POST['did_mod']) && $_POST['did_mod'] == 'exact') { echo 'checked="checked"'; } ?> type="radio" name="did_mod" value="exact" />
</td>
</tr>
<tr>
<?php $dstchannel_tooltip = _("Select Destination Channel to search for. It can be just the channel type like SIP, IAX2 or Local. It can include the destination for the channel, like SIP/1234.");?>
<td><input <?php if (isset($_POST['order']) && $_POST['order'] == 'dstchannel') { echo 'checked="checked"'; } ?> type="radio" name="order" value="dstchannel" />&nbsp;<label for="dstchannel"><?php echo "<a href=\"#\" class=\"info\">"._("Dst Channel")."<span>$dstchannel_tooltip</span></a>"?>:</label></td>
<td><input type="text" name="dstchannel" id="dstchannel" value="<?php if (isset($_POST['dstchannel'])) { echo htmlspecialchars($_POST['dstchannel']); } ?>" />
<?php echo _("Not")?>:<input <?php if ( isset($_POST['dstchannel_neg'] ) && $_POST['dstchannel_neg'] == 'true' ) { echo 'checked="checked"'; } ?> type="checkbox" name="dstchannel_neg" value="true" />
<?php echo _("Begins With")?>:<input <?php if (empty($_POST['dstchannel_mod']) || $_POST['dstchannel_mod'] == 'begins_with') { echo 'checked="checked"'; } ?> type="radio" name="dstchannel_mod" value="begins_with" />
<?php echo _("Contains")?>:<input <?php if (isset($_POST['dstchannel_mod']) && $_POST['dstchannel_mod'] == 'contains') { echo 'checked="checked"'; } ?> type="radio" name="dstchannel_mod" value="contains" />
<?php echo _("Ends With")?>:<input <?php if (isset($_POST['dstchannel_mod']) && $_POST['dstchannel_mod'] == 'ends_with') { echo 'checked="checked"'; } ?> type="radio" name="dstchannel_mod" value="ends_with" />
<?php echo _("Exactly")?>:<input <?php if (isset($_POST['dstchannel_mod']) && $_POST['dstchannel_mod'] == 'exact') { echo 'checked="checked"'; } ?> type="radio" name="dstchannel_mod" value="exact" />
</td>
</tr>
<tr>
<?php $destination_tooltip = _("Search for destination calls. You can enter multiple sources separated by a comma. This field support Asterisk regular expression. Example<br>");?>
<?php $destination_tooltip .= _("<b>_2XXN, _562., _.0075</b> = search for any match of these numbers<br>");?>
<?php $destination_tooltip .= _("<b>_!2XXN, _562., _.0075</b> = Search for any match <b>except</b> for these numbers");?>
<?php $destination_tooltip .= _("<br>Asterisk pattern matching<br>");?>
<?php $destination_tooltip .= _("<b>X</b> = matches any digit from 0-9<br>");?>
<?php $destination_tooltip .= _("<b>Z</b> = matches any digit from 1-9<br>");?>
<?php $destination_tooltip .= _("<b>N</b> = matches any digit from 2-9<br>");?>
<?php $destination_tooltip .= _("<b>[1237-9]</b> = matches any digit or letter in the brackets<br>(in this example, 1,2,3,7,8,9)<br>");?>
<?php $destination_tooltip .= _("<b>.</b> = wildcard, matches one or more characters<br>");?>
<td><input <?php if (isset($_POST['order']) && $_POST['order'] == 'dst') { echo 'checked="checked"'; } ?> type="radio" name="order" value="dst" />&nbsp;<label for="dst"><?php echo "<a href=\"#\" class=\"info\">"._("Destination")."<span>$destination_tooltip</span></a>"?>:</label></td>
<td><input type="text" name="dst" id="dst" value="<?php if (isset($_POST['dst'])) { echo htmlspecialchars($_POST['dst']); } ?>" />
<?php echo _("Not")?>:<input <?php if ( isset($_POST['dst_neg'] ) &&  $_POST['dst_neg'] == 'true' ) { echo 'checked="checked"'; } ?> type="checkbox" name="dst_neg" value="true" />
<?php echo _("Begins With")?>:<input <?php if (empty($_POST['dst_mod']) || $_POST['dst_mod'] == 'begins_with') { echo 'checked="checked"'; } ?> type="radio" name="dst_mod" value="begins_with" />
<?php echo _("Contains")?>:<input <?php if (isset($_POST['dst_mod']) && $_POST['dst_mod'] == 'contains') { echo 'checked="checked"'; } ?> type="radio" name="dst_mod" value="contains" />
<?php echo _("Ends With")?>:<input <?php if (isset($_POST['dst_mod']) && $_POST['dst_mod'] == 'ends_with') { echo 'checked="checked"'; } ?> type="radio" name="dst_mod" value="ends_with" />
<?php echo _("Exactly")?>:<input <?php if (isset($_POST['dst_mod']) && $_POST['dst_mod'] == 'exact') { echo 'checked="checked"'; } ?> type="radio" name="dst_mod" value="exact" />
</td>
</tr>
<tr>
<?php $userfield_tooltip = _("Search for userfield data (if enabled).");?>
<td><input <?php if (isset($_POST['order']) && $_POST['order'] == 'userfield') { echo 'checked="checked"'; } ?> type="radio" name="order" value="userfield" />&nbsp;<label for="userfield"><?php echo "<a href=\"#\" class=\"info\">"._("Userfield")."<span>$userfield_tooltip</span></a>"?>:</label></td>
<td><input type="text" name="userfield" id="userfield" value="<?php if (isset($_POST['userfield'])) { echo htmlspecialchars($_POST['userfield']); } ?>" />
<?php echo _("Not")?>:<input <?php if (  isset($_POST['userfield_neg'] ) && $_POST['userfield_neg'] == 'true' ) { echo 'checked="checked"'; } ?> type="checkbox" name="userfield_neg" value="true" />
<?php echo _("Begins With")?>:<input <?php if (empty($_POST['userfield_mod']) || $_POST['userfield_mod'] == 'begins_with') { echo 'checked="checked"'; } ?> type="radio" name="userfield_mod" value="begins_with" />
<?php echo _("Contains")?>:<input <?php if (isset($_POST['userfield_mod']) && $_POST['userfield_mod'] == 'contains') { echo 'checked="checked"'; } ?> type="radio" name="userfield_mod" value="contains" />
<?php echo _("Ends With")?>:<input <?php if (isset($_POST['userfield_mod']) && $_POST['userfield_mod'] == 'ends_with') { echo 'checked="checked"'; } ?> type="radio" name="userfield_mod" value="ends_with" />
<?php echo _("Exactly")?>:<input <?php if (isset($_POST['userfield_mod']) && $_POST['userfield_mod'] == 'exact') { echo 'checked="checked"'; } ?> type="radio" name="userfield_mod" value="exact" />
</td>
</tr>
<tr>
<?php $accountcode_tooltip = _("Search for accountcode.");?>
<td><input <?php if (isset($_POST['order']) && $_POST['order'] == 'accountcode') { echo 'checked="checked"'; } ?> type="radio" name="order" value="accountcode" />&nbsp;<label for="userfield"><?php echo "<a href=\"#\" class=\"info\">"._("Account Code")."<span>$accountcode_tooltip</span></a>"?>:</label></td>
<td><input type="text" name="accountcode" id="accountcode" value="<?php if (isset($_POST['accountcode'])) { echo htmlspecialchars($_POST['accountcode']); } ?>" />
<?php echo _("Not")?>:<input <?php if ( isset($_POST['accountcode_neg'] ) &&  $_POST['accountcode_neg'] == 'true' ) { echo 'checked="checked"'; } ?> type="checkbox" name="accountcode_neg" value="true" />
<?php echo _("Begins With")?>:<input <?php if (empty($_POST['accountcode_mod']) || $_POST['accountcode_mod'] == 'begins_with') { echo 'checked="checked"'; } ?> type="radio" name="accountcode_mod" value="begins_with" />
<?php echo _("Contains")?>:<input <?php if (isset($_POST['accountcode_mod']) && $_POST['accountcode_mod'] == 'contains') { echo 'checked="checked"'; } ?> type="radio" name="accountcode_mod" value="contains" />
<?php echo _("Ends With")?>:<input <?php if (isset($_POST['accountcode_mod']) && $_POST['accountcode_mod'] == 'ends_with') { echo 'checked="checked"'; } ?> type="radio" name="accountcode_mod" value="ends_with" />
<?php echo _("Exactly")?>:<input <?php if (isset($_POST['accountcode_mod']) && $_POST['accountcode_mod'] == 'exact') { echo 'checked="checked"'; } ?> type="radio" name="accountcode_mod" value="exact" />
</td>
</tr>
<tr>
<?php $duration_tooltip = _("Search for calls that matches the call length specified.");?>
<td><input <?php if (isset($_POST['order']) && $_POST['order'] == 'duration') { echo 'checked="checked"'; } ?> type="radio" name="order" value="duration" />&nbsp;<label><?php echo "<a href=\"#\" class=\"info\">"._("Duration")."<span>$duration_tooltip</span></a>"?>:</label></td>
<td><?php echo _("Between")?>:
<input type="text" name="dur_min" value="<?php if (isset($_POST['dur_min'])) { echo htmlspecialchars($_POST['dur_min']); } ?>" size="3" maxlength="5" />
<?php echo _("And")?>:
<input type="text" name="dur_max" value="<?php if (isset($_POST['dur_max'])) { echo htmlspecialchars($_POST['dur_max']); } ?>" size="3" maxlength="5" />
<?php echo _("Seconds")?>
</td>
</tr>
<tr>
<?php $disposition_tooltip = _("Search for calls that matches either ANSWERED, BUSY, FAILED or NO ANSWER.");?>
<td><input <?php if (isset($_POST['order']) && $_POST['order'] == 'disposition') { echo 'checked="checked"'; } ?> type="radio" name="order" value="disposition" />&nbsp;<label for="disposition"><?php echo "<a href=\"#\" class=\"info\">"._("Disposition")."<span>$disposition_tooltip</span></a>"?>:</label></td>
<td>
<?php echo _("Not")?>:<input <?php if ( isset($_POST['dispositio_neg'] ) && $_POST['disposition_neg'] == 'true' ) { echo 'checked="checked"'; } ?> type="checkbox" name="disposition_neg" value="true" />
<select name="disposition" id="disposition">
<option <?php if (empty($_POST['disposition']) || $_POST['disposition'] == 'all') { echo 'selected="selected"'; } ?> value="all"><?php echo _("All Dispositions")?></option>
<option <?php if (isset($_POST['disposition']) && $_POST['disposition'] == 'ANSWERED') { echo 'selected="selected"'; } ?> value="ANSWERED"><?php echo _("Answered")?></option>
<option <?php if (isset($_POST['disposition']) && $_POST['disposition'] == 'BUSY') { echo 'selected="selected"'; } ?> value="BUSY"><?php echo _("Busy")?></option>
<option <?php if (isset($_POST['disposition']) && $_POST['disposition'] == 'FAILED') { echo 'selected="selected"'; } ?> value="FAILED"><?php echo _("Failed")?></option>
<option <?php if (isset($_POST['disposition']) && $_POST['disposition'] == 'NO ANSWER') { echo 'selected="selected"'; } ?> value="NO ANSWER"><?php echo _("No Answer")?></option>
</select>
</td>
</tr>
<tr>
<td>
<select name="sort" id="sort">
<option <?php if (isset($_POST['sort']) && $_POST['sort'] == 'ASC') { echo 'selected="selected"'; } ?> value="ASC"><?php echo _("Ascending")?></option>
<option <?php if (empty($_POST['sort']) || $_POST['sort'] == 'DESC') { echo 'selected="selected"'; } ?> value="DESC"><?php echo _("Descending")?></option>
</select>
</td>
<td><table width="100%"><tr><td>
<label for="group"><?php echo _("Group By")?>:</label>
<select name="group" id="group">
<optgroup label="<?php echo _("Account Information")?>">
<option <?php if (isset($_POST['group']) && $_POST['group'] == 'accountcode') { echo 'selected="selected"'; } ?> value="accountcode"><?php echo _("Account Code")?></option>
<option <?php if (isset($_POST['group']) && $_POST['group'] == 'userfield') { echo 'selected="selected"'; } ?> value="userfield"><?php echo _("User Field")?></option>
</optgroup>
<optgroup label="<?php echo _("Date/Time")?>">
<option <?php if (isset($_POST['group']) && $_POST['group'] == 'minutes1') { echo 'selected="selected"'; } ?> value="minutes1"><?php echo _("Minute")?></option>
<option <?php if (isset($_POST['group']) && $_POST['group'] == 'minutes10') { echo 'selected="selected"'; } ?> value="minutes10"><?php echo _("10 Minutes")?></option>
<option <?php if (isset($_POST['group']) && $_POST['group'] == 'hour') { echo 'selected="selected"'; } ?> value="hour"><?php echo _("Hour")?></option>
<option <?php if (isset($_POST['group']) && $_POST['group'] == 'hour_of_day') { echo 'selected="selected"'; } ?> value="hour_of_day"><?php echo _("Hour of day")?></option>
<option <?php if (isset($_POST['group']) && $_POST['group'] == 'day_of_week') { echo 'selected="selected"'; } ?> value="day_of_week"><?php echo _("Day of week")?></option>
<option <?php if (empty($_POST['group']) || $_POST['group'] == 'day') { echo 'selected="selected"'; } ?> value="day"><?php echo _("Day")?></option>
<option <?php if (isset($_POST['group']) && $_POST['group'] == 'week') { echo 'selected="selected"'; } ?> value="week"><?php echo _("Week ( Sun-Sat )")?></option>
<option <?php if (isset($_POST['group']) && $_POST['group'] == 'month') { echo 'selected="selected"'; } ?> value="month"><?php echo _("Month")?></option>
</optgroup>
<optgroup label="<?php echo _("Telephone Number")?>">
<option <?php if (isset($_POST['group']) && $_POST['group'] == 'src') { echo 'selected="selected"'; } ?> value="src"><?php echo _("Source Number")?></option>
<option <?php if (isset($_POST['group']) && $_POST['group'] == 'dst') { echo 'selected="selected"'; } ?> value="dst"><?php echo _("Destination Number")?></option>
</optgroup>
</select></td><td align="left" width="40%">
<input type="submit" value="<?php echo _("Search")?>" />
</td></td></table>
</td>
</tr>
</table>
</fieldset>
</form>
</td>
</tr>
</table>
<a id="CDR"></a>

<?php
foreach ( array_keys($_POST) as $key ) {
	$_POST[$key] = preg_replace('/;/', ' ', $_POST[$key]);
	$_POST[$key] = mysql_real_escape_string($_POST[$key]);
}

$startmonth = is_blank($_POST['startmonth']) ? date('m') : $_POST['startmonth'];
$startyear = empty($_POST['startyear']) ? date('Y') : $_POST['startyear'];

if (is_blank($_POST['startday'])) {
	$startday = '01';
} elseif (isset($_POST['startday']) && ($_POST['startday'] > date('t', strtotime("$startyear-$startmonth")))) {
	$startday = $_POST['startday'] = date('t', strtotime("$startyear-$startmonth"));
} else {
	$startday = sprintf('%02d',$_POST['startday']);
}
$starthour = is_blank($_POST['starthour']) ? '00' : sprintf('%02d',$_POST['starthour']);
$startmin = is_blank($_POST['startmin']) ? '00' : sprintf('%02d',$_POST['startmin']);

$startdate = "'$startyear-$startmonth-$startday $starthour:$startmin:00'";
$start_timestamp = mktime( $starthour, $startmin, 59, $startmonth, $startday, $startyear );

$endmonth = is_blank($_POST['endmonth']) ? date('m') : $_POST['endmonth'];  
$endyear = empty($_POST['endyear']) ? date('Y') : $_POST['endyear'];  

if (is_blank($_POST['endday']) || (isset($_POST['endday']) && ($_POST['endday'] > date('t', strtotime("$endyear-$endmonth-01"))))) {
	$endday = $_POST['endday'] = date('t', strtotime("$endyear-$endmonth"));
} else {
	$endday = sprintf('%02d',$_POST['endday']);
}
$endhour = is_blank($_POST['endhour']) ? '23' : sprintf('%02d',$_POST['endhour']);
$endmin = is_blank($_POST['endmin']) ? '59' : sprintf('%02d',$_POST['endmin']);

$enddate = "'$endyear-$endmonth-$endday $endhour:$endmin:59'";
$end_timestamp = mktime( $endhour, $endmin, 59, $endmonth, $endday, $endyear );

#
# asterisk regexp2sqllike
#
if ( is_blank($_POST['src']) ) {
	$src_number = NULL;
} else {
	$src_number = cdr_asteriskregexp2sqllike( 'src', '' );
}

if ( is_blank($_POST['dst']) ) {
	$dst_number = NULL;
} else {
	$dst_number = cdr_asteriskregexp2sqllike( 'dst', '' );
}

$date_range = "calldate BETWEEN $startdate AND $enddate";
$mod_vars['channel'][] = is_blank($_POST['channel']) ? NULL : $_POST['channel'];
$mod_vars['channel'][] = empty($_POST['channel_mod']) ? NULL : $_POST['channel_mod'];
$mod_vars['channel'][] = empty($_POST['channel_neg']) ? NULL : $_POST['channel_neg'];
$mod_vars['src'][] = $src_number;
$mod_vars['src'][] = empty($_POST['src_mod']) ? NULL : $_POST['src_mod'];
$mod_vars['src'][] = empty($_POST['src_neg']) ? NULL : $_POST['src_neg'];
$mod_vars['clid'][] = is_blank($_POST['clid']) ? NULL : $_POST['clid'];
$mod_vars['clid'][] = empty($_POST['clid_mod']) ? NULL : $_POST['clid_mod'];
$mod_vars['clid'][] = empty($_POST['clid_neg']) ? NULL : $_POST['clid_neg'];
$mod_vars['did'][] = is_blank($_POST['did']) ? NULL : $_POST['did'];
$mod_vars['did'][] = empty($_POST['did_mod']) ? NULL : $_POST['did_mod'];
$mod_vars['did'][] = empty($_POST['did_neg']) ? NULL : $_POST['did_neg'];
$mod_vars['dstchannel'][] = is_blank($_POST['dstchannel']) ? NULL : $_POST['dstchannel'];
$mod_vars['dstchannel'][] = empty($_POST['dstchannel_mod']) ? NULL : $_POST['dstchannel_mod'];
$mod_vars['dstchannel'][] = empty($_POST['dstchannel_neg']) ? NULL : $_POST['dstchannel_neg'];
$mod_vars['dst'][] = $dst_number;
$mod_vars['dst'][] = empty($_POST['dst_mod']) ? NULL : $_POST['dst_mod'];
$mod_vars['dst'][] = empty($_POST['dst_neg']) ? NULL : $_POST['dst_neg'];
$mod_vars['userfield'][] = is_blank($_POST['userfield']) ? NULL : $_POST['userfield'];
$mod_vars['userfield'][] = empty($_POST['userfield_mod']) ? NULL : $_POST['userfield_mod'];
$mod_vars['userfield'][] = empty($_POST['userfield_neg']) ? NULL : $_POST['userfield_neg'];
$mod_vars['accountcode'][] = is_blank($_POST['accountcode']) ? NULL : $_POST['accountcode'];
$mod_vars['accountcode'][] = empty($_POST['accountcode_mod']) ? NULL : $_POST['accountcode_mod'];
$mod_vars['accountcode'][] = empty($_POST['accountcode_neg']) ? NULL : $_POST['accountcode_neg'];
$result_limit = is_blank($_POST['limit']) ? $db_result_limit : $_POST['limit'];

foreach ($mod_vars as $key => $val) {
	if (is_blank($val[0])) {
		unset($_POST[$key.'_mod']);
		$$key = NULL;
	} else {
		$pre_like = '';
		if ( $val[2] == 'true' ) {
			$pre_like = ' NOT ';
		}
		switch ($val[1]) {
			case "contains":
				$$key = "AND $key $pre_like LIKE '%$val[0]%'";
			break;
			case "ends_with":
				$$key = "AND $key $pre_like LIKE '%$val[0]'";
			break;
			case "exact":
				if ( $val[2] == 'true' ) {
					$$key = "AND $key != '$val[0]'";
				} else {
					$$key = "AND $key = '$val[0]'";
				}
			break;
			case "asterisk-regexp":
				$ast_dids = preg_split('/\s*,\s*/', $val[0], -1, PREG_SPLIT_NO_EMPTY);
				$ast_key = '';
				foreach ($ast_dids as $did) {
					if (strlen($ast_key) > 0 ) {
						if ( $pre_like == ' NOT ' ) {
							$ast_key .= " and ";
						} else {
							$ast_key .= " or ";
						}
						if ( '_' == substr($did,0,1) ) {
							$did = substr($did,1);
						}
					}
					$ast_key .= " $key $pre_like RLIKE '^$did\$'";
				}
				$$key = "AND ( $ast_key )";
			break;
			case "begins_with":
			default:
				$$key = "AND $key $pre_like LIKE '$val[0]%'";
		}
	}
}

if ( isset($_POST['disposition_neg']) && $_POST['disposition_neg'] == 'true' ) {
	$disposition = (empty($_POST['disposition']) || $_POST['disposition'] == 'all') ? NULL : "AND disposition != '$_POST[disposition]'";
} else {
	$disposition = (empty($_POST['disposition']) || $_POST['disposition'] == 'all') ? NULL : "AND disposition = '$_POST[disposition]'";
}

$duration = (!isset($_POST['dur_min']) || is_blank($_POST['dur_max'])) ? NULL : "AND duration BETWEEN '$_POST[dur_min]' AND '$_POST[dur_max]'";
$order = empty($_POST['order']) ? 'ORDER BY calldate' : "ORDER BY $_POST[order]";
$sort = empty($_POST['sort']) ? 'DESC' : $_POST['sort'];
$group = empty($_POST['group']) ? 'day' : $_POST['group'];

// Build the "WHERE" part of the query
$where = "WHERE $date_range $channel $dstchannel $src $clid $did $dst $userfield $accountcode $disposition $duration";

if ( isset($_POST['need_csv']) && $_POST['need_csv'] == 'true' ) {
	$query = "(SELECT calldate, clid, did, src, dst, dcontext, channel, dstchannel, lastapp, lastdata, duration, billsec, disposition, amaflags, accountcode, uniqueid, userfield FROM $db_name.$db_table_name $where $order $sort LIMIT $result_limit)";
	$resultcsv = $db->getAll($query, DB_FETCHMODE_ASSOC);
	cdr_export_csv($resultcsv);	
}

if ( isset($_POST['need_html']) && $_POST['need_html'] == 'true' ) {
	$query = "SELECT `calldate`, `clid`, `did`, `src`, `dst`, `dcontext`, `channel`, `dstchannel`, `lastapp`, `lastdata`, `duration`, `billsec`, `disposition`, `amaflags`, `accountcode`, `uniqueid`, `userfield`, unix_timestamp(calldate) as `call_timestamp` FROM $db_name.$db_table_name $where $order $sort LIMIT $result_limit";
	$results = $db->getAll($query, DB_FETCHMODE_ASSOC);
}
if ( isset($results) ) {
	$tot_calls_raw = sizeof($results);
} else {
	$tot_calls_raw = 0;
}
if ( $tot_calls_raw ) {
	echo "<p class=\"center title\">"._("Call Detail Record - Search Returned")." ".$tot_calls_raw." "._("Calls")."</p><table class=\"cdr\">";
	
	$i = $h_step - 1;
	foreach($results as $row) {
		++$i;
		if ($i == $h_step) {
		?>
			<tr>
			<th class="record_col"><?php echo _("Call Date")?></th>
			<th class="record_col"><?php echo _("File")?></th>
			<th class="record_col"><?php echo _("System")?></th>
			<th class="record_col"><?php echo _("Src Chan.")?></th>
			<th class="record_col"><?php echo _("Source")?></th>
			<th class="record_col"><?php echo _("DID")?></th>
			<th class="record_col"><?php echo _("App")?></th>
			<th class="record_col"><?php echo _("Dest.")?></th>
			<th class="record_col"><?php echo _("Dst. Chan.")?></th>
			<th class="record_col"><?php echo _("Disposition")?></th>
			<th class="record_col"><?php echo _("Duration")?></th>
			<th class="record_col"><?php echo _("Userfield")?></th>
			<th class="record_col"><?php echo _("Account")?></th>
			<th class="img_col"><a href="#CDR" title="Go to the top of the CDR table"><img src="images/scrollup.gif" alt="CDR Table" /></a></th>
			<th class="img_col"><a href="#Graph" title="Go to the top of the CDR graph"><img src="images/scrolldown.gif" alt="CDR Graph" /></a></th>
			</tr>
			<?php
			$i = 0;
		}
		echo "  <tr class=\"record\">\n";
		cdr_formatCallDate($row['calldate']);
		cdr_formatFiles($row);
		cdr_formatUniqueID($row['uniqueid']);
		cdr_formatChannel($row['channel']);
		cdr_formatSrc($row['src'], $row['clid']);
		cdr_formatDID($row['did']);
		cdr_formatApp($row['lastapp'], $row['lastdata']);
		cdr_formatDst($row['dst'], $row['dcontext']);
		cdr_formatChannel($row['dstchannel']);
		cdr_formatDisposition($row['disposition'], $row['amaflags']);
		cdr_formatDuration($row['duration'], $row['billsec']);
		cdr_formatUserField($row['userfield']);
		cdr_formatAccountCode($row['accountcode']);
		echo "    <td></td>\n";
		echo "    <td></td>\n";
		echo "  </tr>\n";
	} 
	echo "</table>";
}
?>

<!-- Display Call Usage Graph -->
<?php

echo '<a id="Graph"></a>';

//NEW GRAPHS
$group_by_field = $group;
// ConcurrentCalls
$group_by_field_php = array( '', 32, '' );

switch ($group) {
	case "accountcode":
		$graph_col_title = _("Account Code");
	break;
	case "dst":
		$graph_col_title = _("Destination Number");
	break;
	case "src":
		$graph_col_title = _("Source Number");
	break;
	case "userfield":
		$graph_col_title = _("User Field");
	break;
	case "hour":
		$group_by_field_php = array( '%Y-%m-%d %H', 13, '' );
		$group_by_field = "DATE_FORMAT(calldate, '$group_by_field_php[0]')";
		$graph_col_title = _("Hour");
	break;
	case "hour_of_day":
		$group_by_field_php = array('%H',2,'');
		$group_by_field = "DATE_FORMAT(calldate, '$group_by_field_php[0]')";
		$graph_col_title = _("Hour of day");
	break;
	case "week":
		$group_by_field_php = array('%V',2,'');
		$group_by_field = "DATE_FORMAT(calldate, '$group_by_field_php[0]') ";
		$graph_col_title = _("Week ( Sun-Sat )");
	break;
	case "month":
		$group_by_field_php = array('%Y-%m',7,'');
		$group_by_field = "DATE_FORMAT(calldate, '$group_by_field_php[0]')";
		$graph_col_title = _("Month");
	break;
	case "day_of_week":
		$group_by_field_php = array('%w - %A',20,'');
		$group_by_field = "DATE_FORMAT( calldate, '%W' )";
		$graph_col_title = _("Day of week");
	break;
	case "minutes1":
		$group_by_field_php = array( '%Y-%m-%d %H:%M', 16, '' );
		$group_by_field = "DATE_FORMAT(calldate, '%Y-%m-%d %H:%i')";
		$graph_col_title = _("Minute");
	break;
	case "minutes10":
		$group_by_field_php = array('%Y-%m-%d %H:%M',15,'0');
		$group_by_field = "CONCAT(SUBSTR(DATE_FORMAT(calldate, '%Y-%m-%d %H:%i'),1,15), '0')";
		$graph_col_title = _("10 Minutes");
	break;
	case "day":
	default:
		$group_by_field_php = array('%Y-%m-%d',10,'');
		$group_by_field = "DATE_FORMAT(calldate, '$group_by_field_php[0]')";
		$graph_col_title = _("Day");
}

if ( isset($_POST['need_chart']) && $_POST['need_chart'] == 'true' ) {
	$query2 = "SELECT $group_by_field AS group_by_field, count(*) AS total_calls, sum(duration) AS total_duration FROM $db_name.$db_table_name $where GROUP BY group_by_field ORDER BY group_by_field ASC LIMIT $result_limit";
	$result2 = $db->getAll($query2, DB_FETCHMODE_ASSOC);

	$tot_calls = 0;
	$tot_duration = 0;
	$max_calls = 0;
	$max_duration = 0;
	$tot_duration_secs = 0;
	$result_array = array();
	foreach($result2 as $row) {
		$tot_duration_secs += $row['total_duration'];
		$tot_calls += $row['total_calls'];
		if ( $row['total_calls'] > $max_calls ) {
			$max_calls = $row['total_calls'];
		}
		if ( $row['total_duration'] > $max_duration ) {
			$max_duration = $row['total_duration'];
		}
		array_push($result_array,$row);
	}
	$tot_duration = sprintf('%02d', intval($tot_duration_secs/60)).':'.sprintf('%02d', intval($tot_duration_secs%60));

	if ( $tot_calls ) {
		$html = "<p class=\"center title\">"._("Call Detail Record - Call Graph by")." ".$graph_col_title."</p><table class=\"cdr\">";
		$html .= "<tr><th class=\"end_col\">". $graph_col_title . "</th>";
		$html .= "<th class=\"center_col\">"._("Total Calls").": ". $tot_calls ." / "._("Max Calls").": ". $max_calls ." / "._("Total Duration").": ". $tot_duration ."</th>";
		$html .= "<th class=\"end_col\">"._("Average Call Time")."</th>";
		$html .= "<th class=\"img_col\"><a href=\"#CDR\" title=\""._("Go to the top of the CDR table")."\"><img src=\"images/scrollup.gif\" alt=\"CDR Table\" /></a></th>";
		$html .= "<th class=\"img_col\"><a href=\"#Graph\" title=\""._("Go to the CDR Graph")."\"><img src=\"images/scrolldown.gif\" alt=\"CDR Graph\" /></a></th>";
		$html .= "</tr>";
		echo $html;
	
		foreach ($result_array as $row) {
			$avg_call_time = sprintf('%02d', intval(($row['total_duration']/$row['total_calls'])/60)).':'.sprintf('%02d', intval($row['total_duration']/$row['total_calls']%60));
			$bar_calls = $row['total_calls']/$max_calls*100;
			$percent_tot_calls = intval($row['total_calls']/$tot_calls*100);
			$bar_duration = $row['total_duration']/$max_duration*100;
			$percent_tot_duration = intval($row['total_duration']/$tot_duration_secs*100);
			$html_duration = sprintf('%02d', intval($row['total_duration']/60)).':'.sprintf('%02d', intval($row['total_duration']%60));
			echo "  <tr>\n";
			echo "    <td class=\"end_col\">".$row['group_by_field']."</td><td class=\"center_col\"><div class=\"bar_calls\" style=\"width : $bar_calls%\">".$row['total_calls']." - $percent_tot_calls%</div><div class=\"bar_duration\" style=\"width : $bar_duration%\">$html_duration - $percent_tot_duration%</div></td><td class=\"chart_data\">$avg_call_time</td>\n";
			echo "    <td></td>\n";
			echo "    <td></td>\n";
			echo "  </tr>\n";
		}
		echo "</table>";
	}
}
if ( isset($_POST['need_chart_cc']) && $_POST['need_chart_cc'] == 'true' ) {
	$date_range = "( (calldate BETWEEN $startdate AND $enddate) or (calldate + interval duration second  BETWEEN $startdate AND $enddate) or ( calldate + interval duration second >= $enddate AND calldate <= $startdate ) )";
	$where = "WHERE $date_range $channel $dstchannel $src $clid $dst $userfield $accountcode $disposition $duration";
	
	$tot_calls = 0;
	$max_calls = 0;
	$result_array_cc = array();
	$result_array = array();
	if ( strpos($group_by_field,'DATE_FORMAT') === false ) {
		/* not date time fields */
		$query3 = "SELECT $group_by_field AS group_by_field, count(*) AS total_calls, unix_timestamp(calldate) AS ts, duration FROM $db_name.$db_table_name $where GROUP BY group_by_field, unix_timestamp(calldate) ORDER BY group_by_field ASC LIMIT $result_limit";
		$result3 = $db->getAll($query3, DB_FETCHMODE_ASSOC);
		$group_by_str = '';
		foreach($result3 as $row) {
			if ( $group_by_str != $row['group_by_field'] ) {
				$group_by_str = $row['group_by_field'];
				$result_array = array();
			}
			for ( $i=$row['ts']; $i<=$row['ts']+$row['duration']; ++$i ) {
				if ( isset($result_array[ "$i" ]) ) {
					$result_array[ "$i" ] += $row['total_calls'];
				} else {
					$result_array[ "$i" ] = $row['total_calls'];
				}
				if ( $max_calls < $result_array[ "$i" ] ) {
					$max_calls = $result_array[ "$i" ];
				}
				if ( ! isset($result_array_cc[ $row['group_by_field'] ]) || $result_array_cc[ $row['group_by_field'] ][1] < $result_array[ "$i" ] ) {
					$result_array_cc[$row['group_by_field']][0] = $i;
					$result_array_cc[$row['group_by_field']][1] = $result_array[ "$i" ];
				}
			}
			$tot_calls += $row['total_calls'];
		}
	} else {
		/* data fields */
		$query3 = "SELECT unix_timestamp(calldate) AS ts, duration FROM $db_name.$db_table_name $where ORDER BY unix_timestamp(calldate) ASC LIMIT $result_limit";
		$result3 = $db->getAll($query3, DB_FETCHMODE_ASSOC);
		$group_by_str = '';
		foreach($result3 as $row) {
			$group_by_str_cur = substr(strftime($group_by_field_php[0],$row['ts']),0,$group_by_field_php[1]) . $group_by_field_php[2];
			if ( $group_by_str_cur != $group_by_str ) {
				if ( $group_by_str ) {
					for ( $i=$start_timestamp; $i<$row['ts']; ++$i ) {
						if ( ! isset($result_array_cc[ "$group_by_str" ]) || ( isset($result_array["$i"]) && $result_array_cc[ "$group_by_str" ][1] < $result_array["$i"] ) ) {
							$result_array_cc[ "$group_by_str" ][0] = $i;
							$result_array_cc[ "$group_by_str" ][1] = isset($result_array["$i"]) ? $result_array["$i"] : 0;
						}
						unset( $result_array[$i] );
					}
					$start_timestamp = $row['ts'];
				}
				$group_by_str = $group_by_str_cur;
			}
			for ( $i=$row['ts']; $i<=$row['ts']+$row['duration']; ++$i ) {
				if ( isset($result_array["$i"]) ) {
					++$result_array["$i"];
				} else {
					$result_array["$i"]=1;
				}
				if ( $max_calls < $result_array["$i"] ) {
					$max_calls = $result_array["$i"];
				}
			}
			$tot_calls++;
		}
		for ( $i=$start_timestamp; $i<=$end_timestamp; ++$i ) {
			$group_by_str = substr(strftime($group_by_field_php[0],$i),0,$group_by_field_php[1]) . $group_by_field_php[2];
			if ( ! isset($result_array_cc[ "$group_by_str" ]) || ( isset($result_array["$i"]) && $result_array_cc[ "$group_by_str" ][1] < $result_array["$i"] ) ) {
				$result_array_cc[ "$group_by_str" ][0] = $i;
				$result_array_cc[ "$group_by_str" ][1] = isset($result_array["$i"]) ? $result_array["$i"] : 0;
			}
		}
	}
	if ( $tot_calls ) {
		$html = "<p class=\"center title\">"._("Call Detail Record - Concurrent Calls by")." ".$graph_col_title."</p><table class=\"cdr\">";
		$html .= "<tr><th class=\"end_col\">". $graph_col_title . "</th>";
		$html .= "<th class=\"center_col\">"._("Total Calls").": ". $tot_calls ." / "._("Max Calls").": ". $max_calls ."</th>";
		$html .= "<th class=\"end_col\">"._("Time")."</th>";
		$html .= "</tr>";
		echo $html;
	
		ksort($result_array_cc);

		foreach ( array_keys($result_array_cc) as $group_by_key ) {
			$full_time = strftime( '%Y-%m-%d %H:%M:%S', $result_array_cc[ "$group_by_key" ][0] );
			$group_by_cur = $result_array_cc[ "$group_by_key" ][1];
			$bar_calls = $group_by_cur/$max_calls*100;
			echo "  <tr>\n";
			echo "    <td class=\"end_col\">$group_by_key</td><td class=\"center_col\"><div class=\"bar_calls\" style=\"width : $bar_calls%\">&nbsp;$group_by_cur</div></td><td>$full_time</td>\n";
			echo "  </tr>\n";
		}

		echo "</table>";
	}
}
?>

</div>
<div id="footercdr">
<p><small>
        <a href="http://code.google.com/p/asterisk-cdr-viewer/" target="_blank">FreePBX CDR viewer, based on Asterisk CDR Viewer</a>
    <!-- by Igor Okunev ( igor.okunev [at] gmail.com ) -->
</small></p>
</div>
