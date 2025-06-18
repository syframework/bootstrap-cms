<?php
namespace Sy\Bootstrap\Component\Cms\HistoryFeed;

class Page extends \Sy\Component\WebComponent {

	private $datetime;

	private $contentId;

	public function __construct($datetime, $contentId) {
		parent::__construct();
		$this->datetime = $datetime;
		$this->contentId = $contentId;

		$this->mount(function () {
			$this->init();
		});
	}

	private function init() {
		$this->setTemplateFile(__DIR__ . '/Page.html');

		if (is_null($this->datetime)) {
			$item = new Item(null);
			$this->mergeCss($item);
			$this->mergeJs($item);
			$this->setVar('ITEM', '');
			$this->setBlock('ITEM_BLOCK');
			return;
		}

		$service = \Project\Service\Container::getInstance();
		try {
			$versions = $service->contentHistory->retrieveContentVersions($this->contentId, $this->datetime);
		} catch (\Sy\Db\MySql\Exception $e) {
			$versions = [];
		}

		foreach ($versions as $version) {
			$item = new Item($version);
			$this->setVar('ITEM', $item);
			$this->setBlock('ITEM_BLOCK');
		}
	}

}