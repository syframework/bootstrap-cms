(function () {
	<!-- BEGIN UPDATE_BLOCK -->
	document.getElementById('sy-btn-page-update-start').addEventListener('click', function (e) {
		e.preventDefault();
		var frame = document.getElementById('sy-content-iframe');
		if (!frame) return;
		frame.contentWindow.postMessage('start', '*');
		document.getElementById('sy-btn-page-update-start').classList.add("d-none");
		document.getElementById('sy-btn-page-update-stop').classList.remove("d-none");

		// Disable code edit button
		let codeButton = document.getElementById('sy-btn-code');
		if (!codeButton) return;
		codeButton.setAttribute('disabled', 'true');
	});

	document.getElementById('sy-btn-page-update-stop').addEventListener('click', function (e) {
		e.preventDefault();
		var frame = document.getElementById('sy-content-iframe');
		if (!frame) return;
		frame.contentWindow.postMessage('stop', '*');
		document.getElementById('sy-btn-page-update-start').classList.remove("d-none");
		document.getElementById('sy-btn-page-update-stop').classList.add("d-none");

		// Enable code edit button
		let codeButton = document.getElementById('sy-btn-code');
		if (!codeButton) return;
		codeButton.removeAttribute('disabled');
	});
	<!-- END UPDATE_BLOCK -->

	<!-- BEGIN DELETE_BLOCK -->
	document.getElementById('sy-btn-page-delete').addEventListener('click', function (e) {
		e.preventDefault();
		if (confirm((new DOMParser).parseFromString('{CONFIRM_DELETE}', 'text/html').documentElement.textContent)) {
			document.getElementById('{DELETE_FORM_ID}').submit();
		}
	});
	<!-- END DELETE_BLOCK -->

	<!-- BEGIN CODE_BLOCK -->
	let htmlLoaded = false;

	window.addEventListener("message", (event) => {
		if (event.data === 'saved') {
			htmlLoaded = false;
			loadHtml();
		}
	}, false);

	const codeEditorHtml = ace.edit('codearea_codearea_html_{ID}');
	const codeEditorCss = ace.edit('codearea_codearea_css_{ID}');
	const codeEditorJs = ace.edit('codearea_codearea_js_{ID}');

	function resizeCodeArea() {
		let codeEditorHeight = document.querySelector('#sy-code-modal .modal-body').offsetHeight;
		let codeEditorWidth = document.querySelector('#sy-code-modal .modal-body').offsetWidth;

		let htmlEditor = document.querySelector('#codearea_codearea_html_{ID}');
		htmlEditor.style.height = codeEditorHeight + 'px';
		htmlEditor.style.width = codeEditorWidth + 'px';
		codeEditorHtml.resize();

		let cssEditor = document.querySelector('#codearea_codearea_css_{ID}');
		cssEditor.style.height = codeEditorHeight + 'px';
		cssEditor.style.width = codeEditorWidth + 'px';
		codeEditorCss.resize();

		let jsEditor = document.querySelector('#codearea_codearea_js_{ID}');
		jsEditor.style.height = codeEditorHeight + 'px';
		jsEditor.style.width = codeEditorWidth + 'px';
		codeEditorJs.resize();
	}

	function loadHtml() {
		if (htmlLoaded) return;

		var timestamp = new Date().getTime();
		fetch('{GET_URL}&ts=' + timestamp)
			.then(response => response.json())
			.then(res => {
				if (res.status === 'ok') {
					ace.edit('codearea_codearea_html_{ID}').session.setValue(res.html);
					htmlLoaded = true;
					resizeCodeArea();
				}
			});
	}

	window.addEventListener('resize', resizeCodeArea);

	document.getElementById('sy-code-modal').addEventListener('show.bs.modal', function (e) {
		loadHtml();
		screenSplit(window.localStorage.getItem('screen-split-layout'));

		// Disable inline edit button
		let editButton = document.getElementById('sy-btn-page-update-start');
		if (!editButton) return;
		editButton.setAttribute('disabled', 'true');
	});

	document.getElementById('sy-code-modal').addEventListener('shown.bs.modal', function (e) {
		resizeCodeArea();
		showLastSelectedTab();
	});

	document.getElementById('sy-code-modal').addEventListener('hide.bs.modal', function (e) {
		screenSplitReset();

		// Disable inline edit button
		let editButton = document.getElementById('sy-btn-page-update-start');
		if (!editButton) return;
		editButton.removeAttribute('disabled');
	});

	document.querySelector('#sy-code-modal form').addEventListener('submit', function (e) {
		this.js.value = codeEditorJs.getValue();
		this.css.value = codeEditorCss.getValue();

		// Save editor state
		[codeEditorHtml, codeEditorCss, codeEditorJs].forEach(function (editor) {
			saveEditorFoldState(editor);
			saveEditorCursorState(editor);
			saveEditorScrollState(editor);
		});
	});

	let modals = ['#sy-new-page-modal', '#sy-update-page-modal', '#sy-code-modal'];
	modals.forEach(function (modalId) {
		if (!document.querySelector(modalId)) return;
		if (document.querySelector(modalId).querySelector('div.alert')) {
			var bsModal = new bootstrap.Modal(document.querySelector(modalId));
			bsModal.show();
		}
	});

	let alertElement = document.querySelector('#sy-code-modal div.alert');
	let errorMsg = alertElement ? alertElement.textContent : null;
	if (errorMsg) {
		if (errorMsg.startsWith('SCSS')) {
			var bsTab = new bootstrap.Tab(document.querySelector('#sy-css-tab'));
			bsTab.show();
		}
		flash(errorMsg, 'danger');
	}

	// Screen split
	function screenSplit(layout) {
		switch (layout) {
			case 'vertical':
				screenSplitVertical();
				break;

			case 'horizontal':
				screenSplitHorizontal();
				break;

			default:
				screenSplitFull();
				break;
		}
		resizeCodeArea();
	}

	function screenSplitReset() {
		const modal = document.getElementById('sy-code-modal');
		modal.style.position = '';
		modal.style.top = '0';
		modal.style.left = '0';
		modal.style.width = '100vw';
		modal.style.height = '100vh';

		const iframe = document.getElementById('sy-content-iframe');
		iframe.style.top = '0';
		iframe.style.width = '100vw';
		iframe.style.height = '100vh';

		const resizer = document.getElementById('resizer-vertical');
		resizer.style.display = 'none';
	}

	function screenSplitFull() {
		screenSplitReset();

		document.getElementById('btn-screen-split-reset').checked = true;
		window.localStorage.setItem('screen-split-layout', 'full');
	}

	function screenSplitVertical() {
		screenSplitReset();

		// Retrieve width from local storage
		let leftWidth = localStorage.getItem('sy-content-iframe-width');
		if (!leftWidth) leftWidth = 50;
		let rightWidth = 100 - leftWidth;

		const modal = document.getElementById('sy-code-modal');
		const resizer = document.getElementById('resizer-vertical');
		modal.style.position = 'absolute';
		modal.style.left = 'calc(' + leftWidth + '% + ' + resizer.offsetWidth + 'px)';
		modal.style.width = 'calc(' + rightWidth + 'vw - ' + resizer.offsetWidth + 'px)';

		document.getElementById('sy-content-iframe').style.width = leftWidth + 'vw';

		resizer.style.display = 'block';
		resizer.style.left = leftWidth + '%';

		document.getElementById('btn-screen-split-vertical').checked = true;
		window.localStorage.setItem('screen-split-layout', 'vertical');
	}

	function screenSplitHorizontal() {
		screenSplitReset();

		const modal = document.getElementById('sy-code-modal');
		modal.style.position = 'absolute';
		modal.style.height = '50vh';
		modal.style.top = '50%';

		const iframe = document.getElementById('sy-content-iframe');
		iframe.style.top = '0';
		iframe.style.height = '50vh';

		document.getElementById('btn-screen-split-horizontal').checked = true;
		window.localStorage.setItem('screen-split-layout', 'horizontal');
	}

	document.querySelectorAll('input[name="screen-split"]').forEach(function (radio) {
		radio.addEventListener('change', function (e) {
			screenSplit(e.target.value);
		});
	});

	// Screen resizer
	document.addEventListener('DOMContentLoaded', function () {
		// Query the element
		const resizer = document.getElementById('resizer-vertical');
		const leftSide = document.getElementById('sy-content-iframe');
		const rightSide = document.getElementById('sy-code-modal');
		const backdrop = document.getElementById('resizer-backdrop');

		// The current position of mouse
		let x = 0;
		let y = 0;
		let leftWidth = 0;

		function pauseEvent(e) {
			if(e.stopPropagation) e.stopPropagation();
			if(e.preventDefault) e.preventDefault();
			e.cancelBubble=true;
			e.returnValue=false;
			return false;
		}

		// Handle the mousedown event that's triggered when user drags the resizer
		const mouseDownHandler = function (e) {
			pauseEvent(e);

			// Get the current mouse position
			x = e.clientX;
			y = e.clientY;
			leftWidth = leftSide.getBoundingClientRect().width;

			// Show resizer backdrop
			backdrop.style.display = 'block';

			// Resizer style
			resizer.style.cursor = 'col-resize';
			backdrop.style.cursor = 'col-resize';
			document.body.style.cursor = 'col-resize';

			[leftSide, rightSide, backdrop].forEach(function (element) {
				element.style.userSelect = 'none';
				element.style.pointerEvents = 'none';
			});

			// Attach the listeners to document
			document.addEventListener('mousemove', mouseMoveHandler);
			document.addEventListener('mouseup', mouseUpHandler);
		};

		const mouseMoveHandler = function (e) {
			pauseEvent(e);

			// How far the mouse has been moved
			const dx = e.clientX - x;

			const newLeftWidth = ((leftWidth + dx) * 100) / window.innerWidth;
			const newRightWidth = 100 - newLeftWidth;

			// Min left width 10%
			if (newLeftWidth < 10) return;
			// Min right width 10%
			if (newRightWidth < 10) return;

			leftSide.style.width = newLeftWidth + 'vw';
			resizer.style.left = newLeftWidth + '%';

			rightSide.style.width = 'calc(' + newRightWidth + 'vw - ' + resizer.offsetWidth + 'px)';
			rightSide.style.left = 'calc(' + newLeftWidth + '% + ' + resizer.offsetWidth + 'px)';

			resizeCodeArea();

			// Save width in local storage
			localStorage.setItem('sy-content-iframe-width', newLeftWidth);
		};

		const mouseUpHandler = function () {
			resizer.style.removeProperty('cursor');
			backdrop.style.removeProperty('cursor');
			document.body.style.removeProperty('cursor');

			[leftSide, rightSide, backdrop].forEach(function (element) {
				element.style.removeProperty('user-select');
				element.style.removeProperty('pointer-events');
			});

			// Hide resizer pane
			backdrop.style.display = 'none';

			// Remove the handlers of mousemove and mouseup
			document.removeEventListener('mousemove', mouseMoveHandler);
			document.removeEventListener('mouseup', mouseUpHandler);
		};

		// Attach the handler
		resizer.addEventListener('mousedown', mouseDownHandler);
	});

	// Live preview
	function loadPreview() {
		const form = document.getElementById('sy-content-form');
		form.querySelector('input[name="html"]').value = codeEditorHtml.getValue();
		form.querySelector('input[name="css"]').value = codeEditorCss.getValue();
		form.querySelector('input[name="js"]').value = codeEditorJs.getValue();
		form.submit();
	}

	// Save the fold state
	function saveEditorFoldState(editor) {
		let id = editor.container.id;
		localStorage.setItem('folds_' + id, JSON.stringify(getFolds(editor.session.getAllFolds())));
		localStorage.setItem('crc32_' + id, CRC32.str(editor.session.getValue()));
	}

	function getFolds(folds, row = 0) {
		if (folds.length === 0) return [];
		let res = [];
		folds.forEach(function (fold) {
			res.push({
				start: {row: fold.start.row + row, column: fold.start.column},
				end: {row: fold.end.row + row, column: fold.end.column},
				placeholder: fold.placeholder,
				subFolds: getFolds(fold.subFolds, fold.start.row + row)
			})
		});
		return res;
	}

	// Load the fold state
	function loadEditorFoldState(editor) {
		let id = editor.container.id;
		let folds = JSON.parse(localStorage.getItem('folds_' + id));
		if (!folds) return;
		let crc32 = localStorage.getItem('crc32_' + id);
		if (CRC32.str(editor.session.getValue()) !== parseInt(crc32)) return;
		editor.session.addFolds(setFolds(folds));
	}

	var Fold = ace.require("ace/edit_session/fold").Fold;

	function setFolds(folds) {
		let res = [];
		folds.forEach(function (fold) {
			let f = new Fold(new ace.Range(fold.start.row, fold.start.column, fold.end.row, fold.end.column), fold.placeholder);
			if (fold.subFolds.length > 0) {
				setFolds(fold.subFolds).forEach(function (subfold) {
					f.addSubFold(subfold);
				});
			}
			res.push(f);
		});
		return res;
	}

	// Editor focus on tab change
	document.querySelectorAll('#sy-code-modal button[data-bs-toggle="tab"]').forEach(function (tabEl) {
		tabEl.addEventListener('shown.bs.tab', event => {
			let id = event.target.getAttribute('id');
			focus(id);
			window.localStorage.setItem('sy-code-tab', id);
		});
	});

	function focus(id) {
		switch (id) {
			case 'sy-css-tab':
				codeEditorCss.focus();
				break;

			case 'sy-js-tab':
				codeEditorJs.focus();
				break;

			default:
				codeEditorHtml.focus();
				break;
		}
	}

	function showTab(id) {
		let element = document.getElementById(id);
		if (!element) return;
		focus(id);
		bootstrap.Tab.getOrCreateInstance(element).show();
	}

	function showLastSelectedTab() {
		showTab(window.localStorage.getItem('sy-code-tab'));
	}

	// Content iframe scroll position
	const iframe = document.getElementById('sy-content-iframe');

	// Save content iframe scroll position
	function saveScrollPosition() {
		// Store the scroll position in the local storage
		localStorage.setItem('sy-content-iframe-x', iframe.contentWindow.scrollX);
		localStorage.setItem('sy-content-iframe-y', iframe.contentWindow.scrollY);
	}

	// Restore content iframe scroll position
	function restoreScrollPosition() {
		// Get the scroll position from the local storage
		var scrollX = localStorage.getItem('sy-content-iframe-x');
		var scrollY = localStorage.getItem('sy-content-iframe-y');

		// Scroll the iframe to the stored position
		iframe.contentWindow.scrollTo(scrollX, scrollY);
	}

	iframe.addEventListener('load', function () {
		restoreScrollPosition();
		iframe.contentWindow.addEventListener('scroll', saveScrollPosition);
	});

	// Editor scroll cursor position
	function saveEditorScrollState(editor) {
		localStorage.setItem('top_' + editor.container.id, editor.session.getScrollTop());
		localStorage.setItem('left_' + editor.container.id, editor.session.getScrollLeft());
	}

	function loadEditorScrollState(editor) {
		editor.session.setScrollLeft(localStorage.getItem('left_' + editor.container.id));
		editor.session.setScrollTop(localStorage.getItem('top_' + editor.container.id));
	}

	function saveEditorCursorState(editor) {
		localStorage.setItem('cursor_' + editor.container.id, JSON.stringify(editor.getCursorPosition()));
	}

	function loadEditorCursorState(editor) {
		let position = JSON.parse(localStorage.getItem('cursor_' + editor.container.id));
		if (!position) return;
		editor.moveCursorTo(position.row, position.column);
	}

	let code = {};
	let timeoutId;

	[codeEditorHtml, codeEditorCss, codeEditorJs].forEach(function (editor) {
		// Listen change event
		editor.session.on('change', function (delta) {
			let id = editor.container.id;
			if (delta.id === 1) {
				code[id] = editor.getValue();
				loadEditorState(editor);
				return;
			}
			if (code[id] === editor.getValue()) return;
			if (timeoutId) {
				clearTimeout(timeoutId);
			}
			// Reload preview only in coding mode
			if (!document.querySelector('#sy-code-modal').classList.contains('show')) return;
			timeoutId = setTimeout(loadPreview, 2000);
		});
	});

	function loadEditorState(editor) {
		setTimeout(function () {
			loadEditorFoldState(editor);
			loadEditorScrollState(editor);
			loadEditorCursorState(editor)
		}, 100);
	}
	<!-- END CODE_BLOCK -->

})();