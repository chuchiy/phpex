<?php
namespace Pex\Plugin;

use Fig\Cache\Memory\MemoryPool;
use Zend\Diactoros\ServerRequest;

class SessionTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->pool = new MemoryPool;
        $this->session = new Session($this->pool);
    }
    public function testSession()
    {
        $k = uniqid('sess');
        $item = $this->pool->getItem($k);
        $this->pool->save($item->set(['foo'=> 'bar']));
        $cycle = new \Pex\Cycle((new ServerRequest)->withHeader('x-pexsession', $k));
        $runner = function ($cycle) {
            $cycle->session['hello'] = 'world';
            return 'ok';
        };
        $callable = call_user_func($this->session, $runner);
        $r = $callable($cycle);
        $this->assertEquals('ok', $r);
        $val = $this->pool->getItem($k)->get();
        $this->assertEquals('bar', $val['foo']);
        $this->assertEquals('world', $val['hello']);
        $cycle = new \Pex\Cycle((new ServerRequest)->withCookieParams(['PEXSESSION'=> $k]));
        $r = $callable($cycle);
        $this->assertEquals('ok', $r);
    }

    public function testSessionExceptionSave()
    {
        $k = uniqid('sess');
        $this->pool->save($this->pool->getItem($k)->set([]));
        $cycle = new \Pex\Cycle((new ServerRequest)->withHeader('x-pexsession', $k));
        $runner = function ($cycle) {
            $cycle->session['hello'] = 'world';
            throw new \Pex\Exception\HttpException(403);
            return 'ok';
        };
        $callable = call_user_func($this->session, $runner);
        try {
            $r = $callable($cycle);
            //should not reach here
            $this->assertEquals(0, 1);
        } catch (\Pex\Exception\HttpException $ex) {
            $this->assertEquals(403, $ex->getStatusCode());
        }
        $val = $this->pool->getItem($k)->get();
        $this->assertEquals('world', $val['hello']);
    }

    /**
     *
     *
     * @expectedException \Pex\Exception\HttpException
     */
    public function testSessionNotFound()
    {
        $cycle = new \Pex\Cycle((new ServerRequest)->withHeader('x-pexsession', 'haha'));
        $runner = function ($cycle) {
            return 'ok';

        };
        $callable = call_user_func($this->session, $runner);
        $callable($cycle);
    }

    /**
     *
     *
     * @expectedException \Pex\Exception\HttpException
     */
    public function testSessionKeyNotFound()
    {
        $cycle = new \Pex\Cycle(new ServerRequest);
        $runner = function ($cycle) {
            return 'ok';

        };
        $callable = call_user_func($this->session, $runner);
        $callable($cycle);
    }
}
