<?php
namespace Sy\Bootstrap\Component\Cms;

use Sy\Bootstrap\Lib\Url;

class Update extends \Sy\Bootstrap\Component\Form\Crud {

	/**
	 * @var string
	 */
	private $id;

	public function __construct($id) {
		$this->id = $id;
		parent::__construct('content', ['id' => $id]);
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

		// User id
		$service = \Project\Service\Container::getInstance();
		$userId = $service->user->getCurrentUser()->id;
		$this->addHidden(['name' => 'form[updator_id]', 'value' => $userId]);
	}

	public function submitAction() {
		try {
			$this->validatePost();
			$fields = $this->post('form');

			// Save current version in t_content_history if title or description changed
			$service = \Project\Service\Container::getInstance();
			$content = $service->content->retrieve(['id' => $this->id]);
			if (empty($content) or ($content['title'] !== $fields['title'] or $content['description'] !== $fields['description'])) {
				$service->contentHistory->create([
					'id'          => $content['id'],
					'crc32'       => crc32($content['title'] . $content['description'] . $content['html'] . $content['scss'] . $content['css'] . $content['js'] . $content['updator_id'] . $content['updated_at']),
					'title'       => $content['title'],
					'description' => $content['description'],
					'html'        => $content['html'],
					'scss'        => $content['scss'],
					'css'         => $content['css'],
					'js'          => $content['js'],
					'updator_id'  => $content['updator_id'] ?? null,
					'updated_at'  => $content['updated_at'],
				]);
			}

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
			$this->logWarning($e);
			$this->setError($this->_('Database error'));
			$this->fill($_POST);
		}
	}

}