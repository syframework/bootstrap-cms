<?php
namespace Sy\Bootstrap\Component\Cms;

use Sy\Bootstrap\Lib\Url;

class Update extends \Sy\Bootstrap\Component\Form\Crud {

	/**
	 * @var string
	 */
	private $id;

	public function __construct($id) {
		parent::__construct('content', ['id' => $id]);
		$this->id = $id;
	}

	public function init() {
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

		// Visibility
		$service = \Project\Service\Container::getInstance();
		$content = $service->content->retrieve(['id' => $this->id]);
		$options = [
			'private'   => $this->_('Private page'),
			'protected' => $this->_('Secret page'),
			'public'    => $this->_('Public page'),
		];
		$select = $this->getField('visibility');
		foreach ($options as $value => $label) {
			$o = $select->addOption($label, $value);
			if ($content['visibility'] === $value) {
				$o->setAttribute('selected', 'selected');
			}
		}

		// User id
		$service = \Project\Service\Container::getInstance();
		$userId = $service->user->getCurrentUser()->id;
		$this->addHidden(['name' => 'form[updator_id]', 'value' => $userId]);
	}

	public function submitAction() {
		try {
			$this->validatePost();
			$this->updateRow();
			$this->setSuccess($this->_('Saved'), Url::build('page', 'content', ['id' => $this->id]));
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
			if ($e->getCode() === 1644) {
				$this->setSuccess($this->_('No change detected'));
			}
			$this->logWarning($e);
			$this->setError($this->_('Database error'));
			$this->fill($_POST);
		}
	}

}