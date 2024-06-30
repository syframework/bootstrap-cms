import * as Y from 'https://cdn.jsdelivr.net/npm/yjs/+esm';
import Peer from 'https://cdn.jsdelivr.net/npm/peerjs/+esm';

class Node extends EventTarget {

	static INITIALIZING = 0;
	static INITIALIZED = 1;
	static CONNECTING = 2;
	static CONNECTED = 3;
	static DISCONNECTED = 4;

	#masterNodeId;

	#id;

	#peer;

	#connections;

	#isMaster;

	#status;

	constructor(id) {
		super();
		this.#masterNodeId = id;
		this.#isMaster = false;
		this.#status = Node.INITIALIZING;
		this.#connections = new Map();
		this.#initPeer();
	}

	destroy() {
		this.#peer.destroy();
	}

	send(data, peerId) {
		const connection = this.#connections.get(peerId);
		if (!connection) return;
		if (!connection.open) return;
		console.debug('Send data to peer', peerId, data);
		connection.send(data);
	}

	broadcast(data) {
		console.debug('Broadcast data', data);
		this.#connections.forEach(connection => {
			if (!connection.open) return;
			connection.send(data);
		});
	}

	getStatus() {
		return this.#status;
	}

	getId() {
		if (!this.#id) {
			this.#id = this.#peer.id;
		}
		return this.#id;
	}

	isMaster() {
		return this.#isMaster;
	}

	isDisconnected() {
		return this.#status === Node.DISCONNECTED;
	}

	#removeConnection(connection) {
		console.debug('Remove connection', connection.peer);
		this.#connections.delete(connection.peer);
		if (this.#connections.size === 0) {
			console.debug('I am disconnected');
			this.#status = Node.DISCONNECTED;
		}
	}

	#initPeer() {
		this.#peer = new Peer(this.#masterNodeId, { reliable: true });
		this.#peer.on('error', error => {
			if (error.type !== 'unavailable-id') return;
			this.#peer = new Peer({ reliable: true });
			this.#peer.on('open', id => {
				this.#status = Node.INITIALIZED;
				console.debug('I am the node', id);

				const connection = this.#peer.connect(this.#masterNodeId);
				this.#status = Node.CONNECTING;
				console.debug('Connecting to', connection.peer);

				connection.on('open', () => {
					console.debug('Connected to', connection.peer);
					this.#connections.set(connection.peer, connection);
					this.#status = Node.CONNECTED;
				});

				connection.on('data', data => {
					console.debug('Data received from', connection.peer, data);
					this.dispatchEvent(new CustomEvent('data', { detail: data }));
				});

				connection.on('close', () => {
					console.debug('Connection closed with', connection.peer);
					this.#removeConnection(connection);
				});

				connection.on('error', console.error);

				this.dispatchEvent(new Event('open'));
			});

			this.#peer.on('disconnected', () => {
				console.debug('Peer disconnected');
				this.#status = Node.DISCONNECTED;
			});

			this.#peer.on('close', () => {
				console.debug('Peer close');
				this.#status = Node.DISCONNECTED;
			});
		});

		this.#peer.on('open', id => {
			console.debug('I am the master node', id);
			this.#isMaster = true;
			this.#status = Node.INITIALIZED;
			this.dispatchEvent(new Event('open'));
		});

		this.#peer.on('connection', connection => {
			console.debug('Connecting with', connection.peer);
			this.#connections.set(connection.peer, connection);
			this.#status = Node.CONNECTING;

			connection.on('open', () => {
				console.debug('Connected with', connection.peer);
				this.#status = Node.CONNECTED;
				this.dispatchEvent(new CustomEvent('connection', { detail: { peer: connection.peer } }));
			});

			connection.on('data', data => {
				console.debug('Data received from', connection.peer, data);
				this.dispatchEvent(new CustomEvent('data', { detail: data }));
				this.broadcast(data);
			});

			connection.on('close', () => {
				console.debug('Connection closed', connection);
				this.#removeConnection(connection);
			});

			connection.on('error', console.error);
		});

		this.#peer.on('disconnected', () => {
			console.debug('Peer disconnected');
			this.#status = Node.DISCONNECTED;
		});

		this.#peer.on('close', () => {
			console.debug('Peer close');
			this.#status = Node.DISCONNECTED;
		});
	}

}

class LiveEditor {

	id;
	#editor;
	#ydoc;
	#lock;
	#changed;
	#changeCallbacks;
	#cursorPosition;
	#cursorLock;

	constructor(id, ydoc) {
		this.id = id;
		this.#lock = false;
		this.#cursorLock = false;
		this.#changed = false;
		this.#changeCallbacks = [];
		this.#editor = ace.edit(id);
		this.setYdoc(ydoc);

		this.#editor.session.on('change', delta => {
			if (this.#lock) return;
			this.#lock = true;

			const ytext = this.#ydoc.getText(this.id);
			const start = this.#editor.session.doc.positionToIndex(delta.start, 0);
			if (delta.action === 'insert') {
				ytext.insert(start, delta.lines.join('\n'));
			} else if (delta.action === 'remove') {
				const length = delta.lines.join('\n').length;
				ytext.delete(start, length);
			}

			this.#changed = true;

			this.#changeCallbacks.forEach(f => f(delta));

			this.#lock = false;
		});

		this.#editor.session.selection.on('changeCursor', () => {
			if (this.#cursorLock) return;
			this.#cursorLock = true;
			const index = this.#editor.session.doc.positionToIndex(this.#editor.getCursorPosition(), 0);
			console.debug('Cursor change', index);
			this.#cursorPosition = Y.createRelativePositionFromTypeIndex(this.#ydoc.getText(this.id), index);
			this.#cursorLock = false;
		});

		const value = this.getValue();
		if (value) {
			this.setYtext(value);
		}
	}

	focus() {
		this.#editor.focus();
		this.#editor.renderer.updateFull();
	}

	changed() {
		return this.#changed;
	}

	resetState() {
		this.#changed = false;
	}

	resize() {
		this.#editor.resize();
	}

	onChange(f) {
		this.#changeCallbacks.push(f);
	}

	setYdoc(ydoc) {
		this.#ydoc = ydoc;
		this.#ydoc.getText(this.id).observe(() => {
			this.setValue(this.#ydoc.getText(this.id).toString());
		});
	}

	setYtext(value) {
		if (this.#lock) return;
		this.#lock = true;
		this.#ydoc.getText(this.id).insert(0, value);
		this.#lock = false;
	}

	setValue(value) {
		if (this.#lock) return;
		this.#lock = true;
		this.#editor.session.setValue(value);
		this.#lock = false;
	}

	getValue() {
		return this.#editor.getValue();
	}

	updateCursorPosition() {
		if (this.#cursorLock) return;
		this.#cursorLock = true;
		const position = Y.createAbsolutePositionFromRelativePosition(this.#cursorPosition, this.#ydoc);
		console.debug('Update cursor position:', position.index);
		this.#editor.moveCursorToPosition(this.#editor.session.doc.indexToPosition(position.index, 0));
		this.#cursorLock = false;
	}

	saveScrollState() {
		const editor = this.#editor;
		localStorage.setItem('top_' + this.id, editor.session.getScrollTop());
		localStorage.setItem('left_' + this.id, editor.session.getScrollLeft());
	}

	saveCursorState() {
		localStorage.setItem('cursor_' + this.id, JSON.stringify(this.#editor.getCursorPosition()));
	}

	setFolds(folds) {
		const Fold = ace.require("ace/edit_session/fold").Fold;
		const res = [];
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

	getFolds(folds, row = 0) {
		if (folds.length === 0) return [];
		let res = [];
		folds.forEach(fold => {
			res.push({
				start: { row: fold.start.row + row, column: fold.start.column },
				end: { row: fold.end.row + row, column: fold.end.column },
				placeholder: fold.placeholder,
				subFolds: this.getFolds(fold.subFolds, fold.start.row + row),
			});
		});
		return res;
	}

	saveFoldState() {
		const editor = this.#editor;
		const id = this.id;
		localStorage.setItem('folds_' + id, JSON.stringify(this.getFolds(editor.session.getAllFolds())));
		localStorage.setItem('crc32_' + id, CRC32.str(editor.session.getValue()));
	}

	loadFoldState() {
		const id = this.id;
		const folds = JSON.parse(localStorage.getItem('folds_' + id));
		if (!folds) return;
		const crc32 = localStorage.getItem('crc32_' + id);
		if (CRC32.str(this.#editor.session.getValue()) !== parseInt(crc32)) return;
		this.#editor.session.addFolds(this.setFolds(folds));
	}

	loadScrollState() {
		const editor = this.#editor;
		editor.session.setScrollLeft(localStorage.getItem('left_' + this.id));
		editor.session.setScrollTop(localStorage.getItem('top_' + this.id));
	}

	loadCursorState() {
		const position = JSON.parse(localStorage.getItem('cursor_' + this.id));
		if (!position) return;
		this.#editor.moveCursorTo(position.row, position.column);
	}

	loadEditorState() {
		setTimeout(() => {
			this.loadFoldState();
			this.loadScrollState();
			this.loadCursorState()
		}, 100);
	}

}

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
	let formSubmit = false;

	let codeEditorHtml;
	let codeEditorCss;
	let codeEditorJs;

	let ydoc;
	let node;

	const editors = new Map();

	function init() {
		ydoc = new Y.Doc();
		codeEditorHtml = new LiveEditor('codearea_codearea_html_{ID}', ydoc);
		codeEditorCss = new LiveEditor('codearea_codearea_css_{ID}', ydoc);
		codeEditorJs = new LiveEditor('codearea_codearea_js_{ID}', ydoc);
		[codeEditorHtml, codeEditorCss, codeEditorJs].forEach(editor => {
			editors.set(editor.id, editor);
		});

		node = setupNode();
		setInterval(() => {
			if (node.isDisconnected()) {
				node.destroy();
				node = setupNode();
			}
		}, 1000);

		window.addEventListener('resize', resizeCodeArea);

		window.addEventListener("message", event => {
			if (event.data === 'saved') {
				loadHtml();
			}
		}, false);

		editors.forEach(editor => {
			let timeoutId;
			// Listen change event
			editor.onChange(() => {
				const stateVector = Y.encodeStateVector(ydoc);
				node.broadcast({ peer: node.getId(), stateVector: stateVector });

				if (timeoutId) {
					clearTimeout(timeoutId);
				}
				// Live reload only if code editor is opened
				if (!document.getElementById('sy-code-modal').classList.contains('show')) return;
				timeoutId = setTimeout(loadPreview, 2000);
			});
		});
	}

	function setupNode() {
		const node = new Node('{ROOM_ID}');

		node.addEventListener('data', e => {
			const data = e.detail;
			if (data.diff) {
				Y.applyUpdate(ydoc, new Uint8Array(data.diff));
				if (data.stateVector) {
					const diff = Y.encodeStateAsUpdate(ydoc, new Uint8Array(data.stateVector));
					node.send({ peer: node.getId(), diff: diff }, data.peer);
				}
			} else if (data.stateVector) {
				const diff = Y.encodeStateAsUpdate(ydoc, new Uint8Array(data.stateVector));
				node.send({ peer: node.getId(), diff: diff, stateVector: Y.encodeStateVector(ydoc) }, data.peer);
			} else if (data.state) {
				ydoc = new Y.Doc();
				Y.applyUpdate(ydoc, new Uint8Array(data.state));
				editors.forEach(editor => {
					editor.setYdoc(ydoc);
					editor.setValue(ydoc.getText(editor.id).toString());
					loadPreview();
				});
			}
		});

		node.addEventListener('connection', e => {
			const data = e.detail;
			node.send({ peer: node.getId(), state: Y.encodeStateAsUpdate(ydoc) }, data.peer);
		});

		node.addEventListener('open', () => {
			if (!node.isMaster()) return;
			loadHtml();
		});

		return node;
	}

	function updateCursorsPosition() {
		editors.forEach(editor => {
			editor.updateCursorPosition();
		});
	}

	function resizeCodeArea() {
		const codeEditorHeight = document.querySelector('#sy-code-modal .modal-body').offsetHeight;
		const codeEditorWidth = document.querySelector('#sy-code-modal .modal-body').offsetWidth;

		editors.forEach(editor => {
			const element = document.getElementById(editor.id);
			element.style.height = codeEditorHeight + 'px';
			element.style.width = codeEditorWidth + 'px';
			editor.resize();
		});
	}

	function loadHtml() {
		if (codeEditorHtml.getValue() !== '') return;

		const location = new URL('{GET_URL}', window.location.origin);
		location.searchParams.set('ts', Date.now());

		fetch(location.href)
			.then(response => response.json())
			.then(res => {
				if (res.status === 'ok') {
					codeEditorHtml.setValue(res.html);
					codeEditorHtml.setYtext(res.html);
				}
			});
	}

	function codeChanged() {
		let codeChanged = false;
		editors.forEach(editor => {
			if (editor.changed()) codeChanged = true;
		});
		return codeChanged;
	}

	document.getElementById('sy-code-modal').addEventListener('show.bs.modal', () => init(), {once: true});

	document.getElementById('sy-code-modal').addEventListener('show.bs.modal', () => {
		screenSplit(window.localStorage.getItem('screen-split-layout'));
		// Disable toolbar buttons
		document.querySelectorAll('#sy-page-toolbar .btn-circle').forEach(btn => btn.setAttribute('disabled', 'disabled'));
	});

	document.getElementById('sy-code-modal').addEventListener('shown.bs.modal', () => {
		resizeCodeArea();
		showLastSelectedTab();
	});

	document.getElementById('sy-code-modal').addEventListener('hide.bs.modal', e => {
		if (!codeChanged()) return;
		if (formSubmit) return;
		const codeCloseConfirm = confirm((new DOMParser).parseFromString('{CONFIRM_CODE_CLOSE}', 'text/html').documentElement.textContent);
		if (!codeCloseConfirm) {
			e.preventDefault();
			return;
		}
	});

	document.getElementById('sy-code-modal').addEventListener('hidden.bs.modal', () => {
		screenSplitReset();

		// Enable toolbar buttons
		document.querySelectorAll('#sy-page-toolbar .btn-circle').forEach(btn => btn.removeAttribute('disabled'));
	});

	window.addEventListener('beforeunload', e => {
		if (!codeChanged()) return;
		if (formSubmit) return;
		e.preventDefault();
		return;
	});

	document.querySelector('#sy-code-modal form').addEventListener('submit', e => {
		formSubmit = true;

		const form = e.target;
		form.js.value = codeEditorJs.getValue();
		form.css.value = codeEditorCss.getValue();

		// Save editor state
		editors.forEach(editor => {
			editor.saveCursorState();
			editor.saveFoldState();
			editor.saveScrollState();
		});
		sessionStorage.setItem('sy-code-tab', document.querySelector('#sy-code-modal button.active[data-bs-toggle="tab"]').getAttribute('id'));
	});

	const modals = ['#sy-new-page-modal', '#sy-update-page-modal', '#sy-code-modal'];
	modals.forEach(function (modalId) {
		if (!document.querySelector(modalId)) return;
		if (document.querySelector(modalId).querySelector('div.alert')) {
			var bsModal = new bootstrap.Modal(document.querySelector(modalId));
			bsModal.show();
		}
	});

	const alertElement = document.querySelector('#sy-code-modal div.alert');
	const errorMsg = alertElement ? alertElement.textContent : null;
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
			if (e.stopPropagation) e.stopPropagation();
			if (e.preventDefault) e.preventDefault();
			e.cancelBubble = true;
			e.returnValue = false;
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

	// On tab change
	document.querySelectorAll('#sy-code-modal button[data-bs-toggle="tab"]').forEach(tab => {
		tab.addEventListener('shown.bs.tab', e => {
			let id = e.target.getAttribute('id');
			focus(id);
		});
	});

	function focus(id) {
		const codeArea = document.querySelector('#' + id + '-content .ace_editor');
		if (!codeArea) return;
		editors.get(codeArea.getAttribute('id')).focus();
	}

	function showTab(id) {
		let element = document.getElementById(id);
		if (!element) return;
		focus(id);
		bootstrap.Tab.getOrCreateInstance(element).show();
	}

	function showLastSelectedTab() {
		showTab(sessionStorage.getItem('sy-code-tab') ?? 'sy-html-tab');
		sessionStorage.removeItem('sy-code-tab');
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
	<!-- END CODE_BLOCK -->

})();