(function ($) {
	$.fn.drags = function (opt) {

		opt = $.extend({ handle: "", cursor: "move" }, opt);

		if (opt.handle === "") {
			var $el = this;
		} else {
			var $el = this.find(opt.handle);
		}

		return $el.css('cursor', opt.cursor).on("mousedown", function (e) {
			if (opt.handle === "") {
				var $drag = $(this).addClass('draggable');
			} else {
				var $drag = $(this).addClass('active-handle').parent().addClass('draggable');
			}
			var z_idx = $drag.css('z-index'),
				drg_h = $drag.outerHeight(),
				drg_w = $drag.outerWidth(),
				pos_y = $drag.offset().top + drg_h - e.pageY,
				pos_x = $drag.offset().left + drg_w - e.pageX;
			$drag.css('z-index', 1060).parents().on("mousemove", function (e) {
				$('.draggable').offset({
					top: e.pageY + pos_y - drg_h,
					left: e.pageX + pos_x - drg_w
				}).on("mouseup", function () {
					$(this).removeClass('draggable').css('z-index', z_idx);
				});
			});
			e.preventDefault(); // disable selection
		}).on("mouseup", function () {
			if (opt.handle === "") {
				$(this).removeClass('draggable');
			} else {
				$(this).removeClass('active-handle').parent().removeClass('draggable');
			}
		});

	}
})(jQuery);

$(function() {
	<!-- BEGIN UPDATE_BLOCK -->
	var changed = false;
	var csrf = "{CSRF}";

	CKEDITOR.dtd.$removeEmpty['span'] = false;
	CKEDITOR.dtd.$removeEmpty['i'] = false;
	CKEDITOR.plugins.addExternal('sycomponent', '{CKEDITOR_ROOT}/plugins/sycomponent/');
	CKEDITOR.plugins.addExternal('sywidget', '{CKEDITOR_ROOT}/plugins/sywidget/');

	function save(reload) {
		$.post("{URL}", {
			id: "{ID}",
			csrf: csrf,
			content: CKEDITOR.instances['sy-content'].getData()
		}, function(res) {
			if (res.status === 'ko') {
				alert($('<p/>').html(res.message).text());
				if (res.csrf) {
					csrf = res.csrf;
					changed = true;
				} else {
					location.reload(true);
				}
			} else if (reload) {
				location.reload(true);
			}
		}, 'json');
		changed = false;
	}

	$('#sy-btn-page-update-start').click(function(e) {
		e.preventDefault();
		$.getJSON('{GET_URL}', function(res) {
			if (res.status === 'ok') {
				// Extract sycomponents
				let sycomponents = {};
				document.querySelectorAll('[data-sycomponent]').forEach(function (element) {
					let key = element.getAttribute('data-sycomponent');
					let args = element.getAttribute('data-sycomponent-args');
					if (args) key += args;
					sycomponents[btoa(key)] = element.innerHTML;
				});

				// Replace current html by template source code
				$('#sy-content').html(res.content);

				// Replace slots by components
				document.querySelectorAll('[data-sycomponent]').forEach(function (element) {
					let key = element.getAttribute('data-sycomponent');
					let args = element.getAttribute('data-sycomponent-args');
					if (args) key += args;
					if (sycomponents[btoa(key)]) {
						let slot = element.innerHTML;
						element.setAttribute('data-sycomponent-slot', slot);
						element.innerHTML = sycomponents[btoa(key)];
					}
				});

				$('#sy-content').attr('contenteditable', true);
				if (!CKEDITOR.instances['sy-content']) {
					var editor = CKEDITOR.inline('sy-content', {
						entities: false,
						title: false,
						startupFocus: true,
						linkShowAdvancedTab: false,
						filebrowserImageBrowseUrl: '{IMG_BROWSE}',
						filebrowserImageUploadUrl: '{IMG_UPLOAD_AJAX}',
						filebrowserBrowseUrl: '{FILE_BROWSE}',
						filebrowserUploadUrl: '{FILE_UPLOAD_AJAX}',
						filebrowserWindowWidth: 200,
						filebrowserWindowHeight: 400,
						imageUploadUrl: '{IMG_UPLOAD_AJAX}',
						uploadUrl: '{FILE_UPLOAD_AJAX}',
						extraPlugins: 'sharedspace,sycomponent,sywidget,tableresize,uploadimage,uploadfile',
						allowedContent: true,
						justifyClasses: [ 'text-left', 'text-center', 'text-right', 'text-justify' ],
						disallowedContent: 'script; *[on*]; img{width,height}',
						removePlugins: 'about',
						templates: 'websyte',
						templates_files: ['{CKEDITOR_ROOT}/templates.js'],
						sharedSpaces: {
							top: 'sy-page-topbar',
							bottom: 'sy-page-bottombar'
						},
						on: {
							instanceReady: function (ev) {
								this.dataProcessor.writer.setRules('p', {
									indent: true,
									breakBeforeOpen: true,
									breakAfterOpen: true,
									breakBeforeClose: true,
									breakAfterClose: true
								});
								this.dataProcessor.writer.setRules('div', {
									indent: true,
									breakBeforeOpen: true,
									breakAfterOpen: true,
									breakBeforeClose: true,
									breakAfterClose: true
								});
								this.dataProcessor.htmlFilter.addRules({
									elements: {
										img: function(el) {
											el.addClass('img-fluid');
										}
									}
								});
								$('#sy-page-topbar').drags();
							}
						}
					});

					editor.on('blur', function() {
						if (changed) save();
					});

					editor.on('change', function() {
						changed = true;
					});

					editor.config.toolbar = [
						{ name: 'document', items: [ 'Templates' ] },
						{ name: 'clipboard', items: [ 'Undo', 'Redo' ] },
						{ name: 'editing', items: [ 'Find', 'Replace', 'Scayt' ] },
						{ name: 'basicstyles', items: [ 'Bold', 'Italic', 'Underline', 'Strike' ] },
						{ name: 'paragraph', items: [ 'NumberedList', 'BulletedList', '-', 'JustifyLeft', 'JustifyCenter', 'JustifyRight' ] },
						{ name: 'links', items: [ 'Link', 'Unlink' ] },
						{ name: 'insert', items: [ 'Image', 'Table', 'HorizontalRule', 'Iframe' ] },
						{ name: 'styles', items: [ 'Format' ] },
						{ name: 'colors', items: [ 'TextColor', 'BGColor' ] }
					];
				}
				$('#sy-btn-page-update-start').hide();
				$('#sy-btn-page-update-stop').removeClass("d-none");
			}
		});
	});

	$('#sy-btn-page-update-stop').click(function(e) {
		e.preventDefault();
		if (changed) {
			save(true);
		} else {
			location.reload(true);
		}
	});

	setInterval(function() {
		fetch('{CSRF_URL}').then(response => response.json()).then(data => {
			csrf = data.csrf;
		});
	}, 1200000);

	window.addEventListener("beforeunload", function (e) {
		if (changed) {
			var confirmationMessage = 'Unsaved changes';
			e.returnValue = confirmationMessage;
			return confirmationMessage;
		}
	});

	<!-- END UPDATE_BLOCK -->

	<!-- BEGIN DELETE_BLOCK -->
	$('#sy-btn-page-delete').click(function(e) {
		e.preventDefault();
		if (confirm($('<div />').html("{CONFIRM_DELETE}").text())) {
			$('#{DELETE_FORM_ID}').submit();
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
		$.getJSON('{GET_URL}', function(res) {
			if (res.status === 'ok') {
				ace.edit('codearea_codearea_html_{ID}').session.setValue(res.content);
				htmlLoaded = true;
			}
		});
	});

	$('#sy-code-modal form').submit(function(e) {
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
});