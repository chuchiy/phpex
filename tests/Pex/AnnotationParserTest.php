<?php
namespace Pex;

class AnnotationParserTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->ref = new \ReflectionClass('\Pex\AnnoMock');
        $this->homeAnno = $this->ref->getMethod('home')->getDocComment();
        $this->loginAnno = $this->ref->getMethod('login')->getDocComment();
    }

    public function testParseHomeAll()
    {
        $homeanno = AnnotationParser::parseAll($this->homeAnno);
        $this->assertEquals(
            [
              'get' => [['/path']],
              'auth' => [null],
              'bad' => [null],
            ],
            $homeanno
        );
 
    }
    
    public function testParseLogin()
    {
        $loginanno = AnnotationParser::parse($this->loginAnno);
        foreach($loginanno as $name => &$params) {
            $params = array_map(function($p){
                return ($p)?$p():NULL;
            }, $params); 
        }
        $this->assertEquals(
            [
                'get'   => [['/login']],
                'view'  => [['header.html', 'login.html', 'footer.html']],
                'input' => [['username', 'string'], ['password', 'string']],
            ],
            $loginanno
        );
   
    }
    
    public function testParseNoRoutin()
    {
        $this->assertNull(AnnotationParser::parse('/**fdsfs*/'));
    }

    public function testParseHome()
    {
        $homeanno = AnnotationParser::parse($this->homeAnno);
        foreach($homeanno as $name => &$params) {
            $params = array_map(function($p){
                return ($p)?$p():NULL;
            }, $params); 
        }
        $this->assertEquals(
            [
              'get' => [['/path']],
              'auth' => [NULL],
              'bad' => [[]],
            ],
            $homeanno
        );
   
    }
}
