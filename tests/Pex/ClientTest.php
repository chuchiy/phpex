<?php
namespace Pex;

use Zend\Diactoros\ServerRequestFactory;

class ClientTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->request = ServerRequestFactory::fromGlobals(['REMOTE_ADDR'=>'192.168.0.1']);
    }

    public function testIsAjax()
    {
        $c = new Client($this->request);
        $this->assertFalse($c->isAjax());
        $c = new Client($this->request->withHeader('x-requested-with', 'XMLHttpRequest'));
        $this->assertTrue($c->isAjax());
    }
    
    public function testContentType()
    {
        $c = new Client($this->request);
        $this->assertNull($c->contentType());
        $c = new Client($this->request->withHeader('content-type', 'text/plain'));
        $this->assertEquals('text/plain', $c->contentType());
    }

    public function testReferer()
    {
        $c = new Client($this->request->withHeader('referer', 'http://foo/bar'));
        $this->assertEquals('http://foo/bar', $c->referer());
    }

    public function userAgent()
    {
        $c = new Client($this->request->withHeader('user-agent', 'chrome'));
        $this->assertEquals('chrome', $c->userAgent());
    }

    public function testIp()
    {
        $c = new Client($this->request);
        $this->assertEquals('192.168.0.1', $c->ip());
    }

    public function testPublicIp()
    {
        $c = new Client($this->request);
        $this->assertNull($c->publicIp());
        $c = new Client($this->request->withHeader('x-real-ip', '202.108.2.3'));
        $this->assertEquals('202.108.2.3', $c->publicIp());
        $c = new Client($this->request->withHeader('x-real-ip', '192.168.2.2'));
        $this->assertNull($c->publicIp());
        $c = new Client($this->request->withHeader('x-forwarded-for', '192.168.23.2, 172.25.72.2'));
        $this->assertNull($c->publicIp());
        $c = new Client($this->request->withHeader('x-forwarded-for', '192.168.23.2, 202.108.2.1'));
        $this->assertEquals('202.108.2.1', $c->publicIp());
        $c = new Client(ServerRequestFactory::fromGlobals(['REMOTE_ADDR'=> '202.108.3.1']));
        $this->assertEquals('202.108.3.1', $c->publicIp());
    }

    public function testRedirect()
    {
        $c = new Client($this->request);
        $url = '/go/to/home';
        try {
            $c->redirect($url);
            //should not reach here
            $this->assertTrue(false);
        } catch (\Pex\Exception\HttpException $ex) {
            $this->assertEquals(302, $ex->getStatusCode());
            $this->assertEquals(['location'=>$url], $ex->getHeaders());
        }
    }
}
