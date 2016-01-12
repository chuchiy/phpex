<?php
namespace Pex\Route;

trait PluginTrait
{
    private $plugins=[];
    private $annotationPlugins=[];

    public function install($plugin)
    {
        array_unshift($this->plugins, $plugin);
        return $this;
    }

    public function bindAnnotation($name, $pluginMaker)
    {
        $this->annotationPlugins[$name] = $pluginMaker;
        return $this;
    }

    public function getPlugins()
    {
        return $this->plugins; 
    }

    public function getAnnotationPlugins()
    {
        return $this->annotationPlugins; 
    }

}
