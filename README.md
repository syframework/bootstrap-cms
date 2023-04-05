# sy/bootstrap-cms

[sy/bootstrap](https://github.com/syframework/bootstrap) plugin for adding "CMS" feature in your [sy/project](https://github.com/syframework/project) based application.

## Installation

```bash
composer require sy/bootstrap-cms
```

## Database

Use the database installation script: ```sql/install.sql```

Create the first content page on ```t_content``` table.

Create a user and set user role to ```content-admin```.

## Template files

Copy template files into your project templates directory: ```protected/templates/Application/content```

## Page methods

```php
/**
 * Content page
 */
public function contentAction() {
	$service = \Project\Service\Container::getInstance();

	$id   = $this->get('id', 1);
	$lang = $this->get('lang', $service->lang->getLang());

	// Retrieve content
	$content = $service->content->retrieve(['id' => $id, 'lang' => $lang]);

	if (empty($content)) {
		throw new \Sy\Bootstrap\Application\Page\NotFoundException();
	}

	$this->setContentVars([
		'CONTENT' => new \Sy\Bootstrap\Component\Cms\Content($id, $lang),
	]);
}
```

## Language files

## CSS

## Add URL converter in Application.php

```php
<?php
namespace Project;

use Sy\Bootstrap\Lib\Url;

class Application extends \Sy\Bootstrap\Application {

	protected function initUrlConverter() {
		Url\AliasManager::setAliasFile(__DIR__ . '/../conf/alias.php');
		Url::addConverter(new Url\ContentConverter()); // Add the content URL converter
		Url::addConverter(new Url\AliasConverter());
		Url::addConverter(new Url\ControllerActionConverter());
	}

}
```