function cdr_play(row_num, link, title) {
	$("#jquery_jplayer_"+(row_num - 1)).jPlayer({
		ready: function () {
		$(this).jPlayer("setMedia", {
			title: title,
			wav: link,
		});
		},
		swfPath: "/js",
		supplied: "wav",
		cssSelectorAncestor: "#jp_container_"+(row_num - 1)
	});
	$('.playback').hide('fast');
	$('#playback-'+(row_num - 1)).slideDown('fast', function(event){
		$("#jquery_jplayer_"+(row_num - 1)).jPlayer("play",0);
	});
}
