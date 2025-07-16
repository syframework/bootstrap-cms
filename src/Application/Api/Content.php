<?php
namespace Sy\Bootstrap\Application\Api;

class Content extends \Sy\Bootstrap\Component\Api {

	public function security() {
		if (is_null($this->request('id'))) {
			throw new \Sy\Bootstrap\Component\Api\RequestErrorException('Missing page content id');
		}

		$service = \Project\Service\Container::getInstance();
		if (!$service->user->getCurrentUser()->hasPermission('content-update')) {
			throw new \Sy\Bootstrap\Component\Api\ForbiddenException('Permission denied');
		}
	}

	/**
	 * Retrieve content page
	 */
	public function getAction() {
		$id = $this->get('id');
		if (is_null($id)) {
			return $this->requestError([
				'status'  => 'ko',
				'message' => 'Missing id parameter',
			]);
		}
		$service = \Project\Service\Container::getInstance();
		$version = $this->get('version');

		if (is_null($version)) {
			$content = $service->content->retrieve(['id' => $id]);
		} else {
			$content = $service->contentHistory->retrieve(['id' => $id, 'crc32' => $version]);
		}

		if (empty($content)) {
			return $this->notFound([
				'status'  => 'ko',
				'message' => 'Content not found',
			]);
		}

		return $this->ok([
			'status'  => 'ok',
			'html'    => $content['html'],
			'js'      => $content['js'],
		]);
	}

	/**
	 * Update content page
	 */
	public function postAction() {
		$service = \Project\Service\Container::getInstance();
		try {
			// Update page
			$id   = $this->post('id');
			$html = $this->post('content');
			$csrf = $this->post('csrf');
			if ($csrf !== $service->user->getCsrfToken()) {
				return $this->requestError([
					'status'  => 'ko',
					'message' => $this->_('You have taken too long to submit the form please try again'),
					'csrf'    => $service->user->getCsrfToken(),
				]);
			}
			if (is_null($id) or is_null($html)) {
				return $this->requestError([
					'status'  => 'ko',
					'message' => 'Missing id or content parameter',
				]);
			}

			// Save new html
			$service->content->update(['id' => $id], ['html' => $html, 'updator_id' => $service->user->getCurrentUser()->id]);
			return $this->ok(['status' => 'ok']);
		} catch (\Sy\Db\MySql\Exception $e) {
			$this->logError($e);
			if ($e->getCode() === 1644) {
				return $this->ok(['status' => 'ok', 'message' => $this->_('No change detected')]);
			}
			return $this->serverError([
				'status' => 'ko',
				'message' => $this->_('Database error'),
			]);
		}
	}

	/**
	 * Check if content page alias is valid
	 */
	public function validAction() {
		$alias = $this->get('id');

		// Check if alias contains invalid characters
		if (preg_match('/[^a-z0-9\-]/', $alias)) {
			return $this->ok([
				'valid'   => false,
				'message' => $this->_('Unauthorized character in the alias'),
			]);
		}

		$service = \Project\Service\Container::getInstance();
		$valid = $service->content->count(['alias' => $alias]);
		return $this->ok([
			'valid'   => ($valid === 0),
			'message' => ($valid > 0) ? $this->_('Alias already exists') : $this->_('Alias is available'),
		]);
	}

}