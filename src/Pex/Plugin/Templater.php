<?php
namespace Pex\Plugin;

class Templater extends HighorderPlugin
{
    protected $templateDir;
    protected $lazy;

    public function __construct($templateDir, $lazy = true)
    {
        $this->templateDir = $templateDir;
        $this->lazy = $lazy;
    }

    public function render($context, $templates)
    {
        $context = (array)$context;
        if (is_string($templates)) {
            $templates = [$templates];
        }
        return $this->renderMany($context, $templates);
    }


    /**
     * plain html php template
     *
     */
    protected function renderMany($context, $templates)
    {
        extract($context);
        ob_start();
        foreach ($templates as $template) {
            require $this->templateDir. '/' . $template;
        }
        return ob_get_clean();
    }


    protected function lazyRender($context, $templates)
    {
        $stringify = function ($context) use ($templates) {
            return $this->render($context, $templates);
        };
        return new \Pex\ViewRender($stringify, $context);
    }

    protected function apply($cycle, $run, $name, $args)
    {
        $cycle->register($name, function ($cycle) {
            return $this;
        });
        $r = $run($cycle);
        if (!$args) {
            return $r;
        }

        if (is_array($r) and array_keys($r) !== range(0, count($r) - 1)) {
            return ($this->lazy)?$this->lazyRender($r, $args):$this->render($r, $args);
        } else {
            return $r;
        }
    }
}
