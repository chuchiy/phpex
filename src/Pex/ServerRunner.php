<?php
namespace Pex;
use Zend\Diactoros\ServerRequest;

class ServerRunner
{
    private $pex;

    public function __construct($pex)
    {
        $this->pex = $pex;
    }

    public static function createRequest($method, $uri, $headers=[], $data=null)
    {
        $body = fopen('php://memory', 'r+');
        if ($data) {
            fwrite($body, $data);
            rewind($body);
        }
        $serverParams = ['REMOTE_ADDR'=>'127.0.0.1'];
        $uploadedFiles = [];
        $cookieParams = [];
        $parsedBody = [];
        $querypos = strpos($uri, '?');
        $queryParams = [];
        if ($querypos !== false) {
            $querystring = substr($uri, $querypos + 1);
            parse_str($querystring, $queryParams);
        }
        return new ServerRequest($serverParams, $uploadedFiles, $uri, $method, $body, $headers, $cookieParams, $queryParams, $parsedBody);
    }

    public function get($path, $headers=[])
    {
        $request = self::createRequest('GET', $path, $headers);
        return $this->handle($request);
    }

    public function delete($path, $headers=[])
    {
        $request = self::createRequest('DELETE', $path, $headers);
        return $this->handle($request);
    }

    public function post($path, $data=null, $headers=[])
    {
        $request = self::createRequest('POST', $path, $headers, $data);
        return $this->handle($request);
    }

    public function put($path, $data=null, $headers=[])
    {
        $request = self::createRequest('PUT', $path, $headers, $data);
        return $this->handle($request);
    }

    public function handle($request, $flags=0)
    {
        $cycle = new Cycle($request, function($resp){}, 'php://memory');
        $this->pex->serve($cycle, $flags);
        $cycle->response()->getBody()->rewind();
        return $cycle->response(); 
    }
}
