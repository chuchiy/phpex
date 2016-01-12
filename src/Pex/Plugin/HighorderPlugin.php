<?php
namespace Pex\Plugin;

/**
 * class-style high-order plugin for receive annotation arguments
 *
 */
abstract class HighorderPlugin
{
    abstract protected function apply($cycle, $run, $name, $args);

    final public function __invoke($name, $args=null)
    {
        return function($run) use ($name, $args) {
            return function($cycle) use ($run, $name, $args) {
                return $this->apply($cycle, $run, $name, $args);    
            };
        };
    }

}
