(function () {

	<!-- BEGIN UPDATE_BLOCK -->
	$('#sy-btn-page-update-start').on('click', function(e) {
		e.preventDefault();

		var frame = document.getElementById('sy-content-iframe');
		if (frame) {
			frame.contentWindow.postMessage('start', '*');
			$('#sy-btn-page-update-start').hide();
			$('#sy-btn-page-update-stop').removeClass("d-none");
		}
	});

	$('#sy-btn-page-update-stop').on('click', function(e) {
		e.preventDefault();
		var frame = document.getElementById('sy-content-iframe');
		if (frame) {
			frame.contentWindow.postMessage('stop', '*');
			$('#sy-btn-page-update-start').show();
			$('#sy-btn-page-update-stop').addClass("d-none");
			htmlLoaded = false;
		}
	});
	<!-- END UPDATE_BLOCK -->

	<!-- BEGIN DELETE_BLOCK -->
	$('#sy-btn-page-delete').on('click', function(e) {
		e.preventDefault();
		if (confirm($('<div />').html("{CONFIRM_DELETE}").text())) {
			$('#{DELETE_FORM_ID}').trigger('submit');
		}
	});
	<!-- END DELETE_BLOCK -->

	<!-- BEGIN CODE_BLOCK -->
	let htmlLoaded = false;

	function resizeCodeArea() {
		let codeEditorHeight = window.innerHeight - $('#sy-code-modal').find('.modal-header').outerHeight() - $('#sy-code-modal').find('.modal-footer').outerHeight();
		$('#codearea_codearea_html_{ID}').height(codeEditorHeight);
		ace.edit('codearea_codearea_html_{ID}').resize();
		$('#codearea_codearea_css_{ID}').height(codeEditorHeight);
		ace.edit('codearea_codearea_css_{ID}').resize();
		$('#codearea_codearea_js_{ID}').height(codeEditorHeight);
		ace.edit('codearea_codearea_js_{ID}').resize();
	}

	window.addEventListener('resize', resizeCodeArea);

	$('#sy-code-modal').on('shown.bs.modal', function (e) {
		resizeCodeArea();

		if (htmlLoaded) return;

		var timestamp = new Date().getTime();
		$.getJSON('{GET_URL}&ts=' + timestamp, function(res) {
			if (res.status === 'ok') {
				ace.edit('codearea_codearea_html_{ID}').session.setValue(res.html);
				htmlLoaded = true;
			}
		});
	});

	$('#sy-code-modal form').on('submit', function(e) {
		let code = ace.edit('codearea_codearea_js_{ID}').getValue();
		this.js.value = code;
		this.css.value = ace.edit('codearea_codearea_css_{ID}').getValue();
	});

	$('#sy-new-page-modal').has('div.alert').modal('show');
	$('#sy-update-page-modal').has('div.alert').modal('show');
	$('#sy-code-modal').has('div.alert').modal('show');

	// Error message
	let errorMsg = $('#sy-code-modal div.alert').text();
	if (errorMsg) {
		if (errorMsg.startsWith('SCSS')) {
			$('#sy-css-tab').tab('show');
		}
		flash(errorMsg, 'danger');
	}
	<!-- END CODE_BLOCK -->

})();