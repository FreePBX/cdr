var CdrC = UCPC.extend({
	init: function(){
		this.playing = null;
	},
	poll: function(data,url){

	},
	display: function(event) {
		$(document).on('click', '[vm-pjax] a, a[vm-pjax]', function(event) {
			event.preventDefault(); //stop browser event
			var container = $('#dashboard-content');
			$.pjax.click(event, {container: container});
		});
		$('.subplay').click(function() {
			var id = $(this).data('msg');
			if(this.playing === null || this.playing != id) {
				if(this.playing !== null) {
					$("#jquery_jplayer_"+this.playing).jPlayer('stop',0);
				}
				$("#jquery_jplayer_"+id).jPlayer({
					ready: function () {
					$(this).jPlayer("setMedia", {
						title: 'words',
						wav: '?quietmode=1&module=cdr&command=listen&msgid='+id+'&format=wav&type=playback&ext='+extension,
					});
					},
					swfPath: "/js",
					supplied: "wav",
					cssSelectorAncestor: "#jp_container_"+id
				});
				var date = $('#cdr-item-'+id+' .date').html();
				var clid = $('#cdr-item-'+id+' .clid .text').html();
				$('#cdr-playback-'+id+' .title-text').html(date + " " + clid);
				$('.cdr-playback').slideUp('fast');
				$('#cdr-playback-'+id).slideDown('fast', function(){
					$("#jquery_jplayer_"+id).bind($.jPlayer.event.play, function(event) { // Add a listener to report the time play began
						$('#cdr-item-'+id+' .subplay i').removeClass('fa-play').addClass('fa-pause');
					});
					$("#jquery_jplayer_"+id).bind($.jPlayer.event.pause, function(event) { // Add a listener to report the time play began
						$('#cdr-item-'+id+' .subplay i').removeClass('fa-pause').addClass('fa-play');
					});
					$("#jquery_jplayer_"+id).bind($.jPlayer.event.stop, function(event) { // Add a listener to report the time play began
						$('#cdr-item-'+id+' .subplay i').removeClass('fa-pause').addClass('fa-play');
					});
					$("#jquery_jplayer_"+id).jPlayer('play',0);

				});
				this.playing = id;
			} else {
				if($('#cdr-item-'+this.playing+' .subplay i').hasClass('fa-pause')) {
					$("#jquery_jplayer_"+this.playing).jPlayer('pause');
				} else {
					$("#jquery_jplayer_"+this.playing).jPlayer('play');
				}
			}
		});
	},
	hide: function(event) {
		$(document).off('click', '[vm-pjax] a, a[vm-pjax]');
	},
	windowState: function(state) {
		//console.log(state);
	},
	saveSettings: function(data) {
		data.conference = conference;
		$.post( "index.php?quietmode=1&module=conferencespro&command=settings", data, function( data ) {
			$('.conferencesettings #message').text(data.message).addClass('alert-'+data.alert).fadeIn('fast', function() {
				$(this).delay(5000).fadeOut('fast');
			});
		});
	},
});
var Cdr = new CdrC();
