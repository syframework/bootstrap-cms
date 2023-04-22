<?php
namespace Sy\Bootstrap\Component\Cms;

class HistoryFeed extends \Sy\Bootstrap\Component\Feed {

	/**
	 * @var int
	 */
	private $contentId;

	/**
	 * @param int|null $contentId
	 */
	public function __construct($contentId = null) {
		parent::__construct(0, 'DOWN', false);
		$this->contentId = is_null($contentId) ? $this->get('content_id') : $contentId;
	}

	public function getPage($n) {
		if (is_null($n)) $n = 0;
		return new HistoryFeed\Page($n, $this->contentId);
	}

	public function isLastPage($n) {
		try {
			if (is_null($n)) $n = 0;
			$service = \Project\Service\Container::getInstance();
			$nb = $service->contentHistory->countContentVersions($this->contentId, $n);
			return $nb <= 10;
		} catch (\Sy\Bootstrap\Service\Crud\Exception $e) {
			$this->logError('SQL Error');
			return true;
		}
	}

	public function getParams() {
		return [
			'content_id' => $this->contentId,
			'version'    => $this->get('version'),
		];
	}

}