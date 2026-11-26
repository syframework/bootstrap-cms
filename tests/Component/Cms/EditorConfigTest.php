<?php
namespace Sy\Test\Component\Cms;

use PHPUnit\Framework\TestCase;
use Sy\Bootstrap\Component\Cms\EditorConfig;

class EditorConfigTest extends TestCase {

	private function build(array $overrides = []) {
		return EditorConfig::build(
			7,
			'fr',
			'token',
			$overrides['urls'] ?? [
				'save'         => '/api/content',
				'csrf'         => '/api/csrf',
				'image_browse' => '/editor/content/browse?type=image',
				'image_upload' => '/editor/content/upload?type=image',
				'file_browse'  => '/editor/content/browse?type=file',
				'file_upload'  => '/editor/content/upload?type=file',
				'translate'    => '/api/content-translation',
			],
			$overrides['assets'] ?? [
				'engine'       => '/assets/js/wysiwyg.js',
				'ckeditorJs'   => '/ckeditor.js',
				'ckeditorRoot' => '/assets/ckeditor',
				'plugins'      => '/assets/js/plugins',
			],
			$overrides['allowlist'] ?? [],
			$overrides['translations'] ?? ['hello' => 'bonjour'],
			$overrides['labels'] ?? ['language' => 'Langue', 'key' => 'Clé', 'value' => 'Valeur', 'add' => 'Ajouter'],
			$overrides['uiLang'] ?? null
		);
	}

	public function testContainerAndElement() {
		$config = $this->build();

		$this->assertSame('sy-content', $config['element-id']);
		$this->assertFalse($config['container']);
		$this->assertTrue($config['editable']);
		$this->assertSame('inline', $config['mode']);
	}

	public function testAdapterOwnsTheLoad() {
		$config = $this->build();

		// D7: the CMS adapter loads the source itself, the engine never GETs.
		$this->assertArrayNotHasKey('load-url', $config);
		$this->assertSame('/api/content', $config['save-url']);
		$this->assertSame(['id' => 7], $config['save-data']);
	}

	public function testCsrfAndGuard() {
		$config = $this->build();

		$this->assertSame('token', $config['csrf']);
		$this->assertSame('/api/csrf', $config['csrf-url']);
		$this->assertSame(1200, $config['csrf-refresh']);
		$this->assertFalse($config['guard-unload']);
		$this->assertSame(0, $config['autosave']['interval']);
		$this->assertTrue($config['autosave']['on-blur']);
		$this->assertFalse($config['autosave']['on-dialog-close']);
	}

	public function testAllowedContentHasNoStyle() {
		$config = $this->build();

		$preset = $config['allowed-content']['$1'];
		$this->assertSame(['@dtd' => true], $preset['elements']);
		$this->assertFalse($preset['styles']);
		$this->assertTrue($preset['classes']);
		$this->assertSame(['span', 'i'], $config['preserve-empty']);
	}

	public function testPluginsAndSharedSpace() {
		$config = $this->build();

		$this->assertContains('sharedspace', $config['extra-plugins']);
		$this->assertContains('sytranslate', $config['extra-plugins']);
		$this->assertContains('sywidget', $config['extra-plugins']);
		$this->assertNotContains('sycomponent', $config['extra-plugins']);
		$this->assertSame(['about', 'exportpdf'], $config['remove-plugins']);
		$this->assertSame('sy-page-topbar', $config['config']['sharedSpaces']['top']);
		$this->assertSame('sy-page-bottombar', $config['config']['sharedSpaces']['bottom']);
	}

	public function testSycomponentOnlyWithAnAllowlist() {
		$config = $this->build(['allowlist' => ['Project\\Component\\Cms\\Widget']]);

		$this->assertContains('sycomponent', $config['extra-plugins']);
		$this->assertTrue($config['components']['enabled']);
		$this->assertSame(['Project\\Component\\Cms\\Widget'], $config['components']['allowlist']);
	}

	public function testEmptyAllowlistDisablesTheFilter() {
		$config = $this->build();

		$this->assertFalse($config['components']['enabled']);
		$this->assertSame([], $config['components']['allowlist']);
	}

	public function testComponentPreviewUsesTheRenderEndpoint() {
		$allowlist = ['Project\\Component\\Map'];

		// Without a render URL the widget keeps its "save to render" mask.
		$config = $this->build(['allowlist' => $allowlist]);
		$this->assertSame($allowlist, $config['components']['allowlist']);
		$this->assertNull($config['components']['render-url']);

		// With one, the widget posts `component` + `args` after an insertion or
		// an edit; the endpoint is shared through Editor\Render (wysiwyg 1.9).
		$config = $this->build([
			'allowlist' => $allowlist,
			'urls'      => [
				'save'         => '/api/content',
				'csrf'         => '/api/csrf',
				'image_browse' => '/editor/content/browse?type=image',
				'image_upload' => '/editor/content/upload?type=image',
				'file_browse'  => '/editor/content/browse?type=file',
				'file_upload'  => '/editor/content/upload?type=file',
				'translate'    => '/api/content-translation',
				'render'       => '/editor/content/render?id=7',
			],
		]);
		$this->assertSame('/editor/content/render?id=7', $config['components']['render-url']);
	}

	public function testTranslationOptions() {
		$config = $this->build();

		$this->assertSame(['hello' => 'bonjour'], $config['translations']);
		$this->assertSame('/api/content-translation', $config['translate-url']);
		$this->assertSame('fr', $config['translate-lang']);
		$this->assertSame('Clé', $config['translate-labels']['key']);
	}

	public function testSharedSpaceTargetsLiveInTheIframeTemplate() {
		$template = file_get_contents(__DIR__ . '/../../../src/Component/Cms/Content.html');

		// The sharedspace targets are the CMS responsibility, not bootstrap.
		$this->assertStringContainsString('<div id="sy-page-topbar"></div>', $template);
		$this->assertStringContainsString('<div id="sy-page-bottombar"></div>', $template);

		$css = file_get_contents(__DIR__ . '/../../../src/Component/Cms/Iframe.css');
		$this->assertStringContainsString('#sy-page-topbar', $css);
		$this->assertStringContainsString('#sy-page-bottombar', $css);
	}

	public function testNoTemplates() {
		$config = $this->build();

		// D25: CMS ships no CKEditor templates; a project can opt in.
		$this->assertArrayNotHasKey('templates', $config);
		$this->assertArrayNotHasKey('templates-files', $config);
	}

	public function testUiLanguageIsSeparateFromTheContentLanguage() {
		$config = $this->build(['uiLang' => 'en']);

		$this->assertSame('en', $config['config']['language']);
		$this->assertSame('fr', $config['translate-lang']);
	}

}
