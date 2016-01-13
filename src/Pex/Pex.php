<?php namespace Pex;

use Zend\Diactoros\Response;

class Pex
{
    const DISPATCH_FLAG_DO_NOT_PARSE_ANNOTATION = 0x01;
    const DISPATCH_FLAG_RESERVED_1              = 0x02;
    const DISPATCH_FLAG_RESERVED_2              = 0x04;
    const DISPATCH_FLAG_RESERVED_3              = 0x08;

    use RouteTrait;
    use Route\PluginTrait;

    public function emit($cycle, $r = null)
    {
        $writer = ($cycle->writer())?$cycle->writer():$cycle();
        if ($r) {
            if (is_callable($r)) {
                $r = $r();
            }
            if ((is_resource($r) and get_resource_type($r) == 'stream')) {
                //flush header
                $writer();
                $stream = $cycle->response()->getBody()->detach();
                stream_copy_to_stream($r, $stream);
                $cycle->response()->getBody()->attach($stream);
            } elseif (is_array($r) or ($r instanceof \Traversable) or ($r instanceof \Generator)) {
                foreach ($r as $part) {
                    $writer($part);
                }
            } else {
                $writer((string)$r);
            }
        } else {
            //flush if not
            $writer();
        }
    }

    public function serve($cycle = null, $flags = 0)
    {
        $cycle = ($cycle)?$cycle:Cycle::create();
        $result = $this->dispatch($cycle->request()->getMethod(), $cycle->request()->getUri()->getPath(), $flags);
        if (!$result) {
            $w = $cycle(404, []);
            $w($cycle->request()->getUri());
            return $this->emit($cycle);
        }

        $plugins = $result['plugins'];
        $cycle->setMountPoint($result['mountpoint']);
        $cycle->setPathParameters($result['parameters']);
        $runner = $result['handler'];
        if (!is_callable($runner)) {
            //build method annotation plugins
            $plugins = array_merge(
                $runner->buildPlugins($result['annotationPlugins'] + $this->getAnnotationPlugins()),
                $plugins
            );
            $runner = $runner->getCallable();
        }

        //build callable
        $callable = array_reduce(
            array_merge($plugins, $this->plugins),
            function (
                $carry,
                $item
            ) {
                return $item($carry);
            },
            $runner
        );

        $r = $callable($cycle);
        return $this->emit($cycle, $r);
    }
}
