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

	/**
	 * @param integer $id Content id
	 */
	public function __construct(int $id) {
		$this->id = $id;

		$service = \Project\Service\Container::getInstance();
		$user = $service->user->getCurrentUser();

		// Content version
		$version = $this->get('version');
		if (!is_null($version) and $user->hasPermission('content-history-view')) {
			$content = $service->contentHistory->retrieve(['id' => $this->id, 'crc32' => $version]);
		} else {
			$condition = ['id' => $this->id];
			if (!$user->hasPermission('content-read')) {
				$condition['visibility'] = 'public';
			}
			$content = $service->content->retrieve($condition);
		}

		// Content not found
		if (empty($content)) {
			throw new \Sy\Bootstrap\Application\Page\NotFoundException();
		}

		// Set meta title, description and canonical
		HeadData::setTitle(Str::escape($content['title']));
		HeadData::setDescription(Str::escape($content['description']));
		HeadData::setCanonical(PROJECT_URL . Url::build('page', 'content', ['id' => $id]));

		if ($this->get('mode') === 'view') {
			HeadData::setBase(target: '_parent');
		}

		$this->mount(function () use ($content) {
			$this->init($content);
		});
	}

	/**
	 * @param array $content Content row
	 */
	private function init(array $content) {
		$this->addTranslator(LANG_DIR . '/bootstrap-cms');
		$this->addTranslator(__DIR__ . '/../../../lang/bootstrap-cms');
		$this->setTemplateFile(__DIR__ . '/Content.html');

		$service = \Project\Service\Container::getInstance();
		$user = $service->user->getCurrentUser();
		$mode = $this->get('mode', 'iframe');
		$version = $this->get('version');

		if ($user->hasPermission('content-code') and $mode === 'iframe' and is_null($version)) {
			// Developer mode
			$this->initIframe();
		} else {
			// Init html css js
			$this->initContent($content);
		}

		// Init toolbar
		if (($user->hasPermission('content-update-inline') and !$user->hasPermission('content-code')) or ($user->hasPermission('content-code') and $mode !== 'view')) {
			$this->initToolbar($content);
		}
	}

	/**
	 * For developer mode
	 */
	private function initIframe() {
		$this->setVar('IFRAME_URL', Url::build('page', 'content', ['id' => $this->id, 'mode' => 'view']));
		$this->setBlock('CONTENT_BLOCK');
	}

	/**
	 * Init content html css and js
	 *
	 * @param array $content
	 */
	private function initContent($content) {
		$html = new WebComponent();
		$html->addTranslator(LANG_DIR);
		$html->setTemplateContent($content['html']);

		// Load content translations
		$service = \Project\Service\Container::getInstance();
		$translations = $service->contentTranslation->retrieveAll(['WHERE' => ['lang' => $service->lang->getLang()]]);
		foreach ($translations as $translation) {
			$html->setVar($translation['key'], $translation['value']);
		}

		// Add web components in content
		$this->initComponents($html, $content['html']);
		$this->setVar('HTML', Str::convertTemplateSlot(strval($html)));
		$this->mergeCss($html);
		$this->mergeJs($html);

		// CSS
		if (!empty($content['css'])) $this->addCssCode($content['css']);

		// JS
		if (!empty($content['js'])) $this->addJsCode($content['js']);
	}

	/**
	 * Init toolbar
	 *
	 * @param array $content
	 */
	private function initToolbar($content) {
		$service = \Project\Service\Container::getInstance();
		$user = $service->user->getCurrentUser();
		$version = $this->get('version');
		$mode = $this->get('mode', 'iframe');

		// Version history
		if ($user->hasPermission('content-history-view')) {
			$this->setBlock('HISTORY_BTN_BLOCK');
			$this->setBlock('HISTORY_MODAL_BLOCK', ['HISTORY_LIST' => new HistoryFeed($this->id)]);
		}

		// Duplicate
		if ($user->hasPermission('content-create')) {
			$duplicateForm = new Create($content['html'], $content['scss'], $content['css'], $content['js']);
			$duplicateForm->initialize();
			$this->setVar('DUPLICATE_PAGE_FORM', $duplicateForm);
			$this->setBlock('DUPLICATE_BTN_BLOCK');
			$this->setBlock('DUPLICATE_MODAL_BLOCK');
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
		if ($user->hasPermission('content-create')) {
			$form = new Create();
			$form->initialize();
			$this->setVar('NEW_PAGE_FORM', $form);
			$this->setBlock('CREATE_BTN_BLOCK');
			$this->setBlock('CREATE_MODAL_BLOCK');
		}

		// Javascript code
		$js = new \Sy\Component();
		$js->setTemplateFile(__DIR__ . '/Content.js');

		// Update inline
		if ($user->hasPermission('content-update-inline')) {
			$this->addJsLink(CKEDITOR_JS);

			// Retrieve translations data
			$transArray = $service->contentTranslation->retrieveAll(['WHERE' => ['lang' => $service->lang->getLang()]]);
			$translations = array_combine(array_column($transArray, 'key'), array_column($transArray, 'value'));

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
				'LANG'             => $service->lang->getLang(),
				'TRANSLATE_URL'    => Url::build('api', 'content-translation'),
				'TRANSLATIONS'     => json_encode($translations),
				'LANGUAGE'         => $this->_('In english'),
				'ADD_TRANSLATE'    => $this->_('Add new translation slot'),
				'TRANSLATE_KEY'    => $this->_('Translation identifier'),
				'TRANSLATE_VALUE'  => $this->_('Translation value'),
			]);
			$js->setBlock('UPDATE_BLOCK');
			if (!$user->hasPermission('content-code') or $mode === 'inline') {
				$this->setBlock('UPDATE_INLINE_BTN_BLOCK');
			}

			// Ckeditor hidden fields css
			$this->addCssCode('[data-sycomponent] .cke_hidden {display: none;}');
		}

		// Update
		if ($user->hasPermission('content-update')) {
			$this->setComponent('UPDATE_PAGE_FORM', new Update($this->id));
			$this->setBlock('UPDATE_BTN_BLOCK');
			$this->setBlock('UPDATE_MODAL_BLOCK');
		}

		// Delete: the first content cannot be deleted
		if ($user->hasPermission('content-delete') and $this->id > 1) {
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
		if ($user->hasPermission('content-code')) {
			$this->setVars([
				'CODE_FORM'    => new Code($this->id),
				'CODE_FORM_ID' => 'code_form_' . $this->id,
			]);

			$js->setBlock('CODE_BLOCK');
			$this->setBlock('CODE_BTN_BLOCK');
			$this->setBlock('CODE_MODAL_BLOCK');
		}

		// Add javascript code
		$this->addJsCode($js, ['position' => WebComponent::JS_TOP]);
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
			if (empty($class)) {
				$this->logError('Class not specified');
				continue;
			}
			$slot = $element->nodeValue;
			if (empty($slot)) {
				$this->logError('Slot not specified');
				continue;
			}
			$slot = trim($slot);
			if (strncmp($slot, '{', 1) !== 0 or substr($slot, -1) !== '}') {
				$this->logError('Slot must start with a { and end with a }');
				continue;
			}
			$class = trim($class);
			$slot = trim($slot, '{}');
			if (!class_exists($class)) {
				$this->logError("Class '$class' not found");
				continue;
			}

			// Retrieve arguments from attribute data-sycomponent-args
			try {
				$args = $element->getAttribute('data-sycomponent-args');
				if (!empty($args)) {
					$args = json_decode($args, true);
					$component = new $class(...$args);
				} else {
					$component = new $class();
				}
				$container->setVar($slot, $component);
			} catch (\Error $e) {
				$this->logError($e);
			}
		}
	}

}