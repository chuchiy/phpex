<?php
namespace Pex\Plugin;

class CatchHttpException extends BasePlugin
{
    protected function apply($cycle, $run)
    {
        try {
            return $run($cycle);
        } catch (\Pex\Exception\HttpException $ex) {
            $w = $cycle($ex->getStatusCode(), $ex->getHeaders());
            $w($ex->getBody());
        }
    }
}

