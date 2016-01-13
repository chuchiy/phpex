<?php
namespace Pex;

trait RouteTrait
{
    use Route\HttpMethodTrait;

    public function attach($mountpoint = '/', $class = null)
    {
        $route = new Route($mountpoint, $class);
        $this->routes[] = $route;
        return $route;
    }

    private function add($methods, $path, $handler)
    {
        $route = $this->attach();
        $route->addRoute((array)$methods, $path, $handler);
        return $route;
    }

    public function dispatch($requestMethod, $requestPath, $flags)
    {
        $parameters = [];
        foreach ($this->routes as $route) {
            if (!$route->accept($requestPath, $flags)) {
                continue;
            }
            $handler = $route->match($requestMethod, $requestPath, $parameters);
            if ($handler) {
                $result['handler'] = $handler;
                $result['plugins'] = $route->getPlugins();
                $result['annotationPlugins'] = $route->getAnnotationPlugins();
                $result['parameters'] = $parameters;
                $result['mountpoint'] = $route->getMountPoint();
                return $result;
            }
        }
        return null;
    }
}
