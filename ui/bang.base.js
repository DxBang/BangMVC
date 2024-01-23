var classNames = {};

function error(error) {
	if (!$('#error').length)  {
		$('body').append($('<div id="error"></div>'));
	}
	if (typeof(error) == 'object') {
		error = '<b>' + error.code + '</b> ' + error.message;
	}
	$('#error').append($('<div>' + error + '</div>'))
		.animate({scrollTop: $('#error').prop('scrollHeight')}, 200);
}

function sysWait(init) {
	switch (init) {
		case 1:
			$('html').addClass('cursor progress');
		break;
		case 2:
			$('html').addClass('cursor wait');
		break;
		default:
			$('html').removeClass('wait progress');
		break;
	}
}
var query;

function parseQueryString() {
	var r = {};
	items = window.location.search.substring(1).split('&');
	for (var i = 0; i < items.length; i++) {
		var item = items[i].split('=');
		r[decodeURIComponent(item[0])] = decodeURIComponent(item[1]);
	}
	return r;
}

$(document).ready(function() {
	query = parseQueryString();
	classNames = {};
	$.each($('[class]'), function (k,v) {
		if (v.className.length) {
			$.each(v.className.trim().split(/\s+/g), function (i,n) {
				if (typeof(classNames[n]) == 'undefined') {
					classNames[n] = [n.length, 1];
				}
				else {
					classNames[n][1]++;
				}
			});
		}
	});
	$('.row .toggle').click(function() {
		id = this.id;
		k = id.split('_')[1];
		$('#message_' + k).toggle(
			function() {
				$(this).addClass('hide');
			},
			function() {
				$(this).removeClass('hide');
			}
		);
	});
	if (window.location.pathname.indexOf('/search') >= 0) {
		
	}
}).ajaxStart(function (event) {
	$('#loading').fadeIn(200);
}).ajaxStop(function (event) {
	$('#loading').fadeOut(500);
}).ajaxSend(function (event, request, settings) {
//	console.log('ajaxSend', event, request, settings);
}).ajaxComplete(function (event, request, settings) {
//	console.log('ajaxComplete', event, request, settings);
}).ajaxError(function (event, request, settings, error) {
	console.log('ajaxError', {event: event}, {request: request}, {settings: settings}, {error: error});
	window.error('AJAX Error: ' + error);
});
