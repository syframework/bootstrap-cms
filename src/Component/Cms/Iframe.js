(function () {

	'use strict';

	const elementId = 'sy-content';
	let resExecuted = false;
	let resJs = '';

	function instance() {
		return window.SyWysiwyg ? window.SyWysiwyg.get(elementId) : null;
	}

	function locationReload() {
		const url = new URL(location.href);
		url.searchParams.set('ts', Date.now());
		location.href = url.href;
	}

	function componentKey(element) {
		let key = element.getAttribute('data-sycomponent') || '';
		const args = element.getAttribute('data-sycomponent-args');
		if (args) key += '\n' + args;
		return key;
	}

	/**
	 * Capture the rendered components before the source replaces the DOM.
	 * The engine reinjects this HTML after CKEditor parsing, so the source
	 * slot stays the only saved data (C73).
	 */
	function captureRendered() {
		const rendered = {};
		document.querySelectorAll('[data-sycomponent]').forEach(function (element) {
			rendered[componentKey(element)] = element.innerHTML;
		});
		return rendered;
	}

	function draggable(element) {
		if (!element) return;
		let pos1 = 0, pos2 = 0, pos3 = 0, pos4 = 0;
		element.onmousedown = dragMouseDown;

		function dragMouseDown(e) {
			e = e || window.event;
			e.preventDefault();
			pos3 = e.clientX;
			pos4 = e.clientY;
			document.onmouseup = closeDragElement;
			document.onmousemove = elementDrag;
		}

		function elementDrag(e) {
			e = e || window.event;
			e.preventDefault();
			pos1 = pos3 - e.clientX;
			pos2 = pos4 - e.clientY;
			pos3 = e.clientX;
			pos4 = e.clientY;
			element.style.top = (element.offsetTop - pos2) + 'px';
			element.style.left = (element.offsetLeft - pos1) + 'px';
		}

		function closeDragElement() {
			document.onmouseup = null;
			document.onmousemove = null;
		}
	}

	/** CKEditor writer rules and image class, moved from `instanceReady`. */
	function applyWriter(editor) {
		const writer = editor.dataProcessor.writer;

		['p', 'div'].forEach(function (tag) {
			writer.setRules(tag, {
				indent: true,
				breakBeforeOpen: true,
				breakAfterOpen: true,
				breakBeforeClose: true,
				breakAfterClose: true
			});
		});

		editor.dataProcessor.htmlFilter.addRules({
			elements: {
				img: function (element) {
					element.addClass('img-fluid');
				}
			}
		});
	}

	/** `res.js` runs once, after the engine is ready (§3.4.1). */
	function runResJs() {
		if (resExecuted || !resJs) return;
		resExecuted = true;

		import('data:text/javascript;charset=utf-8,' + encodeURIComponent(resJs))
			.then(function () {
				window.dispatchEvent(new Event('load'));
			})
			.catch(function (error) {
				console.error('CMS script failed', error);
			});
	}

	document.addEventListener('wysiwyg:ready', function (event) {
		if (event.detail.elementId !== elementId) return;
		applyWriter(event.detail.editor);
		draggable(document.getElementById('sy-page-topbar'));
		runResJs();
	});

	document.addEventListener('wysiwyg:saved', function (event) {
		if (event.detail.elementId !== elementId) return;
		window.parent.postMessage('saved', '*');
	});

	document.addEventListener('wysiwyg:error', function (event) {
		if (event.detail.elementId !== elementId) return;
		if (event.detail.retryable) {
			const message = event.detail.message || 'Unable to save the page';
			alert((new DOMParser).parseFromString(message, 'text/html').documentElement.textContent);
			return;
		}
		locationReload();
	});

	document.addEventListener('wysiwyg:stopped', function (event) {
		if (event.detail.elementId !== elementId) return;
		// A retryable failure keeps the editor open: the content is unsaved.
		if (!event.detail.saved && event.detail.dirty) return;
		locationReload();
	});

	/**
	 * The adapter owns the source loading: no `load-url` (D7). It captures the
	 * rendered components, replaces the DOM with the source, then starts the
	 * engine with the captured HTML.
	 */
	function startEdit() {
		const editor = instance();
		if (!editor || editor.isStarted()) return;

		const rendered = captureRendered();
		const url = new URL('{GET_URL}', window.location.origin);
		url.searchParams.set('ts', Date.now());

		fetch(url.href, {credentials: 'same-origin'})
			.then(function (response) {
				if (!response.ok) throw new Error('Content load failed');
				return response.json();
			})
			.then(function (res) {
				if (!res || res.status !== 'ok' || typeof res.html !== 'string') return;
				resJs = typeof res.js === 'string' ? res.js : '';
				document.getElementById(elementId).innerHTML = res.html;
				editor.setRenderedComponents(rendered);
				return editor.start();
			})
			.catch(function (error) {
				console.error('Error:', error);
			});
	}

	function stopEdit() {
		const editor = instance();
		if (!editor || !editor.isStarted()) {
			locationReload();
			return;
		}
		editor.stop({save: true});
	}

	window.addEventListener('message', function (event) {
		switch (event.data) {
			case 'start':
				startEdit();
				break;

			case 'stop':
				stopEdit();
				break;
		}
	}, false);

	// Prevent navigation on empty href links
	document.querySelectorAll('a[href^="#"],a[href=""]').forEach(function (link) {
		link.setAttribute('target', '_self');
	});

})();
