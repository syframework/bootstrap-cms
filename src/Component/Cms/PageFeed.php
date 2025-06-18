<?php
namespace Sy\Bootstrap\Component\Cms;

class PageFeed extends \Sy\Bootstrap\Component\Feed {

	public function __construct() {
		parent::__construct(0, 'DOWN', false);
	}

	public function getPage($n) {
		if (is_null($n)) $n = 0;
		return new PageFeed\Page($n);
	}

	public function isLastPage($n) {
		try {
			if (is_null($n)) $n = 0;
			$service = \Project\Service\Container::getInstance();
			$where = null;
			if (!empty($n)) {
				$where = ['updated_at' => ['<' => $n]];
			}
			$nb = $service->content->count($where);
			return $nb <= 50;
		} catch (\Sy\Db\MySql\Exception $e) {
			$this->logError('SQL Error');
			return true;
		}
	}

}