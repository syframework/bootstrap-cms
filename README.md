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
3. Create flyway migration file
4. Copy assets files
5. Run composer build
6. Run composer db migrate
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

Converters are evaluated in registration order. `ContentConverter` uses an empty prefix by default, so it checks every root-level path against the content aliases: it must be registered after more specific converters and **before** `ControllerActionConverter`, which is the terminal fallback. A content is then reachable as `/<alias>` or `/<lang>/<alias>` and is mapped to `p=page`, `a=content`, `id=<id>`.

See the [URL Converters](https://github.com/syframework/bootstrap/wiki/URL-Converters) wiki page for the full converter contract, ordering rules and `Url::build()` examples.

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

`Content` adds one entry to the sitemap index (`<project>/sitemap/content`) and lists every content whose `visibility` is `public`: `<lastmod>` is its `updated_at` date, and every language declared in `LANGS` gets its own URL plus an `<alternate>` set. With a single language in `LANGS`, the URL keeps `lang=none`.

The provider must be registered: `Sitemap` resolves the requested action to `Project\Application\Sitemap\<Action>` then `Sy\Bootstrap\Application\Sitemap\<Action>`, and answers 404 when no provider class matches the action or when that class was never added with `addProvider()`. An unregistered Content sitemap is therefore a 404, not an empty file; leaving it out of `Sitemap.php` also keeps the content URLs out of the sitemap index.
