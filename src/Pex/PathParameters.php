<?php
namespace Pex;

class PathParameters
{
    public static function match($path, $pattern)
    {
        $paramKeys = [];
        $regexPattern = preg_replace_callback(
            '/<(\w*)>/',
            function ($matches) use ($paramKeys) {
                $paramKeys[] = $matches[1];
                return "(?P<".$matches[1].">\w+)";
            },
            str_replace(
                '/',
                '\/',
                $pattern
            )
        );
        $regexPattern = '/^' . $regexPattern . '$/';
        $r = preg_match($regexPattern, $path, $matches);
        if (!$r) {
            return false;
        }
        // remove numeric key
        // this only works for php 5.6+
        // $matches = array_filter($matches, function($v, $k){return !is_numeric($k);}, ARRAY_FILTER_USE_BOTH);
        foreach ($matches as $key => $val) {
            if (is_numeric($key)) {
                unset($matches[$key]);
            }
        }
        return $matches;
    }
}
