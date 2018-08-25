# Using Router

- [Installation](#installation)
  1. [Basic Usage](#basic-usage)
- [Hosts](#hosts)
  1. [Register host](#register-host)
  2. [Remove host](#remove-host)
- [Request](#request)
  1. [Basic](#basic)
  2. [Patern](#patern)
  3. [Home](#home)
  4. [Not Found](#not-found)
- [Caller](#caller)
  1. [String](#string)
  2. [Anonymous function](#anonymous-function)
  3. [Class Method](#class-method)
- [Points](#points)
- [Caller Args](#caller-args)

## Installation

olive-cms/router is available on Packagist ([olive-cms/router](http://packagist.org/packages/olive-cms/router)) and as such installable via [Composer](http://getcomposer.org/).

```bash
composer require olive-cms/router
```

If you do not use Composer, you can grab the code from GitHub, and use any PSR-0 compatible autoloader (e.g. the [Symfony2 ClassLoader component](https://github.com/symfony/ClassLoader)) to load Monolog classes.


### Basic Usage

``` php
require_once 'vendor/autoload.php';
use Olive\Router;

$router = new Router();
$router->addHost('https://arshen.ir/', 1);
$router->addHost('https://blog.arshen.ir/', 2);

// global route
$router->add('/api', function(){
  return 'api area!'
});

// host 1 (https://arshen.ir/) route
$router->add('/login', function(){
  return 'login area! only use in https://arshen.ir/'
}, [], 1);

// not found route
$router->addNotFound('not found');

// render adress
echo $router->render('https://arshen.ir/login');
```

## Hosts

router can register many host for render only use then host!

### Register host

``` php
// $router->addHost('host address', host id);
$router->addHost('https://arshen.ir/', 1);
$router->addHost('https://arshen.ir/site1/', 2);
$router->addHost('//arshen.ir/site1/site2/', 3);
$router->addHost('https://blog.arshen.ir/', 4);
$router->addHost('//mehdi.hosseinzade.com/', 101);
```

### Remove host

``` php
// $router->removeHost(host id);
$router->removeHost(3);
```

## Request

### Basic

``` php
// $router->request('patern', 'callable or string', 'points send', 'host id')
$router->request('/blog', 'message');
```

### Patern

#### `{}`

Only one match

``` php
$router->request('/topic/{}', 'function name');
// http://host/topic/news -> news send to caller
// http://host/topic/ -> not valid and skip

$router->request('/user/{}/{}', 'function name');
// http://host/user/edit/avatar -> edit, avatar send to caller
// http://host/user/ and http://host/user/edit/ -> not valid and skip
```

#### `{+}`

One and more

``` php
$router->request('/category/{+}', 'function name');
// http://host/category/news -> [news] send to caller
// http://host/category/news/it -> [news, it] send to caller
// http://host/category/ -> not valid and skip

$router->request('/category/{+}/{+}/rss', 'function name');
// http://host/category/news/sport/footbal/rss -> [news, [sport ,footbal]] send to caller
// http://host/category/rss and http://host/category/news/rss -> not valid and skip
```

#### `{*}`

and more

``` php
$router->request('/category/{*}', 'function name');
// http://host/category/news -> [news] send to caller
// http://host/category/news/it -> [news, it] send to caller
// http://host/category/ -> it's valid and run!!

$router->request('/category/{*}/{*}/rss', 'function name');
// http://host/category/news/sport/footbal/rss -> [news, [sport ,footbal]] send to caller
// http://host/category/rss and http://host/category/news/rss -> it's valid and run!!
```

### Home

``` php
$router->request('/', 'home area');
```

### Not Found

``` php
// $router->addNotFound('caller', 'host id');
$router->addNotFound('not found area');
```

## Caller

### String

``` php
$router->request('/api', 'api area!');
// or function name
function api_fun(){
  return 'api area!';
}
$router->request('/api', 'api_fun');
```

### Anonymous function

``` php
$router->request('/api', function(){
   return 'api area!';
});
```

### Class Method

``` php
class ApiClass
{
  public function api_method()
  {
    return 'api area!';
  }
}

$o=new ApiClass();

$router->request('/api', [$o, 'api_method']);
```

## Points

app can send args to caller:

``` php
$version='v1';
$key='xxxx-xxxx-xxxx-xxxx';
$router->request('/api', function($arg1, $arg2){
   return "api $arg1 area! with $key"; // output: api v1 area! with xxxx-xxxx-xxxx-xxxx
}, [$version, $key]);
```

## Caller Args

with point

``` php
$db=new PDO();
$bool=true;
$router->request('/user/{}/{}', function($arg1, $arg2, $arg3, $arg4){
   return "arg1: $arg1,
           arg2: $arg2,
           arg3: $arg3,
           arg4: $arg4";
   /*
   output for render http://host/user/edit/avatar
     arg1: (Object),
     arg2: (Booloan),
     arg3: edit,
     arg4: avatar,
    */
}, [$db, $bool]);
```
