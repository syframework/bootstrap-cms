<?php
namespace Sy\Bootstrap\Component\Cms\PageFeed;

class Page extends \Sy\Component\WebComponent {

	/**
	 * Updated at
	 *
	 * @var string
	 */
	private $n;

	/**
	 * @param string|null $n
	 */
	public function __construct($n) {
		parent::__construct();
		$this->n = $n;

		$this->mount(function () {
			$this->init();
		});
	}

	private function init() {
		$this->setTemplateFile(__DIR__ . '/Page.html');

		if (is_null($this->n)) {
			$item = new Item(null);
			$this->mergeCss($item);
			$this->mergeJs($item);
			$this->setVar('ITEM', '');
			$this->setBlock('ITEM_BLOCK');
			return;
		}

		$service = \Project\Service\Container::getInstance();
		try {
			$conditions = ['ORDER BY' => 'updated_at DESC', 'LIMIT' => 50];
			if (!empty($this->n)) {
				$conditions['WHERE'] = ['updated_at' => ['<' => $this->n]];
			}
			$pages = $service->content->retrieveAll($conditions);
		} catch (\Sy\Db\MySql\Exception $e) {
			$pages = [];
		}

		foreach ($pages as $page) {
			$item = new Item($page);
			$this->setVar('ITEM', $item);
			$this->setBlock('ITEM_BLOCK');
		}
	}

}