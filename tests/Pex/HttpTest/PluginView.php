<?php
namespace Pex\HttpTest;

class PluginView
{
    public function __invoke($route)
    {
        $route->install(PexPluginTest::stepInserter('G', 'H'));
        $route->bindAnnotation('step', function ($name, $args) {
            return \Pex\HttpTest\PexPluginTest::stepInserter($args[0], $args[1]);
        });
    }

    /**
     *
     * @step('X', 'Y')
     * @step('V', 'W')
     * @get('/show')
     */
    public function show($cycle)
    {
        return '-UU-';
    }

    /**
     *
     * @step('Q', 'P')
     * @get('/disp')
     */
    public function disp($cycle)
    {
        return '-RR-';
    }
}
