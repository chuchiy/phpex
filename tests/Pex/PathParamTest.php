<?php
namespace Pex;

class PathParamTest extends \PHPUnit_Framework_TestCase
{
    public function testParamMatch() {

        $pattern = '/path/<uid>/<name>';
        $path = '/path/1202/foo';

        $matches = PathParameters::match($path, $pattern);
        $this->assertEquals(['name'=>'foo', 'uid'=>'1202'], $matches);
    }

    public function testNoneParamMatch() {

        $pattern = '/user';
        $path = '/user';

        $matches = PathParameters::match($path, $pattern);
        $this->assertEquals([], $matches);
    }

    public function testExactParamMatch() {

        $pattern = '/path';
        $path = '/path2my';

        $matches = PathParameters::match($path, $pattern);
        $this->assertEquals(false, $matches);
    }



}
