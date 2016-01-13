<?php
namespace Pex;

class ReplyTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->reply = new Reply;
    }

    public function testHeader()
    {
        $this->reply['my-header'] = 'yes';
        $this->reply->setHeader('foo', 'bar');
        $this->assertEquals(['my-header'=>'yes', 'foo'=>'bar'], $this->reply->getHeaders());
        $this->assertEquals('yes', $this->reply['my-header']);
        $this->assertEquals('bar', $this->reply['foo']);
        $this->assertEquals(null, $this->reply['bar']);
        $this->assertTrue(isset($this->reply['foo']));
        $this->assertFalse(isset($this->reply['hello']));
        unset($this->reply['foo']);
        unset($this->reply['hello']);
        $this->assertEquals(['my-header'=>'yes'], $this->reply->getHeaders());
    }

    /**
     * @expectedException InvalidArgumentException
     *
     */
    public function testNullSet()
    {
        $this->reply->setHeader('', 'world');
    }

    public function testStringStream()
    {
        $stream = Reply::stringstream('hello world');
        $this->assertEquals('hello world', stream_get_contents($stream));
    }
}
