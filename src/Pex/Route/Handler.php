<?php
namespace Pex\Route;

class Handler 
{
    private $instance;
    private $methodRef;
    private $methodAnnos;

    use PluginTrait;

    public function __construct($instance, $methodRef, $methodAnnotations)
    {
        $this->instance = $instance;
        $this->methodRef = $methodRef;
        $this->methodAnnos = $methodAnnotations;
    }

    public function getCallable()
    {
        return [$this->instance, $this->methodRef->getName()];
    }

    public function buildPlugins($annotationPlugins=[])
    {
        foreach ($this->methodAnnos as $name=>$paramslist) {
            if (!isset($annotationPlugins[$name])) {
                continue;
            }
            foreach ($paramslist as $params) {
                $this->install(call_user_func($annotationPlugins[$name], $name, $params));
            }
        }
        return $this->getPlugins();
    }

}
