<?php
namespace Sy\Bootstrap\Component\Cms;

use Sy\Bootstrap\Lib\Url;

class Update extends \Sy\Bootstrap\Component\Form\Crud {

	/**
	 * @var string
	 */
	private $id;

	/**
	 * @var string
	 */
	private $lang;

	public function __construct($id, $lang) {
		$this->id = $id;
		$this->lang = $lang;
		parent::__construct('content', ['id' => $id, 'lang' => $lang]);
	}

	public function init() {
		parent::init();

		// Alias
		$this->getField('alias')->addValidator(function($value) {
			if (preg_match('/^[a-z0-9\-]*$/', $value) === 1) return true;
			$this->setError($this->_('Unauthorized character in the alias'));
			return false;
		});

		// Title
		$this->getField('title')->setAttribute('maxlength', '128');

		// Description
		$this->getField('description')->setAttribute('maxlength', '256');
	}

	public function submitAction() {
		try {
			$this->validatePost();
			$this->updateRow();
			$this->setSuccess($this->_('Saved'), Url::build('page', 'content', ['id' => $this->id, 'lang' => $this->lang]));
		} catch (\Sy\Component\Html\Form\Exception $e) {
			$this->logWarning($e);
			if (is_null($this->getOption('error'))) {
				$this->setError($this->_('Please fill the form correctly'));
			}
			$this->fill($_POST);
		} catch (\Sy\Db\MySql\DuplicateEntryException $e) {
			$this->logWarning($e);
			$this->setError($this->_('Alias already used'));
			$this->fill($_POST);
		} catch (\Sy\Db\MySql\Exception $e) {
			$this->logWarning($e);
			$this->setError($this->_('Database error'));
			$this->fill($_POST);
		}
	}

}