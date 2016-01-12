# The PHPex Web Framework

PHPex is a lightweight, flexible, developer friendly micro web framework written in PHP.

## Features

The main purpose of PHPex framework is to solve some important real-world application implemention problem in elegant way. The framework provides:

- high-order function based powerful middleware/plugin with flexible process chain
- class method annotation and direct function based url routing with parameters and pattern matching
- an all-in-one cycle object pass to request handler does input, output, lazy initialization dependency injection and more.
- request handler can return String, Callable, Traversable, Generator, Stream. Just for your convenience
- lazy template for real-world complex web page composition

## Getting Started

Single file demo, don't use it in production:

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
Set the SCRIPT_FILENAME to a static entrypoint file and php-fpm will by-pass all http traffic the that file. 

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
$pex->attach('/path-at/')->get('a', function($c){
    return 'a';
})->post('b', function($c){
    return 'b';
})->with(function($run){
    return funcion($c) use ($run) {
        $r = $run($c);
        return (is_string($r))?strtoupper($r):$r;
    };
});
```
call \$pex->attach will mount all routing in latter method chaining at the attaced path. 
call \$pex->with in a method chaining will install a plugin for all routing in that method chaining.

So http://localhost:8080/path-at/a will display A and http://localhost:8080/a will give a 404 not found http status code.

In a real world projects, we always use difference classes to process application logic. Put all routing and handler stuff in one file would mess the whole project. PHPex could add routing and set handler with classes.

In front controller, just set as:
```php
$pex->attach('/api/', '\NS\Api');
$pex->attach('/site/', '\NS\Site');
```
And all request start with /api/ will goes to \\NS\\Api and request start with /site/ will goes to \\NS\\Site.

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

1. You can use annotation command such as @get/@post/@route to add route(this way will also be used in method plugin install)
2. If the class is callable(implement __invoke), you can add route in __invoke magic method(and you can install class-level plugin here).

For a large project, it's convenience to use one dedicate class to handle logical relevant requests and combine them for more complex business logical.
With PHPex. you can just setup 1st level mountpoint dispatch map at front controller and write real routing and process in code in different classes.

### Request Handler

A request handler in PHPex is a callable to process http request.Request handler receive one cycle object as parameter and natively return string, callable, traversable, generator, stream or nothing(if you direct write to reponse body). Request handler, in most case, is wrapped by some plugins. Then became the processchain.

The plugins can alter the parameter and return of Request handler with ease. In real world development, the most widely use return type of request handler is array. And the plugins will transform the assoc-array to json string or html page in different scenario and plugin configurations.

## Middleware/Plugin

Middleware/Plugin is the key concept in PHPex. Plugin is a high-order function which receive an inner request handler and return a new request handler which wrap the inner request handler. Plugins and RequestHandler build up the process chain. 

```php
//demonstration proces chain build
$callable = $plugin1($plugin2($plugin3($handler)));
$r = $callable($cycle);

```

The following plugin check the type of inner callable return value. if the return value is stdClass then json_encode it.

```php
function jsonize($run) {
    return function($cycle) use ($run) {
        $r = $run($cycle);
        return ($r instanceof stdClass)?json_encode($r):$r;
    };
}
```

Plugin can also implement with class. just use __invoke magic method to receive the callable and return new handler. see: \\Pex\\Plugin\\BasePlugin.

Plugin has 4 effective scope: 

- *Global Level* 
Global plugin install via $pex->install, such plugins will take effect to any matched http request.

```php
$pex = new \Pex\Pex;
$pex->install('global_plugin');
$pex->install('global_plugin2');
```

- *Group Level*
Group plugin install with routing method chain, which will take effect to handler in method chain.

```php
$pex->post('foo', 'foo_handler')->get('bar', 'bar_handler')->with('awesome_plugin');
```
- *Class Level*
Class plugin install at class's __invoke method, which will take effect to class method handler.

```php
class Foo {

    public function __invoke($route) {
        $route->install('foo_plugin')
    }

    ...
    ..
}

```

- *Method Level*
Method plugin install with method annotation, $pex/$route->bindAnnotation should be called to bind a annotation command to a high-order plugin. High-order plugin is a callable receive annotation command name and args pass to annotation command, return a valid plugin, so you a utilize the args pass to annotation command 

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
$pex->bindAnnotation('custhdr', function($name, $args){
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

Request handler only has a parameter: an all-in-one $cycle object. Cycle object can be used to:
1. Get input parameters
2. Dependency Injection & Service Container
3. Wrapper for PSR-7 compatible HTTP Request/Response and handy object for request/response process

### Parameters

You can get path/cookie/post/get parameters by using \$cycle->want('arg') and \$cycle->get('arg'). ArrayAccess
like \$cycle\["args"\], isset(\$cycle\["args"\]) is also available.

\$cycle->want will thrown InvaidArgumentException if the parameter name is not found. \$cycle->get all accept a 
second \$default(null if not set) parameter to return $default when parameter name is not found.

### Dependency Injection & Service Container

Plugin/Request handler use \$cycle to inject and get service. You can use inject/register method to inject a callable to a service name. 

The difference between inject and register is: 

..If you use inject, everytime you use \$cycle->srvname the relevant callable will be called and you get the return. 
..If you use register, the callable will only be called at the first time, and the result will be cached, you will always get the same result in latter \$cycle->srvname.

Use callable for injection give your ability of lazy initialization, the service is create only when you actual use it.

```php
$cnt = 0;
$cycle->inject('counter'), function($cycle) use (&$cnt) {
    return ++$cnt;
});

$cycle->register('counter2'), function($cycle) use (&$cnt) {
    return ++$cnt;
});

var_dump($cycle->counter); //will output 1
var_dump($cycle->counter); //will output 2
var_dump($cycle->counter2); //will output 1
var_dump($cycle->counter2); //will output 1
```
 
### Request and Response

You can get PSR-7 compatible HTTP Request/Response use \$cycle->request() and \$cycle->response(). 

Cycle also provides \$cycle->client() to give a \\Pex\\Client instance with some handy method to get common http header. 

If you want to add reponse headers, use \$cycle->reply()->setHeader() to add headers, ArrayAccess operator is also available, the added headers will dump to client after end of process chain. 

Cycle object is also callable. Just like python wsgi's start_response, call the \$cycle immediately flush the status code and headers to client and return a writer callable which can be used to write contents to client. \$cycle->response() will return the created http response after 

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

\$cycle->interrupt($status, $headers=[], $body=null) will throw a \\Pex\\Exception\\HttpException. 

After install \\Pex\\Plugin\\CatchHttpException plugin at global scope, you can use \$cycle->interrupt to interrupt current process and direct return with given $status, $headers and $body, such as page redirect.

```php
$pex->install(new \Pex\Plugin\CatchHttpException);
    ...
    ...
$pex->get('/redir', function($cycle){
    $cycle->interrupt(302, ['Location'=>$cycle->want('cont')]);
})
```

## Useful Built-in Plugins

### Templater & TwigPlugin

PHPex comes with \\Pex\\Plugin\\Templater and \\Pex\\Plugin\\TwigPlugin for plain php template or twig template render.
Template plugin will use the return data to render every template arguments set in annotation command. 

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

Although the template plugin is the method plugin and should render the request handler's return before global/group plugins. The returnof template plugin is not actual render the request handler's return to string, it returns \\Pex\\ViewRender, subclass of ArrayObject. 

The outter plugins can manipulate the returned view-render as array. So in template file we can use all variables that process chain plugins insert into the view render. 

At the process chain finish execute, because view render is callable, the framework will try to call the result and get the real rendered page.

Template plugin will also inject a template instance to \$cycle with the annotation name. In the above example, request handler can use \$cycle->view->render(\$context, \$templateFile) to direct render template.

### Jsonize & CatchHttpException

The Jsonize plugin will json_encode all assoc-array return and by-pass other type of result. So it's safe to install the Jsonize plugin at the very early global level.

CatchHttpException need to install as the first plugin of whole process chain. This plugin will catch the \\Pex\\Exception\\HttpException and properly response to client.


## Code Recipes

Process time counting plugin

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

Inject a database instance

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
Client staff

```php
$cycle->client()->userAgent(); //get request user-agent
$cycle->client()->contentType(); //get request content-type
$cycle->client()->isAjax(); //whether the request is ajax request or not
$cycle->client()->publicIp(); //get the most likely public ip of client
$cycle->client()->redirect($url); //redirect client to $url
$cycle->client()->referer(); //get request referer
```

Class-Style Plugin

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

High-order Class-Style Plugin

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
```

View plugin without parameters

```php
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



```php
//use built-in Jsonize Plugin
$pex->install(\Pex\Plugin\Jsonize);
//inject a sqlite instance

//add annotation plugin maker
$pex->bindAnnotation('csv', function($name, $fields){
    $plugin = function($run) use ($name, $fields) {
        return function($cycle) use ($run) {
            $r = $run($cycle);
            $fp = fopen('php://memory', 'r+');
            fputcsv($fp, $fields);
            foreach($r as $rec) {
                fputcsv($fp, $rec);
            }
            rewind($fp);
            return $fp;
        };
    };
    return $plugin;
})

$pex->attach('/quick/')->get('/list', function($cycle){
    return $cycle->db->query("select * from foo", \PDO::FETCH_ASSOC);
})->get('/show/<id>', function($cycle){
    $stmt = $cycle->db->prepare("select * from foo where id = ?");
    $stmt->execute([$cycle['id']]);
    return $stmt;
})->with(function($run){
    return function($cycle) use ($run) {
        $r = $run($cycle);
        return ($r instanceof \PDOStatement)?$r->fetchAll(\PDO::FETCH_ASSOC):$r;
    };
});

class View {
    public function __invoke($route) {
        //class level plugin install
        $route->install(function($run){
            return function($cycle) use ($run) {
                //json decode request body
                $cycle->register('input', function($c) {
                    return json_decode((string)$c->request()->getBody(), true);
                });
                return $run($cycle);
            };
        });
        $route->bindAnnotation('rot13', function($name, $args){
            return function($run) {
                return function($cycle) use ($run) {
                    $r = $run($cycle);
                    return (is_string)?str_rot13($r):$r;
                };
            };
        });
    }

    /**
     * @post('/echoback')
     */
    public function echoBack($cycle) {
        return $cycle->input;
    }    

    /**
     * @get('/csv')
     * @get('/show/csv')
     * @csv('id', 'name', 'city')
     */
    public function showCsv($cycle) {
        $rs = [];
        foreach ($cycle->db->query("select id,name,city,ctime from foo", \PDO::FETCH_NUM) as $row) {
            $rs[] = $row;
        }
        return $rs;
    }

    /**
     * @get('/rot13/<message>')
     * @rot13
     */
    public function rot13($cycle) {
        return $cycle['message'];
    }

}

$pex->serve();

```

