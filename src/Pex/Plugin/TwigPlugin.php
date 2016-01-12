<?php
namespace Pex\Plugin;
/**
 * plugin use twig template as render
 *
 *
 */
class TwigPlugin extends \Pex\Plugin\Templater
{
    private $twig;
    protected $cacheDir;

    public function __construct($templateDir, $cacheDir, $lazy=true)
    {
        $this->cacheDir = $cacheDir;
        parent::__construct($templateDir, $lazy);
    }

    /**
     * reference twig init. override for project customize
     *
     */
    public function twig()
    {
        if (!$this->twig) {
            $loader = new \Twig_Loader_Filesystem($this->templateDir);
            $twig = new \Twig_Environment($loader, ['cache' => $this->cacheDir, 'auto_reload' => true]); 
            $escaper = new \Twig_Extension_Escaper(true); 
            $twig->addExtension($escaper); 
            $this->twig = $twig;
        }
        return $this->twig;
    }

    protected function renderMany($context, $templates) {
        return implode(array_map(function($template) use ($context) {
            return $this->twig()->render($template, $context); 
        }, $templates));
    }
}

