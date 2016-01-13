<?php
namespace Pex\Exception;

class HttpException extends \Exception
{
    private $statusCode;
    private $headers=[];
    private $body;

    public function __construct($status, $headers = [], $body = null)
    {
        $this->statusCode = $status;
        $this->headers = $headers;
        $this->body = $body;
    }

    public function getStatusCode()
    {
        return $this->statusCode;
    }

    public function getHeaders()
    {
        return $this->headers;
    }

    public function getBody()
    {
        return $this->body;
    }
}
