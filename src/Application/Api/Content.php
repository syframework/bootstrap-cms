<?php
namespace Sy\Bootstrap\Application\Api;

class Content extends \Sy\Bootstrap\Component\Api {

	public function security() {
		$service = \Project\Service\Container::getInstance();
		if (!$service->user->getCurrentUser()->hasPermission('content-update')) {
			throw new \Sy\Bootstrap\Component\Api\ForbiddenException('Permission denied');
		}
	}

	/**
	 * Retrieve content page
	 *
	 * @return void
	 */
	public function getAction() {
		$id   = $this->get('id');
		$lang = $this->get('lang');
		if (is_null($id) or is_null($lang)) {
			return $this->requestError();
		}
		$service = \Project\Service\Container::getInstance();
		$content = $service->content->retrieve(['id' => $id, 'lang' => $lang]);

		if (empty($content)) {
			return $this->requestError([
				'status' => 'ko',
				'message' => 'Content not found',
			]);
		}

		return $this->ok([
			'status' => 'ok',
			'content' => $content['html'],
		]);
	}

	/**
	 * Update content page
	 *
	 * @return void
	 */
	public function postAction() {
		$service = \Project\Service\Container::getInstance();
		try {
			// Update page
			$id      = $this->post('id');
			$lang    = $this->post('lang');
			$content = $this->post('content');
			$csrf    = $this->post('csrf');
			if ($csrf !== $service->user->getCsrfToken()) {
				return $this->requestError([
					'status'  => 'ko',
					'message' => $this->_('You have taken too long to submit the form please try again'),
					'csrf'    => $service->user->getCsrfToken(),
				]);
			}
			if (is_null($id) or is_null($lang) or is_null($content)) $this->requestError();

			// TODO: Save a version in t_page_history
			// $service->pageHistory->change([
			// 	'user_id'      => $service->user->getCurrentUser()->id,
			// 	'page_id'      => $id,
			// 	'page_crc32'   => crc32($content),
			// 	'page_content' => $content,
			// ], [
			// 	'user_id'    => $service->user->getCurrentUser()->id,
			// 	'updated_at' => date('Y-m-d H:i:s'),
			// ]);

			// Save content
			$service->content->update(['id' => $id, 'lang' => $lang], ['html' => $content]);
			return $this->ok(['status' => 'ok']);
		} catch (\Sy\Db\MySql\Exception $e) {
			return $this->serverError([
				'status' => 'ko',
				'message' => $this->_('Database error'),
			]);
		}
	}

}