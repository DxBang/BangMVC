/*
class Bang {
	constructor(f,n) {
		console.log(typeof f, f);
		switch (typeof f) {
			case 'function':
			break;
			case 'string':
			break;
			case 'object':
			break;

		}
	}
	fetch(url) {
		console.log(typeof url, url);
	}

}

let b = function(selector) {
	return new Bang(selector);
}

function _(f,n) {
	this.f = f;
	console.log(typeof f);
	switch (typeof f) {
		case 'function':
		break;
		case 'string':
		break;
		case 'object':
		break;

	}
	this.fetch = function(url) {
		this._url = url;
	};
	this.getURL = function() {
		return this._url;
	}
}
_.prototype.html = function(html) {
	console.log(typeof html, html);
};
*/