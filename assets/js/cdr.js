function cdr_play(row_num, link) {
	$("#jquery_jplayer_"+(row_num - 1)).jPlayer({
		ready: function () {
		$(this).jPlayer("setMedia", {
			title: "Bubble",
			wav: link,
		});
		},
		swfPath: "/js",
		supplied: "wav",
		cssSelectorAncestor: "#jp_container_"+(row_num - 1)
	});
	$('.playback').hide('fast');
	$('#playback-'+(row_num - 1)).slideDown('fast');
}
