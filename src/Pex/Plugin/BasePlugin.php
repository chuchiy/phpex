<?php
namespace Pex\Plugin;

/**
 * base class of class-style plugin
 */
abstract class BasePlugin
{
    abstract protected function apply($cycle, $run);

    final public function __invoke($run)
    {
        return function ($cycle) use ($run) {
            return $this->apply($cycle, $run);
        };
    }
}
