<?php
namespace Sy\Bootstrap\Component\Cms\PageFeed;

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
		// Visibility
		$visibility = [
			'private'   => ['name' => $this->_('Private page'), 'color' => 'danger'],
			'protected' => ['name' => $this->_('Secret page'),  'color' => 'warning'],
			'public'    => ['name' => $this->_('Public page'),  'color' => 'success'],
		];
		$date = new \Sy\Bootstrap\Lib\Date($data['updated_at']);
		$this->setVars([
			'ALIAS'       => $data['alias'],
			'UPDATED_AT'  => $data['updated_at'],
			'TITLE'       => empty($data['title']) ? $this->_('No title') : Str::escape($data['title']),
			'DESCRIPTION' => Str::escape($data['description']),
			'TIMESTAMP'   => $date->timestamp(),
			'DATE'        => $date->humanTimeDiff(),
			'DATE_FULL'   => $date->date(),
			'URL'         => Url::build('page', 'content', ['id' => $data['id']]),
			'VISIBILITY'  => $visibility[$data['visibility']]['name'],
			'COLOR'       => $visibility[$data['visibility']]['color'],
		]);

		// Home page
		if ((int)$data['id'] === 1) {
			$this->setBlock('HOME_BLOCK');
		}
	}

}