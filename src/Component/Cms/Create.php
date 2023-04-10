<?php
namespace Sy\Bootstrap\Component\Cms;

class Create extends \Sy\Bootstrap\Component\Form\Crud\Create {

	public function __construct() {
		parent::__construct('content');
	}

	public function init() {
		parent::init();

		// Alias
		$this->getField('alias')->setAttribute('placeholder', $this->_('Authorized characters:') . ' a-z 0-9');
		$this->getField('alias')->setAttribute('maxlength', '128');
		$this->getField('alias')->addValidator(function($value) {
			if (preg_match('/^[a-z0-9\-]*$/', $value) === 1) return true;
			$this->setError($this->_('Unauthorized character in the alias'));
			return false;
		});

		// Title
		$this->getField('title')->setAttribute('maxlength', '128');

		// Description
		$this->getField('description')->setAttribute('maxlength', '512');
	}

	public function submitAction() {
		try {
			$this->validatePost();
			$fields = $this->post('form');

			// Default content
			$fields['html'] = '<div class="container">Hello world!</div>';
			$fields['scss'] = ' ';
			$fields['css'] = ' ';
			$fields['js'] = ' ';

			$service = \Project\Service\Container::getInstance();
			$fields['creator_id'] = $service->user->getCurrentUser()->id;

			$id = $this->getService()->create($fields);
			$this->setSuccess(
				$this->_('Page created successfully'),
				\Sy\Bootstrap\Lib\Url::build('page', 'content', ['id' => $id])
			);
		} catch (\Sy\Component\Html\Form\Exception $e) {
			$this->logWarning($e);
			if (is_null($this->getOption('error'))) {
				$this->setError($this->_('Please fill the form correctly'));
			}
			$this->fill($_POST);
		} catch (\Sy\Db\MySql\DuplicateEntryException $e) {
			$this->logWarning($e);
			$this->setError($this->_('Page id already exists'));
			$this->fill($_POST);
		} catch (\Sy\Db\MySql\Exception $e) {
			$this->logWarning($e);
			$this->setError($this->_('Database error'));
			$this->fill($_POST);
		}
	}

}