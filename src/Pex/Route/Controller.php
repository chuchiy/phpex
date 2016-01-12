<?php
namespace Pex\Route;

class Controller
{
    private static $routeMethods = ['get'=>'GET', 'post'=>'POST', 'route'=>['GET', 'POST']];
    private $routeDefinitions=[];
    private $annotations;

    public function __construct($instance, $mountpoint) 
    {
        $this->instance = $instance;
        $this->reflection = new \ReflectionClass($instance);
        foreach($this->reflection->getMethods() as $methodRef) {
            $annos = \Pex\AnnotationParser::parseAll($methodRef->getDocComment());
            foreach(self::$routeMethods as $annoName=>$httpMethods) {
                if (!isset($annos[$annoName])) {
                    continue;
                }
                foreach($annos[$annoName] as $pattern) {
                    /*
                    $pattern = $pattern[0];
                    //@TODO we need a universal mountpoint pattern normalizer
                    if ($mountpoint[strlen($mountpoint)-1] == '/' and $pattern[0] == '/') {
                        $pattern = substr($pattern, 1); 
                    }
                    */
                    $pattern = \Pex\Route::joinPath($mountpoint, $pattern[0]);
                    foreach((array)$httpMethods as $httpMethod) {
                        $this->routeDefinitions[$httpMethod][] = [new Handler($this->instance, $methodRef, $annos), $pattern];
                    }
                }
            }
        }
    }

    public function insertDefinitions(&$definitions)
    {
        foreach($this->routeDefinitions as $method=>$defs) {
            foreach($defs as $def) {
                $definitions[$method][] = $def;
            }
        }
    }

}
