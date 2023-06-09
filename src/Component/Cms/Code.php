<?php
namespace Sy\Bootstrap\Component\Cms;

class Code extends \Sy\Bootstrap\Component\Form {

	private $id;

	public function __construct($id) {
		parent::__construct();
		$this->id = $id;
	}

	public function init() {
		$this->addCssCode('
			#sy-code-modal .ace_editor {font-size: 14px;}
			#sy-code-modal div.alert {display: none;}
		');

		$this->setAttributes([
			'id'    => 'code_form_' . $this->id,
			'class' => 'tab-content',
		]);

		// Load content
		$service = \Project\Service\Container::getInstance();
		$content = $service->content->retrieve(['id' => $this->id]);

		// HTML
		$htmlArea = new \Sy\Bootstrap\Component\Form\Element\CodeArea();
		$htmlArea->setAttributes([
			'name'        => 'html',
			'id'          => 'codearea_html_' . $this->id,
			'placeholder' => 'HTML Code here...',
		]);
		$htmlArea->setMode('php');
		$htmlArea->setTheme('monokai');

		$this->addDiv([
			'class'           => 'tab-pane fade show active',
			'id'              => 'sy-html-tab-content',
			'role'            => 'tabpanel',
			'aria-labelledby' => 'html-tab',
		])->addElement($htmlArea);

		// CSS
		$cssArea = new \Sy\Bootstrap\Component\Form\Element\CodeArea();
		$cssArea->setAttributes([
			'name'        => 'css',
			'id'          => 'codearea_css_' . $this->id,
			'placeholder' => 'CSS Code here...',
		]);
		$cssArea->setMode('scss');
		$cssArea->setTheme('monokai');

		if (!empty($content)) {
			$cssArea->addText($content['scss']);
		}

		$this->addDiv([
			'class'           => 'tab-pane fade',
			'id'              => 'sy-css-tab-content',
			'role'            => 'tabpanel',
			'aria-labelledby' => 'css-tab',
		])->addElement($cssArea);

		// JS
		$jsArea = new \Sy\Bootstrap\Component\Form\Element\CodeArea();
		$jsArea->setAttributes([
			'name'        => 'js',
			'id'          => 'codearea_js_' . $this->id,
			'placeholder' => 'JS Code here...',
		]);
		$jsArea->setMode('javascript');
		$jsArea->setTheme('monokai');

		if (!empty($content)) {
			$jsArea->addText($content['js']);
		}

		$this->addDiv([
			'class'           => 'tab-pane fade',
			'id'              => 'sy-js-tab-content',
			'role'            => 'tabpanel',
			'aria-labelledby' => 'js-tab',
		])->addElement($jsArea);
	}

	public function submitAction() {
		try {
			$this->validatePost();
			$html = $this->post('html');
			$scss = $this->post('css');
			$js   = $this->post('js');

			// Compile scss
			$compiler = new \ScssPhp\ScssPhp\Compiler();
			$css = $compiler->compileString($scss)->getCss();

			// Save content
			$service = \Project\Service\Container::getInstance();
			$service->content->update(['id' => $this->id], [
				'html'       => $html,
				'scss'       => $scss,
				'css'        => $css,
				'js'         => $js,
				'updator_id' => $service->user->getCurrentUser()->id,
			]);

			$this->setSuccess($this->_('Source code updated successfully'));
		} catch (\Sy\Component\Html\Form\Exception $e) {
			$this->logWarning($e);
			$this->setError($this->_('Please fill the form correctly'));
			$this->fill($_POST);
		} catch (\Sy\Db\MySql\Exception $e) {
			if ($e->getCode() === 1644) {
				$this->setSuccess($this->_('No change detected'));
			}
			$this->logWarning($e);
			$this->setError($this->_('Database error'));
			$this->fill($_POST);
		} catch (\ScssPhp\ScssPhp\Exception\ParserException $e) {
			$this->logWarning($e);
			$this->setError('SCSS ' . $e->getMessage());
			$this->fill($_POST);
		}
	}

}