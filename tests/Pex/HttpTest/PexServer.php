<?php
namespace Pex\HttpTest;

class PexServer
{
    public static function setup()
    {
        $pex = new \Pex\Pex;
        $pex->attach('/v/', '\Pex\HttpTest\View');
        $pex->attach('/twig/', '\Pex\HttpTest\TwigView');
        $pex->get('/find/<name>', function($cycle) {
            return 'find ' . $cycle['name']; 
        });


        $pex->post('/set/<id>', function($cycle) {
            return $cycle->input + ['id'=>$cycle['id']];        
        })->post('/create/<id>', function($cycle) {
            return ['id'=>$cycle['id'], 'dat'=>$cycle->input]; 
        })->with(function($run){
            return function($cycle) use ($run) {
                $cycle->register('input', function($c) {
                    return json_decode((string)$c->request()->getBody(), true); 
                });
                return $run($cycle);    
            };
        });
        $pex->install(new \Pex\Plugin\CatchHttpException);
        $pex->install(new \Pex\Plugin\Jsonize);
        $pex->install(function($run){
            return function($cycle) use ($run) {
                $cycle->register('logger', function($cycle) {
                    return new \Psr\Log\NullLogger; 
                });
                return $run($cycle); 
            };
        });
        $pex->bindAnnotation('twig', new \Pex\Plugin\TwigPlugin(__DIR__ . '/templates/', sys_get_temp_dir() . '/twig_cache/'));
        //annotation plugin
        $pex->bindAnnotation('decor', function($name, $args){
            $plugin = function($run) use ($args) {
                return function($cycle) use ($run, $args) {
                    $r = $run($cycle);    
                    if (is_string($r)) {
                        return array_merge($args, [$r]); 
                    } else {
                        return $r;
                    }
                }; 
            };
            return $plugin;
        });
        return $pex;  
    }
}
