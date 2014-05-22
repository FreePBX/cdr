var CdrC = UCPC.extend({
	init: function(){
	},
	poll: function(data,url){

	},
	display: function(event) {
		$(document).on('click', '[vm-pjax] a, a[vm-pjax]', function(event) {
			event.preventDefault(); //stop browser event
			var container = $('#dashboard-content');
			$.pjax.click(event, {container: container});
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
