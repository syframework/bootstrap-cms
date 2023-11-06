<?php
namespace Sy\Bootstrap\Application;

use Sy\Bootstrap\Lib\Url;

class Content extends \Sy\Component {

	public function __construct() {
		parent::__construct();
		$this->addTranslator(LANG_DIR);
		$this->addTranslator(__DIR__ . '/../../lang/bootstrap-cms');
		$this->actionDispatch(ACTION_TRIGGER);
	}

	/**
	 * Restore a content version from history
	 */
	public function restoreAction() {
		$id = $this->get('id');
		$version = $this->get('version');

		if (is_null($id) or is_null($version)) {
			\Sy\Bootstrap\Lib\FlashMessage::setError($this->_('Missing id or version parameter'));
			$this->redirect(isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : WEB_ROOT . '/');
		}

		$service = \Project\Service\Container::getInstance();
		$content = $service->contentHistory->retrieve(['id' => $id, 'crc32' => $version]);

		if (empty($content)) {
			\Sy\Bootstrap\Lib\FlashMessage::setError($this->_('No content version found'));
			$this->redirect(isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : WEB_ROOT . '/');
		}

		$user = $service->user->getCurrentUser();
		if (!$user->hasPermission('content-history-restore')) {
			\Sy\Bootstrap\Lib\FlashMessage::setError($this->_('No permission'));
			$this->redirect(isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : WEB_ROOT . '/');
		}

		try {
			$service->content->update(['id' => $id], [
				'title'       => $content['title'],
				'description' => $content['description'],
				'html'        => $content['html'],
				'scss'        => $content['scss'],
				'css'         => $content['css'],
				'js'          => $content['js'],
				'updator_id'  => $user->id,
			]);
			\Sy\Bootstrap\Lib\FlashMessage::setMessage($this->_('Version restored successfully'));
			$this->redirect(Url::build('page', 'content', ['id' => $id]));
		} catch (\Sy\Db\MySql\Exception $e) {
			if ($e->getCode() === 1644) {
				\Sy\Bootstrap\Lib\FlashMessage::setMessage($this->_('No content version found'));
			} else {
				$this->logError($e);
				\Sy\Bootstrap\Lib\FlashMessage::setError($this->_('Database error'));
			}
			$this->redirect(isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : WEB_ROOT . '/');
		}
	}

}