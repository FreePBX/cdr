var playing = null;
function cdr_play(rowNum, link, title) {
	var playerId = (rowNum - 1);
	if (playing !== null && playing != playerId) {
		$("#jquery_jplayer_" + playing).jPlayer("stop", 0);
		playing = playerId;
	} else if (playing === null) {
		playing = playerId;
	}
	$("#jquery_jplayer_" + playerId).jPlayer({
		ready: function() {
		$(this).jPlayer("setMedia", {
			title: title,
			wav: link
		});
		},
		swfPath: "/js",
		supplied: "wav",
		cssSelectorAncestor: "#jp_container_" + playerId
	});
	$(".playback").hide("fast");
	$("#playback-" + playerId).slideDown("fast", function(event) {
		$("#jquery_jplayer_" + playerId).jPlayer("play", 0);
	});
}
