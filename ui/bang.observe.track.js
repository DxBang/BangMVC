"use strict";

window.uwuntuObserve = window.uwuntuObserve || [];
window.uwuntuObserveSeen = window.uwuntuObserveSeen || [];
window.uwuntuTrack = window.uwuntuTrack || [];

const uwuntu = {
	_debug: false,
	trackLimit: 10,
	online: true,
	state: "init",
	timer: null,
	ignoreAutoCollection: ["uwuntu", "uwuntuId", "uwuntuJson",],
	init: () => {
		uwuntu._debug = (document.cookie.indexOf("uwuntu_debug=1") > -1);
		["online", "offline"].forEach((event) => {
			window.addEventListener(event, () => {
				uwuntu.online = navigator.onLine;
				if (uwuntu.online) {
					uwuntu.send();
				}
			});
		});
		["pagehide", "freeze"].forEach((event) => {
			window.addEventListener(event, () => {
				uwuntu.send()
			});
		});
		document.addEventListener("visibilitychange", () => {
			uwuntu.send()
		}, false);
		if (document.readyState !== "complete") {
			document.addEventListener("DOMContentLoaded", () => {
				uwuntu.scan("loaded");
			}, false);
		}
		uwuntu.scan("init"); uwuntu.timer = setInterval(() => {
			uwuntu.send();
		}, 5000);
	},
	allowed() {
		return parseInt(
			(navigator.msDoNotTrack !== undefined && !!navigator.msDoNotTrack ? 1 : 0) ||
			(window.doNotTrack !== undefined && !!window.doNotTrack ? 1 : 0) ||
			(navigator.doNotTrack !== undefined && !!navigator.doNotTrack ? 1 : 0),
			10
		) === 0;
	},
	debug: (...args) => {
		if (uwuntu._debug) {
			console.log(...args);
		}
	},
	track: (track) => {
		window.uwuntuTrack.push(track);
	},
	observe: (observe) => {
		window.uwuntuObserve.push(observe);
	},
	add: (element) => {
		if (element.dataset.uwuntuTag) {
			return;
		}
		return uwuntu.observe(element);
	},
	scan: (tag) => {
		if (!uwuntu.allowed()) { return; }
		uwuntu.state = tag;
		uwuntu.debug("uwuntu.scan()", tag);
		document.querySelectorAll("[data-uwuntu]:not([data-uwuntu-tag])").forEach(element => {
			uwuntu.add(element);
		});
	},
	send: () => {
		uwuntu.debug("uwuntu.send()"); if (!uwuntu.online) { return; }
		if (window.uwuntuTrack.length == 0) return;
		window.uwuntuTrack.forEach((track, idx) => {
			let eventName = 'uwuntu_' + track.event;
			if ("zaraz" in window && "track" in window.zaraz) {
				window.zaraz.track(eventName, track);
			}
			if (typeof window.gtag === "function") {
				uwuntu.debug('gtag', eventName, track);
				gtag('event', eventName, track);
			}
		});
		window.uwuntuTrack.splice(0, window.uwuntuTrack.length);
	},
	camelToSnake: (str) => {
		return str.replace(/[A-Z]/g, letter => `_${letter.toLowerCase()}`);
	},
	collectData: (element) => {
		let data = {
			id: element.id || element.dataset.uwuntuId, type: element.dataset.uwuntuType || element.tagName.toLowerCase() || "unknown",
			data: element.dataset.uwuntuData || element.value || element.href || element.src || element.textContent.substring(0, 32).trim() || "unknown",
			element: `${element.offsetWidth}x${element.offsetHeight}`,
		};
		if (element.dataset.uwuntuJson) {
			let json = JSON.parse(element.dataset.uwuntuJson);
			Object.keys(json).forEach(key => {
				data[key] = json[key];
			});
		}
		Object.keys(element.dataset).forEach(key => {
			if (uwuntu.ignoreAutoCollection.includes(key)) return;
			if (key.indexOf("uwuntu") == 0) {
				let snake = uwuntu.camelToSnake(key),
					snake_key = snake.replace("uwuntu_", "");
				data[snake_key] = element.dataset[key];
			}
		});
		return data;
	}
};

uwuntu.init();
const uwuntuObserver = new IntersectionObserver((entries, observerOptions) => {
	uwuntu.debug("uwuntuObserver", entries, observerOptions); entries.forEach(entry => {
		let id = entry.target.id || entry.target.dataset.uwuntuId;
		if (typeof entry.isVisible == 'undefined') {
			entry.isVisible = true;
		}
		if (entry.isIntersecting && entry.isVisible) {
			if (entry.target.dataset.uwuntuObserveZIndex && parseInt(getComputedStyle(entry.target).zIndex) < parseInt(entry.target.dataset.uwuntuObserveZIndex)) {
				return;
			}
			window.uwuntuObserveSeen.push(id);
			return window.uwuntuTrack.push({
				event: "view",
				element: entry.target
			});
		}
		if (window.uwuntuObserveSeen.includes(id)) {
			if (entry.target.dataset.uwuntuObserveZIndex && parseInt(getComputedStyle(entry.target).zIndex) > parseInt(entry.target.dataset.uwuntuObserveZIndex)) {
				return;
			}
			window.uwuntuObserveSeen.splice(window.uwuntuObserveSeen.indexOf(id), 1);
			return window.uwuntuTrack.push({
				event: "unview",
				element: entry.target
			});
		}
	});
}, {
	rootMargin: "0px",
	delay: 2000,
	threshold: 0.3,
	trackVisibility: true,
});
window._uwuntuObserve = [...window.uwuntuObserve];
Object.defineProperty(window.uwuntuObserve, "push", {
	value: function (observe) {
		uwuntu.debug("window.uwuntuObserve.push()", typeof observe, observe);
		if (typeof observe == "string") {
			observe = document.getElementById(observe) || document.querySelector(`[data-uwuntu-id="${observe}"]`);
		}
		if (observe instanceof HTMLElement) {
			if (observe.dataset.uwuntuTag) {
				return;
			}
			if (!observe.dataset.uwuntu) {
				observe.dataset.uwuntu = "view";
			}
			let id = observe.id || observe.dataset.uwuntuId,
				events = observe.dataset.uwuntu.split(",");
			if (!id) {
				return console.error("uwuntu.observe.push() element missing id or data-uwuntu-id", observe);
			}
			if (events.includes("all")) {
				events = ["view", "hover", "click", "change"];
			}
			events.forEach(event => {
				uwuntu.debug(`trigger ${event} on ${id}`);
				switch (event) {
					case "hover":
					case "touch":
						observe.addEventListener("mouseenter", () => {
							uwuntu.track({ event: "hover", element: observe });
						}, {
							passive: true
						});
						observe.addEventListener("touchstart", () => {
							uwuntu.track({ event: "touch", element: observe });
						}, {
							passive: true
						});
					break;
					case "click":
						observe.addEventListener("click", () => {
							uwuntu.track({ event: "click", element: observe });
						}, {
							passive: true
						});
					break;
					case "input":
					case "watch":
					case "change":
						observe.addEventListener("change", () => {
							uwuntu.track({ event: "change", element: observe });
						}, {
							passive: true
						});
					break;
					case "view":
						uwuntuObserver.observe(observe);
					break;
				}
			});
			observe.dataset.uwuntuTag = uwuntu.state;
			return;
		}
		return console.warn(`Unable to observe ${observe}`);
	}
});

window._uwuntuObserve.forEach(id => {
	uwuntu.state = "capture";
	window.uwuntuObserve.push(id);
});
delete window._uwuntuObserve;
window._uwuntuTrack = [...window.uwuntuTrack];
Object.defineProperty(window.uwuntuTrack, "push", {
	value: function (track) {
		if (!uwuntu.allowed()) { return; }
		uwuntu.debug("window.uwuntuTrack.push()", typeof track, track);
		if (track == null) {
			return console.warn("uwuntuTrack.push() is null");
		}
		let element;
		switch (typeof track) {
			case "undefined":
				return;
			case "string":
				element = document.getElementById(track) || document.querySelector(`[data-uwuntu-id="${track}"]`);
			break;
			case "object":
				if (track instanceof HTMLElement) {
					element = track;
				}
				else if (track.element instanceof HTMLElement) {
					element = track.element;
				}
			break;
		}
		if (element) {
			track = {
				...uwuntu.collectData(element), ...{
					event: track.event || "triggered",
					element: `${element.offsetWidth}x${element.offsetHeight}`,
				},
			}
			if (element.dataset.uwuntuOwner) {
				let owner = document.getElementById(element.dataset.uwuntuOwner) || document.querySelector(`[data-uwuntu-id="${element.dataset.uwuntuOwner}"]`);
				if (owner) {
					track = {
						...uwuntu.collectData(owner),
						...track,
					};
				}
			}
		}
		track = {
			...track,
			...{
				location: document.location.href,
				timestamp: Date.now(),
				user_agent: navigator.userAgent,
				screen: `${window.screen.width}x${window.screen.height}`,
				window: `${window.innerWidth}x${window.innerHeight}`,
				scroll: `${window.scrollX}x${window.scrollY}`,
				scroll_percentage: `${Math.round((window.scrollY / (document.documentElement.scrollHeight - window.innerHeight) || 0) * 100)}%`,
				referrer: document.referrer,
				title: document.title,
				language: navigator.language,
				platform: navigator.platform,
			}
		};
		Array.prototype.push.call(this, track);
		if (this.length >= uwuntu.trackLimit) {
			uwuntu.send();
		}
	}
});

window._uwuntuTrack.forEach(track => {
	window.uwuntuTrack.push(track);
});
delete window._uwuntuTrack;
