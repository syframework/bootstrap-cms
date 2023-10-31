(function () {

	<!-- BEGIN UPDATE_BLOCK -->
	document.getElementById('sy-btn-page-update-start').addEventListener('click', function(e) {
		e.preventDefault();
		var frame = document.getElementById('sy-content-iframe');
		if (frame) {
			frame.contentWindow.postMessage('start', '*');
			document.getElementById('sy-btn-page-update-start').classList.add("d-none");
			document.getElementById('sy-btn-page-update-stop').classList.remove("d-none");
		}
	});

	document.getElementById('sy-btn-page-update-stop').addEventListener('click', function(e) {
		e.preventDefault();
		var frame = document.getElementById('sy-content-iframe');
		if (frame) {
			frame.contentWindow.postMessage('stop', '*');
			document.getElementById('sy-btn-page-update-start').classList.remove("d-none");
			document.getElementById('sy-btn-page-update-stop').classList.add("d-none");
		}
	});
	<!-- END UPDATE_BLOCK -->

	<!-- BEGIN DELETE_BLOCK -->
	document.getElementById('sy-btn-page-delete').addEventListener('click', function(e) {
		e.preventDefault();
		if (confirm((new DOMParser).parseFromString('{CONFIRM_DELETE}', 'text/html').documentElement.textContent)) {
			document.getElementById('{DELETE_FORM_ID}').dispatchEvent(new Event('submit'));
		}
	});
	<!-- END DELETE_BLOCK -->

	<!-- BEGIN CODE_BLOCK -->
	let htmlLoaded = false;

	window.addEventListener("message", (event) => {
		if (event.data === 'saved') {
			htmlLoaded = false;
		}
	}, false);

	function resizeCodeArea() {
		let modalHeaderHeight = document.querySelector('#sy-code-modal .modal-header').offsetHeight;
		let modalFooterHeight = document.querySelector('#sy-code-modal .modal-footer').offsetHeight;
		let codeEditorHeight = window.innerHeight - modalHeaderHeight - modalFooterHeight;

		let htmlEditor = document.querySelector('#codearea_codearea_html_{ID}');
		htmlEditor.style.height = codeEditorHeight + 'px';
		ace.edit('codearea_codearea_html_{ID}').resize();

		let cssEditor = document.querySelector('#codearea_codearea_css_{ID}');
		cssEditor.style.height = codeEditorHeight + 'px';
		ace.edit('codearea_codearea_css_{ID}').resize();

		let jsEditor = document.querySelector('#codearea_codearea_js_{ID}');
		jsEditor.style.height = codeEditorHeight + 'px';
		ace.edit('codearea_codearea_js_{ID}').resize();
	}

	window.addEventListener('resize', resizeCodeArea);

	document.getElementById('sy-code-modal').addEventListener('shown.bs.modal', function (e) {
		resizeCodeArea();

		if (htmlLoaded) return;

		var timestamp = new Date().getTime();
		fetch('{GET_URL}&ts=' + timestamp)
			.then(response => response.json())
			.then(res => {
				if (res.status === 'ok') {
					ace.edit('codearea_codearea_html_{ID}').session.setValue(res.html);
					htmlLoaded = true;
				}
			});
	});

	document.querySelector('#sy-code-modal form').addEventListener('submit', function(e) {
		this.js.value = ace.edit('codearea_codearea_js_{ID}').getValue();
		this.css.value = ace.edit('codearea_codearea_css_{ID}').getValue();
	});

	let modals = ['#sy-new-page-modal', '#sy-update-page-modal', '#sy-code-modal'];
	modals.forEach(function(modalId) {
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
	<!-- END CODE_BLOCK -->

})();