function getCdrGrid() {
	return $('#cdrGrid');
}

// Format date for display
function dateFormatter(value, row, index) {
	if (!value) return '';
	// Check if value is already a timestamp or needs conversion
	var timestamp = (typeof value === 'string' && value.includes('-')) ? 
		new Date(value).getTime() / 1000 : 
		(row.timestamp || value);
	
	if (!timestamp || isNaN(timestamp)) return value;
	
	var date = new Date(timestamp * 1000);
	return date.toLocaleString();
}

// Format duration
function durationFormatter(value, row, index) {
	if (!row.niceDuration) return value + 's';
	return row.niceDuration;
}

// Format caller ID
function callerIdFormatter(value, row, index) {
	var cnam = row.cnam || '';
	var cnum = row.cnum || row.src || '';
	if (cnam && cnum) {
		return '"' + cnam + '" <' + cnum + '>';
	} else if (cnum) {
		return '<' + cnum + '>';
	}
	return value || '';
}

// Format destination
function destinationFormatter(value, row, index) {
	var dst_cnam = row.dst_cnam || '';
	var dst = row.dst || '';
	if (dst_cnam && dst) {
		return '"' + dst_cnam + '" ' + dst;
	}
	return dst || value || '';
}

// Format recording file
function recordingFormatter(value, row, index) {
	if (!row.recordingfile || row.recordingfile === '') {
		return '';
	}
	
	var html = '';
	var uid = row.niceUniqueid || row.uniqueid.replace('.', '_');
	
	// Play button - triggers modal audio playback
	html += '<a href="#" onclick="playRecordingModal(\'' + row.uniqueid + '\'); return false;" title="' + _('Play Recording') + '">';
	html += '<i class="fa fa-play-circle text-success"></i></a> ';
	
	// Download button
	html += '<a href="?display=cdr&action=download_audio&cdr_file=' + row.uniqueid + '" title="' + _('Download Recording') + '">';
	html += '<i class="fa fa-download text-primary"></i></a>';
	
	return html;
}

// Format disposition with color coding
function dispositionFormatter(value, row, index) {
	var className = '';
	switch(value) {
		case 'ANSWERED':
			className = 'text-success';
			break;
		case 'BUSY':
			className = 'text-warning';
			break;
		case 'FAILED':
		case 'NO ANSWER':
			className = 'text-danger';
			break;
		default:
			className = 'text-muted';
	}
	return '<span class="' + className + '">' + value + '</span>';
}


// Initialize date range picker
function initDateRangePicker() {
	var start = moment().subtract(29, 'days');
	var end = moment();
	
	function cb(start, end) {
		$('#daterange span').html(start.format('MMMM D, YYYY') + ' - ' + end.format('MMMM D, YYYY'));
		$('#startdate').val(start.format('YYYY-MM-DD HH:mm:ss'));
		$('#enddate').val(end.format('YYYY-MM-DD HH:mm:ss'));

		// Keep the Advanced Search Options From/To Date fields in sync. The
		// Hour dropdowns are left untouched (blank) rather than set to the
		// picker's current time-of-day, so the backend's whole-day defaults
		// (00:00:00 / 23:59:59) apply unless the user explicitly picks an hour.
		$('#from_day').val(start.format('DD'));
		$('#from_month').val(start.format('MM'));
		$('#from_year').val(start.format('YYYY'));
		$('#to_day').val(end.format('DD'));
		$('#to_month').val(end.format('MM'));
		$('#to_year').val(end.format('YYYY'));

		getCdrGrid().bootstrapTable('refresh');
	}
	
	$('#daterange').daterangepicker({
		startDate: start,
		endDate: end,
		ranges: {
			'Today': [moment(), moment()],
			'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
			'Last 7 Days': [moment().subtract(6, 'days'), moment()],
			'Last 30 Days': [moment().subtract(29, 'days'), moment()],
			'This Month': [moment().startOf('month'), moment().endOf('month')],
			'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
		},
		alwaysShowCalendars: true,
		showCustomRangeLabel: true,
		// Let each calendar navigate to a different month independently, and
		// add month/year dropdowns, so picking two far-apart dates (e.g. a
		// 2021 start and a 2025 end) doesn't require clicking "next" dozens
		// of times on one calendar just to reach the other one's month.
		linkedCalendars: false,
		showDropdowns: true,
		// CDRs are historical by nature, so the year dropdown must reach back
		// far enough to be useful (matches the "From/To Date" Advanced Search
		// Options year select, which also starts at 2000). Without an explicit
		// maxYear, the library defaults it to minYear + 100, so both calendars'
		// year dropdowns offered nonsensical future years (up to 2100) — no CDR
		// can ever be dated after today, so cap it there.
		minYear: 2000,
		maxYear: moment().year(),
		opens: 'left',
		drops: 'down'
	}, cb);

	// Allow picking the range in either order (a later date first, then an earlier
	// one) instead of forcing the user to always click the left/earlier day first.
	// The bundled daterangepicker.js resets the selection to a new single day
	// whenever the day clicked second is chronologically before the first one, so
	// we replace its click handler with one that swaps start/end when needed.
	(function() {
		var picker = $('#daterange').data('daterangepicker');
		if (!picker || !picker.container || !picker.leftCalendar || !picker.rightCalendar) {
			return;
		}
		var pickingEnd = false;
		// The library binds its own day-click handler directly on the two
		// .drp-calendar elements (not on .container), and that handler calls
		// stopPropagation(). Overriding on .container would never fire because
		// the event never bubbles that far, so we must rebind on the same
		// elements the library itself uses.
		var calendars = picker.container.find('.drp-calendar');
		calendars.off('mousedown.daterangepicker', 'td.available');
		calendars.on('mousedown.daterangepicker', 'td.available', function(ev) {
			var el = $(ev.target);
			var title = el.attr('data-title');
			var row = title.substr(1, 1);
			var col = title.substr(3, 1);
			var isLeft = el.parents('.drp-calendar').hasClass('left');
			var clicked = (isLeft ? picker.leftCalendar.calendar[row][col] : picker.rightCalendar.calendar[row][col]).clone();

			if (pickingEnd) {
				var currentStart = picker.startDate.clone();
				if (clicked.isBefore(currentStart, 'day')) {
					picker.setStartDate(clicked);
					picker.setEndDate(currentStart);
				} else {
					picker.setEndDate(clicked);
				}
				pickingEnd = false;
			} else {
				picker.endDate = null;
				picker.setStartDate(clicked);
				pickingEnd = true;
			}
			picker.updateView();
			ev.stopPropagation();
		});
	})();

	// Fix exclusive selection behavior for date range picker
	$('#daterange').on('show.daterangepicker', function(ev, picker) {
		// Add click handlers to range options
		setTimeout(function() {
			$('.daterangepicker .ranges li').off('click.exclusive').on('click.exclusive', function() {
				// Remove active class from all range options
				$('.daterangepicker .ranges li').removeClass('active');
				// Add active class to clicked option
				$(this).addClass('active');
			});
		}, 100);
	});
	
	cb(start, end);
}

// Play recording function
function cdr_play(index, uid) {
	var playbackRow = '#playback-' + index;
	
	if ($(playbackRow).is(':visible')) {
		$(playbackRow).hide();
		return;
	}
	
	// Hide other playback rows
	$('.playback').hide();
	
	// Get HTML5 files
	$.post(window.FreePBX.ajaxurl, {
		module: 'cdr',
		command: 'gethtml5',
		uid: uid
	}, function(data) {
		if (data.status) {
			// Initialize jPlayer
			$('#jquery_jplayer_' + index).jPlayer({
				ready: function() {
					$(this).jPlayer('setMedia', data.files);
				},
				swfPath: 'assets/js/jplayer',
				supplied: Object.keys(data.files).join(','),
				cssSelectorAncestor: '#jp_container_' + index,
				wmode: 'window'
			});
			
			$(playbackRow).show();
		} else {
			alert(_('No recording available'));
		}
	});
}

// Initialize on document ready
$(document).ready(function() {
	// No custom refresh button needed - using native bootstrap-table refresh
	
	// Initialize advanced search functionality
	initAdvancedSearch();
});

// Initialize advanced search functionality
function initAdvancedSearch() {
	// Apply search button
	$('#apply-search').on('click', function() {
		getCdrGrid().bootstrapTable('refresh');
	});
	
	// Reset search button
	$('#reset-search').on('click', function() {
		resetAdvancedSearch();
	});
	
	// Export CSV button
	$('#export-csv').on('click', function() {
		exportCdrData();
	});
	
	// Show graph button
	$('#show-graph').on('click', function() {
		showGraphModal();
	});
	
	// Refresh graph button
	$('#refresh-graph').on('click', function() {
		var graphType = $('#graph-type').val();
		loadGraphData(graphType);
	});
	
	// Graph type change
	$('#graph-type').on('change', function() {
		var graphType = $(this).val();
		loadGraphData(graphType);
	});
	
	// Auto-apply search when Enter is pressed in text fields
	$('#advanced-search-form input[type="text"], #advanced-search-form input[type="number"]').on('keypress', function(e) {
		if (e.which === 13) { // Enter key
			getCdrGrid().bootstrapTable('refresh');
		}
	});
	
	// Auto-apply search when dropdowns change
	$('#advanced-search-form select').on('change', function() {
		// Small delay to allow user to make multiple selections
		clearTimeout(window.searchTimeout);
		window.searchTimeout = setTimeout(function() {
			getCdrGrid().bootstrapTable('refresh');
		}, 500);
	});

	// Keep the Quick Date Range Picker (startdate/enddate + its displayed
	// label/calendar) in sync whenever the From/To Date fields under Advanced
	// Search Options are changed directly, so both controls always agree on
	// the same range instead of silently fighting each other.
	$('#from_day, #from_month, #from_year, #from_hour, #to_day, #to_month, #to_year, #to_hour').on('change', function() {
		syncQuickPickerFromAdvancedFields();
	});
	
	// Auto-apply search when checkboxes change
	$('#advanced-search-form input[type="checkbox"]').on('change', function() {
		getCdrGrid().bootstrapTable('refresh');
	});
	
	// Set default values
	setDefaultSearchValues();
}

// Mirror of the PHP-side buildDateFromComponents() date defaulting, used to
// keep the Quick Date Range Picker visually in sync with manual From/To Date
// edits under Advanced Search Options.
function syncQuickPickerFromAdvancedFields() {
	var fromDay = $('#from_day').val(), fromMonth = $('#from_month').val(), fromYear = $('#from_year').val();
	var toDay = $('#to_day').val(), toMonth = $('#to_month').val(), toYear = $('#to_year').val();

	if (!fromDay && !fromMonth && !fromYear && !toDay && !toMonth && !toYear) {
		return;
	}

	var fromHour = $('#from_hour').val() || '00';
	var start = moment({
		year: fromYear || moment().year(),
		month: (fromMonth || '01') - 1,
		day: fromDay || '01',
		hour: fromHour
	});

	var toHourVal = $('#to_hour').val();
	var end = moment({
		year: toYear || moment().year(),
		month: (toMonth || '01') - 1,
		day: toDay || '01',
		hour: toHourVal || (toHourVal === '' ? 23 : toHourVal)
	});
	if (!toHourVal) {
		end.set({ hour: 23, minute: 59, second: 59 });
	}

	$('#startdate').val(start.format('YYYY-MM-DD HH:mm:ss'));
	$('#enddate').val(end.format('YYYY-MM-DD HH:mm:ss'));
	$('#daterange span').html(start.format('MMMM D, YYYY') + ' - ' + end.format('MMMM D, YYYY'));

	var picker = $('#daterange').data('daterangepicker');
	if (picker) {
		picker.startDate = start.clone();
		picker.endDate = end.clone();
		picker.updateMonthsInView();
	}
}

// Reset advanced search form
function resetAdvancedSearch() {
	$('#advanced-search-form')[0].reset();
	
	// Reset checkboxes to default state
	$('input[name="report_type[]"]').prop('checked', true);
	
	// Reset select dropdowns to default values
	$('#result_limit').val('50');
	
	// Clear date/time fields
	$('#from_day, #from_month, #from_year, #from_hour').val('');
	$('#to_day, #to_month, #to_year, #to_hour').val('');
	
	// Refresh table
	getCdrGrid().bootstrapTable('refresh');
}

// Set default search values
function setDefaultSearchValues() {
	// Set default date range to last 30 days
	var today = new Date();
	var lastMonth = new Date();
	lastMonth.setDate(today.getDate() - 30);
	
	// Set from date
	$('#from_day').val(String(lastMonth.getDate()).padStart(2, '0'));
	$('#from_month').val(String(lastMonth.getMonth() + 1).padStart(2, '0'));
	$('#from_year').val(lastMonth.getFullYear());
	
	// Set to date
	$('#to_day').val(String(today.getDate()).padStart(2, '0'));
	$('#to_month').val(String(today.getMonth() + 1).padStart(2, '0'));
	$('#to_year').val(today.getFullYear());
}

// Export CDR data with current filters
function exportCdrData() {
	var params = queryParams({});
	params.export = 'csv';
	
	// Build query string
	var queryString = $.param(params);
	
	// Create download link
	var downloadUrl = 'ajax.php?module=cdr&command=export_csv&' + queryString;
	
	// Trigger download
	window.location.href = downloadUrl;
}

// Query params for bootstrap table
function queryParams(params) {
	// Add date range if set
	if ($('#startdate').val()) {
		params.startdate = $('#startdate').val();
	}
	if ($('#enddate').val()) {
		params.enddate = $('#enddate').val();
	}
	
	// Add advanced search parameters
	var form = $('#advanced-search-form');
	if (form.length) {
		// Date/Time fields
		if ($('#from_day').val() || $('#from_month').val() || $('#from_year').val()) {
			params.from_day = $('#from_day').val();
			params.from_month = $('#from_month').val();
			params.from_year = $('#from_year').val();
			params.from_hour = $('#from_hour').val();
		}
		if ($('#to_day').val() || $('#to_month').val() || $('#to_year').val()) {
			params.to_day = $('#to_day').val();
			params.to_month = $('#to_month').val();
			params.to_year = $('#to_year').val();
			params.to_hour = $('#to_hour').val();
		}
		
		// Search fields with modifiers
		var searchFields = ['cnum', 'cnam', 'outbound_cnum', 'did', 'dst', 'dst_cnam', 'userfield', 'accountcode'];
		$.each(searchFields, function(i, field) {
			if ($('#' + field).val()) {
				params[field] = $('#' + field).val();
				params[field + '_modifier'] = $('#' + field + '_modifier').val();
			}
		});
		
		// Duration range
		if ($('#duration_min').val()) {
			params.duration_min = $('#duration_min').val();
		}
		if ($('#duration_max').val()) {
			params.duration_max = $('#duration_max').val();
		}
		
		// Disposition
		if ($('#disposition').val()) {
			params.disposition = $('#disposition').val();
		}
		
		// Report type
		var reportTypes = [];
		$('input[name="report_type[]"]:checked').each(function() {
			reportTypes.push($(this).val());
		});
		if (reportTypes.length > 0) {
			params.report_type = reportTypes.join(',');
		}
		
		// Result limit
		if ($('#result_limit').val()) {
			params.result_limit = $('#result_limit').val();
		}
		
		// Group by
		if ($('#group_by').val()) {
			params.group_by = $('#group_by').val();
		}
	}
	
	return params;
}

// Detail formatter for bootstrap table - shows CEL events
function detailFormatter(index, row) {
	if (typeof cel_enabled === 'undefined' || !cel_enabled) {
		return '<div class="alert alert-info">CEL (Call Event Logging) is not enabled on this system.</div>';
	}
	
	var html = '<div class="detail-view-loading" data-uniqueid="' + row.uniqueid + '">';
	html += '<i class="fa fa-spinner fa-spin"></i> Loading call events...';
	html += '</div>';
	
	// Load CEL data asynchronously
	setTimeout(function() {
		loadCelEvents(row.uniqueid, index);
	}, 100);
	
	return html;
}

// Load CEL events for a specific call
function loadCelEvents(uniqueid, index) {
	$.post('ajax.php', {
		module: 'cdr',
		command: 'getCelEvents',
		uniqueid: uniqueid
	}, function(data) {
		var container = $('.detail-view-loading[data-uniqueid="' + uniqueid + '"]');
		
		if (data.status && data.events && data.events.length > 0) {
			var html = '<div class="cel-events">';
			html += '<h4><i class="fa fa-list"></i> Call Event Log</h4>';
			html += '<div class="table-responsive">';
			html += '<table class="table table-condensed table-striped">';
			html += '<thead><tr>';
			html += '<th>Time</th><th>Event</th><th>Channel</th><th>Application</th><th>Data</th>';
			html += '</tr></thead><tbody>';
			
			$.each(data.events, function(i, event) {
				html += '<tr>';
				html += '<td>' + event.eventtime + '</td>';
				html += '<td><span class="label label-info">' + event.eventtype + '</span></td>';
				html += '<td>' + (event.channame || '') + '</td>';
				html += '<td>' + (event.appname || '') + '</td>';
				html += '<td>' + (event.appdata || '') + '</td>';
				html += '</tr>';
			});
			
			html += '</tbody></table></div></div>';
			container.html(html);
		} else {
			container.html('<div class="alert alert-warning">No call events found for this call.</div>');
		}
	}).fail(function() {
		var container = $('.detail-view-loading[data-uniqueid="' + uniqueid + '"]');
		container.html('<div class="alert alert-danger">Error loading call events.</div>');
	});
}

// Play recording in modal
function playRecordingModal(uniqueid) {
	// Create modal if it doesn't exist
	if ($('#recordingModal').length === 0) {
		var modalHtml = '<div class="modal fade" id="recordingModal" tabindex="-1" role="dialog">';
		modalHtml += '<div class="modal-dialog modal-lg" role="document">';
		modalHtml += '<div class="modal-content">';
		modalHtml += '<div class="modal-header">';
		modalHtml += '<button type="button" class="close" data-dismiss="modal" aria-label="Close">';
		modalHtml += '<span aria-hidden="true">&times;</span></button>';
		modalHtml += '<h4 class="modal-title"><i class="fa fa-play-circle"></i> Recording Playback</h4>';
		modalHtml += '</div>';
		modalHtml += '<div class="modal-body">';
		modalHtml += '<div id="recording-player-container">';
		modalHtml += '<div id="jquery_jplayer_modal" class="jp-jplayer"></div>';
		modalHtml += '<div id="jp_container_modal" data-player="jquery_jplayer_modal" class="jp-audio-freepbx" role="application" aria-label="media player">';
		modalHtml += '<div class="jp-type-single">';
		modalHtml += '<div class="jp-gui jp-interface">';
		modalHtml += '<div class="jp-controls">';
		modalHtml += '<button class="jp-play btn btn-primary" role="button" tabindex="0"><i class="fa fa-play"></i></button>';
		modalHtml += '<button class="jp-pause btn btn-primary" role="button" tabindex="0"><i class="fa fa-pause"></i></button>';
		modalHtml += '<button class="jp-stop btn btn-primary" role="button" tabindex="0"><i class="fa fa-stop"></i></button>';
		modalHtml += '</div>';
		modalHtml += '<div class="jp-progress">';
		modalHtml += '<div class="jp-seek-bar progress" style="margin-top: 10px;">';
		modalHtml += '<div class="jp-current-time" role="timer" aria-label="time">00:00</div>';
		modalHtml += '<div class="progress-bar jp-play-bar" role="progressbar" style="width: 0%;"></div>';
		modalHtml += '<div class="jp-duration" role="timer" aria-label="duration">00:00</div>';
		modalHtml += '</div>';
		modalHtml += '</div>';
		modalHtml += '<div class="jp-volume-controls" style="margin-top: 10px;">';
		modalHtml += '<button class="jp-mute btn btn-default btn-sm" role="button" tabindex="0"><i class="fa fa-volume-up"></i></button>';
		modalHtml += '<button class="jp-unmute btn btn-default btn-sm" role="button" tabindex="0" style="display: none;"><i class="fa fa-volume-off"></i></button>';
		modalHtml += '</div>';
		modalHtml += '</div>';
		modalHtml += '<div class="jp-no-solution alert alert-danger" style="display: none;">';
		modalHtml += '<span>Update Required</span>';
		modalHtml += '<p>You are missing support for playback in this browser.</p>';
		modalHtml += '</div>';
		modalHtml += '</div>';
		modalHtml += '</div>';
		modalHtml += '</div>';
		modalHtml += '</div>';
		modalHtml += '<div class="modal-footer">';
		modalHtml += '<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>';
		modalHtml += '</div>';
		modalHtml += '</div>';
		modalHtml += '</div>';
		modalHtml += '</div>';
		
		$('body').append(modalHtml);
	}
	
	// Show modal
	$('#recordingModal').modal('show');
	
	// Load and play recording
	$.post('ajax.php', {
		module: 'cdr',
		command: 'gethtml5',
		uid: uniqueid
	}, function(data) {
		if (data.status && data.files) {
			// Destroy existing jPlayer instance if it exists
			if ($('#jquery_jplayer_modal').data('jPlayer')) {
				$('#jquery_jplayer_modal').jPlayer('destroy');
			}
			
			// Initialize jPlayer with improved configuration
			$('#jquery_jplayer_modal').jPlayer({
				ready: function() {
					$(this).jPlayer('setMedia', data.files);
				},
				ended: function() {
					// Handle end of playback
					$(this).jPlayer('pause');
				},
				error: function(event) {
					console.log('jPlayer Error:', event.jPlayer.error);
					// Try to recover from errors
					if (event.jPlayer.error.type === 'e_url_not_set') {
						$(this).jPlayer('setMedia', data.files);
					}
				},
				loadstart: function() {
					// Audio is starting to load
					console.log('Audio loading started');
				},
				progress: function(event) {
					// Audio is loading
					if (event.jPlayer.status.seekPercent === 100) {
						console.log('Audio fully loaded');
					}
				},
				canplay: function() {
					// Audio can start playing
					console.log('Audio ready to play');
				},
				swfPath: 'assets/js/jplayer',
				supplied: Object.keys(data.files).join(','),
				cssSelectorAncestor: '#jp_container_modal',
				wmode: 'window',
				useStateClassSkin: true,
				autoBlur: false,
				smoothPlayBar: true,
				keyEnabled: true,
				remainingDuration: true,
				toggleDuration: true,
				preload: 'auto',
				volume: 0.8,
				muted: false,
				backgroundColor: '#000000',
				cssSelectorAncestor: '#jp_container_modal'
			});
		} else {
			$('#recording-player-container').html('<div class="alert alert-danger">No recording available for playback.</div>');
		}
	}).fail(function() {
		$('#recording-player-container').html('<div class="alert alert-danger">Error loading recording.</div>');
	});
}

// Show graph modal
function showGraphModal() {
	$('#graphModal').modal('show');
	// Load default graph
	loadGraphData('calls_by_hour');
}

// Load graph data based on type
function loadGraphData(graphType) {
	// Show loading
	$('#cdr-chart-container').html('<div class="text-center"><i class="fa fa-spinner fa-spin fa-2x"></i><br>Loading graph data...</div>');
	
	// Get current search parameters
	var params = queryParams({});
	params.graph_type = graphType;
	
	$.post('ajax.php', {
		module: 'cdr',
		command: 'getGraphData',
		params: JSON.stringify(params)
	}, function(data) {
		if (data.status && data.chartData) {
			renderChart(graphType, data.chartData);
		} else {
			$('#cdr-chart-container').html('<div class="alert alert-warning">No data available for the selected criteria.</div>');
		}
	}).fail(function() {
		$('#cdr-chart-container').html('<div class="alert alert-danger">Error loading graph data.</div>');
	});
}

// Render chart using CanvasJS
function renderChart(graphType, chartData) {
	// Check if CanvasJS is loaded
	if (typeof CanvasJS === 'undefined') {
		// Try to load CanvasJS dynamically as fallback
		loadCanvasJSFallback(function() {
			if (typeof CanvasJS !== 'undefined') {
				renderChartWithCanvasJS(graphType, chartData);
			} else {
				renderChartFallback(graphType, chartData);
			}
		});
		return;
	}
	
	renderChartWithCanvasJS(graphType, chartData);
}

// Load CanvasJS as fallback
function loadCanvasJSFallback(callback) {
	$('#cdr-chart-container').html('<div class="text-center"><i class="fa fa-spinner fa-spin"></i> Loading chart library...</div>');
	
	// Try multiple possible paths
	var paths = [
		'modules/dashboard/assets/js/canvasjs.js',
		'../dashboard/assets/js/canvasjs.js',
		'https://canvasjs.com/assets/script/canvasjs.min.js'
	];
	
	function tryLoadPath(index) {
		if (index >= paths.length) {
			callback();
			return;
		}
		
		var script = document.createElement('script');
		script.src = paths[index];
		script.onload = function() {
			callback();
		};
		script.onerror = function() {
			tryLoadPath(index + 1);
		};
		document.head.appendChild(script);
	}
	
	tryLoadPath(0);
}

// Render chart with CanvasJS
function renderChartWithCanvasJS(graphType, chartData) {
	
	var chartOptions = {
		animationEnabled: true,
		theme: "light2",
		height: 380,
		width: 750, // Optimal width that fits well in container
		backgroundColor: "#FFFFFF",
		axisY: {
			includeZero: true
		},
		data: []
	};
	
	switch (graphType) {
		case 'calls_by_hour':
			chartOptions.title = { text: _('Calls by Hour') };
			chartOptions.axisX = { title: _('Hour of Day') };
			chartOptions.axisY.title = _('Number of Calls');
			chartOptions.data = [{
				type: "column",
				dataPoints: chartData.map(function(item) {
					return { label: item.hour + ':00', y: parseInt(item.call_count) };
				})
			}];
			break;
			
		case 'calls_by_day':
			chartOptions.title = { text: _('Calls by Day') };
			chartOptions.axisX = { title: _('Date') };
			chartOptions.axisY.title = _('Number of Calls');
			chartOptions.data = [{
				type: "column",
				dataPoints: chartData.map(function(item) {
					return { label: item.call_date, y: parseInt(item.call_count) };
				})
			}];
			break;
			
		case 'calls_by_disposition':
			chartOptions.title = { text: _('Calls by Disposition') };
			chartOptions.data = [{
				type: "pie",
				showInLegend: true,
				legendText: "{label}",
				indexLabel: "{label}: {y}",
				dataPoints: chartData.map(function(item) {
					return { label: item.disposition, y: parseInt(item.call_count) };
				})
			}];
			break;
			
		case 'duration_by_hour':
			chartOptions.title = { text: _('Call Duration by Hour') };
			chartOptions.axisX = { title: _('Hour of Day') };
			chartOptions.axisY.title = _('Total Duration (minutes)');
			chartOptions.data = [{
				type: "column",
				dataPoints: chartData.map(function(item) {
					return { label: item.hour + ':00', y: Math.round(parseInt(item.total_duration) / 60) };
				})
			}];
			break;
			
		case 'calls_by_source':
			chartOptions.title = { text: _('Top 10 Sources') };
			chartOptions.axisX = { title: _('Source Number') };
			chartOptions.axisY.title = _('Number of Calls');
			chartOptions.data = [{
				type: "bar",
				dataPoints: chartData.slice(0, 10).map(function(item) {
					return { label: item.src || 'Unknown', y: parseInt(item.call_count) };
				})
			}];
			break;
			
		case 'calls_by_destination':
			chartOptions.title = { text: _('Top 10 Destinations') };
			chartOptions.axisX = { title: _('Destination Number') };
			chartOptions.axisY.title = _('Number of Calls');
			chartOptions.data = [{
				type: "bar",
				dataPoints: chartData.slice(0, 10).map(function(item) {
					return { label: item.dst || 'Unknown', y: parseInt(item.call_count) };
				})
			}];
			break;
	}
	
	// Clear container and create chart
	$('#cdr-chart-container').empty();
	var chart = new CanvasJS.Chart("cdr-chart-container", chartOptions);
	chart.render();
}

// Fallback chart rendering when CanvasJS is not available
function renderChartFallback(graphType, chartData) {
	var html = '<div class="alert alert-info">';
	html += '<i class="fa fa-info-circle"></i> Chart library not available. Displaying data in table format.';
	html += '</div>';
	
	html += '<div class="table-responsive">';
	html += '<table class="table table-striped table-condensed">';
	
	switch (graphType) {
		case 'calls_by_hour':
			html += '<thead><tr><th>Hour</th><th>Number of Calls</th></tr></thead><tbody>';
			$.each(chartData, function(i, item) {
				html += '<tr><td>' + item.hour + ':00</td><td>' + item.call_count + '</td></tr>';
			});
			break;
			
		case 'calls_by_day':
			html += '<thead><tr><th>Date</th><th>Number of Calls</th></tr></thead><tbody>';
			$.each(chartData, function(i, item) {
				html += '<tr><td>' + item.call_date + '</td><td>' + item.call_count + '</td></tr>';
			});
			break;
			
		case 'calls_by_disposition':
			html += '<thead><tr><th>Disposition</th><th>Number of Calls</th></tr></thead><tbody>';
			$.each(chartData, function(i, item) {
				html += '<tr><td>' + item.disposition + '</td><td>' + item.call_count + '</td></tr>';
			});
			break;
			
		case 'duration_by_hour':
			html += '<thead><tr><th>Hour</th><th>Total Duration (minutes)</th></tr></thead><tbody>';
			$.each(chartData, function(i, item) {
				var minutes = Math.round(parseInt(item.total_duration) / 60);
				html += '<tr><td>' + item.hour + ':00</td><td>' + minutes + '</td></tr>';
			});
			break;
			
		case 'calls_by_source':
			html += '<thead><tr><th>Source</th><th>Number of Calls</th></tr></thead><tbody>';
			$.each(chartData.slice(0, 10), function(i, item) {
				html += '<tr><td>' + (item.src || 'Unknown') + '</td><td>' + item.call_count + '</td></tr>';
			});
			break;
			
		case 'calls_by_destination':
			html += '<thead><tr><th>Destination</th><th>Number of Calls</th></tr></thead><tbody>';
			$.each(chartData.slice(0, 10), function(i, item) {
				html += '<tr><td>' + (item.dst || 'Unknown') + '</td><td>' + item.call_count + '</td></tr>';
			});
			break;
	}
	
	html += '</tbody></table></div>';
	$('#cdr-chart-container').html(html);
}
