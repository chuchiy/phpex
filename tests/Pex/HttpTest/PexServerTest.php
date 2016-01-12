<?php
namespace Pex\HttpTest;

class PexServerTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->pex = PexServer::setup();
        $this->runner = new \Pex\ServerRunner($this->pex);
    }

    public function testStreamReturn()
    {
        $r = $this->runner->get('/v/stream');
        $this->assertEquals(200, $r->getStatusCode());
        $this->assertEquals("it's a stream", (string)$r->getBody());
    }

    public function testSimpleGet()
    {
        $r = $this->runner->get('/v/print');
        $this->assertEquals('helloworld', (string)$r->getBody());
    }

    public function testJsonize()
    {
        $r = $this->runner->get('/v/hello');
        $json = json_decode((string)$r->getBody(), true);
        $this->assertEquals('application/json; charset=utf-8', $r->getHeader('content-type')[0]);
        $this->assertEquals('yang', $json['chuchi']);
        $this->assertEquals('chuchi', $json['author']);
        $this->assertEquals('var is b', $json['render']);
    }

    public function testNotFound()
    {
        $r = $this->runner->get('/path/to/not/found');
        $this->assertEquals(404, $r->getStatusCode());
        $this->assertEquals('/path/to/not/found', (string)$r->getBody());
    }

    public function testCatchHttpException()
    {
        $r = $this->runner->get('/v/deny');
        $this->assertEquals(403, $r->getStatusCode());
        $this->assertEquals('Forbidden', $r->getReasonPhrase());
        $this->assertEquals('Access Denied', (string)$r->getBody());
    }

    public function testPost()
    {
        $name = 'foo';
        $r = $this->runner->post('/v/get/'.$name);
        $json = json_decode((string)$r->getBody(), true);
        $this->assertEquals($name, $json['user']);
    }
    
    public function testGenerator()
    {
        $r = $this->runner->get('/v/generator');
        $this->assertEquals('012345678910', (string)$r->getBody());
    }

    public function testMethodPlugin()
    {
        $r = $this->runner->get('/v/decor');
        $this->assertEquals(200, $r->getStatusCode());
        $this->assertEquals('foobarplaintext', (string)$r->getBody());
    }

    public function testPage()
    {
        $r = $this->runner->get('/v/page');
    
        $this->assertEquals('text/plain; charset=utf-8', $r->getHeader('content-type')[0]);
        $text = "Plain Doc\n==========================\n* item foo\n* item bar\n* item hello\n* item world\n\nchuchi @ 2015";
        $this->assertEquals($text, (string)$r->getBody());
    }

    public function testPostBody()
    {
        $r = $this->runner->post('/v/indat?p=test', 'foobar');
        $json = json_decode((string)$r->getBody(), true);
        $this->assertEquals('test', $json['p']);
        $this->assertEquals('foobar', $json['dat']);
    }

    public function testTwigShow()
    {
        $r = $this->runner->get('/twig/show');
        $this->assertEquals("my name is chuchi\n", (string)$r->getBody());
    }

    public function testTwigRender()
    {
        $r = $this->runner->get('/twig/render/show');
        $this->assertEquals("my name is foobar\n", (string)$r->getBody());
    }

    public function testRouteFind()
    {
        $r = $this->runner->get('/find/nemo');
        $this->assertEquals("find nemo", (string)$r->getBody());
        $r = $this->runner->post('/find/nemo');
        $this->assertEquals(404, $r->getStatusCode());
    }

    public function testRoutePlugin()
    {
        $r = $this->runner->post('/set/1234', json_encode(['hello'=>'world']));
        $json = json_decode((string)$r->getBody(), true);
        $this->assertEquals(['hello'=>'world', 'id'=>1234], $json);

        $r = $this->runner->post('/create/4321', json_encode(['foo'=>'bar']));
        $json = json_decode((string)$r->getBody(), true);
        $this->assertEquals(['dat'=>['foo'=>'bar'], 'id'=>4321], $json);
    }

    public function testRouteAttach()
    {
        $this->pex->attach('/test/')->route('/redir', function($cycle){
            $cycle->client()->redirect($cycle->want('cont'));
        })->get('/with/<uid>/<pid>', function($cycle){
            return [$cycle->get('uid'), $cycle->get('pid')];
        })->route('/display', function($cycle){
            return 'display';    
        });
        $this->pex->route('/goto/<dest>', function($cycle){
            return $cycle->want('dest'); 
        });
        $r = $this->runner->post('/goto/home');
        $this->assertEquals("home", (string)$r->getBody());
   
        $r = $this->runner->post('/test/redir?cont=test');
        $this->assertEquals(302, $r->getStatusCode());
        $this->assertEquals(['test'], $r->getHeader('location'));
        $r = $this->runner->get('/test/display');
        $this->assertEquals("display", (string)$r->getBody());
        $r = $this->runner->get('/test/with/4321/1234');
        $this->assertEquals("43211234", (string)$r->getBody());
    }

    public function testWithOutAnnotation()
    {
        $req = $this->runner->createRequest('GET', '/v/print');
        $r = $this->runner->handle($req, \Pex\Pex::DISPATCH_FLAG_DO_NOT_PARSE_ANNOTATION);
        $this->assertEquals(404, $r->getStatusCode());
   
        $r = $this->runner->get('/v/myname');
        $this->assertEquals("Chu-Chi Yang At /v/", (string)$r->getBody());
    }

    public function testFullHttpMethod()
    {
        $items = [];
        $this->pex->install(function($run){
            return function($cycle) use ($run) {
                $cycle->register('input', function($c) {
                    return json_decode((string)$c->request()->getBody(), true); 
                });
                return $run($cycle);    
            };
        });

        $this->pex->put('/items/', function($cycle) use (&$items) {
            $id = uniqid();
            $items[$id] = $cycle->input;
            return ['id'=>$id];
        })->delete('/items/', function($cycle) use (&$items){
            $items = [];
            return ['ok'=>True];
        })->get('/items/', function($cycle) use (&$items){
            return ['items'=>$items]; 
        });
        $this->pex->attach('/items')->put('/<id>', function($cycle) use (&$items){
            $items[$cycle['id']] = $cycle->input;
            return ['ok'=>True];
        })->delete('/<id>', function($cycle) use (&$items){
            unset($items[$cycle['id']]);
            return ['ok'=>True];
        })->get('/<id>', function($cycle) use (&$items){
            return ['item'=>$items[$cycle['id']]];
        });
        $this->assertEquals(['items'=>[]], json_decode($this->runner->get('/items/')->getBody(), true));
        $item = ['name'=>'foobar'];
        $itemId = json_decode($this->runner->put('/items/', json_encode($item))->getBody(), true)['id'];
        $this->assertEquals(['item'=>$item], json_decode($this->runner->get('/items/'.$itemId)->getBody(), true));
        $this->assertEquals(['items'=>[$itemId=>$item]], json_decode($this->runner->get('/items/')->getBody(), true));
        $this->assertTrue(json_decode($this->runner->delete('/items/'.$itemId)->getBody(), true)['ok']);
        $this->runner->put('/items/', json_encode($item));
        $this->runner->delete('/items/');
        $this->assertEquals(['items'=>[]], json_decode($this->runner->get('/items/')->getBody(), true));
    }
}


