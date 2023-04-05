<?php
namespace Sy\Bootstrap\Component\Cms;

class Css extends \Sy\Bootstrap\Component\Form {

	private $id;

	private $lang;

	public function __construct($id, $lang) {
		$this->id = $id;
		$this->lang = $lang;
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
		$content = $service->content->retrieve(['id' => $this->id, 'lang' => $this->lang]);

		if (!empty($content)) {
			$codeArea->addText($content['scss']);
		}

		if (file_exists(TPL_DIR . "/Application/Page/css/$this->id.scss")) {
			$codeArea->addText(file_get_contents(TPL_DIR . "/Application/Page/css/$this->id.scss"));
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