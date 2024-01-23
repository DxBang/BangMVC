'use strict';

function syntaxHighlight(json) {
	json = JSON.stringify(json, null, 4);
	json = json
		.replace(/&/g, '&amp;')
		.replace(/</g, '&lt;')
		.replace(/>/g, '&gt;');
	return json.replace(/("(\\u[a-zA-Z0-9]{4}|\\[^u]|[^\\"])*"(\s*:)?|\b(true|false|null)\b|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?)/g, function (m) {
		let c = 'number';
		if (/^"/.test(m)) {
			if (/:$/.test(m)) {
				c = 'key';
			} else {
				c = 'string';
			}
		} else if (/true|false/.test(m)) {
			c = 'boolean';
		} else if (/null/.test(m)) {
			c = 'null';
		}
		return '<span class="'+c+'">'+m+'</span>';
	});
}