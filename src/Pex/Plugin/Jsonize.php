<?php
namespace Pex\Plugin;

class Jsonize extends BasePlugin
{
    protected function apply($cycle, $run)
    {
        $r = $run($cycle);
        if ((is_array($r)
             and array_keys($r) !== range(0, count($r) - 1))
             or (is_object($r) and $r instanceof \stdClass)) {
            $cycle->reply()['Content-Type'] = 'application/json; charset=utf-8';
            $r = json_encode($r, JSON_UNESCAPED_UNICODE);
        }
        return $r;
    }
}
