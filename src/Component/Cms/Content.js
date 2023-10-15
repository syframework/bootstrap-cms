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

	// Widget SyComponent
	let sycomponentslots = {};

	CKEDITOR.plugins.add('sycomponent', {
		requires: 'widget',
		init: function(editor) {
			editor.widgets.add('sycomponent', {
				upcast: function(element) {
					return (typeof element.attributes['data-sycomponent'] !== 'undefined' && element.attributes['data-sycomponent'].length > 0);
				},
				downcast: function(element) {
					if (typeof element.attributes['data-sycomponent'] !== 'undefined' && element.attributes['data-sycomponent'].length > 0) {
						let key = element.attributes['data-sycomponent'];
						let args = element.attributes['data-sycomponent-args'];
						if (args) key += args;
						element.setHtml(sycomponentslots[btoa(key)]);
						return element;
					}
				},
				mask: true
			});
		}
	});

	// Widget SyTranslate
	const sytranslations = {TRANSLATIONS};

	function sytranslateset(key, value) {
		if (!key || !value) return;
		fetch('{TRANSLATE_URL}', {
			method: 'POST',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded'
			},
			body: `key=${encodeURIComponent(key)}&value=${encodeURIComponent(value)}`
		}).then(response => {
			return response.json();
		}).catch(error => console.error('There has been a problem with your fetch operation:', error));
	}

	CKEDITOR.dialog.add('sytranslate', function (editor) {
		return {
			title: '{LANGUAGE}',
			minWidth: 300,
			minHeight: 50,
			contents: [
				{
					id: 'info',
					elements: [
						{
							id: 'key',
							type: 'text',
							style: 'width: 100%',
							label: '{TRANSLATE_KEY}',
							'default': '',
							required: true,
							validate: function () {
								if (!this.getValue()) return false;
							},
							setup: function( widget ) {
								this.setValue( widget.data.name );
							},
							commit: function( widget ) {
								widget.setData('name', this.getValue());
							}
						},
						{
							id: 'value',
							type: 'text',
							style: 'width: 100%',
							label: '{TRANSLATE_VALUE}',
							'default': '',
							required: true,
							validate: function () {
								if (!this.getValue()) return false;
							},
							setup: function( widget ) {
								this.setValue( widget.data.value );
							},
							commit: function( widget ) {
								widget.setData('value', this.getValue());
								document.querySelectorAll(`.sytranslate[data-key="${widget.data.name}"]`).forEach(function (element) {
									element.innerText = widget.data.value;
								});
							}
						}
					]
				}
			],
			onOk: function() {
				sytranslateset(this.getValueOf('info', 'key'), this.getValueOf('info', 'value'));
			}
		};
	});

	CKEDITOR.plugins.add('sytranslate', {
		requires: 'widget,dialog',

		onLoad: function() {
			// Register styles for placeholder widget frame.
			CKEDITOR.addCss( '.sytranslate{background-color:#ff0}' );
		},

		init: function (editor) {
			editor.widgets.add('sytranslate', {
				dialog: 'sytranslate',

				template: '<span class="sytranslate">{""}</span>',

				init: function() {
					var regex = /\{\".*\"\}/;
					if (!regex.test(this.element.getText().trim())) return;

					// Note that placeholder markup characters are stripped for the name.
					var key = this.element.getText().slice( 2, -2 );
					this.setData('name', key);
					this.element.setAttribute('data-key', key);
					var value = (key in sytranslations) ? sytranslations[key] : key;
					this.setData('value', value);
				},
				downcast: function() {
					return new CKEDITOR.htmlParser.text("{\"" + this.data.name + "\"}");
				},
				data: function() {
					this.element.setAttribute('data-key', this.data.name);
					this.element.setText(this.data.value);
				},
				mask: true
			});

			editor.ui.addButton( 'Sytranslate', {
				label: '{ADD_TRANSLATE}',
				command: 'sytranslate',
				toolbar: 'insert,5',
				icon: 'https://www.systemuicons.com/images/icons/create.svg'
			} );
		},

		afterInit: function( editor ) {
			var placeholderReplaceRegex = /\{\"([^\{\}])+\"\}/g;

			editor.dataProcessor.dataFilter.addRules( {
				text: function(text) {
					return text.replace( placeholderReplaceRegex, function( match ) {
						// Creating widget code.
						var widgetWrapper = null,
							innerElement = new CKEDITOR.htmlParser.element('span', {
								'class': 'sytranslate'
							});

						// Adds placeholder identifier as innertext.
						innerElement.add( new CKEDITOR.htmlParser.text( match ) );
						widgetWrapper = editor.widgets.wrapElement( innerElement, 'sytranslate' );

						// Return outerhtml of widget wrapper so it will be placed as replacement.
						return widgetWrapper.getOuterHtml();
					} );
				}
			} );
		}
	});

	// Widget SyWidget
	CKEDITOR.plugins.add('sywidget', {
		requires: 'widget',
		init: function(editor) {
			editor.widgets.add('sywidget', {
				upcast: function(element, data) {
					if (typeof element.attributes['data-sylock'] !== 'undefined') {
						if (element.attributes['data-sylock'] === 'attributes') {
							storeAttributes(element);
						} else {
							data.html = element.getHtml();
						}
						return true;
					}
					return false;
				},
				downcast: function(element) {
					if (typeof element.attributes['data-sylock-attributes'] !== 'undefined' && element.attributes['data-sylock'] === 'attributes') {
						element.setHtml(this.editables.content.getData());
						restoreAttributes(element);
						var res = new CKEDITOR.htmlParser.element(element.name, element.attributes);
						res.setHtml(element.getHtml());
					} else {
						var html = this.data.html;
						var res = new CKEDITOR.htmlParser.element(element.name, element.attributes);
						res.setHtml(html);
					}
					return res;
				},
				editables: {
					content: '[data-sylock="attributes"]'
				}
			});
		}
	});

	function storeAttributes(element) {
		element.attributes['data-sylock-attributes'] = JSON.stringify(element.attributes);

		if (typeof element.attributes['data-sylock'] !== 'undefined' && element.attributes['data-sylock'] !== 'attributes') return;

		element.children.forEach(function (element) {
			if (element.type === CKEDITOR.NODE_ELEMENT) {
				storeAttributes(element);
			}
		});
	}

	function restoreAttributes(element) {
		if (typeof element.attributes['data-sylock-attributes'] !== 'undefined') {
			element.attributes = JSON.parse(element.attributes['data-sylock-attributes']);
			delete element.attributes['data-sylock-attributes'];
		}

		element.children.forEach(function (element) {
			if (element.type === CKEDITOR.NODE_ELEMENT) {
				restoreAttributes(element);
			}
		});
	}

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

	$('#sy-btn-page-update-start').on('click', function(e) {
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
				$('#sy-content').html(res.html);

				// Execute js code
				eval(res.js);

				// Replace slots by components
				document.querySelectorAll('[data-sycomponent]').forEach(function (element) {
					let key = element.getAttribute('data-sycomponent');
					let args = element.getAttribute('data-sycomponent-args');
					if (args) key += args;
					if (sycomponents[btoa(key)]) {
						let slot = element.innerHTML;
						sycomponentslots[btoa(key)] = slot;
						element.innerHTML = sycomponents[btoa(key)];
					}
				});

				$('#sy-content').attr('contenteditable', true);
				if (!CKEDITOR.instances['sy-content']) {
					var editor = CKEDITOR.inline('sy-content', {
						language: '{LANG}',
						entities: false,
						title: false,
						startupFocus: true,
						linkShowAdvancedTab: false,
						clipboard_handleImages: false,
						filebrowserImageBrowseUrl: '{IMG_BROWSE}',
						filebrowserImageUploadUrl: '{IMG_UPLOAD_AJAX}',
						filebrowserBrowseUrl: '{FILE_BROWSE}',
						filebrowserUploadUrl: '{FILE_UPLOAD_AJAX}',
						filebrowserWindowWidth: 200,
						filebrowserWindowHeight: 400,
						imageUploadUrl: '{IMG_UPLOAD_AJAX}',
						uploadUrl: '{FILE_UPLOAD_AJAX}',
						extraPlugins: 'sharedspace,sycomponent,sywidget,sytranslate,tableresize,uploadimage,uploadfile',
						allowedContent: true,
						justifyClasses: [ 'text-left', 'text-center', 'text-right', 'text-justify' ],
						removePlugins: 'about,exportpdf',
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
						{ name: 'insert', items: [ 'Image', 'Table', 'HorizontalRule', 'Iframe', 'Sytranslate' ] },
						{ name: 'styles', items: [ 'Format' ] },
						{ name: 'colors', items: [ 'TextColor', 'BGColor' ] }
					];
				}
				$('#sy-btn-page-update-start').hide();
				$('#sy-btn-page-update-stop').removeClass("d-none");
			}
		});
	});

	$('#sy-btn-page-update-stop').on('click', function(e) {
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
		$.getJSON('{GET_URL}', function(res) {
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
});