<?php
namespace Sy\Bootstrap\Component\Cms;

use Sy\Bootstrap\Lib\Url;
use Sy\Bootstrap\Lib\HeadData;
use Sy\Bootstrap\Lib\Str;

class Content extends \Sy\Component\WebComponent {

	/**
	 * @var int
	 */
	private $id;

	/**
	 * @var string
	 */
	private $lang;

	public function __construct(int $id, string $lang) {
		$this->id   = $id;
		$this->lang = $lang;

		$service = \Project\Service\Container::getInstance();
		$content = $service->content->retrieve(['id' => $id, 'lang' => $lang]);

		// Set meta title, description and canonical
		HeadData::setTitle(Str::escape($content['title']));
		HeadData::setDescription(Str::escape($content['description']));
		HeadData::setCanonical(PROJECT_URL . Url::build('page', 'content', ['id' => $id, 'lang' => $lang]));

		$this->mount(function () {
			$this->init();
		});
	}

	private function init() {
		$this->setTemplateFile(__DIR__ . '/Content.html');

		$service = \Project\Service\Container::getInstance();
		$content = $service->content->retrieve(['id' => $this->id, 'lang' => $this->lang]);

		$this->setVar('HTML', $content['html']);

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
				'ID'              => $content['id'],
				'LANG'            => $this->lang,
				'CSRF'            => $service->user->getCsrfToken(),
				'URL'             => Url::build('api', 'content'),
				'WEB_ROOT'        => WEB_ROOT,
				'IMG_BROWSE'      => Url::build('editor', 'browse', ['id' => $this->id, 'item' => 'content', 'type' => 'image']),
				'IMG_UPLOAD'      => Url::build('editor', 'upload', ['id' => $this->id, 'item' => 'content', 'type' => 'image']),
				'FILE_BROWSE'     => Url::build('editor', 'browse', ['id' => $this->id, 'item' => 'content', 'type' => 'file']),
				'FILE_UPLOAD'     => Url::build('editor', 'upload', ['id' => $this->id, 'item' => 'content', 'type' => 'file']),
				'IMG_UPLOAD_AJAX' => Url::build('editor', 'upload', ['id' => $this->id, 'item' => 'content', 'type' => 'image', 'json' => '']),
				'FILE_UPLOAD_AJAX' => Url::build('editor', 'upload', ['id' => $this->id, 'item' => 'content', 'type' => 'file', 'json' => '']),
				'CKEDITOR_ROOT'   => CKEDITOR_ROOT,
				'GET_URL'         => Url::build('api', 'content', ['id' => $this->id, 'lang' => $this->lang]),
			]);
			$js->setBlock('UPDATE_BLOCK');
			$this->setBlock('UPDATE_INLINE_BTN_BLOCK');
		}

		// Update
		if ($service->user->getCurrentUser()->hasPermission('content-update')) {
			$this->setComponent('UPDATE_PAGE_FORM', new Update($this->id, $this->lang));
			$this->setBlock('UPDATE_BTN_BLOCK');
			$this->setBlock('UPDATE_MODAL_BLOCK');
		}

		// Delete
		if ($service->user->getCurrentUser()->hasPermission('content-delete')) {
			$deleteForm = new \Sy\Bootstrap\Component\Form\Crud\Delete('content', ['id' => $this->id, 'lang' => $this->lang]);
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
			$this->setComponent('HTML_FORM', new Html($this->id, $this->lang));
			$this->setVar('FORM_HTML_ID', 'form_html_' . $this->id);
			$js->setVars([
				'CM_HTML_ID' => 'codearea_html_' . $this->id,
			]);

			// CSS Code
			$this->setComponent('CSS_FORM', new Css($this->id, $this->lang));
			$this->setVar('FORM_CSS_ID', 'form_css_' . $this->id);
			$js->setVar('CM_CSS_ID', 'codearea_css_' . $this->id);

			// JS Code
			$this->setComponent('JS_FORM', new Js($this->id, $this->lang));
			$this->setVar('FORM_JS_ID', 'form_js_' . $this->id);
			$js->setVar('CM_JS_ID', 'codearea_js_' . $this->id);

			$js->setBlock('CODE_BLOCK');
			$this->setBlock('CODE_BTN_BLOCK');
			$this->setBlock('CODE_MODAL_BLOCK');
		}

		// Add javascript code
		$this->addJsCode($js);
	}

}