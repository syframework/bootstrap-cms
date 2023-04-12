<?php
namespace Sy\Bootstrap\Component\Cms;

class Html extends \Sy\Bootstrap\Component\Form {

	private $id;

	public function __construct($id) {
		$this->id = $id;
		parent::__construct();
	}

	public function init() {
		parent::init();

		$this->setAttribute('id', 'form_html_' . $this->id);

		$codeArea = new \Sy\Bootstrap\Component\Form\Element\CodeArea();
		$codeArea->setAttributes([
			'name' => 'html',
			'id'   => 'codearea_html_' . $this->id,
			'placeholder' => 'HTML Code here...',
		]);
		$codeArea->setMode('php');

		$this->addElement($codeArea);

		$this->addHidden(['name' => 'css']);
		$this->addHidden(['name' => 'js']);
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
			$service->content->update(['id' => $this->id], [
				'html' => $html,
				'scss' => $scss,
				'css'  => $css,
				'js'   => $js,
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
		}
	}

}