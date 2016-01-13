<?php
namespace Pex;

use Zend\Diactoros\ServerRequestFactory;
use Zend\Diactoros\Response;

class Cycle implements \ArrayAccess
{
    private $request;
    private $response;
    private $params;
    private $pathParameters;
    private $injections;
    private $mountpoint;
    private $reply;
    private $writer;
    private $client;
    protected $responseBody;
    protected $responseHeaderSender;

    public static function create()
    {
        return new self();
    }

    public function __construct($request = null, $responseHeaderSender = null, $responseBody = 'php://output')
    {
        $this->request = ($request)?$request:ServerRequestFactory::fromGlobals();
        $this->responseBody = $responseBody;
        $this->responseHeaderSender =
            ($responseHeaderSender)?$responseHeaderSender:[new ResponseSender, 'emitStatusLineAndHeaders'];
            $this->client = new Client($this->request);
            $parsedbody = ($this->request->getParsedBody())?$this->request->getParsedBody():[];
            $this->params = $this->request->getCookieParams() + $parsedbody + $this->request->getQueryParams();
            $this->reply = new Reply;
    }

    public function inject($name, $callable)
    {
        if ($callable === null) {
            unset($this->injections[$name]);
        } else {
            $this->injections[$name] = $callable;
        }
    }

    public function register($name, $callable)
    {
        $closure = function ($cycle) use ($callable) {
            static $object;
            if (null === $object) {
                $object = $callable($cycle);
            }
            return $object;
        };
        $this->inject($name, $closure->bindTo(null));
    }

    /**
     * throw a \Pex\Exception\HttpException to interrupt process and return a custom http response
     * should be used with \Pex\Plugin\CatchHttpException
     */
    public function interrupt($status, $headers = [], $body = null)
    {
        throw new \Pex\Exception\HttpException($status, $headers, $body);
    }

    public function get($name, $default = null)
    {
        try {
            return $this->want($name);
        } catch (\InvalidArgumentException $ex) {
            return $default;
        }
    }

    public function __isset($name)
    {
        return isset($this->injections[$name]);
    }

    public function __set($name, $value)
    {
        throw new \RuntimeException("cycle object properties is reserved for service injection");
    }

    public function __get($name)
    {
        if (isset($this->injections[$name])) {
            return $this->injections[$name]($this);
        }
        throw new \RuntimeException("injection callable $name is not exist");
    }

    public function want($name)
    {
        if (isset($this->pathParameters[$name])) {
            return $this->pathParameters[$name];
        } elseif (isset($this->params[$name])) {
            return $this->params[$name];
        } else {
            throw new \InvalidArgumentException("$name is not found in parameters");
        }
    }

    public function offsetSet($offset, $value)
    {
        throw new \RuntimeException('cycle params is immutable');
    }

    public function offsetExists($offset)
    {
        try {
            $this->want($offset);
            return true;
        } catch (\InvalidArgumentException $ex) {
            return false;
        }
    }

    public function offsetUnset($offset)
    {
        throw new \RuntimeException('cycle params is immutable');
    }

    public function offsetGet($offset)
    {
        return $this->get($offset, null);
    }

    public function client()
    {
        return $this->client;
    }

    public function reply()
    {
        return $this->reply;
    }

    public function request()
    {
        return $this->request;
    }

    public function response()
    {
        return $this->response;
    }

    public function writer()
    {
        return $this->writer;
    }

    public function mountpoint()
    {
        return $this->mountpoint;
    }

    public function setPathParameters($params)
    {
        $this->pathParameters = $params;
    }

    public function setMountPoint($mountpoint)
    {
        $this->mountpoint = $mountpoint;
    }

    public function __invoke($status = 200, array $headers = [])
    {
        if ($this->response) {
            throw new \RuntimeException("http response is already initilized. cycle object could only be called once");
        }
        $headers = array_merge($this->reply->getHeaders(), $headers);
        $this->response = new Response($this->responseBody, $status, $headers);
        $headers_sent = [];
        $this->writer = function ($data = null) use (&$headers_sent, $headers) {
            if (!$headers_sent) {
                call_user_func($this->responseHeaderSender, $this->response);
                $headers_sent = $headers;
            }
            if ($data) {
                return $this->response->getBody()->write($data);
            }
        };
        return $this->writer;
    }
}
