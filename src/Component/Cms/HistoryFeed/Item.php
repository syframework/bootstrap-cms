<?php
namespace Sy\Bootstrap\Component\Cms\HistoryFeed;

use Sy\Bootstrap\Lib\Str;
use Sy\Bootstrap\Lib\Url;

class Item extends \Sy\Component\WebComponent {

	private $data;

	public function __construct($data) {
		parent::__construct();
		$this->data = $data;

		$this->mount(function () {
			$this->init();
		});
	}

	private function init() {
		$this->addTranslator(__DIR__ . '/../../../../lang/bootstrap-cms');
		$this->setTemplateFile(__DIR__ . '/Item.html');

		$data = $this->data;
		$date = new \Sy\Bootstrap\Lib\Date($data['updated_at']);
		$this->setVars([
			'ID'          => $data['updated_at'],
			'TITLE'       => Str::escape($data['title']),
			'DESCRIPTION' => Str::escape($data['description']),
			'USERNAME'    => Str::convertName($data['username']),
			'USERIMG'     => Url::avatar($data['email']),
			'TIMESTAMP'   => $date->timestamp(),
			'DATE'        => $date->humanTimeDiff(),
			'DATE_FULL'   => $date->date(),
			'URL'         => Url::build('page', 'content', ['id' => $data['id'], 'version' => $data['crc32']]),
		]);

		if ((int)$this->get('version') === (int)$data['crc32']) {
			$this->setBlock('CURRENT_BLOCK');
		}
	}

}