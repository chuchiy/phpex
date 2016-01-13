<?php
namespace Pex;

class Reply implements \ArrayAccess
{
    private $headers=[];

    public function getHeaders()
    {
        return $this->headers;
    }

    public function setHeader($name, $value)
    {
        $this[$name] = $value;
    }

    public function offsetSet($offset, $value)
    {
        if (!$offset) {
            throw new \InvalidArgumentException("empty response header name");
        } else {
            $this->headers[$offset] = $value;
        }
    }

    public function offsetExists($offset)
    {
        return isset($this->headers[$offset]);
    }

    public function offsetUnset($offset)
    {
        unset($this->headers[$offset]);
    }

    public function offsetGet($offset)
    {
        return isset($this->headers[$offset]) ? $this->headers[$offset] : null;
    }

    /**
     * make a valid stream resource from string
     * handy function for body stream of http response
     */
    public static function stringstream($str)
    {
        $fp = fopen('php://memory', 'r+');
        if ($str) {
            fwrite($fp, $str);
        }
        rewind($fp);
        return $fp;
    }
}
