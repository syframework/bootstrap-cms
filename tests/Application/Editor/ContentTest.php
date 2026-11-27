<?php
namespace Sy\Test\Application\Editor;

use PHPUnit\Framework\TestCase;
use Sy\Bootstrap\Application\Editor\Content;

/**
 * The browser endpoints of the CMS editor: browse/upload come from `CkFile`,
 * the component preview from the shared `Render` trait (wysiwyg 1.9) and the
 * component catalogue from `Metadata` (wysiwyg 1.10).
 */
class ContentTest extends TestCase {

	public function testUsesTheSharedEditorTraits() {
		$reflection = new \ReflectionClass(Content::class);

		$this->assertContains('Sy\Bootstrap\Application\Editor\CkFile', $reflection->getTraitNames());
		$this->assertContains('Sy\Bootstrap\Application\Editor\Render', $reflection->getTraitNames());
		$this->assertTrue($reflection->hasMethod('renderAction'));
		$this->assertContains('Sy\Bootstrap\Application\Editor\Metadata', $reflection->getTraitNames());
		$this->assertTrue($reflection->hasMethod('metadataAction'));
	}

	public function testRenderActionComesFromTheEngine() {
		$file = (new \ReflectionMethod(Content::class, 'renderAction'))->getFileName();

		// One implementation, shipped by wysiwyg: the consumer declares none.
		$this->assertStringEndsWith(
			'Application/Editor/Render.php',
			str_replace('\\', '/', $file)
		);
	}

	public function testRenderTranslatorsIncludeThePackageDirectory() {
		$instance = (new \ReflectionClass(Content::class))->newInstanceWithoutConstructor();
		// ReflectionMethod::setAccessible() is a no-op since PHP 8.1.
		$method = new \ReflectionMethod(Content::class, 'renderTranslators');

		$directories = $method->invoke($instance, 'fr');

		$this->assertContains(LANG_DIR, $directories);
		$this->assertContains(
			realpath(__DIR__ . '/../../../lang/bootstrap-cms'),
			array_map('realpath', $directories)
		);
	}

}
