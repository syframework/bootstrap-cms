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
	 *
	 * @return void
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
		$content = $service->content->retrieve(['id' => $id]);

		if (empty($content)) {
			return $this->notFound([
				'status'  => 'ko',
				'message' => 'Content not found',
			]);
		}

		return $this->ok([
			'status'  => 'ok',
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

			// Compare with current version
			$content = $service->content->retrieve(['id' => $id]);
			if (!empty($content) and ($content['html'] === $html)) {
				return $this->ok(['status' > 'ok', 'message' => 'No change detected']);
			}

			// Save current version in t_content_history
			$service->contentHistory->create([
				'crc32'       => crc32($content['title'] . $content['description'] . $content['html'] . $content['scss'] . $content['css'] . $content['js'] . $content['updator_id'] . $content['updated_at']),
				'title'       => $content['title'],
				'description' => $content['description'],
				'html'        => $content['html'],
				'scss'        => $content['scss'],
				'css'         => $content['css'],
				'js'          => $content['js'],
				'updator_id'  => $content['updator_id'] ?? null,
				'updated_at'  => $content['updated_id'] ?? null,
			]);

			// Save new html
			$service->content->update(['id' => $id], ['html' => $html, 'updator_id' => $service->user->getCurrentUser()->id]);
			return $this->ok(['status' => 'ok']);
		} catch (\Sy\Db\MySql\Exception $e) {
			return $this->serverError([
				'status' => 'ko',
				'message' => $this->_('Database error'),
			]);
		}
	}

}