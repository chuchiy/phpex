<?php
namespace Pex;

class DispatchTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->disp = new Dispatcher;
    }

    public function testBasic()
    {
        $noop = function () {
            return;
        };
        $this->disp->add('/foo', $noop);
        $this->disp->add('/user/<uid>/<bid>', $noop);
        $r = $this->disp->find('/user/chuchi/yang');
        $this->assertEquals(['uid'=>'chuchi', 'bid'=>'yang'], $r[1]);

        $this->disp->add('/create/<oid:int>', $noop);
        $this->disp->add('/list', $noop);
        $this->disp->add('/build/<oid>', $noop);
        
        $r = $this->disp->find('/create/222');
        $this->assertEquals($noop, $r[0]);
        $this->assertEquals(['oid'=>222], $r[1]);
        $r = $this->disp->find('/helloworld');
        $this->assertNull($r);
        $r = $this->disp->find('/list');
        $this->assertEquals([], $r[1]);
        $r = $this->disp->find('/build/foo');
        $this->assertEquals(['oid'=>'foo'], $r[1]);
        $r = $this->disp->find('/user/chuchi/yang');
        $this->assertEquals(['uid'=>'chuchi', 'bid'=>'yang'], $r[1]);
    }

    public function testParamType()
    {
        $pattern = '/path/<num:int>';
        $this->disp->add($pattern, null);
        $r = $this->disp->find('/path/1202');
        $this->assertEquals(['num'=>1202], $r[1]);
        $matches = $this->disp->find('/path/foo', $pattern);
        $this->assertNull($matches);

    }

    public function testCustomParamType()
    {
        //numeric
        $pattern = '/path/<num:[-+]?\d*[.]?\d+>';
        $this->disp->add($pattern, null);
        $path = '/path/1202.333';
        $matches = $this->disp->find($path);
        $this->assertEquals(['num'=>1202.333], $matches[1]);

        $this->disp->add('/pathnum/<num:numeric>', null);
        $matches = $this->disp->find('/pathnum/1202.333');
        $this->assertEquals(['num'=>1202.333], $matches[1]);
        $matches = $this->disp->find('/pathnum/.1333', $pattern);
        $this->assertEquals(['num'=>.1333], $matches[1]);
        $matches = $this->disp->find('/pathnum/333', $pattern);
        $this->assertEquals(['num'=>333], $matches[1]);


        $this->disp->add('/path/float/<num:float>', null);
        $matches = $this->disp->find('/path/float/.1333');
        $this->assertEquals(['num'=>.1333], $matches[1]);
        $matches = $this->disp->find('/path/float/foo');
        $this->assertNull($matches);
        $matches = $this->disp->find('/path/float/222');
        $this->assertNull($matches);
    }

    public function testParamMatch()
    {
        $pattern = '/path/<uid>/<name>';
        $path = '/path/1202/foo';

        $this->disp->add($pattern, null);
        $matches = $this->disp->find($path);
        $this->assertEquals(['name'=>'foo', 'uid'=>'1202'], $matches[1]);
    }

    public function testNoneParamMatch()
    {
        $pattern = '/user';
        $path = '/user';

        $this->disp->add($pattern, null);
        $matches = $this->disp->find($path);
        $this->assertEquals([], $matches[1]);
    }

    public function testExactParamMatch()
    {
        $pattern = '/path';
        $path = '/path2my';
        $this->disp->add($pattern, null);
        $matches = $this->disp->find($path);
        $this->assertEquals(false, $matches);
    }
}
