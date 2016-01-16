# The PHPex Web Framework

[![Build Status](https://api.travis-ci.org/chuchiy/phpex.svg)](https://travis-ci.org/chuchiy/phpex)
[![Coverage Status](https://coveralls.io/repos/chuchiy/phpex/badge.svg?branch=master&service=github)](https://coveralls.io/github/chuchiy/phpex?branch=master)
[![License](https://poser.pugx.org/pugx/badge-poser/license?format=plastic)](https://packagist.org/packages/chuchiy/phpex)

PHPex is a lightweight, flexible, developer friendly micro web framework written in PHP.

## Features

- High-order function based powerful plugin(middleware) with flexible process chain
- Class method annotation and function callback based url routing with parameters and pattern matching
- The all-in-one cycle object handle [PSR-7](http://www.php-fig.org/psr/psr-7/) request, response, dependency injection and more.
- Request handler can return string, stream, iterable or anything you want with the help of plugin
- Lazy template rendering for complex web page composition

## Installation

Use [Composer](https://getcomposer.org/) to install PHPex.

```bash
$ composer require chuchiy/phpex
```

## Getting Started

Single file demo, **don't use it in production**:

```php
<?php
require 'vendor/autoload.php';
$pex = new \Pex\Pex; 
//install plugin for result jsonize and inject a console logger
$pex->install(function($run){
    return function($cycle) use ($run) {
        $cycle->register('log', function($c) {
            return function($msg) { error_log($msg, 4); };
        });
        $r = $run($cycle);
        return ($r instanceof stdClass)?json_encode($r):$r;
    };
});

//routing with anonymous function
$pex->attach('/console')->get('/send/<msg>', function($cycle){
    call_user_func($cycle->log, "recv msg: {$cycle['msg']}");
    return (object)['ok'=> True];
});

//handler class use method annotation routing
class PexHandler {

    /** 
     * @get('/hello/<name>')
     */
    public function hello($cycle) {
        return 'hello ' . $cycle['name'];
    }

    /**
     * @route('/get')
     */
    public function getId($cycle) {
        return (object)['id'=>$cycle->want('id'), 'name'=>$cycle->get('name', 'foo')];
    }
};
$pex->attach('/', 'PexHandler');

$pex->serve();
```

Save above contents to index.php and test with the built-in PHP server:

```bash
$ php -S localhost:8080
```
Visit http://localhost:8080/hello/world will display *hello world*.

Visit http://localhost:8080/get?id=123 will get jsonize result *{"name": "foo", 'id': 123}* 

Visit http://localhost:8080/console/send/foobar will log text *foobar* to server console

## Configuration

### nginx + php-fpm

PHPex utilize php-fpm's special param SCRIPT_FILENAME for front controller setting.
Set the SCRIPT_FILENAME to the entrypoint file and php-fpm will passthrough all http traffic to that file. 

```nginx
    ...
    location / {
            fastcgi_param SCRIPT_FILENAME /var/www/index.php;
            fastcgi_pass ...;
            ...
            ...
    }
    ...
```

## Routing & Handler 

### Routing Mechanisms

PHPex gives you 2 difference routing mechanisms and 2 level routing dispatch to increase performance.

For small projects, you can just set function handler for routing:

```php
$pex->get('/path/to/a', function($c){return 'a'});
$pex->get('/path/to/b', function($c){return 'b'});
```

If you want handlers organize as group to mount at specific path and share plugins:

```php
$pex->attach('/path-at/')->get('/a', function($c){
    return 'a';
})->post('/b', function($c){
    return 'b';
})->with(function($run){
    return funcion($c) use ($run) {
        $r = $run($c);
        return (is_string($r))?strtoupper($r):$r;
    };
});
```
call `$pex->attach` will mount all routing staff in method chaining tp the attached path. 
call `$pex->with` in a method chaining will install a plugin for all routing in that method chaining.

So http://localhost:8080/path-at/a will display *A* (with the help of strtoupper plugin) and http://localhost:8080/a will give you 404 not found http status code.

In real world projects, we always use difference classes to process application logic. Put all routing and handler stuff in one file would mess the whole project. PHPex provides ability to add routing and set handler with classes.

In front controller, just set as:
```php
$pex->attach('/api/', '\NS\Api');
$pex->attach('/site/', '\NS\Site');
```
And all request start with /api/ will goes to `\NS\Api` and request start with /site/ will goes to `\NS\Site`.

Below is how the class looks like:

```php
class Api {

    public function __invoke($route) {
        $route->post('delete', [$this, 'delete']);
    }

    public function delete($cycle) {
        return ['ok'=>True];
    }

    /**
     * @post('/create')
     */
    public function create($cycle) {
        return ['id'=>uniqid()];
    }

    /**
     * @get('/list')
     */
    public function list($cycle) {
        return ['list'=>range(0, 10)];
    }

}
```

PHPex provides two mechanisms to add route for a class:

1. You can use annotation command such as @get/@post/@route to add route(this way will also be used in method plugin install).
2. You can add route in __invoke magic method(and you can install class-level plugin here).

For a large project, it's convenience to use difference dedicate class to handle logical relevant requests and combine them for more complex business logical.
With PHPex, you can just setup 1st level mountpoint dispatch map at front controller and write real routing and process in code in different classes.

### Request Handler

A request handler in PHPex is a callable to process http request. Request handler receive one cycle object as parameter and natively return string, callable, traversable, generator, stream or nothing(if you direct write to reponse body). Request handler, in most case, is wrapped by some plugins, then became the process chain.

The plugins can alter the parameter and return of request handler with ease. In real world development, the most widely use return type of request handler is assoc-array. And the plugins will transform the assoc-array to json string or html page in different scenario and plugin configurations.

## Plugin(Middleware)

Plugin(Middleware) is the key concept in PHPex. Plugin is a high-order function which receive an inner request handler(`$run`) and return a new request handler who wrap the inner request handler. Plugins and request-handler build up the process chain. 

```php
//demonstration proces chain build
$callable = $plugin1($plugin2($plugin3($handler)));
$r = $callable($cycle);

```

The following plugin check the type of inner callable return value. if the return value is stdClass then json_encode it.

```php
function jsonize($run) {
    return function($cycle) use ($run) {
        //run the inner callable
        $r = $run($cycle);
        return ($r instanceof stdClass)?json_encode($r):$r;
    };
}
```

Plugin can also implement with class. just use __invoke magic method to receive the callable and return new handler. PHPex provides a abstract class `\Pex\Plugin\BasePlugin` for handy class-style plugin implemention.

Plugin has 4 effective scope: 

### *Global Level Plugin* 

Global plugin install via `$pex->install`, such plugins will apply to all http request.

```php
$pex = new \Pex\Pex;
$pex->install('global_plugin');
$pex->install('global_plugin2');
```

### *Group Level Plugin*

Group plugin install with routing method chain, which will apply to handler in method chain.

```php
$pex->post('/foo', 'foo_handler')->get('/bar', 'bar_handler')->with('awesome_plugin');
```
### *Class Level Plugin*

Class plugin install at class's __invoke method, which will aplyy to all method handler in class.

```php
class Foo {

    public function __invoke($route) {
        $route->install('foo_plugin')
    }

    ...
    ..
}

```

### *Method Level Plugin*

Method plugin install with method annotation, `$pex->bindAnnotation` or `$route->bindAnnotation` should be called to bind a annotation command to a high-order plugin. High-order plugin is a callable receive annotation command name and args pass to annotation command, return a valid plugin, so you can utilize the args pass to annotation command 

```php
class Foo {

    public function __invoke($route) {
        $route->bindAnnotation('view', new \Pex\Plugin\Templater(__DIR__));
    }

    /**
     * @get('/bar')
     * @view('bar.tpl')
     * @custhdr('x-author', 'pex')
     * @custhdr('x-site', 'test')
     */
    public function bar($cycle) {
        return [];
    }

}
$pex->attach('/', 'Foo');
//bind a high-order function to annotation command custhdr
//$name = custhdr, $args = ['x-...', '....']
$pex->bindAnnotation('custhdr', function($name, $args){
    //return a plugin
    return function($run) use ($name, $args) {
        return function($cycle) use ($run, $name, $args) {
            $r = $run($cycle);
            $cycle->reply()[$args[0]] = $args[1];
            return $r;
        };
    };
});

```

## The Cycle Object

Request handler only has one parameter: an all-in-one `$cycle` object. Cycle object can be used to:
1. Get input parameters from path/cookie/post/get
2. Dependency Injection & Service Container
3. Wrapper for PSR-7 compatible HTTP Request/Response and handy way for request/response process

### Parameters

You can get parameters by using `$cycle->want('arg')` and `$cycle->get('arg')`. ArrayAccess
like `$cycle["args"]`, `isset($cycle["args"])` is also available. Cycle will get the parameters with the order: parameters in request path, cookie, post and get.

`$cycle->want($name)` will thrown InvaidArgumentException if the parameter name is not found. `$cycle->get($name, $default=null)` will return `$default` when parameter name is not found.

### Dependency Injection & Service Container

Plugin/request-handler use `$cycle` to inject and get service. You can use inject/register method to inject a callable as service. 

The difference between inject and register is: 

If you use inject, everytime you use `$cycle->srvname` the relevant callable will be called and you get the new return value. 

If you use register, the callable will only be called at the first time, and the result will be cached, you will always get the same result in latter `$cycle->srvname`.

Use callable for injection give your ability of lazy initialization, the service is create only when you actual use it.

```php
$cnt = 0;
$cycle->inject('counter', function($cycle) use (&$cnt) {
    return ++$cnt;
});

$cycle->register('counter2', function($cycle) use (&$cnt) {
    return ++$cnt;
});

var_dump($cycle->counter); //will output 1
var_dump($cycle->counter); //will output 2
var_dump($cycle->counter2); //will output 1
var_dump($cycle->counter2); //will output 1
```
 
### Request and Response

You can get PSR-7 compatible HTTP Request/Response use `$cycle->request()` and `$cycle->response()`. 

Cycle also provides `$cycle->client()` to get a `\Pex\Client` instance with some handy method to retrieve common http header. 

If you want to add headers to response, use `$cycle->reply()->setHeader()` to add headers, ArrayAccess operator is also available, the added headers will dump to client after end of process chain. 

Cycle object is also callable. Just like python wsgi's start_response, call the `$cycle($status, $headers)` immediately flush the status code and headers to client and return a callable writer which can be used to send contents to client. `$cycle->response()` will return the created http response only after `$cycle` is called. 

```php
$request = $cycle->request();  //return a PSR-7 http request

$ua = $cycle->client()->userAgent();

$cycle->reply()->setHeader('content-type', 'text/plain')
$cycle->reply()['x-author'] = 'Pex';

$writer = $cycle(200, ['x-site'=>'test']);
$writer('body');
//return a PSR-7 http response. you can only get response after $cycle($status, $headers) is called.
$response = $cycle->response(); 

```

`$cycle->interrupt($status, $headers=[], $body=null)` will throw a `\Pex\Exception\HttpException`. 

After install `\Pex\Plugin\CatchHttpException` plugin at global scope, you can use `$cycle->interrupt` to interrupt current process and `CatchHttpException` will catch the exception and send the given `$status`, `$headers` and `$body` to client.

```php
$pex->install(new \Pex\Plugin\CatchHttpException);
    ...
    ...
$pex->get('/redir', function($cycle){
    //http page redirect
    $cycle->interrupt(302, ['Location'=>$cycle->want('cont')]);
})
```

## Useful Built-in Plugins

### Templater & TwigPlugin

PHPex comes with `\Pex\Plugin\Templater` for plain php template render and `\Pex\Plugin\TwigPlugin` for twig template render.
Template plugin will use the return data of request handler to render every template arguments pass to annotation command. 

```php
$pex->bindAnnotation('view', new \Pex\Plugin\Templater(__DIR__));
$pex->bindAnnotation('twig', new \Pex\Plugin\TwigPlugin(__DIR__, sys_get_temp_dir().'/twig/'));

class Page {

    public function __invoke($route) {
        $route->install(function($run) {
            return function($cycle) use ($run) {
                $r = $cycle($run);
                $r['menu'] = ['a', 'b', 'c']
                $r['user'] = 'pex';
                return $r;
            };
        });
    }

    /**
     * @view('header.php', 'main.php', 'footer.php')
     * @get('/test')
     */
    public function test($cycle) {
        return ['name'=>'test'];
    }

    /**
     * @twig('hello.html')
     * @get('/hello')
     */
    public function hello($cycle) {
        return ['name'=>'hello']
    }
}
```

The most important feature of built-in template plugin is *lazy rendering*. 

Although the template plugin is the method plugin and should render the request handler's return before global/group plugins. The return of template plugin is not actual template rendered result, it returns `\Pex\ViewRender`, a callable subclass of ArrayObject. 

The outter plugins can manipulate the returned `ViewRender` as array. So in template file we can use all variables that process chain plugins insert into the view render. 

After the process chain finish execute, because `ViewRender` is callable, the framework will try to call the result and get the real rendered result.

Template plugin will also inject a template instance to `$cycle` with the annotation name. In the above example, request handler can use `$cycle->view->render($context, $templateFile)` to direct render template.

### Jsonize & CatchHttpException

The Jsonize plugin will json_encode all assoc-array return value and by-pass other type of result. So it's safe to install the Jsonize plugin at the very early global level.

CatchHttpException need to install as the first plugin of whole process chain. This plugin will catch the `\Pex\Exception\HttpException` and properly send response to client.


## Code Recipes

### Process time counting plugin

```php
<?php
require 'vendor/autoload.php';
$pex = new \Pex\Pex; 
//inject timer
$pex->install(function($run){
    return function($cycle) use ($run) {
        $start = microtime(true);
        $r = $run($cycle);
        //set reply header
        $cycle->reply()['x-proc-msec'] = round((microtime(true)-$start)*1000, 3);
        return $r;
    }
});
```

### Inject a database instance

```php
$pex->install(function($run){
    return function($cycle) use ($run) {
        $cycle->register('db', function($c){
            return new \PDO('sqlite::memory:');
        });
        return $run($cycle);
    }
});
```
### Client staff

```php
$cycle->client()->userAgent(); //get request user-agent
$cycle->client()->contentType(); //get request content-type
$cycle->client()->isAjax(); //whether the request is ajax request or not
$cycle->client()->publicIp(); //get the most likely public ip of client
$cycle->client()->redirect($url); //redirect client to $url
$cycle->client()->referer(); //get request referer
```

### Class-Style Plugin

```php

class Jsonize extends \Pex\Plugin\BasePlugin 
{
    protected function apply($cycle, $run)
    {
        $r = $run($cycle);
        if((is_array($r) and array_keys($r) !== range(0, count($r) - 1)) or (is_object($r) and $r instanceof \stdClass)) {
            $cycle->reply()['Content-Type'] = 'application/json; charset=utf-8';
            $r = json_encode($r, JSON_UNESCAPED_UNICODE);
        }
        return $r;
    }    
}

```

### High-order Class-Style Plugin

```php
class AddHeader extends HighorderPlugin
{
    protected function apply($cycle, $run, $name, $args)
    {
        $r = $run($cycle);
        $cycle->reply()[$args[0]] = $args[1];
        return $r;        
    }
}

$pex->bindAnnotation('custhdr', new AddHeader);

class A {
    /**
     * @get('/test')
     * @custhdr('x-extra', 'foobar')
     */
    public function($cycle) {
        return 'abc';
    }
}
```

### Templater plugin without parameters

```php
$pex->bindAnnotation('view', new \Pex\Plugin\Templater(__DIR__));

class Page {
    
    /**
     * use view command without parameters will only inject template instance
     * @get('/test')
     * @view
     */
    public function render($cycle) {
        return $cycle->view->render(['name'=>'foobar'], 'test.php');
    }
}
```

### Proper redirect with mountpoint info

```php
function go2login($cycle) {
    $cycle->client()->redirect($cycle->mountpoint() . 'login'); //will redirect to /app/login
}
$pex->attach('/app/')->get('/auth', 'go2login');
```

### Request parameters retrieval

```php
$val = $cycle->want('foo'); //throw InvalidArgumentException if parameter not found
$val = $cycle->get('foo'); //return null if parameter not found
$val = $cycle->get('foo', 'dftval');
$val = $cycle['foo'];
isset($cycle['foo']);
```
