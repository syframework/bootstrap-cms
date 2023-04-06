<?php
namespace Sy\Bootstrap\Lib\Url;

class ContentConverter implements IConverter {

	/**
	 * @var string
	 */
	private $prefix;

	/**
	 * @param string $prefix
	 */
	public function __construct($prefix = '') {
		$this->prefix = $prefix;
	}

	/**
	 * {@inheritDoc}
	 */
	public function paramsToUrl(array $params) {
		if (empty($params[CONTROLLER_TRIGGER])) return false;
		if ($params[CONTROLLER_TRIGGER] !== 'page') return false;
		unset($params[CONTROLLER_TRIGGER]);

		if (empty($params[ACTION_TRIGGER])) return false;
		if ($params[ACTION_TRIGGER] !== 'content') return false;
		unset($params[ACTION_TRIGGER]);

		if (!empty($params['alias'])) {
			$url = WEB_ROOT . '/' . $this->prefix . $params['slug'];
			unset($params['alias']);
			return $url . (empty($params) ? '' : '?' . http_build_query($params));
		}

		if (empty($params['id'])) return false;
		$id = $params['id'];
		unset($params['id']);

		$service = \Project\Service\Container::getInstance();
		$content = $service->content->retrieve(['id' => $id]);
		if (empty($content['alias'])) return false;
		return WEB_ROOT . '/' . $this->prefix . $content['alias'] . (empty($params) ? '' : '?' . http_build_query($params));
	}

	/**
	 * {@inheritDoc}
	 */
	public function urlToParams($url) {
		$uri = parse_url($url, PHP_URL_PATH);
		$queryString = parse_url($url, PHP_URL_QUERY);

		list($alias) = sscanf(substr($uri, strlen(WEB_ROOT) + 1), $this->prefix . "%s");
		if (empty($alias)) return false;

		$service = \Project\Service\Container::getInstance();
		$content = $service->content->retrieve(['alias' => $alias]);
		if (empty($content)) return false;

		$params[CONTROLLER_TRIGGER] = 'page';
		$params[ACTION_TRIGGER] = 'content';
		$params['id'] = $content['id'];

		$queryParams = [];
		if (!is_null($queryString)) parse_str($queryString, $queryParams);

		return $params + $queryParams;
	}

}