<?php
namespace Pex;
use Zend\Diactoros\ServerRequest;
use Zend\Diactoros\Uri;

class CycleTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $headerWriter = function($response) use (&$headers) {
            $this->headers = $response->getHeaders(); 
            $this->statusCode = $response->getStatusCode();
        };
        $this->plainCycle = new Cycle(new ServerRequest, $headerWriter, 'php://memory');
    }

    public function testParam()
    {
        $request = (new ServerRequest)->withUri(new Uri('/test'))->withQueryParams(['foo'=>'bar']);
        $cycle = new Cycle($request);
        $this->assertEquals('bar', $cycle['foo']);
        $this->assertEquals('bar', $cycle->want('foo'));
        $this->assertEquals('bar', $cycle->get('foo'));
        $this->assertEquals('world', $cycle->get('hello', 'world'));
    }

    /**
     * @expectedException RuntimeException 
     *           
     */
    public function testDupCall()
    {
        $cycle = new Cycle;
        $w = $cycle(); 
        $w = $cycle(); 
    
    }

    public function testCall()
    {
        $cycle = $this->plainCycle;
        $headers = ['hello'=>'world', 'foo'=>['bar', 'abc']];
        $w = $cycle(403, $headers);
        $w('yes ');
        $w('i can');
        $this->assertEquals($this->statusCode, 403);
        $this->assertEquals(['world'], $this->headers['hello']);
        $this->assertEquals(['bar', 'abc'], $this->headers['foo']);
        $cycle->response()->getBody()->rewind();
        $this->assertEquals('yes i can', (string)$cycle->response()->getBody());
    }

    public function testMountpoint()
    {
        $this->plainCycle->setMountpoint('/mnt'); 
        $this->assertEquals('/mnt', $this->plainCycle->mountpoint());
    }

    /**
     *
     *
     * @expectedException \Pex\Exception\HttpException
     */
    public function testInterrupt()
    {
        $this->plainCycle->interrupt(403); 
    }

    public function testRegister()
    {
        $cnt = 0;
        $this->plainCycle->register('abc', function($c) use (&$cnt) {
            return ++$cnt; 
        });
        $this->assertTrue(isset($this->plainCycle->abc));
        $this->assertFalse(isset($this->plainCycle->notfound));
        $this->assertEquals(1, $this->plainCycle->abc);
        $this->assertEquals(1, $this->plainCycle->abc);
        $this->assertEquals(1, $this->plainCycle->abc);
    }
 
    public function testInject()
    {
        $cnt = 0;
        $this->plainCycle->inject('abc', function($c) use (&$cnt) {
            return ++$cnt; 
        });
        $this->assertEquals(1, $this->plainCycle->abc);
        $this->assertEquals(2, $this->plainCycle->abc);
        $this->assertEquals(3, $this->plainCycle->abc);
        $this->plainCycle->inject('abc', null);
        try {
            $this->plainCycle->abc;
            //should not reach here
            $this->assertEquals(1, 0);
        } catch (\RuntimeException $ex) {
            $this->assertEquals('injection callable abc is not exist', $ex->getMessage());
        }
    }
    
    public function testPathParam()
    {
        $request = (new ServerRequest)->withUri(new Uri('/test'))->withQueryParams(['foo'=>'bar']);
        $cycle = new Cycle($request);
        $pathParams['foo'] = 'hello';
        $cycle->setPathParameters($pathParams);
        $this->assertEquals('hello', $cycle['foo']);
    }

    public function testParamsOrder()
    {
        $request = (new ServerRequest);
        $cycle = new Cycle($request->withParsedBody(['foo' => 'bar']));
        $this->assertEquals('bar', $cycle['foo']);
        $cycle = new Cycle($request->withCookieParams(['foo' => 'cookie']));
        $this->assertEquals('cookie', $cycle['foo']);
        $cycle = new Cycle($request->withCookieParams(['foo' => 'cookie'])->withParsedBody(['foo' => 'post']));
        $this->assertEquals('cookie', $cycle['foo']);
        $cycle = new Cycle($request->withQueryParams(['foo' => 'get'])->withParsedBody(['foo' => 'post']));
        $this->assertEquals('post', $cycle['foo']);
        $cycle = new Cycle($request->withQueryParams(['foo' => 'get'])->withParsedBody(['foo' => 'post']));
        $cycle->setPathParameters(['foo' => 'path']);
        $this->assertEquals('path', $cycle['foo']);
    }

    /**
     * @expectedException InvalidArgumentException
     *           
     */
    public function testWantNoExist()
    {
        $cycle = new Cycle();
        $cycle->want('foo');
    }

    public function getClient()
    {
        $c = $this->plainCycle->client();
        $this->assertNull($c->userAgent());
    }

    /**
     * @expectedException RuntimeException
     *           
     */
    public function testPropSet()
    {
        $cycle = Cycle::create();
        $cycle->bar = 'bar';
    }

    /**
     * @expectedException RuntimeException
     *           
     */
    public function testPropGetNoneExist()
    {
        $cycle = new Cycle();
        $a = $cycle->bar;
    }

    /**
     * @expectedException RuntimeException
     *           
     */
    public function testParamSet()
    {
        $cycle = new Cycle();
        $cycle['foo'] = 'bar';
    }

    /**
     * @expectedException RuntimeException
     *           
     */
    public function testParamUnset()
    {
        $cycle = new Cycle();
        unset($cycle['foo']);
    }

    public function testParamIsset()
    {
        $request = (new ServerRequest);
        $cycle = new Cycle($request->withParsedBody(['foo' => 'bar']));
        $this->assertTrue(isset($cycle['foo']));
        $this->assertFalse(isset($cycle['bar']));
    }


}

