<?php
namespace Sy\Bootstrap\Application\Editor;

class Content extends \Sy\Bootstrap\Component\Api {

	use CkFile;

	public function security() {
		if (is_null($this->request('id'))) {
			throw new \Sy\Bootstrap\Component\Api\RequestErrorException('Missing page content id');
		}

		$service = \Project\Service\Container::getInstance();
		if (!$service->user->getCurrentUser()->hasPermission('content-update')) {
			throw new \Sy\Bootstrap\Component\Api\ForbiddenException('Permission denied');
		}
	}

}