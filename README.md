# Router

OliveCMS Router with host handle

## Installation

Install the latest version with

```
$ composer require olive-cms/router
```

If you do not use Composer, you can download composered zip from [release Github page](https://github.com/OliveCMS/Router/releases/latest)

## Basic Usage

``` php
require_once 'vendor/autoload.php';
use Olive\Router;

$router = new Router();
$router->addHost('https://arshen.ir/', 1);
$router->addHost('https://blog.arshen.ir/', 2);

// global route
$router->add('/api', function(){
  return 'api area!';
});

// host 1 (https://arshen.ir/) route
$router->add('/login', function(){
  return 'login area! only use in https://arshen.ir/';
}, [], 1);

// not found route
$router->addNotFound('not found');

// render address
echo $router->render('https://arshen.ir/login');
```

## Documentation

- [Usage Instructions](doc/01-usage.md)

## Requirements

- PHP 5.5+.

## License

olive-cms/router is licensed under the [MIT license](http://opensource.org/licenses/MIT).
