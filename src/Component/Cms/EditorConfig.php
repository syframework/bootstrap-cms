<?php
namespace Sy\Bootstrap\Component\Cms;

/**
 * CKEditor configuration for the CMS iframe editor.
 *
 * The iframe adapter owns the source loading (no `load-url`): it fetches the
 * source, restores the rendered components and then starts the engine. The
 * editor element `#sy-content` stays in `Content.html` (`container => false`).
 *
 * `styles` is false: HTML written through the editor is sanitized on save, so
 * a style offered by the editor would be silently dropped.
 */
class EditorConfig {

	/**
	 * Component classes the CMS authorizes by default.
	 *
	 * Empty on purpose: the CMS content is data-driven and the framework owns
	 * no CMS component class, so a project declares its own through
	 * `WYSIWYG_COMPONENT_ALLOWLIST['content']`. An empty list disables the
	 * filter instead of neutralizing every stored component.
	 */
	public const COMPONENT_ALLOWLIST = [];

	public const ALLOWED_CONTENT = [
		'$1' => [
			'elements'   => ['@dtd' => true],
			'attributes' => true,
			'styles'     => false,
			'classes'    => true,
		],
	];

	/**
	 * @param int|string  $id
	 * @param string      $lang            Content language
	 * @param string      $csrf
	 * @param array       $urls            save, csrf, image_browse, image_upload, file_browse, file_upload, translate, render, metadata
	 * @param array       $assets          engine, ckeditorJs, ckeditorRoot, plugins
	 * @param array       $componentAllowlist
	 * @param array       $translations    key => value map for the sytranslate widget
	 * @param array       $labels          language, key, value, add
	 * @param string|null $uiLang          CKEditor UI language (defaults to the content language)
	 * @return array
	 */
	public static function build(
		$id,
		$lang,
		$csrf,
		array $urls,
		array $assets,
		array $componentAllowlist = [],
		array $translations = [],
		array $labels = [],
		$uiLang = null
	) {
		$extraPlugins = ['sharedspace', 'sywidget', 'sytranslate', 'tableresize', 'uploadimage', 'uploadfile'];
		$insertItems  = ['Image', 'Table', 'HorizontalRule', 'Iframe', 'Sytranslate'];
		if ($componentAllowlist !== []) {
			$extraPlugins[] = 'sycomponent';
			array_unshift($insertItems, 'Sycomponent');
		}

		return [
			'element-id'  => 'sy-content',
			'mode'        => 'inline',
			'container'   => false,
			'editable'    => true,
			'content'     => '',
			'save-url'    => $urls['save'],
			'save-data'   => ['id' => $id],
			'csrf'        => $csrf,
			'csrf-url'    => $urls['csrf'],
			'csrf-refresh' => 1200,
			'upload'      => [
				'image' => [
					'browse' => $urls['image_browse'],
					'upload' => $urls['image_upload'],
					'mode'   => 'ajax',
					'window' => ['width' => 400, 'height' => 400],
				],
				'file' => [
					'browse' => $urls['file_browse'],
					'upload' => $urls['file_upload'],
					'mode'   => 'ajax',
					'window' => ['width' => 400, 'height' => 400],
				],
			],
			'allowed-content' => self::ALLOWED_CONTENT,
			'preserve-empty'  => ['span', 'i'],
			'normalize-empty-blocks' => true,
			'extra-plugins'   => $extraPlugins,
			'remove-plugins'  => ['about', 'exportpdf'],
			'components'      => [
				'enabled'      => $componentAllowlist !== [],
				'allowlist'    => $componentAllowlist,
				// Preview and catalogue endpoints shared by the consumers
				// (`Editor\Content`).
				'render-url'   => $urls['render'] ?? null,
				'metadata-url' => $urls['metadata'] ?? null,
				// Opt-in: a project that wants failures to be visible.
				'strict'       => defined('WYSIWYG_COMPONENT_STRICT') && WYSIWYG_COMPONENT_STRICT,
			],
			'translations'    => $translations,
			'translate-url'   => $urls['translate'] ?? null,
			'translate-lang'  => $lang,
			'translate-labels' => $labels,
			'config'          => [
				'language'                  => $uiLang ?: $lang,
				'entities'                  => false,
				'title'                     => false,
				'startupFocus'              => true,
				'linkShowAdvancedTab'       => false,
				'clipboard_handleImages'    => false,
				'justifyClasses'            => ['text-left', 'text-center', 'text-right', 'text-justify'],
				'sharedSpaces'              => [
					'top'    => 'sy-page-topbar',
					'bottom' => 'sy-page-bottombar',
				],
				'toolbar'                   => [
					['name' => 'document', 'items' => ['Sourcedialog', '-', 'Save']],
					['name' => 'clipboard', 'items' => ['Undo', 'Redo']],
					['name' => 'editing', 'items' => ['Find', 'Replace']],
					['name' => 'basicstyles', 'items' => ['Bold', 'Italic', 'Underline', 'Strike']],
					['name' => 'paragraph', 'items' => ['NumberedList', 'BulletedList', '-', 'JustifyLeft', 'JustifyCenter', 'JustifyRight']],
					['name' => 'links', 'items' => ['Link', 'Unlink']],
					['name' => 'insert', 'items' => $insertItems],
					['name' => 'styles', 'items' => ['Format']],
					['name' => 'colors', 'items' => ['TextColor', 'BGColor']],
				],
			],
			'autosave'     => ['interval' => 0, 'on-blur' => true, 'on-dialog-close' => false],
			'guard-unload' => false,
			'assets'       => [
				'engine'        => $assets['engine'],
				'ckeditor-js'   => $assets['ckeditorJs'],
				'ckeditor-root' => $assets['ckeditorRoot'],
				'plugins'       => $assets['plugins'],
			],
		];
	}

}
