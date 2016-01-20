<?php
namespace Pex;

class PathParamTest extends \PHPUnit_Framework_TestCase
{
    public function testParamType()
    {
        $pattern = '/path/<num:int>';
        $path = '/path/1202';

        $matches = PathParameters::match($path, $pattern);
        $this->assertEquals(['num'=>1202], $matches);
        $matches = PathParameters::match('/path/foo', $pattern);
        $this->assertEquals(false, $matches);

        //numeric
        $pattern = '/path/<num:[-+]?(\d*[.])?\d+>';
        $path = '/path/1202.333';
        $matches = PathParameters::match($path, $pattern);
        $this->assertEquals(['num'=>1202.333], $matches);
        $matches = PathParameters::match($path, '/path/<num:numeric>');
        $this->assertEquals(['num'=>1202.333], $matches);
        $matches = PathParameters::match('/path/.1333', $pattern);
        $this->assertEquals(['num'=>.1333], $matches);
        $matches = PathParameters::match('/path/333', $pattern);
        $this->assertEquals(['num'=>333], $matches);
        $matches = PathParameters::match('/path/.1333', '/path/<num:float>');
        $this->assertEquals(['num'=>.1333], $matches);
        $matches = PathParameters::match('/path/foo', '/path/<num:float>');
        $this->assertFalse($matches);
        $matches = PathParameters::match('/path/222', '/path/<num:float>');
        $this->assertFalse($matches);
    }

    public function testParamMatch()
    {

        $pattern = '/path/<uid>/<name>';
        $path = '/path/1202/foo';

        $matches = PathParameters::match($path, $pattern);
        $this->assertEquals(['name'=>'foo', 'uid'=>'1202'], $matches);
    }

    public function testNoneParamMatch()
    {

        $pattern = '/user';
        $path = '/user';

        $matches = PathParameters::match($path, $pattern);
        $this->assertEquals([], $matches);
    }

    public function testExactParamMatch()
    {

        $pattern = '/path';
        $path = '/path2my';

        $matches = PathParameters::match($path, $pattern);
        $this->assertEquals(false, $matches);
    }
}
