<?php
namespace Pex\Route;

trait HttpMethodTrait
{
    public function get($path, $handler)
    {
        return $this->add('GET', $path, $handler);
    }

    public function route($path, $handler)
    {
        return $this->add(['GET', 'POST'], $path, $handler);
    }

    public function post($path, $handler)
    {
        return $this->add('POST', $path, $handler);
    }

    public function put($path, $handler)
    {
        return $this->add('PUT', $path, $handler);
    }

    public function delete($path, $handler)
    {
        return $this->add('DELETE', $path, $handler);
    }

    public function addRoute($methods, $path, $handler)
    {
        return $this->add($methods, $path, $handler);
    }
}
