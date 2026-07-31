<!-- Include required CSS and JS for date range picker -->
<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
<script type="text/javascript" src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>

<!-- Bootstrap Table Extensions -->
<script src="https://unpkg.com/bootstrap-table@1.21.4/dist/extensions/export/bootstrap-table-export.min.js"></script>
<script src="https://unpkg.com/tableexport.jquery.plugin/tableExport.min.js"></script>

<!-- CanvasJS for Charts -->
<script src="<?php echo $amp_conf['AMPWEBROOT']; ?>/admin/modules/dashboard/assets/js/canvasjs.js"></script>

<!-- Custom CDR Styles -->
<link rel="stylesheet" type="text/css" href="modules/cdr/assets/css/cdr-custom.css" />

<div class="container-fluid">
	<div class="row">
		<div class="col-sm-12">
			<div class="fpbx-container">
				<div class="display full-border">
					<h1><?php echo _('CDR Reports'); ?></h1>
					
					<!-- Advanced Search Form -->
					<div class="panel panel-primary" id="advanced-search-panel">
						<div class="panel-heading">
							<h4 class="panel-title">
								<a data-toggle="collapse" href="#advanced-search-collapse" aria-expanded="false">
									<i class="fa fa-search"></i> <?php echo _('Advanced Search Options'); ?>
									<i class="fa fa-chevron-down pull-right"></i>
								</a>
							</h4>
						</div>
						<div id="advanced-search-collapse" class="panel-collapse collapse">
							<div class="panel-body">
								<form id="advanced-search-form">
									<div class="row">
										<!-- Date/Time Selection -->
										<div class="col-md-6">
											<h5><i class="fa fa-calendar"></i> <?php echo _('Date/Time Range'); ?></h5>
											<div class="form-group">
												<label><?php echo _('From Date'); ?>:</label>
												<div class="row">
													<div class="col-sm-3">
														<select class="form-control" id="from_day" name="from_day">
															<option value=""><?php echo _('Day'); ?></option>
															<?php for($i = 1; $i <= 31; $i++): ?>
																<option value="<?php echo sprintf('%02d', $i); ?>"><?php echo $i; ?></option>
															<?php endfor; ?>
														</select>
													</div>
													<div class="col-sm-4">
														<select class="form-control" id="from_month" name="from_month">
															<option value=""><?php echo _('Month'); ?></option>
															<option value="01"><?php echo _('January'); ?></option>
															<option value="02"><?php echo _('February'); ?></option>
															<option value="03"><?php echo _('March'); ?></option>
															<option value="04"><?php echo _('April'); ?></option>
															<option value="05"><?php echo _('May'); ?></option>
															<option value="06"><?php echo _('June'); ?></option>
															<option value="07"><?php echo _('July'); ?></option>
															<option value="08"><?php echo _('August'); ?></option>
															<option value="09"><?php echo _('September'); ?></option>
															<option value="10"><?php echo _('October'); ?></option>
															<option value="11"><?php echo _('November'); ?></option>
															<option value="12"><?php echo _('December'); ?></option>
														</select>
													</div>
													<div class="col-sm-3">
														<select class="form-control" id="from_year" name="from_year">
															<option value=""><?php echo _('Year'); ?></option>
															<?php for($i = date('Y'); $i >= 2000; $i--): ?>
																<option value="<?php echo $i; ?>"><?php echo $i; ?></option>
															<?php endfor; ?>
														</select>
													</div>
													<div class="col-sm-2">
														<select class="form-control" id="from_hour" name="from_hour">
															<option value=""><?php echo _('Hour'); ?></option>
															<?php for($i = 0; $i <= 23; $i++): ?>
																<option value="<?php echo sprintf('%02d', $i); ?>"><?php echo sprintf('%02d', $i); ?></option>
															<?php endfor; ?>
														</select>
													</div>
												</div>
											</div>
											<div class="form-group">
												<label><?php echo _('To Date'); ?>:</label>
												<div class="row">
													<div class="col-sm-3">
														<select class="form-control" id="to_day" name="to_day">
															<option value=""><?php echo _('Day'); ?></option>
															<?php for($i = 1; $i <= 31; $i++): ?>
																<option value="<?php echo sprintf('%02d', $i); ?>"><?php echo $i; ?></option>
															<?php endfor; ?>
														</select>
													</div>
													<div class="col-sm-4">
														<select class="form-control" id="to_month" name="to_month">
															<option value=""><?php echo _('Month'); ?></option>
															<option value="01"><?php echo _('January'); ?></option>
															<option value="02"><?php echo _('February'); ?></option>
															<option value="03"><?php echo _('March'); ?></option>
															<option value="04"><?php echo _('April'); ?></option>
															<option value="05"><?php echo _('May'); ?></option>
															<option value="06"><?php echo _('June'); ?></option>
															<option value="07"><?php echo _('July'); ?></option>
															<option value="08"><?php echo _('August'); ?></option>
															<option value="09"><?php echo _('September'); ?></option>
															<option value="10"><?php echo _('October'); ?></option>
															<option value="11"><?php echo _('November'); ?></option>
															<option value="12"><?php echo _('December'); ?></option>
														</select>
													</div>
													<div class="col-sm-3">
														<select class="form-control" id="to_year" name="to_year">
															<option value=""><?php echo _('Year'); ?></option>
															<?php for($i = date('Y'); $i >= 2000; $i--): ?>
																<option value="<?php echo $i; ?>"><?php echo $i; ?></option>
															<?php endfor; ?>
														</select>
													</div>
													<div class="col-sm-2">
														<select class="form-control" id="to_hour" name="to_hour">
															<option value=""><?php echo _('Hour'); ?></option>
															<?php for($i = 0; $i <= 23; $i++): ?>
																<option value="<?php echo sprintf('%02d', $i); ?>"><?php echo sprintf('%02d', $i); ?></option>
															<?php endfor; ?>
														</select>
													</div>
												</div>
											</div>
										</div>
										
										<!-- Search Fields -->
										<div class="col-md-6">
											<h5><i class="fa fa-filter"></i> <?php echo _('Search Filters'); ?></h5>
											
											<!-- CallerID Number -->
											<div class="form-group">
												<label><?php echo _('CallerID Number'); ?>:</label>
												<div class="row">
													<div class="col-sm-3">
														<select class="form-control" name="cnum_modifier" id="cnum_modifier">
															<option value="contains"><?php echo _('Contains'); ?></option>
															<option value="not"><?php echo _('Not'); ?></option>
															<option value="begins"><?php echo _('Begins With'); ?></option>
															<option value="ends"><?php echo _('Ends With'); ?></option>
															<option value="exactly"><?php echo _('Exactly'); ?></option>
														</select>
													</div>
													<div class="col-sm-9">
														<input type="text" class="form-control" name="cnum" id="cnum" placeholder="<?php echo _('Enter CallerID Number'); ?>">
													</div>
												</div>
											</div>
											
											<!-- CallerID Name -->
											<div class="form-group">
												<label><?php echo _('CallerID Name'); ?>:</label>
												<div class="row">
													<div class="col-sm-3">
														<select class="form-control" name="cnam_modifier" id="cnam_modifier">
															<option value="contains"><?php echo _('Contains'); ?></option>
															<option value="not"><?php echo _('Not'); ?></option>
															<option value="begins"><?php echo _('Begins With'); ?></option>
															<option value="ends"><?php echo _('Ends With'); ?></option>
															<option value="exactly"><?php echo _('Exactly'); ?></option>
														</select>
													</div>
													<div class="col-sm-9">
														<input type="text" class="form-control" name="cnam" id="cnam" placeholder="<?php echo _('Enter CallerID Name'); ?>">
													</div>
												</div>
											</div>
											
											<!-- Outbound CallerID -->
											<div class="form-group">
												<label><?php echo _('Outbound CallerID'); ?>:</label>
												<div class="row">
													<div class="col-sm-3">
														<select class="form-control" name="outbound_cnum_modifier" id="outbound_cnum_modifier">
															<option value="contains"><?php echo _('Contains'); ?></option>
															<option value="not"><?php echo _('Not'); ?></option>
															<option value="begins"><?php echo _('Begins With'); ?></option>
															<option value="ends"><?php echo _('Ends With'); ?></option>
															<option value="exactly"><?php echo _('Exactly'); ?></option>
														</select>
													</div>
													<div class="col-sm-9">
														<input type="text" class="form-control" name="outbound_cnum" id="outbound_cnum" placeholder="<?php echo _('Enter Outbound CallerID'); ?>">
													</div>
												</div>
											</div>
										</div>
									</div>
									
									<div class="row">
										<div class="col-md-6">
											<!-- DID -->
											<div class="form-group">
												<label><?php echo _('DID'); ?>:</label>
												<div class="row">
													<div class="col-sm-3">
														<select class="form-control" name="did_modifier" id="did_modifier">
															<option value="contains"><?php echo _('Contains'); ?></option>
															<option value="not"><?php echo _('Not'); ?></option>
															<option value="begins"><?php echo _('Begins With'); ?></option>
															<option value="ends"><?php echo _('Ends With'); ?></option>
															<option value="exactly"><?php echo _('Exactly'); ?></option>
														</select>
													</div>
													<div class="col-sm-9">
														<input type="text" class="form-control" name="did" id="did" placeholder="<?php echo _('Enter DID'); ?>">
													</div>
												</div>
											</div>
											
											<!-- Destination -->
											<div class="form-group">
												<label><?php echo _('Destination'); ?>:</label>
												<div class="row">
													<div class="col-sm-3">
														<select class="form-control" name="dst_modifier" id="dst_modifier">
															<option value="contains"><?php echo _('Contains'); ?></option>
															<option value="not"><?php echo _('Not'); ?></option>
															<option value="begins"><?php echo _('Begins With'); ?></option>
															<option value="ends"><?php echo _('Ends With'); ?></option>
															<option value="exactly"><?php echo _('Exactly'); ?></option>
														</select>
													</div>
													<div class="col-sm-9">
														<input type="text" class="form-control" name="dst" id="dst" placeholder="<?php echo _('Enter Destination'); ?>">
													</div>
												</div>
											</div>
											
											<!-- Destination CallerID Name -->
											<div class="form-group">
												<label><?php echo _('Destination CallerID Name'); ?>:</label>
												<div class="row">
													<div class="col-sm-3">
														<select class="form-control" name="dst_cnam_modifier" id="dst_cnam_modifier">
															<option value="contains"><?php echo _('Contains'); ?></option>
															<option value="not"><?php echo _('Not'); ?></option>
															<option value="begins"><?php echo _('Begins With'); ?></option>
															<option value="ends"><?php echo _('Ends With'); ?></option>
															<option value="exactly"><?php echo _('Exactly'); ?></option>
														</select>
													</div>
													<div class="col-sm-9">
														<input type="text" class="form-control" name="dst_cnam" id="dst_cnam" placeholder="<?php echo _('Enter Destination CallerID Name'); ?>">
													</div>
												</div>
											</div>
										</div>
										
										<div class="col-md-6">
											<!-- Userfield -->
											<div class="form-group">
												<label><?php echo _('Userfield'); ?>:</label>
												<div class="row">
													<div class="col-sm-3">
														<select class="form-control" name="userfield_modifier" id="userfield_modifier">
															<option value="contains"><?php echo _('Contains'); ?></option>
															<option value="not"><?php echo _('Not'); ?></option>
															<option value="begins"><?php echo _('Begins With'); ?></option>
															<option value="ends"><?php echo _('Ends With'); ?></option>
															<option value="exactly"><?php echo _('Exactly'); ?></option>
														</select>
													</div>
													<div class="col-sm-9">
														<input type="text" class="form-control" name="userfield" id="userfield" placeholder="<?php echo _('Enter Userfield'); ?>">
													</div>
												</div>
											</div>
											
											<!-- Account Code -->
											<div class="form-group">
												<label><?php echo _('Account Code'); ?>:</label>
												<div class="row">
													<div class="col-sm-3">
														<select class="form-control" name="accountcode_modifier" id="accountcode_modifier">
															<option value="contains"><?php echo _('Contains'); ?></option>
															<option value="not"><?php echo _('Not'); ?></option>
															<option value="begins"><?php echo _('Begins With'); ?></option>
															<option value="ends"><?php echo _('Ends With'); ?></option>
															<option value="exactly"><?php echo _('Exactly'); ?></option>
														</select>
													</div>
													<div class="col-sm-9">
														<input type="text" class="form-control" name="accountcode" id="accountcode" placeholder="<?php echo _('Enter Account Code'); ?>">
													</div>
												</div>
											</div>
											
											<!-- Duration Range -->
											<div class="form-group">
												<label><?php echo _('Duration Range (seconds)'); ?>:</label>
												<div class="row">
													<div class="col-sm-6">
														<input type="number" class="form-control" name="duration_min" id="duration_min" placeholder="<?php echo _('Min Duration'); ?>" min="0">
													</div>
													<div class="col-sm-6">
														<input type="number" class="form-control" name="duration_max" id="duration_max" placeholder="<?php echo _('Max Duration'); ?>" min="0">
													</div>
												</div>
											</div>
											
											<!-- Disposition -->
											<div class="form-group">
												<label><?php echo _('Disposition'); ?>:</label>
												<select class="form-control" name="disposition" id="disposition">
													<option value=""><?php echo _('All Dispositions'); ?></option>
													<option value="ANSWERED"><?php echo _('ANSWERED'); ?></option>
													<option value="BUSY"><?php echo _('BUSY'); ?></option>
													<option value="FAILED"><?php echo _('FAILED'); ?></option>
													<option value="NO ANSWER"><?php echo _('NO ANSWER'); ?></option>
													<option value="CONGESTION"><?php echo _('CONGESTION'); ?></option>
												</select>
											</div>
										</div>
									</div>
									
									<!-- Extra Options -->
									<div class="row">
										<div class="col-md-12">
											<h5><i class="fa fa-cogs"></i> <?php echo _('Extra Options'); ?></h5>
										</div>
									</div>
									<div class="row">
										<div class="col-md-6">
											<div class="form-group">
												<label><?php echo _('Report Type'); ?>:</label>
												<div class="checkbox-group">
													<label class="checkbox-inline">
														<input type="checkbox" name="report_type[]" value="inbound" checked> <?php echo _('Inbound'); ?>
													</label>
													<label class="checkbox-inline">
														<input type="checkbox" name="report_type[]" value="outbound" checked> <?php echo _('Outbound'); ?>
													</label>
													<label class="checkbox-inline">
														<input type="checkbox" name="report_type[]" value="internal" checked> <?php echo _('Internal'); ?>
													</label>
												</div>
											</div>
											
											<div class="form-group">
												<label><?php echo _('Result Limit'); ?>:</label>
												<select class="form-control" name="result_limit" id="result_limit">
													<option value="50">50</option>
													<option value="100">100</option>
													<option value="250">250</option>
													<option value="500">500</option>
													<option value="1000">1000</option>
													<option value="0"><?php echo _('No Limit'); ?></option>
												</select>
											</div>
										</div>
										
										<div class="col-md-6">
											<div class="form-group">
												<label><?php echo _('Group By'); ?>:</label>
												<select class="form-control" name="group_by" id="group_by">
													<option value=""><?php echo _('No Grouping'); ?></option>
													<optgroup label="<?php echo _('Account Information'); ?>">
														<option value="accountcode"><?php echo _('Account Code'); ?></option>
														<option value="userfield"><?php echo _('User Field'); ?></option>
													</optgroup>
													<optgroup label="<?php echo _('Date/Time'); ?>">
														<option value="date"><?php echo _('Date'); ?></option>
														<option value="hour"><?php echo _('Hour'); ?></option>
														<option value="day_of_week"><?php echo _('Day of Week'); ?></option>
														<option value="month"><?php echo _('Month'); ?></option>
													</optgroup>
													<optgroup label="<?php echo _('Telephone Number'); ?>">
														<option value="src"><?php echo _('Source'); ?></option>
														<option value="dst"><?php echo _('Destination'); ?></option>
														<option value="did"><?php echo _('DID'); ?></option>
													</optgroup>
													<optgroup label="<?php echo _('Tech Info'); ?>">
														<option value="disposition"><?php echo _('Disposition'); ?></option>
														<option value="lastapp"><?php echo _('Last Application'); ?></option>
														<option value="channel"><?php echo _('Channel'); ?></option>
													</optgroup>
												</select>
											</div>
										</div>
									</div>
									
									<!-- Action Buttons -->
									<div class="row">
										<div class="col-md-12">
											<div class="form-group">
								<button type="button" class="btn btn-primary" id="apply-search">
									<i class="fa fa-search"></i> <?php echo _('Apply Search'); ?>
								</button>
								<button type="button" class="btn btn-primary" id="reset-search">
									<i class="fa fa-refresh"></i> <?php echo _('Reset'); ?>
								</button>
								<button type="button" class="btn btn-primary" id="export-csv">
									<i class="fa fa-download"></i> <?php echo _('Export CSV'); ?>
								</button>
								<button type="button" class="btn btn-primary" id="show-graph">
									<i class="fa fa-bar-chart"></i> <?php echo _('Graph'); ?>
								</button>
											</div>
										</div>
									</div>
								</form>
							</div>
						</div>
					</div>
					
					<!-- Toolbar -->
					<div id="toolbar-cdr" class="toolbar">
						<!-- Quick Date Range Picker -->
						<div class="btn-group pull-right" role="group">
							<div id="daterange" class="btn btn-default" title="<?php echo _('Tip: pick a day on the left (From) calendar first to unlock earlier years on the right (To) calendar. Changing only the year/month dropdown does not do this -- you must click an actual day.'); ?>">
								<i class="fa fa-calendar"></i>&nbsp;
								<span></span> <i class="fa fa-caret-down"></i>
							</div>
						</div>
					</div>
					
					<!-- Hidden inputs for date range -->
					<input type="hidden" id="startdate" name="startdate" />
					<input type="hidden" id="enddate" name="enddate" />
					
					<!-- Bootstrap Table -->
					<table 
						id="cdrGrid"
						data-escape="true"
						data-toolbar="#toolbar-cdr"
						data-url="ajax.php?module=cdr&command=getJSON"
						data-cache="false"
						data-side-pagination="server"
						data-pagination="true"
						data-page-size="50"
						data-page-list="[10, 25, 50, 100, 200]"
						data-search="true"
						data-show-refresh="true"
						data-show-toggle="true"
						data-show-columns="true"
						data-show-export="true"
						data-export-types="['csv', 'excel']"
						data-export-options='{
							"fileName": "cdr_export"
						}'
						data-sort-name="calldate"
						data-sort-order="desc"
						data-toggle="table"
						data-query-params="queryParams"
						data-response-handler="responseHandler"
						data-detail-view="true"
						data-detail-formatter="detailFormatter"
						data-icons-prefix="fa"
						data-icons='{"detailOpen": "fa-chevron-down", "detailClose": "fa-chevron-up"}'
						class="table table-striped">
						<thead>
							<tr>
								<th data-field="calldate" data-formatter="dateFormatter" data-sortable="true" data-width="150"><?php echo _("Call Date"); ?></th>
								<th data-field="uniqueid" data-visible="false" data-width="100"><?php echo _("System"); ?></th>
								<th data-field="src" data-formatter="callerIdFormatter" data-sortable="true" data-width="200"><?php echo _("CallerID"); ?></th>
								<th data-field="outbound_cnum" data-sortable="true" data-width="150"><?php echo _("Outbound CallerID"); ?></th>
								<th data-field="did" data-sortable="true" data-width="120"><?php echo _("DID"); ?></th>
								<th data-field="lastapp" data-sortable="true" data-width="100"><?php echo _("App"); ?></th>
								<th data-field="dst" data-formatter="destinationFormatter" data-sortable="true" data-width="150"><?php echo _("Destination"); ?></th>
								<th data-field="disposition" data-formatter="dispositionFormatter" data-sortable="true" data-width="100"><?php echo _("Disposition"); ?></th>
								<th data-field="duration" data-formatter="durationFormatter" data-sortable="true" data-width="100"><?php echo _("Duration"); ?></th>
								<th data-field="userfield" data-sortable="true" data-width="120"><?php echo _("Userfield"); ?></th>
								<th data-field="accountcode" data-sortable="true" data-width="120"><?php echo _("Account"); ?></th>
								<th data-field="recordingfile" data-formatter="recordingFormatter" data-width="80" data-align="center"><?php echo _("Recording"); ?></th>
							</tr>
						</thead>
					</table>
				</div>
			</div>
		</div>
	</div>
</div>

<!-- Playback rows will be dynamically inserted here -->
<div id="playback-container"></div>

<!-- Graph Modal -->
<div class="modal fade" id="graphModal" tabindex="-1" role="dialog" aria-labelledby="graphModalLabel">
	<div class="modal-dialog modal-lg" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
				<h4 class="modal-title" id="graphModalLabel">
					<i class="fa fa-bar-chart"></i> <?php echo _('CDR Statistics Graph'); ?>
				</h4>
			</div>
			<div class="modal-body">
				<div class="row">
					<div class="col-md-12">
						<div class="form-group">
							<label><?php echo _('Graph Type'); ?>:</label>
							<select class="form-control" id="graph-type">
								<option value="calls_by_hour"><?php echo _('Calls by Hour'); ?></option>
								<option value="calls_by_day"><?php echo _('Calls by Day'); ?></option>
								<option value="calls_by_disposition"><?php echo _('Calls by Disposition'); ?></option>
								<option value="duration_by_hour"><?php echo _('Duration by Hour'); ?></option>
								<option value="calls_by_source"><?php echo _('Top Sources'); ?></option>
								<option value="calls_by_destination"><?php echo _('Top Destinations'); ?></option>
							</select>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-md-12">
						<div id="cdr-chart-container" style="height: 400px; width: 100%;"></div>
					</div>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _('Close'); ?></button>
				<button type="button" class="btn btn-primary" id="refresh-graph">
					<i class="fa fa-refresh"></i> <?php echo _('Refresh Graph'); ?>
				</button>
			</div>
		</div>
	</div>
</div>

<script>
// Response handler for bootstrap table
function responseHandler(res) {
	// Add playback rows after table data is loaded
	setTimeout(function() {
		addPlaybackRows(res.rows);
	}, 100);
	
	return {
		total: res.total,
		rows: res.rows
	};
}

// Add playback rows for recordings
function addPlaybackRows(rows) {
	var container = $('#playback-container');
	container.empty();
	
	$.each(rows, function(index, row) {
		if (row.recordingfile && row.recordingfile !== '') {
			var uid = row.niceUniqueid || row.uniqueid.replace('.', '_');
			var playbackHtml = '<div id="playback-' + index + '" class="playback" style="display:none;">' +
				'<div class="row">' +
				'<div class="col-sm-12">' +
				'<div id="jquery_jplayer_' + index + '" class="jp-jplayer"></div>' +
				'<div id="jp_container_' + index + '" data-player="jquery_jplayer_' + index + '" class="jp-audio-freepbx" role="application" aria-label="media player">' +
				'<div class="jp-type-single">' +
				'<div class="jp-gui jp-interface">' +
				'<div class="jp-controls">' +
				'<i class="fa fa-play jp-play"></i>' +
				'<i class="fa fa-undo jp-restart"></i>' +
				'</div>' +
				'<div class="jp-progress">' +
				'<div class="jp-seek-bar progress">' +
				'<div class="jp-current-time" role="timer" aria-label="time">&nbsp;</div>' +
				'<div class="progress-bar progress-bar-striped active" style="width: 100%;"></div>' +
				'<div class="jp-play-bar progress-bar"></div>' +
				'<div class="jp-play-bar"><div class="jp-ball"></div></div>' +
				'<div class="jp-duration" role="timer" aria-label="duration">&nbsp;</div>' +
				'</div>' +
				'</div>' +
				'<div class="jp-volume-controls">' +
				'<i class="fa fa-volume-up jp-mute"></i>' +
				'<i class="fa fa-volume-off jp-unmute"></i>' +
				'</div>' +
				'</div>' +
				'<div class="jp-no-solution">' +
				'<span><?php echo _("Update Required"); ?></span>' +
				'<?php echo sprintf(_("You are missing support for playback in this browser. To fully support HTML5 browser playback you will need to install programs that can not be distributed with the PBX. If you\'d like to install the binaries needed for these conversions click <a href=\'%s\'>here</a>"), "http://wiki.freepbx.org/display/FOP/Installing+Media+Conversion+Libraries"); ?>' +
				'</div>' +
				'</div>' +
				'</div>' +
				'</div>' +
				'</div>' +
				'</div>';
			
			container.append(playbackHtml);
		}
	});
}

// Check if CEL is enabled
var cel_enabled = <?php echo (isset($amp_conf['CEL_ENABLED']) && $amp_conf['CEL_ENABLED']) ? 'true' : 'false'; ?>;

// Supported HTML5 formats
var supportedHTML5 = "<?php 
try {
	echo implode(",", \FreePBX::Media()->getSupportedHTML5Formats()); 
} catch(Exception $e) {
	echo "mp3,wav,ogg";
}
?>";

// Initialize everything when document is ready
$(document).ready(function() {
	// Wait for moment.js and daterangepicker to load
	if (typeof moment !== 'undefined' && typeof $.fn.daterangepicker !== 'undefined') {
		initDateRangePicker();
	} else {
		// Retry after a short delay
		setTimeout(function() {
			if (typeof moment !== 'undefined' && typeof $.fn.daterangepicker !== 'undefined') {
				initDateRangePicker();
			}
		}, 500);
	}
});
</script>
