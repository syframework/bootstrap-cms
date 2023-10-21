<?php
namespace Sy\Bootstrap\Application\Sitemap;

use Sy\Bootstrap\Lib\Url;
use Sy\Bootstrap\Lib\Date;

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
	 *
	 * @return array
	 */
	public function getUrls() {
		$urls = [];

		// Content pages
		$service = \Project\Service\Container::getInstance();
		$service->content->foreachRow(function($row) use(&$urls) {
			$date = new Date($row['updated_at']);
			$alias = [];
			foreach (LANGS as $lang => $label) {
				$alias[$lang] = PROJECT_URL . Url::build('page', 'content', ['id' => $row['id'], 'alias' => $row['alias'], 'lang' => $lang]);
			}

			if (count($alias) > 1) {
				foreach ($alias as $lang => $loc) {
					$urls[] = ['loc' => $loc, 'alternate' => $alias, 'lastmod' => $date->f('yyyy-MM-dd')];
				}
			} else {
				$urls[] = [
					'loc'     => PROJECT_URL . Url::build('page', 'content', ['id' => $row['id'], 'alias' => $row['alias'], 'lang' => 'none']),
					'lastmod' => $date->f('yyyy-MM-dd')
				];
			}
		}, ['WHERE' => ['visibility' => 'public']]);

		return $urls;
	}

}