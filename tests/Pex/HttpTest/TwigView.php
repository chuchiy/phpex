<?php
namespace Pex\HttpTest;

class TwigView
{

    /**
     *
     * @twig('show.html')
     * @get('/show')
     */
    public function show($cycle)
    {
        return ['name'=> 'chuchi'];
    }

    /**
     *
     * @twig
     * @get('/render/<page>')
     */
    public function render($cycle)
    {
        return $cycle->twig->render(['name'=>'foobar'], $cycle['page'] . '.html');
    }
}
