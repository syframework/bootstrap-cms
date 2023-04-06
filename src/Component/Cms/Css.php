<?php
namespace Sy\Bootstrap\Component\Cms;

class Css extends \Sy\Bootstrap\Component\Form {

	private $id;

	public function __construct($id) {
		$this->id = $id;
		parent::__construct();
	}

	public function init() {
		parent::init();

		$this->setAttribute('id', 'form_css_' . $this->id);

		$codeArea = new \Sy\Bootstrap\Component\Form\Element\CodeArea();
		$codeArea->setAttributes([
			'name' => 'css',
			'id'   => 'codearea_css_' . $this->id,
			'placeholder' => 'CSS Code here...',
		]);
		$codeArea->setMode('scss');

		// Load scss
		$service = \Project\Service\Container::getInstance();
		$content = $service->content->retrieve(['id' => $this->id]);

		if (!empty($content)) {
			$codeArea->addText($content['scss']);
		}

		$this->addElement($codeArea);
	}

	public function submitAction() {
		try {
			$this->validatePost();

			// Submit on Html form

			$this->setSuccess($this->_('CSS updated successfully'));
		} catch (\Sy\Component\Html\Form\Exception $e) {
			$this->logWarning($e);
			$this->setError($this->_('Please fill the form correctly'));
			$this->fill($_POST);
		} catch (\Sy\Db\MySql\Exception $e) {
			$this->logWarning($e);
			$this->setError($this->_('Database error'));
			$this->fill($_POST);
		}
	}

}