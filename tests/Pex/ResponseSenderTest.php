<?php
namespace Pex;
use Zend\Diactoros\Response;

function header($string, $replace=true)
{
    ResponseSenderTest::header($string, $replace);
}

class ResponseSenderTest extends \PHPUnit_Framework_TestCase
{
    private static $headers = [];
    
    public static function header($string, $replace)
    {
        self::$headers[] = [$string, $replace];
    }

    public function setUp()
    {
        self::$headers = [];
        $this->sender = new ResponseSender;
    }

    public function testEmitStatusLineAndHeaders()
    {
        $resp = new Response('php://memory', 401, ['content-type'=>'text/plain', 'x-user'=>['foo', 'bar']]);
        $this->sender->emitStatusLineAndHeaders($resp);
        $hdrs = self::$headers;
        $this->assertEquals(["HTTP/1.1 401 Unauthorized", true], $hdrs[0]);
        $this->assertEquals(["content-type: text/plain", true], $hdrs[1]);
        $this->assertEquals(["x-user: foo", true], $hdrs[2]);
        $this->assertEquals(["x-user: bar", false], $hdrs[3]);
         
    
    }

}
