<?php
namespace Project\Service;

use Sy\Bootstrap\Service\NullService;

class Container extends \Sy\Bootstrap\Service\Container {

	public function __construct() {
		parent::__construct();

		$this->user = function () {
			return new NullService();
		};
	}

}