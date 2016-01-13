<?php
namespace Pex;

class RouteTest extends \PHPUnit_Framework_TestCase
{
    public function testBasicRoute()
    {
        $r = new Route('/test/');
        $echoback = function ($r) {
            return $r;
        };
        $r->get('/foo', $echoback);
        $this->assertTrue($r->accept('/test/foo'));
        $fn = $r->match('GET', '/test/foo', $parameters);
        $this->assertEquals($echoback, $fn);
    }

    /**
     *
     * @expectedException RuntimeException
     */
    public function testMatchBeforeAccept()
    {
        $this->markTestSkipped('we do not explicit throw exception now');
        $r = new Route('/test/', '\Pex\HttpTest\View');
        $r->match('GET', '/test/foo', $parameters);
    }

    public function testClassRoute()
    {
        $r = new Route('/test/', '\Pex\HttpTest\View');
        $this->assertTrue($r->accept('/test/print'));
        $fn = $r->match('GET', '/test/print', $parameters);
        $this->assertEquals([new \Pex\HttpTest\View, 'printer'], $fn->getCallable());

        $fn = $r->match('GET', '/test/get/item', $parameters);
        $this->assertNotNull($fn);
        $this->assertEquals([new \Pex\HttpTest\View, 'getUser'], $fn->getCallable());
        $this->assertEquals(['user'=>'item'], $parameters);
        
        $fn = $r->match('GET', '/test/myname', $parameters);
        $this->assertEquals([new \Pex\HttpTest\View, 'myname'], $fn);
    }

    public function testRouteMethodPlugins()
    {
        $r = new Route('/test/', '\Pex\HttpTest\View');
        $this->assertTrue($r->accept('/test/decor'));
        $fn = $r->match('GET', '/test/decor', $parameters);
        $this->assertEquals([new \Pex\HttpTest\View, 'decor'], $fn->getCallable());
        $dummyPlugin = function ($name, $args) {
            return [$name, $args];
        };
        $this->assertEquals(
            [['decor',
            ['foo',
            'bar']],
            ['view',
            ['abc.php']]],
            $fn->buildPlugins(['view'=>$dummyPlugin, 'decor'=>$dummyPlugin])
        );
  
    }
}
