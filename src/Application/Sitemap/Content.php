<?php
namespace Sy\Bootstrap\Application\Sitemap;

use Sy\Bootstrap\Lib\Url;

class Content implements IProvider {

	/**
	 * Returns sitemap index urls
	 *
	 * @return array An array of URL string
	 */
	public function getIndexUrls() {
		return [PROJECT_URL . Url::build('sitemap', 'content')];
	}

	/**
	 * Returns sitemap urls
	 */
	public function getUrls() {
		$urls = [];

		// Content pages
		$service = \Project\Service\Container::getInstance();
		$service->page->foreachRow(function($row) use(&$urls) {
			$content = trim(Url::build('page', 'content', ['id' => $row['id']]), '/');
			$alias = [];
			foreach (LANGS as $lang => $label) {
				$loc = \Sy\Bootstrap\Lib\Url\AliasManager::retrieveAlias($content, $lang);
				if (is_null($loc)) continue;
				$alias[$lang] = PROJECT_URL . '/' . $loc;
			}

			if (count($alias) > 1) {
				foreach ($alias as $lang => $loc) {
					$urls[] = ['loc' => $loc, 'alternate' => $alias];
				}
			} elseif (count($alias) === 1) {
				$urls[] = ['loc' => array_pop($alias)];
			} else {
				$urls[] = ['loc' => PROJECT_URL . '/' . $content];
			}
		});

		return $urls;
	}

}