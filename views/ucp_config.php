<div class="element-container">
	<div class="row">
		<div class="col-md-12">
			<div class="row">
				<div class="form-group">
					<div class="col-md-3">
						<label class="control-label" for="ucp_cdr"><?php echo _("Allowed CDR")?></label>
						<i class="fa fa-question-circle fpbx-help-icon" data-for="ucp_cdr"></i>
					</div>
					<div class="col-md-9">
						<select data-placeholder="Extensions" id="ucp_cdr" class="form-control chosenmultiselect" name="ucp_cdr[]" multiple="multiple">
							<?php foreach($ausers as $key => $value) {?>
								<option value="<?php echo $key?>" <?php echo in_array($key,$cdrassigned) ? 'selected' : '' ?>><?php echo $value?></option>
							<?php } ?>
						</select>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="row">
		<div class="col-md-12">
			<span id="ucp_cdr-help" class="help-block fpbx-help-block"><?php echo _("These are the assigned and active extensions which will show up for this user to control and edit in UCP")?></span>
		</div>
	</div>
</div>
<div class="element-container">
	<div class="row">
		<div class="col-md-12">
			<div class="row">
				<div class="form-group">
					<div class="col-md-3">
						<label class="control-label" for="cdr_download"><?php echo _("Allow CDR Playback")?></label>
						<i class="fa fa-question-circle fpbx-help-icon" data-for="cdr_download"></i>
					</div>
					<div class="col-md-9">
						<span class="radioset">
							<input type="radio" name="cdr_download" id="cdr_download_yes" value="yes" <?php echo ($download) ? 'checked' : ''?>>
							<label for="cdr_download_yes">Yes</label>
							<input type="radio" name="cdr_download" id="cdr_download_no" value="no" <?php echo !($download) ? 'checked' : ''?>>
							<label for="cdr_download_no">No</label>
						</span>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="row">
		<div class="col-md-12">
			<span id="cdr_download-help" class="help-block fpbx-help-block"><?php echo _("Allow this user to playback recordings in UCP")?></span>
		</div>
	</div>
</div>
<div class="element-container">
	<div class="row">
		<div class="col-md-12">
			<div class="row">
				<div class="form-group">
					<div class="col-md-3">
						<label class="control-label" for="cdr_playback"><?php echo _("Allow CDR Downloads")?></label>
						<i class="fa fa-question-circle fpbx-help-icon" data-for="cdr_playback"></i>
					</div>
					<div class="col-md-9">
						<span class="radioset">
							<input type="radio" name="cdr_playback" id="cdr_playback_yes" value="yes" <?php echo ($playback) ? 'checked' : ''?>>
							<label for="cdr_playback_yes">Yes</label>
							<input type="radio" name="cdr_playback" id="cdr_playback_no" value="no" <?php echo !($playback) ? 'checked' : ''?>>
							<label for="cdr_playback_no">No</label>
						</span>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="row">
		<div class="col-md-12">
			<span id="cdr_playback-help" class="help-block fpbx-help-block"><?php echo _("Allow users to download recordings in UCP")?></span>
		</div>
	</div>
</div>
