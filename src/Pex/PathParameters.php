<?php
namespace Pex;

class PathParameters
{
    private static $typeHintDefine = [
        'int'     => '\d+',
        'float'   => '[-+]?(\d*)?\.\d+',
        'numeric' => '[-+]?(\d*[.])?\d+',
    ];

    public static function match($path, $pattern)
    {
        $regexPattern = preg_replace_callback(
            '/<([^<>]*)>/',
            function ($matches) {
                $parts = explode(':', $matches[1]);
                $captureName = $parts[0];
                if (count($parts) > 1) {
                    $typeHint = (isset(self::$typeHintDefine[$parts[1]]))?self::$typeHintDefine[$parts[1]]:$parts[1];
                } else {
                    $typeHint = '\w+';
                }
                return "(?P<".$captureName.">".$typeHint.")";
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
