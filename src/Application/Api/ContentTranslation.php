<?php
namespace Sy\Bootstrap\Application\Api;

class ContentTranslation extends \Sy\Bootstrap\Component\Api {

	public function security() {
		$service = \Project\Service\Container::getInstance();
		if (!$service->user->getCurrentUser()->hasPermission('content-update')) {
			throw new \Sy\Bootstrap\Component\Api\ForbiddenException('Permission denied');
		}
	}

	/**
	 * Retrieve content translation
	 */
	public function getAction() {
		$key = $this->get('key');
		if (is_null($key)) {
			return $this->requestError([
				'status'  => 'ko',
				'message' => 'Missing key parameter',
			]);
		}

		$service = \Project\Service\Container::getInstance();
		$lang = $service->lang->getLang();
		$translation = $service->contentTranslation->retrieve(['lang' => $lang, 'key' => $key]);

		if (empty($translation)) {
			return $this->ok([
				'status' => 'ok',
				'value'  => $key,
			]);
		}

		return $this->ok([
			'status' => 'ok',
			'value'  => $translation['value'],
		]);
	}

	/**
	 * Update content translation
	 */
	public function postAction() {
		$service = \Project\Service\Container::getInstance();
		try {
			// Update page
			$key   = $this->post('key');
			$value = $this->post('value');

			if (is_null($key) or is_null($value)) {
				return $this->requestError([
					'status'  => 'ko',
					'message' => 'Missing key or value parameter',
				]);
			}

			// Save translation value
			$lang = $service->lang->getLang();
			$service->contentTranslation->change(['lang' => $lang, 'key' => $key, 'value' => $value], ['value' => $value]);
			return $this->ok(['status' => 'ok']);
		} catch (\Sy\Db\MySql\Exception $e) {
			$this->logError($e);
			return $this->serverError([
				'status' => 'ko',
				'message' => $this->_('Database error'),
			]);
		}
	}

}