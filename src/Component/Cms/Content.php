<?php
namespace Sy\Bootstrap\Component\Cms;

use Sy\Bootstrap\Lib\Url;
use Sy\Bootstrap\Lib\HeadData;
use Sy\Bootstrap\Lib\Str;
use Sy\Component\WebComponent;

class Content extends WebComponent {

	/**
	 * @var int
	 */
	private $id;

	public function __construct(int $id) {
		$this->id = $id;

		$service = \Project\Service\Container::getInstance();
		$content = $service->content->retrieve(['id' => $id]);

		// Set meta title, description and canonical
		HeadData::setTitle(Str::escape($content['title']));
		HeadData::setDescription(Str::escape($content['description']));
		HeadData::setCanonical(PROJECT_URL . Url::build('page', 'content', ['id' => $id]));

		$this->mount(function () {
			$this->init();
		});
	}

	private function init() {
		$this->setTemplateFile(__DIR__ . '/Content.html');

		$service = \Project\Service\Container::getInstance();
		$user = $service->user->getCurrentUser();

		// Content version
		$version = $this->get('version');
		if (!is_null($version) and $user->hasPermission('content-history-view')) {
			$content = $service->contentHistory->retrieve(['id' => $this->id, 'crc32' => $version]);
		} else {
			$content = $service->content->retrieve(['id' => $this->id]);
		}

		// Content not found
		if (empty($content)) {
			throw new \Sy\Bootstrap\Application\Page\NotFoundException();
		}

		// TODO: maybe use a specific lang directory for CMS content
		$html = new WebComponent();
		$html->addTranslator(LANG_DIR);
		$html->setTemplateContent($content['html']);

		// Add web components in content
		$this->initComponents($html, $content['html']);
		$this->setVar('HTML', Str::convertTemplateSlot(strval($html)));
		$this->mergeCss($html);
		$this->mergeJs($html);

		// CSS
		if (!empty($content['css'])) $this->addCssCode($content['css']);

		// JS
		if (!empty($content['js'])) $this->addJsCode($content['js']);

		// Version history
		if ($user->hasPermission('content-history-view')) {
			$this->setBlock('HISTORY_BTN_BLOCK');
			$this->setBlock('HISTORY_MODAL_BLOCK', ['HISTORY_LIST' => new HistoryFeed($this->id)]);
		}

		// Version history restore
		if (!is_null($version)) {
			$this->setBlock('BACK_BTN_BLOCK', ['BACK_URL' => Url::build('page', 'content', ['id' => $this->id])]);

			if ($user->hasPermission('content-history-restore')) {
				$this->setBlock('RESTORE_BTN_BLOCK', ['RESTORE_URL' => Url::build('content', 'restore', ['id' => $this->id, 'version' => $version])]);
			}
			return;
		}

		// Create
		if ($service->user->getCurrentUser()->hasPermission('content-create')) {
			$form = new Create();
			$form->initialize();
			$this->setComponent('NEW_PAGE_FORM', $form);
			$this->setBlock('CREATE_BTN_BLOCK');
			$this->setBlock('CREATE_MODAL_BLOCK');
		}

		// Javascript code
		$js = new \Sy\Component();
		$js->setTemplateFile(__DIR__ . '/Content.js');

		// Update inline
		if ($service->user->getCurrentUser()->hasPermission('content-update-inline')) {
			$this->addJsLink(CKEDITOR_JS);
			$js->setVars([
				'ID'               => $content['id'],
				'CSRF'             => $service->user->getCsrfToken(),
				'URL'              => Url::build('api', 'content'),
				'WEB_ROOT'         => WEB_ROOT,
				'IMG_BROWSE'       => Url::build('editor', 'content/browse', ['id' => $this->id, 'type' => 'image']),
				'IMG_UPLOAD'       => Url::build('editor', 'content/upload', ['id' => $this->id, 'type' => 'image']),
				'FILE_BROWSE'      => Url::build('editor', 'content/browse', ['id' => $this->id, 'type' => 'file']),
				'FILE_UPLOAD'      => Url::build('editor', 'content/upload', ['id' => $this->id, 'type' => 'file']),
				'IMG_UPLOAD_AJAX'  => Url::build('editor', 'content/upload', ['id' => $this->id, 'type' => 'image', 'json' => '']),
				'FILE_UPLOAD_AJAX' => Url::build('editor', 'content/upload', ['id' => $this->id, 'type' => 'file', 'json' => '']),
				'CKEDITOR_ROOT'    => CKEDITOR_ROOT,
				'GET_URL'          => Url::build('api', 'content', ['id' => $this->id]),
				'CSRF_URL'         => Url::build('api', 'csrf'),
			]);
			$js->setBlock('UPDATE_BLOCK');
			$this->setBlock('UPDATE_INLINE_BTN_BLOCK');

			// Ckeditor hidden fields css
			$this->addCssCode('[data-sycomponent] .cke_hidden {display: none;}');
		}

		// Update
		if ($service->user->getCurrentUser()->hasPermission('content-update')) {
			$this->setComponent('UPDATE_PAGE_FORM', new Update($this->id));
			$this->setBlock('UPDATE_BTN_BLOCK');
			$this->setBlock('UPDATE_MODAL_BLOCK');
		}

		// Delete: the first content cannot be deleted
		if ($service->user->getCurrentUser()->hasPermission('content-delete') and $this->id > 1) {
			$deleteForm = new \Sy\Bootstrap\Component\Form\Crud\Delete('content', ['id' => $this->id]);
			$deleteForm->setAttribute('id', 'delete-' . $this->id);
			$this->setComponent('DELETE_PAGE_FORM', $deleteForm);
			$this->setBlock('DELETE_BTN_BLOCK');
			$js->setVars([
				'CONFIRM_DELETE' => $this->_('Are you sure to delete this page?'),
				'DELETE_FORM_ID' => 'delete-' . $this->id,
			]);
			$js->setBlock('DELETE_BLOCK');
		}

		// Code
		if ($service->user->getCurrentUser()->hasPermission('content-code')) {
			// HTML Code
			$this->setComponent('HTML_FORM', new Html($this->id));
			$this->setVar('FORM_HTML_ID', 'form_html_' . $this->id);
			$js->setVars([
				'CM_HTML_ID' => 'codearea_html_' . $this->id,
			]);

			// CSS Code
			$this->setComponent('CSS_FORM', new Css($this->id));
			$this->setVar('FORM_CSS_ID', 'form_css_' . $this->id);
			$js->setVar('CM_CSS_ID', 'codearea_css_' . $this->id);

			// JS Code
			$this->setComponent('JS_FORM', new Js($this->id));
			$this->setVar('FORM_JS_ID', 'form_js_' . $this->id);
			$js->setVar('CM_JS_ID', 'codearea_js_' . $this->id);

			$js->setBlock('CODE_BLOCK');
			$this->setBlock('CODE_BTN_BLOCK');
			$this->setBlock('CODE_MODAL_BLOCK');
		}

		// Add javascript code
		$this->addJsCode($js);
	}

	/**
	 * Find from the html code all the components and set then in the container component
	 *
	 * @param WebComponent $container
	 * @param string $html
	 */
	private function initComponents($container, $html) {
		libxml_use_internal_errors(true);
		$doc = new \DOMDocument();
		$doc->loadHTML($html);
		$xpath = new \DOMXPath($doc);
		$elements = $xpath->query("//*[@data-sycomponent]");

		foreach ($elements as $element) {
			$class = $element->getAttribute('data-sycomponent');
			if (empty($class)) continue;
			$slot = $element->nodeValue;
			if (empty($slot)) continue;
			$class = trim($class);
			$slot = trim(trim($slot), '{}');
			if (!class_exists($class)) continue;

			// TODO: retrieve arguments from attribute data-sycomponent-args
			$component = new $class();
			$container->setVar($slot, $component);
		}
	}

}