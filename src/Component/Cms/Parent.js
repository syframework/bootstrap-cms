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
					loadFoldState(codeEditorHtml, 'html_{ID}');
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
	});

	let modals = ['#sy-new-page-modal', '#sy-update-page-modal', '#sy-code-modal'];
	modals.forEach(function (modalId) {
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
	}

	function screenSplitFull() {
		screenSplitReset();

		document.getElementById('btn-screen-split-reset').checked = true;
		window.localStorage.setItem('screen-split-layout', 'full');
	}

	function screenSplitVertical() {
		screenSplitReset();

		const modal = document.getElementById('sy-code-modal');
		modal.style.position = 'absolute';
		modal.style.left = '50%';
		modal.style.width = '50vw';

		document.getElementById('sy-content-iframe').style.width = '50vw';

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

	// Live preview
	function loadPreview() {
		const form = document.getElementById('sy-content-form');
		form.querySelector('input[name="html"]').value = codeEditorHtml.getValue();
		form.querySelector('input[name="css"]').value = codeEditorCss.getValue();
		form.querySelector('input[name="js"]').value = codeEditorJs.getValue();
		form.submit();
	}

	let html, scss, js;
	let timeoutId;

	codeEditorHtml.session.on('change', function (delta) {
		if (delta.id === 1) {
			html = codeEditorHtml.getValue();
			return;
		}
		if (html === codeEditorHtml.getValue()) return;
		saveFoldState(codeEditorHtml, 'html_{ID}');
		if (timeoutId) {
			clearTimeout(timeoutId);
		}
		timeoutId = setTimeout(loadPreview, 2000);
	});
	codeEditorCss.session.on('change', function (delta) {
		if (delta.id === 1) {
			scss = codeEditorCss.getValue();
			loadFoldState(codeEditorCss, 'css_{ID}');
			return;
		}
		if (scss === codeEditorCss.getValue()) return;
		saveFoldState(codeEditorHtml, 'css_{ID}');
		if (timeoutId) {
			clearTimeout(timeoutId);
		}
		timeoutId = setTimeout(loadPreview, 2000);
	});
	codeEditorJs.session.on('change', function (delta) {
		if (delta.id === 1) {
			js = codeEditorJs.getValue();
			loadFoldState(codeEditorJs, 'js_{ID}');
			return;
		}
		if (js === codeEditorJs.getValue()) return;
		saveFoldState(codeEditorHtml, 'js_{ID}');
		if (timeoutId) {
			clearTimeout(timeoutId);
		}
		timeoutId = setTimeout(loadPreview, 2000);
	});

	// Listen code fold event
	codeEditorHtml.session.on('changeFold', function (e) {
		saveFoldState(codeEditorHtml, 'html_{ID}');
	});
	codeEditorCss.session.on('changeFold', function (e) {
		saveFoldState(codeEditorCss, 'css_{ID}');
	});
	codeEditorJs.session.on('changeFold', function (e) {
		saveFoldState(codeEditorJs, 'js_{ID}');
	});

	// Save the fold state
	function saveFoldState(editor, id) {
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
	function loadFoldState(editor, id) {
		var folds = JSON.parse(localStorage.getItem('folds_' + id));
		if (!folds) return;
		var crc32 = localStorage.getItem('crc32_' + id);
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
	<!-- END CODE_BLOCK -->

})();