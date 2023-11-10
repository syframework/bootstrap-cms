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
			#sy-code-modal .ace_editor {font-size: 14px;}
			#sy-code-modal div.alert {display: none;}
		');

		$this->setAttributes([
			'id'    => 'code_form_' . $this->id,
			'class' => 'tab-content',
		]);

		// Load content
		$service = \Project\Service\Container::getInstance();
		$content = $service->content->retrieve(['id' => $this->id]);

		// HTML
		$htmlArea = new CodeArea();
		$htmlArea->setAttributes([
			'name'        => 'html',
			'id'          => 'codearea_html_' . $this->id,
			'placeholder' => 'HTML Code here...',
		]);
		$htmlArea->setMode('php');
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

			$this->setSuccess($this->_('Source code updated successfully'));
		} catch (\Sy\Component\Html\Form\Exception $e) {
			$this->logWarning($e);
			$this->setError($this->_('Please fill the form correctly'));
			$this->cssArea->setContent(Str::escape($scss));
			$this->jsArea->setContent(Str::escape($js));
		} catch (\Sy\Db\MySql\Exception $e) {
			if ($e->getCode() === 1644) {
				$this->setSuccess($this->_('No change detected'));
			}
			$this->logWarning($e);
			$this->setError($this->_('Database error'));
			$this->cssArea->setContent(Str::escape($scss));
			$this->jsArea->setContent(Str::escape($js));
		} catch (\ScssPhp\ScssPhp\Exception\ParserException $e) {
			$this->logWarning($e);
			$this->setError('SCSS ' . $e->getMessage());
			$this->cssArea->setContent(Str::escape($scss));
			$this->jsArea->setContent(Str::escape($js));
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