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

		$url = WEB_ROOT . '/';
		$service = \Project\Service\Container::getInstance();
		if (!empty($params['lang']) and $service->lang->isAvailable($params['lang'])) {
			$url .= $params['lang'] . '/';
			unset($params['lang']);
		}
		if (isset($params['lang'])) unset($params['lang']);

		if (isset($params['alias'])) {
			$url .= $this->prefix . $params['alias'];
			unset($params['alias']);
			if (isset($params['id'])) unset($params['id']);
			return rtrim($url, '/') . (empty($params) ? '' : '?' . http_build_query($params));
		}

		if (empty($params['id'])) return false;
		$id = $params['id'];
		unset($params['id']);

		$content = $service->content->retrieve(['id' => $id]);
		if (empty($content)) return false;
		return rtrim($url . $this->prefix . $content['alias'], '/') . (empty($params) ? '' : '?' . http_build_query($params));
	}

	/**
	 * {@inheritDoc}
	 */
	public function urlToParams($url) {
		$uri = parse_url($url, PHP_URL_PATH);
		$uri = trim(substr($uri, strlen(WEB_ROOT) + 1), '/');
		$queryString = parse_url($url, PHP_URL_QUERY);

		// Check if there is the lang parameter
		$parts = explode('/', $uri);
		$service = \Project\Service\Container::getInstance();
		if ($service->lang->isAvailable($parts[0])) {
			$params['lang'] = $parts[0];
			$uri = implode('/', array_slice($parts, 1));
		}

		list($alias) = sscanf($uri, $this->prefix . "%s");
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