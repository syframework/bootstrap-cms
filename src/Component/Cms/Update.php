<?php
namespace Sy\Bootstrap\Component\Cms;

use Sy\Bootstrap\Lib\Url;

class Update extends \Sy\Bootstrap\Component\Form\Crud {

	/**
	 * @var string
	 */
	private $id;

	/**
	 * @param string $id Content id
	 */
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
			return $this->jsonSuccess('Saved', ['redirection' => Url::build('page', 'content', ['id' => $this->id])]);
		} catch (\Sy\Component\Html\Form\Exception $e) {
			$this->logWarning($e);
			return $this->jsonError($this->getOption('error') ?? 'Please fill the form correctly');
		} catch (\Sy\Db\MySql\DuplicateEntryException $e) {
			$this->logWarning($e);
			return $this->jsonError('Alias already used');
		} catch (\Sy\Db\MySql\Exception $e) {
			if ($e->getCode() === 1644) {
				return $this->jsonSuccess('No change detected', ['color' => 'info']);
			}
			$this->logWarning($e);
			return $this->jsonError('Database error');
		}
	}

}