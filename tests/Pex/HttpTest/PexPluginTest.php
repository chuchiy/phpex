<?php
namespace Pex\HttpTest;

class PexPluginTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->pex = new \Pex\Pex;
        $this->runner = new \Pex\ServerRunner($this->pex);
        $this->pex->install(function($run) {
            return function($cycle) use ($run) {
                $cycle->register('pre', function($c){
                    return new \ArrayObject;
                });
                $cycle->register('after', function($c){
                    return new \ArrayObject;
                });
                $r = $run($cycle);
                return join('>', (array)$cycle->pre) . $r . join('>', (array)$cycle->after);
            };
        });
    }

    public static function stepInserter($pre, $after)
    {
         $stepPlugin = function($run) use ($pre, $after) {
            return function($cycle) use ($run, $pre, $after) {
                $cycle->pre[] = $pre;
                $r = $run($cycle);
                $cycle->after[] = $after;
                return $r; 
            };
        };       
        return $stepPlugin; 
    }

    public function testPluginExecutionOrder()
    {
        $this->pex->install(self::stepInserter('A', 'B'));
        $this->pex->install(self::stepInserter('C', 'D'));

        $this->pex->get('/run', function($cycle) {
            return '-ZZ-'; 
        })->with(self::stepInserter('E', 'F'))->with(self::stepInserter('G', 'H'));

        $this->assertEquals('A>C>E>G-ZZ-H>F>D>B', (string)$this->runner->get('/run')->getBody());
    }

    public function testClassPluginExecution()
    {
        $this->pex->install(self::stepInserter('A', 'B'));
        $this->pex->install(self::stepInserter('C', 'D'));
        $this->pex->attach('/', '\Pex\HttpTest\PluginView')->get('/foo', function($cycle){
            return '-ZZ-'; 
        })->with(self::stepInserter('E', 'F'));
        //plugin install in class __invoke will also take effect on direct route 
        $this->assertEquals('A>C>E>G-ZZ-H>F>D>B', (string)$this->runner->get('/foo')->getBody());
        //method annotation plugin take effect
        $this->assertEquals('A>C>E>G>X>V-UU-W>Y>H>F>D>B', (string)$this->runner->get('/show')->getBody());
        $this->assertEquals('A>C>E>G>Q-RR-P>H>F>D>B', (string)$this->runner->get('/disp')->getBody());
    }

}
