<?php
namespace Sy\Bootstrap\Component\Cms;

use Masterminds\HTML5;
use Sy\Bootstrap\Component\Form\Element\CodeArea;
use Sy\Bootstrap\Lib\Str;

class Code extends \Sy\Bootstrap\Component\Form {

	private $id;

	/**
	 * @var CodeArea
	 */
	private $cssArea;

	/**
	 * @var CodeArea
	 */
	private $jsArea;

	public function __construct($id) {
		parent::__construct();
		$this->id = $id;
	}

	public function init() {
		$this->addCssCode('
			#sy-code-modal div.alert {display: none;}
		');

		$this->setAttributes([
			'id'     => 'code_form_' . $this->id,
			'class'  => 'tab-content',
			'action' => isset($_SERVER['REQUEST_URI']) ? parse_url($_SERVER['REQUEST_URI'])['path'] : '',
		]);

		$this->addCsrfField();

		// Load content
		$service = \Project\Service\Container::getInstance();
		$version = $this->get('version');

		if (is_null($version)) {
			$content = $service->content->retrieve(['id' => $this->id]);
		} else {
			$content = $service->contentHistory->retrieve(['id' => $this->id, 'crc32' => $version]);
		}

		// HTML
		$htmlArea = new CodeArea();
		$htmlArea->setAttributes([
			'name'        => 'html',
			'id'          => 'codearea_html_' . $this->id,
			'placeholder' => 'HTML Code here...',
		]);
		$htmlArea->setMode('html_elixir');
		$htmlArea->setTheme('monokai');

		$this->addDiv([
			'class'           => 'tab-pane fade show active',
			'id'              => 'sy-html-tab-content',
			'role'            => 'tabpanel',
			'aria-labelledby' => 'html-tab',
		])->addElement($htmlArea);

		// CSS
		$cssArea = new CodeArea();
		$cssArea->setAttributes([
			'name'        => 'css',
			'id'          => 'codearea_css_' . $this->id,
			'placeholder' => 'CSS Code here...',
		]);
		$cssArea->setMode('scss');
		$cssArea->setTheme('monokai');
		$this->cssArea = $cssArea;

		if (!empty($content)) {
			$cssArea->addText(Str::escape($content['scss']));
		}

		$this->addDiv([
			'class'           => 'tab-pane fade',
			'id'              => 'sy-css-tab-content',
			'role'            => 'tabpanel',
			'aria-labelledby' => 'css-tab',
		])->addElement($cssArea);

		// JS
		$jsArea = new CodeArea();
		$jsArea->setAttributes([
			'name'        => 'js',
			'id'          => 'codearea_js_' . $this->id,
			'placeholder' => 'JS Code here...',
		]);
		$jsArea->setMode('javascript');
		$jsArea->setTheme('monokai');
		$this->jsArea = $jsArea;

		if (!empty($content)) {
			$jsArea->addText(Str::escape($content['js']));
		}

		$this->addDiv([
			'class'           => 'tab-pane fade',
			'id'              => 'sy-js-tab-content',
			'role'            => 'tabpanel',
			'aria-labelledby' => 'js-tab',
		])->addElement($jsArea);
	}

	public function submitAction() {
		try {
			$this->validatePost();
			$html = $this->post('html');
			$scss = $this->post('css');
			$js   = $this->post('js');

			// Check HTML
			$html = self::checkHtml($html);

			// Compile scss
			$css = self::compileScss($scss);

			// Save content
			$service = \Project\Service\Container::getInstance();
			$service->content->update(['id' => $this->id], [
				'html'       => $html,
				'scss'       => $scss,
				'css'        => $css,
				'js'         => $js,
				'updator_id' => $service->user->getCurrentUser()->id,
			]);

			return $this->jsonSuccess('Source code updated successfully');
		} catch (\Sy\Component\Html\Form\Exception $e) {
			$this->logWarning($e);
			return $this->jsonError('Please fill the form correctly');
		} catch (\Sy\Db\MySql\Exception $e) {
			if ($e->getCode() === 1644) {
				return $this->jsonSuccess('No change detected', ['color' => 'info']);
			}
			$this->logWarning($e);
			return $this->jsonError('Database error');
		} catch (\ScssPhp\ScssPhp\Exception\ParserException $e) {
			$this->logWarning($e);
			return $this->jsonError('SCSS ' . $e->getMessage());
		}
	}

	/**
	 * @param  string $html
	 * @return string
	 */
	public static function checkHtml($html) {
		$html5 = new HTML5();
		$dom = $html5->loadHTMLFragment($html);
		return $html5->saveHTML($dom);
	}

	/**
	 * @param  string $scss
	 * @return string
	 */
	public static function compileScss($scss) {
		$compiler = new \ScssPhp\ScssPhp\Compiler();
		return $compiler->compileString($scss)->getCss();
	}

}