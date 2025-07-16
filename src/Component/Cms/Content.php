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
	 * Content translations data
	 * [
	 *   'Translation key 1' => 'Translation value 1',
	 *   'Translation key 2' => 'Translation value 2',
	 *   ...
	 * ]
	 *
	 * @var array
	 */
	private $translations;

	/**
	 * @param integer $id Content id
	 */
	public function __construct(int $id) {
		$this->id = $id;

		// Retrieve translations data
		$service = \Project\Service\Container::getInstance();
		$transArray = $service->contentTranslation->retrieveAll(['WHERE' => ['lang' => $service->lang->getLang()]]);
		$this->translations = array_combine(array_column($transArray, 'key'), array_column($transArray, 'value'));

		// Set meta title, description and canonical
		$content = $this->getContent();
		HeadData::setTitle(Str::escape(isset($this->translations[$content['title']]) ? $this->translations[$content['title']] : $content['title']));
		HeadData::setDescription(Str::escape(isset($this->translations[$content['description']]) ? $this->translations[$content['description']] : $content['description']));
		HeadData::setCanonical(PROJECT_URL . Url::build('page', 'content', ['id' => $this->id]));
		if ($this->get('mode') === 'iframe') {
			HeadData::setBase(target: '_parent');
		}
		if (isset($content['visibility']) and $content['visibility'] !== 'public') {
			HeadData::addMeta('robots', 'noindex');
		}

		$this->mount(fn () => $this->init($content));
	}

	/**
	 * Init component
	 *
	 * @param array $content
	 */
	private function init($content) {
		$this->addTranslator(__DIR__ . '/../../../lang/bootstrap-cms');
		$this->setTemplateFile(__DIR__ . '/Content.html');

		$service = \Project\Service\Container::getInstance();
		$user = $service->user->getCurrentUser();

		// User has a content related permission
		if ($user->hasPermissionAmong(['content-create', 'content-delete', 'content-update', 'content-update-inline', 'content-code', 'content-history-view'])) {
			if ($this->get('mode') === 'iframe') {
				// Iframe view
				$this->initIframe($content);
			} else {
				// Parent view
				$this->initParent($content);
			}
			return;
		}

		// Read only view
		$this->initContent($content);
	}

	/**
	 * Init the iframe view
	 *
	 * @param array $content
	 */
	private function initIframe($content) {
		$this->initContent($content);

		// Javascript template code
		$service = \Project\Service\Container::getInstance();
		$js = new \Sy\Component();
		$js->setTemplateFile(__DIR__ . '/Iframe.js');
		$js->setVars([
			'ID'               => $content['id'],
			'CSRF'             => $service->user->getCsrfToken(),
			'URL'              => Url::build('api', 'content'),
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
			'TRANSLATIONS'     => json_encode($this->translations),
			'LANGUAGE'         => $this->_('In english'),
			'ADD_TRANSLATE'    => $this->_('Add new translation slot'),
			'TRANSLATE_KEY'    => $this->_('Translation identifier'),
			'TRANSLATE_VALUE'  => $this->_('Translation value'),
		]);

		// Add javascript and css code
		$this->addJsCode($js, ['position' => WebComponent::JS_TOP]);
		$this->addJsLink(CKEDITOR_JS);
		$this->addCssCode(__DIR__ . '/Iframe.css');
	}

	/**
	 * Init the parent view
	 *
	 * @param array $content
	 */
	private function initParent($content) {
		$this->setVar('IFRAME_URL', Url::build('page', 'content', ['id' => $this->id, 'version' => $this->get('version'), 'mode' => 'iframe', 'ts' => time()]));
		$this->setBlock('CONTENT_BLOCK');
		$this->initToolbar($content);

		// Css
		$this->addCssCode(__DIR__ . '/Parent.css');

		// Ace Collaborative Extensions
		$this->addCssLink('https://cdn.jsdelivr.net/npm/@convergencelabs/ace-collab-ext/dist/css/ace-collab-ext.min.css');
		$this->addJsLink('https://cdn.jsdelivr.net/npm/@convergencelabs/ace-collab-ext/dist/umd/ace-collab-ext.min.js');

		// CRC32 js
		$this->addJsLink('https://cdn.jsdelivr.net/npm/crc-32/crc32.min.js');
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

		// Version history
		if ($user->hasPermission('content-history-view')) {
			$this->setBlock('HISTORY_BTN_BLOCK');
			$this->setBlock('HISTORY_MODAL_BLOCK', ['HISTORY_LIST' => new HistoryFeed($this->id)]);
		}

		// Duplicate
		if ($user->hasPermission('content-create')) {
			$this->setVar('DUPLICATE_PAGE_FORM', new Create($content['html'], $content['scss'], $content['css'], $content['js']));
			$this->setBlock('DUPLICATE_BTN_BLOCK');
			$this->setBlock('DUPLICATE_MODAL_BLOCK');
		}

		// Javascript code
		$js = new \Sy\Component();
		$js->setTemplateFile(__DIR__ . '/Parent.js');

		// Code
		if ($user->hasPermission('content-code')) {
			$this->setVars([
				'CODE_FORM'    => new Code($this->id),
				'CODE_FORM_ID' => 'code_form_' . $this->id,
			]);

			$js->setVars([
				'ID'      => $this->id,
				'USER_ID' => $user->id,
				'USER_NAME' => Str::convertName($user->firstname . ' ' . $user->lastname),
				'ROOM_ID' => md5(PROJECT_URL . 'content' . $this->id),
				'GET_URL' => Url::build('api', 'content', ['id' => $this->id, 'version' => $version]),
				'CONFIRM_CODE_CLOSE' => Str::escape($this->_('Code not saved, are you sure to close?')),
			]);
			$js->setBlock('CODE_BLOCK');
			$this->setBlock('CODE_BTN_BLOCK');
			$this->setBlock('CODE_MODAL_BLOCK');
		}

		// Version history restore
		if (!is_null($version)) {
			$this->setBlock('BACK_BTN_BLOCK', ['BACK_URL' => Url::build('page', 'content', ['id' => $this->id])]);

			if ($user->hasPermission('content-history-restore')) {
				$this->setBlock('RESTORE_BTN_BLOCK', ['RESTORE_URL' => Url::build('content', 'restore', ['id' => $this->id, 'version' => $version])]);
			}

			// Add javascript code
			$this->addJsCode($js, ['position' => WebComponent::JS_TOP]);
			return;
		}

		// Create
		if ($user->hasPermission('content-create')) {
			$this->setVar('NEW_PAGE_FORM', new Create());
			$this->setBlock('CREATE_BTN_BLOCK');
			$this->setBlock('CREATE_MODAL_BLOCK');
			$js->setVar('ALIAS_VALIDATION_URL', Url::build('api', ['content', 'valid']));
			$js->setBlock('CREATE_BLOCK');
		}

		// Read all pages
		if ($user->hasPermission('content-read')) {
			$this->setVar('PAGE_FEED', new PageFeed());
			$this->setBlock('ALL_PAGES_MODAL_BLOCK');
			$this->setBlock('READ_BTN_BLOCK');
		}

		// Update inline
		if ($user->hasPermission('content-update-inline')) {
			$js->setBlock('UPDATE_BLOCK');
			$this->setBlock('UPDATE_INLINE_BTN_BLOCK');
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
				'CONFIRM_DELETE' => Str::escape($this->_('Are you sure to delete this page?')),
				'DELETE_FORM_ID' => 'delete-' . $this->id,
			]);
			$js->setBlock('DELETE_BLOCK');
		}

		// Add javascript code
		$this->addJsCode($js, ['position' => WebComponent::JS_TOP]);
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
		foreach ($this->translations as $key => $value) {
			$html->setVar($key, $value);
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
	 * Find from the html code all the components and set then in the container component
	 *
	 * @param WebComponent $container
	 * @param string $html
	 */
	private function initComponents($container, $html) {
		if (empty($html)) return;
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

	/**
	 * Retrieve content
	 *
	 * @throws \Sy\Bootstrap\Application\Page\NotFoundException
	 * @return array
	 */
	private function getContent() {
		$service = \Project\Service\Container::getInstance();
		$user = $service->user->getCurrentUser();

		// Content version
		$version = $this->get('version');
		if ($user->hasPermission('content-code') and $this->post('form-id') === 'sy-content-form') {
			// Iframe live preview
			$content = [
				'id'          => $this->id,
				'title'       => 'Preview page',
				'description' => 'Preview page',
				'html'        => Code::checkHtml($this->post('html')),
				'css'         => Code::compileScss($this->post('css', '')),
				'js'          => $this->post('js', ''),
			];
		} elseif (!is_null($version) and $user->hasPermission('content-history-view')) {
			// History view
			$content = $service->contentHistory->retrieve(['id' => $this->id, 'crc32' => $version]);
		} else {
			$condition = ['id' => $this->id];
			if (!$user->hasPermission('content-read')) {
				$condition['visibility'] = ['public', 'protected'];
			}
			$content = $service->content->retrieve($condition);
		}

		// Content not found
		if (empty($content)) {
			throw new \Sy\Bootstrap\Application\Page\NotFoundException();
		}

		return $content;
	}

}