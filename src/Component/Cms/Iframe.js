(function () {

	var changed = false;
	var csrf = '{CSRF}';

	CKEDITOR.dtd.$removeEmpty['span'] = false;
	CKEDITOR.dtd.$removeEmpty['i'] = false;

	// Widget SyComponent
	let sycomponentslots = {};

	CKEDITOR.plugins.add('sycomponent', {
		requires: 'widget',
		init: function (editor) {
			editor.widgets.add('sycomponent', {
				upcast: function (element) {
					return (typeof element.attributes['data-sycomponent'] !== 'undefined' && element.attributes['data-sycomponent'].length > 0);
				},
				downcast: function (element) {
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
							setup: function (widget) {
								this.setValue(widget.data.name);
							},
							commit: function (widget) {
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
							setup: function (widget) {
								this.setValue(widget.data.value);
							},
							commit: function (widget) {
								widget.setData('value', this.getValue());
								document.querySelectorAll(`.sytranslate[data-key="${widget.data.name}"]`).forEach(function (element) {
									element.innerText = widget.data.value;
								});
							}
						}
					]
				}
			],
			onOk: function () {
				sytranslateset(this.getValueOf('info', 'key'), this.getValueOf('info', 'value'));
			}
		};
	});

	CKEDITOR.plugins.add('sytranslate', {
		requires: 'widget,dialog',

		onLoad: function () {
			// Register styles for placeholder widget frame.
			CKEDITOR.addCss('.sytranslate{border: 1px dashed #ccc}');
		},

		init: function (editor) {
			editor.widgets.add('sytranslate', {
				dialog: 'sytranslate',

				template: '<span class="sytranslate">{""}</span>',

				init: function () {
					var regex = /\{\".*\"\}/;
					if (!regex.test(this.element.getText().trim())) return;

					// Note that placeholder markup characters are stripped for the name.
					var key = this.element.getText().slice(2, -2);
					this.setData('name', key);
					this.element.setAttribute('data-key', key);
					var value = (key in sytranslations) ? sytranslations[key] : key;
					this.setData('value', value);
				},
				downcast: function () {
					return new CKEDITOR.htmlParser.text("{\"" + this.data.name + "\"}");
				},
				data: function () {
					this.element.setAttribute('data-key', this.data.name);
					this.element.setText(this.data.value);
				},
				mask: true
			});

			editor.ui.addButton('Sytranslate', {
				label: '{ADD_TRANSLATE}',
				command: 'sytranslate',
				toolbar: 'insert,5',
				icon: 'https://www.systemuicons.com/images/icons/create.svg'
			});
		},

		afterInit: function (editor) {
			var placeholderReplaceRegex = /\{\"([^\{\}])+\"\}/g;

			editor.dataProcessor.dataFilter.addRules({
				text: function (text) {
					return text.replace(placeholderReplaceRegex, function (match) {
						// Creating widget code.
						var widgetWrapper = null,
							innerElement = new CKEDITOR.htmlParser.element('span', {
								'class': 'sytranslate'
							});

						// Adds placeholder identifier as innertext.
						innerElement.add(new CKEDITOR.htmlParser.text(match));
						widgetWrapper = editor.widgets.wrapElement(innerElement, 'sytranslate');

						// Return outerhtml of widget wrapper so it will be placed as replacement.
						return widgetWrapper.getOuterHtml();
					});
				}
			});
		}
	});

	// Widget SyWidget
	CKEDITOR.plugins.add('sywidget', {
		requires: 'widget',
		init: function (editor) {
			editor.widgets.add('sywidget', {
				upcast: function (element, data) {
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
				downcast: function (element) {
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

	function draggable(element) {
		var pos1 = 0, pos2 = 0, pos3 = 0, pos4 = 0;
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

	function save(reload) {
		let data = {
			id: "{ID}",
			csrf: csrf,
			content: CKEDITOR.instances['sy-content'].getData()
		};

		fetch('{URL}', {
			method: 'POST',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded'
			},
			body: Object.keys(data).map(key => `${encodeURIComponent(key)}=${encodeURIComponent(data[key])}`).join("&")
		})
			.then(response => response.json())
			.then(res => {
				if (res.status === 'ko') {
					alert((new DOMParser).parseFromString(res.message, 'text/html').documentElement.textContent);
					if (res.csrf) {
						csrf = res.csrf;
						changed = true;
					} else {
						location.reload(true);
					}
				} else if (reload) {
					location.reload(true);
				}
				window.parent.postMessage('saved', '*');
			})
			.catch((error) => {
				console.error('Error:', error);
			});
		changed = false;
	}

	function startEdit() {
		var timestamp = new Date().getTime();
		fetch('{GET_URL}&ts=' + timestamp)
			.then(response => response.json())
			.then(res => {
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
					document.getElementById('sy-content').innerHTML = res.html;

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

					// Execute js code
					eval(res.js);
					window.dispatchEvent(new Event('load'));

					document.getElementById('sy-content').setAttribute('contenteditable', 'true');
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
							justifyClasses: ['text-left', 'text-center', 'text-right', 'text-justify'],
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
											img: function (el) {
												el.addClass('img-fluid');
											}
										}
									});
									draggable(document.getElementById('sy-page-topbar'));
								}
							}
						});

						editor.on('blur', function () {
							if (changed) save();
						});

						editor.on('change', function () {
							changed = true;
						});

						editor.config.toolbar = [
							{ name: 'document', items: ['Templates'] },
							{ name: 'clipboard', items: ['Undo', 'Redo'] },
							{ name: 'editing', items: ['Find', 'Replace', 'Scayt'] },
							{ name: 'basicstyles', items: ['Bold', 'Italic', 'Underline', 'Strike'] },
							{ name: 'paragraph', items: ['NumberedList', 'BulletedList', '-', 'JustifyLeft', 'JustifyCenter', 'JustifyRight'] },
							{ name: 'links', items: ['Link', 'Unlink'] },
							{ name: 'insert', items: ['Image', 'Table', 'HorizontalRule', 'Iframe', 'Sytranslate'] },
							{ name: 'styles', items: ['Format'] },
							{ name: 'colors', items: ['TextColor', 'BGColor'] }
						];
					}
				}
			})
			.catch(error => console.error('Error:', error));
	}

	function stopEdit() {
		if (changed) {
			save(true);
		} else {
			location.reload(true);
		}
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

	setInterval(function () {
		fetch('{CSRF_URL}').then(response => response.json()).then(data => {
			csrf = data.csrf;
		});
	}, 1200000);

	window.addEventListener('beforeunload', function (e) {
		if (changed) {
			var confirmationMessage = 'Unsaved changes';
			e.returnValue = confirmationMessage;
			return confirmationMessage;
		}
	});

	// Prevent navigation on empty href links
	document.querySelectorAll('a[href^="#"],a[href=""]').forEach(function (link) {
		link.setAttribute('target', '_self');
	});

})();