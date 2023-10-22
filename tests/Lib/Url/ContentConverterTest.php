<?php
namespace Sy\Test\Lib\Url;

use PHPUnit\Framework\TestCase;
use Sy\Bootstrap\Lib\Url\ContentConverter;
use Sy\Db\MySql\Gate;

class ContentConverterTest extends TestCase {

	public function testParamsToUrl() {
		$converter = new ContentConverter();

		$params = [
			ACTION_TRIGGER => 'foo',
		];
		$this->assertEquals(false, $converter->paramsToUrl($params));

		$params = [
			CONTROLLER_TRIGGER => 'foo',
		];
		$this->assertEquals(false, $converter->paramsToUrl($params));

		$params = [
			CONTROLLER_TRIGGER => 'foo',
			ACTION_TRIGGER => 'bar',
		];
		$this->assertEquals(false, $converter->paramsToUrl($params));

		$params = [
			CONTROLLER_TRIGGER => 'foo',
			ACTION_TRIGGER => 'bar',
		];
		$this->assertEquals(false, $converter->paramsToUrl($params));

		$params = [
			CONTROLLER_TRIGGER => 'page',
			ACTION_TRIGGER => 'content',
			'alias' => 'foo',
		];
		$this->assertEquals(WEB_ROOT . '/foo', $converter->paramsToUrl($params));

		$params = [
			CONTROLLER_TRIGGER => 'page',
			ACTION_TRIGGER => 'content',
			'id' => '2',
		];
		$this->assertEquals(WEB_ROOT . '/foo', $converter->paramsToUrl($params));

		$params = [
			CONTROLLER_TRIGGER => 'page',
			ACTION_TRIGGER => 'content',
			'alias' => '',
		];
		$this->assertEquals(WEB_ROOT, $converter->paramsToUrl($params));

		$params = [
			CONTROLLER_TRIGGER => 'page',
			ACTION_TRIGGER => 'content',
			'id' => '1',
		];
		$this->assertEquals(WEB_ROOT, $converter->paramsToUrl($params));

		$params = [
			CONTROLLER_TRIGGER => 'page',
			ACTION_TRIGGER => 'content',
			'id' => '3',
			'p1' => 'one',
			'p2' => 'two',
		];
		$this->assertEquals(WEB_ROOT . '/bar?p1=one&p2=two', $converter->paramsToUrl($params));

		// // Lang parameter
		$params = [
			CONTROLLER_TRIGGER => 'page',
			ACTION_TRIGGER => 'content',
			'alias' => 'foo',
			'lang' => 'en',
		];
		$this->assertEquals(WEB_ROOT . '/en/foo', $converter->paramsToUrl($params));
		$params = [
			CONTROLLER_TRIGGER => 'page',
			ACTION_TRIGGER => 'content',
			'id' => '2',
			'lang' => 'en',
		];
		$this->assertEquals(WEB_ROOT . '/en/foo', $converter->paramsToUrl($params));
		$params = [
			CONTROLLER_TRIGGER => 'page',
			ACTION_TRIGGER => 'content',
			'alias' => '',
			'lang' => 'en',
		];
		$this->assertEquals(WEB_ROOT . '/en', $converter->paramsToUrl($params));
		$params = [
			CONTROLLER_TRIGGER => 'page',
			ACTION_TRIGGER => 'content',
			'id' => '1',
			'lang' => 'en',
		];
		$this->assertEquals(WEB_ROOT . '/en', $converter->paramsToUrl($params));
		$params = [
			CONTROLLER_TRIGGER => 'page',
			ACTION_TRIGGER => 'content',
			'id' => '1',
			'alias' => '',
			'lang' => 'none',
		];
		$this->assertEquals(WEB_ROOT, $converter->paramsToUrl($params));
	}

	public function testUrlToParams() {
		$converter = new ContentConverter();

		$this->assertEquals(false, $converter->urlToParams(null));
		$this->assertEquals(false, $converter->urlToParams(''));
		$this->assertEquals(false, $converter->urlToParams(WEB_ROOT . '/'));
		$this->assertEquals(false, $converter->urlToParams(WEB_ROOT));

		$params = [
			CONTROLLER_TRIGGER => 'page',
			ACTION_TRIGGER => 'content',
			'id' => '2',
		];
		$this->assertEquals($params, $converter->urlToParams(WEB_ROOT . '/foo'));

		$params = [
			CONTROLLER_TRIGGER => 'page',
			ACTION_TRIGGER => 'content',
			'id' => '2',
		];
		$this->assertEquals($params, $converter->urlToParams(WEB_ROOT . '/foo/'));

		$params = [
			CONTROLLER_TRIGGER => 'page',
			ACTION_TRIGGER => 'content',
			'id' => '2',
			'other' => 'baz',
		];
		$this->assertEquals($params, $converter->urlToParams(WEB_ROOT . '/foo?other=baz'));

		$params = [
			CONTROLLER_TRIGGER => 'page',
			ACTION_TRIGGER => 'content',
			'id' => '2',
			'other' => 'baz',
		];
		$this->assertEquals($params, $converter->urlToParams(WEB_ROOT . '/foo/?other=baz'));

		$params = [
			CONTROLLER_TRIGGER => 'page',
			ACTION_TRIGGER => 'content',
			'id' => '2',
			'p1' => 'one',
			'p2' => 'two',
		];
		$this->assertEquals($params, $converter->urlToParams(WEB_ROOT . '/foo?p1=one&p2=two'));

		$this->assertEquals(false, $converter->urlToParams(WEB_ROOT . '/foo/bar/hello/world?p1=one&p2=two'));

		// Lang param
		$params = [
			CONTROLLER_TRIGGER => 'page',
			ACTION_TRIGGER => 'content',
			'id' => '2',
			'other' => 'baz',
			'lang' => 'en',
		];
		$this->assertEquals($params, $converter->urlToParams(WEB_ROOT . '/en/foo?other=baz'));
	}

	protected function setUp(): void {
		$gate = new Gate(DATABASE_CONFIG);
		$gate->execute("
			CREATE TABLE IF NOT EXISTS `t_content` (
				`id` int UNSIGNED NOT NULL AUTO_INCREMENT,
				`alias` varchar(128) NOT NULL DEFAULT '',
				`title` varchar(128) NOT NULL DEFAULT '',
				`description` varchar(512) NOT NULL DEFAULT '' COMMENT 'textarea',
				`html` mediumtext NOT NULL COMMENT 'none',
				`scss` text NOT NULL COMMENT 'none',
				`css` text NOT NULL COMMENT 'none',
				`js` text NOT NULL COMMENT 'none',
				`visibility` enum('private','public') NOT NULL DEFAULT 'private' COMMENT 'select',
				`creator_id` int UNSIGNED NULL DEFAULT NULL COMMENT 'none',
				`updator_id` int UNSIGNED NULL DEFAULT NULL COMMENT 'none',
				`created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'none',
				`updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'none',
				PRIMARY KEY (`id`),
				UNIQUE INDEX `alias`(`alias` ASC) USING BTREE
			);
			INSERT IGNORE INTO `t_content` (`id`, `alias`, `title`, `description`, `html`, `scss`, `css`, `js`) VALUES (1, '', 'Home page', 'This is the home page', '<div>Hello world!</div>', '', '', '');
			INSERT IGNORE INTO `t_content` (`id`, `alias`, `title`, `description`, `html`, `scss`, `css`, `js`) VALUES (2, 'foo', 'Foo page', 'This is the foo page', '<div>Hello world!</div>', '', '', '');
			INSERT IGNORE INTO `t_content` (`id`, `alias`, `title`, `description`, `html`, `scss`, `css`, `js`) VALUES (3, 'bar', 'Bar page', 'This is the bar page', '<div>Hello world!</div>', '', '', '');
		");
	}

}