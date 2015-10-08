var CdrC = UCPMC.extend({
	init: function() {
		this.playing = null;
	},
	poll: function(data, url) {

	},
	display: function(event) {
		$(document).on("click", "[vm-pjax] a, a[vm-pjax]", function(event) {
			var container = $("#dashboard-content");
			$.pjax.click(event, { container: container });
		});
		$(".clickable").click(function(e) {
			var text = $(this).text();
			if (UCP.validMethod("Contactmanager", "showActionDialog")) {
				UCP.Modules.Contactmanager.showActionDialog("number", text, "phone");
			}
		});
		$(".subplay").click(function() {
			var id = $(this).data("msg"),
					date = $("#cdr-item-" + id + " .date").html(),
					clid = $("#cdr-item-" + id + " .clid .text").html();
			if (Cdr.playing === null || Cdr.playing != id) {
				if (Cdr.playing !== null) {
					$("#jquery_jplayer_" + Cdr.playing).jPlayer("stop", 0);
				}
				$("#jquery_jplayer_" + id).jPlayer({
					ready: function() {
					$(this).jPlayer("setMedia", {
						title: clid,
						wav: "?quietmode=1&module=cdr&command=listen&msgid=" + id + "&format=wav&type=playback&ext=" + extension,
						oga: "?quietmode=1&module=cdr&command=listen&msgid=" + id + "&format=oga&type=playback&ext=" + extension,
					});
					},
					swfPath: "/js",
					supplied: supportedMediaFormats,
					cssSelectorAncestor: "#jp_container_" + id
				}).bind($.jPlayer.event.loadstart, function(event) {
					$("#jp_container_" + id + " .jp-message-window").show();
					$("#jp_container_" + id + " .jp-message-window .message").css("color","");
					$("#jp_container_" + id + " .jp-seek-bar").css("background", 'url("modules/Cdr/assets/images/jplayer.blue.monday.seeking.gif") 0 0 repeat-x');
				});

				$("#cdr-playback-" + id + " .title-text").html(date + " " + clid);
				$(".cdr-playback").slideUp("fast");
				$("#cdr-playback-" + id).slideDown("fast", function() {
					$("#jquery_jplayer_" + id).bind($.jPlayer.event.error, function(event) {
						$("#jp_container_" + id + " .jp-message-window").show();
						$("#jp_container_" + id + " .message").text(event.jPlayer.error.message).css("color","red");
						$("#jp_container_" + id + " .jp-seek-bar").css("background","");
					});
					$("#jquery_jplayer_" + id).bind($.jPlayer.event.canplay, function(event) {
						$(".jp-message-window").fadeOut("fast");
						$("#jp_container_" + id + " .jp-seek-bar").css("background","");
					});
					$("#jquery_jplayer_" + id).bind($.jPlayer.event.play, function(event) { // Add a listener to report the time play began
						$("#cdr-item-" + id + " .subplay i").removeClass("fa-play").addClass("fa-pause");
					});
					$("#jquery_jplayer_" + id).bind($.jPlayer.event.pause, function(event) { // Add a listener to report the time play began
						$("#cdr-item-" + id + " .subplay i").removeClass("fa-pause").addClass("fa-play");
					});
					$("#jquery_jplayer_" + id).bind($.jPlayer.event.stop, function(event) { // Add a listener to report the time play began
						$("#cdr-item-" + id + " .subplay i").removeClass("fa-pause").addClass("fa-play");
					});
					$("#jquery_jplayer_" + id).jPlayer("play", 0);

				});
				Cdr.playing = id;
			} else {
				if ($("#cdr-item-" + Cdr.playing + " .subplay i").hasClass("fa-pause")) {
					$("#jquery_jplayer_" + Cdr.playing).jPlayer("pause");
				} else {
					$("#jquery_jplayer_" + Cdr.playing).jPlayer("play");
				}
			}
		});

	},
	hide: function(event) {
		$(document).off("click", "[vm-pjax] a, a[vm-pjax]");
		$(".clickable").off("click");
		if(Cdr.playing !== null) {
			$("#jquery_jplayer_" + Cdr.playing).jPlayer("stop", 0);
			Cdr.playing = null;
		}
	},
	windowState: function(state) {
		//console.log(state);
	},
	formatDescription: function (value, row, index) {
		var icons = '';
		$.each(row.icons, function(i, v) {
			icons += '<i class="fa '+v+'"></i> ';
		});
		return icons + " " + value;
	},
	formatActions: function (value, row, index) {
		if(row.recordingfile === '' || !showDownload) {
			return '';
		}
		var link = '<a class="download" alt="'+_("Download")+'" href="?quietmode=1&amp;module=cdr&amp;command=download&amp;msgid='+row.niceUniqueid+'&amp;type=download&amp;format='+row.recordingformat+'&amp;ext='+extension+'" target="_blank"><i class="fa fa-cloud-download"></i></a>';
		return link;
	},
	formatPlayback: function (value, row, index) {
		return '';
	},
	formatDuration: function (value, row, index) {
		return row.niceDuration;
	},
	formatDate: function(value, row, index) {
		return UCP.dateFormatter(value);
	},
}), Cdr = new CdrC();
