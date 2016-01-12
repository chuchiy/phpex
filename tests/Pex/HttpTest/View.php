<?php
namespace Pex\HttpTest;

class View
{
    /**
     * class level plugin and annotation bind
     *
     */
    public function __invoke($route)
    {
        $route->get('/myname', [$this, 'myname']);
        $route->bindAnnotation('view', new \Pex\Plugin\Templater(__DIR__.'/templates/'));    
        //add some extra data
        $route->install(function($run){
            return function($cycle) use ($run) {
                $r = $run($cycle);
                if ((is_array($r) and array_keys($r) !== range(0, count($r) - 1)) or ($r instanceof \ArrayAccess)){
                    $r['author'] = 'chuchi'; 
                    $r['date'] = 2015; 
                    return $r;
                } else {
                    return $r;
                }
            };
        });
    }

    public function myname($cycle)
    {
        return "Chu-Chi Yang At " . $cycle->mountpoint(); 
    }



    /**
     *
     * no route annotation here
     */
    public function handy()
    {
        return 0;
    }

    /**
     *
     * @view('plaintext.php')
     * @get('/page')
     */
    public function plainpage($cycle)
    {
        $cycle->reply()->setHeader('Content-Type', 'text/plain; charset=utf-8');
        $r['list'] = [
            ['name' => 'foo'],
            ['name' => 'bar'],
            ['name' => 'hello'],
            ['name' => 'world'],
        ];
        return $r; 
    }

    /**
     * @view
     * @decor('a', 'b')
     * @get('/hello')
     */
    public function hello($cycle) 
    {
        $r = $cycle->view->render(['a' => 'b'], 'small.php');

        return ['chuchi'=> 'yang', 'render' => $r, 'handy' => $this->handy()];
    }

    /**
     *
     * view will not work
     * @view('abc.php')
     * @decor('foo', 'bar')
     * @get('/decor')
     */
    public function decor($cycle) 
    {
        return 'plaintext';
    }

    /**
     *
     * @get('/generator')
     */
    public function generator($cycle)
    {
        foreach(range(0, 10) as $n) {
            yield $n;
        }
    }

    /**
     *
     * @get('/print')
     */
    public function printer($cycle) 
    {
        return ['hello', 'world'];
    }

    /**
     *
     * @route('/get/<user>')
     */
    public function getUser($cycle)
    {
        $cycle->logger->info("visit get user");
        return ['user' => $cycle->want('user'), 'ua' => $cycle->client()->userAgent(), 'ip' => $cycle->client()->ip()];
    }

    /**
     *
     * @get('/stream')
     */
    public function stream($cycle)
    {
        $fp = fopen('php://memory', 'r+');
        fwrite($fp, "it's a stream");
        rewind($fp);
        return $fp;
    }

    /**
     *
     * @get('/deny')
     */
    public function accessDeny($cycle)
    {
        throw new \Pex\Exception\HttpException(403, [], 'Access Denied');
    }

    /**
     * @post('/indat')
     *
     */
    public function inputData($cycle)
    {
        return ['p' => $cycle->want('p'), 'dat' => (string)$cycle->request()->getBody()];
    }

}
