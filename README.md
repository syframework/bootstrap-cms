# sy/bootstrap-cms

[sy/bootstrap](https://github.com/syframework/bootstrap) plugin for adding "CMS" feature in your [sy/project](https://github.com/syframework/project) based application.

## Installation

From your sy/project based application directory, run this command:

```bash
composer install-plugin cms
```

It's equivalent to:

```bash
composer require sy/bootstrap-cms
```

---
**NOTES**

The install-plugin command will do all these following steps:

1. Run composer require
2. Copy templates files
3. Copy lang files
4. Create flyway migration file
5. Copy scss files
6. Copy assets files
7. Run composer build
8. Run composer db migrate
---

## Page methods

Create a method in your ```Project\Application\Page``` class (in ```protected/src/Application/Page.php```):
```php
	/**
	 * Content page
	 */
	public function contentAction() {
		$this->setContentVars([
			'CONTENT' => new \Sy\Bootstrap\Component\Cms\Content($this->get('id', 1)),
		]);
	}
```

Optionally, override the home page with the content page
```php
	/**
	 * Home page
	 */
	public function homeAction() {
		$this->copy('content');
	}
```

## Add URL converter in Application.php

In ```protected/src/Application.php```

```php
<?php
namespace Project;

use Sy\Bootstrap\Lib\Url;

class Application extends \Sy\Bootstrap\Application {

	protected function initUrlConverter() {
		Url\AliasManager::setAliasFile(__DIR__ . '/../conf/alias.php');
		Url::addConverter(new Url\AliasConverter());
		Url::addConverter(new Url\ContentConverter()); // Add the content URL converter
		Url::addConverter(new Url\ControllerActionConverter());
	}

}
```

## Add the content pages sitemap in Sitemap.php

In ```protected/src/Application/Sitemap.php```

```php
<?php
namespace Project\Application;

class Sitemap extends \Sy\Bootstrap\Application\Sitemap {

	public function __construct() {
		parent::__construct();
		$this->addProvider(new \Sy\Bootstrap\Application\Sitemap\Page());
		$this->addProvider(new \Sy\Bootstrap\Application\Sitemap\Content()); // Add the content sitemap
	}

}
```