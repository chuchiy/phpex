<?php
namespace Pex;

class Route
{
    private $definitions=[];
    private $mountpoint;
    private $controllerClass;
    private $controller;

    use Route\PluginTrait;
    use Route\HttpMethodTrait;

    public function __construct($mountpoint = '/', $class = null)
    {
        $this->mountpoint = $mountpoint;
        $this->controllerClass = $class;
    }

    public function getMountPoint()
    {
        return $this->mountpoint;
    }

    /**
     * accept check whether the requestPath starts with mountpoint or not
     * then accept will try to initialize controllerObject
     * accept must be called before match
     */
    public function accept($requestPath, $flags = 0)
    {
        if (strpos($requestPath, $this->mountpoint) !== 0) {
            return false;
        }
        //initialize controller only if route accept this request
        if ($this->controllerClass and !$this->controller) {
            $controllerObject = new $this->controllerClass;
            if (is_callable($controllerObject)) {
                $controllerObject($this);
            }
            //flag to turn off annotation based routing
            if ($flags & \Pex\Pex::DISPATCH_FLAG_DO_NOT_PARSE_ANNOTATION) {
                return true;
            }

            $this->controller = new Route\Controller($controllerObject, $this->mountpoint);
            $this->controller->insertDefinitions($this->definitions);
        }

        return true;
    }

    public function with($plugin)
    {
        return $this->install($plugin);
    }

    public function match($method, $requestPath, &$pathParameters)
    {
        /*
        if ($this->controllerClass and !$this->controller) {
            throw new \RuntimeException('call accept first for mountpoint check and controller initialize');
        }
        */
        if (!isset($this->definitions[$method])) {
            return null;
        }
        $disp = $this->definitions[$method];
        $r = $disp->find($requestPath);
        if ($r) {
            list($handler, $pathParams) = $r;
            $pathParameters = $pathParams;
            return $handler;
        }
        /*
        foreach ($this->definitions[$method] as $define) {
            list($handler, $pattern) = $define;
            $pathParams = PathParameters::match($requestPath, $pattern);
            if ($pathParams !== false) {
                $pathParameters = $pathParams;
                return $handler;
            }
        }
         */
        return null;
    }

    private function add($methods, $pattern, $handler)
    {
        foreach ((array)$methods as $method) {
            if (!isset($this->definitions[$method])) {
                $this->definitions[$method] = new Dispatcher;
            }
            $disp = $this->definitions[$method];
            $disp->add(self::joinPath($this->mountpoint, $pattern), $handler);
            #$this->definitions[$method][] = [$handler, self::joinPath($this->mountpoint, $pattern)];
        }
        return $this;
    }

    public static function joinPath()
    {
        $paths = func_get_args();
        return preg_replace('/\/+/', '/', join('/', $paths));
    }
}
