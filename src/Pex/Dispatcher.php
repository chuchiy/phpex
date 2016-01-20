<?php
namespace Pex;

/**
 * use combined regular expressions from FastRoute
 * http://nikic.github.io/2014/02/18/Fast-request-routing-using-regular-expressions.html
 *
 */
class Dispatcher
{
    private static $typeHintDefine = [
        'int'     => '\d+',
        'float'   => '[-+]?\d*?\.\d+',
        'numeric' => '[-+]?\d*[.]?\d+',
    ];

    private $findTable;
    private $paramOffset=1;
    private $combinePatterns=[];
    private $regex='';

    public function add($pattern, $handler)
    {
        $params = [];
        $regexPattern = preg_replace_callback(
            '/<([^<>]*)>/',
            function ($matches) use (&$params) {
                $parts = explode(':', $matches[1]);
                $captureName = $parts[0];
                if (count($parts) > 1) {
                    $typeHint = (isset(self::$typeHintDefine[$parts[1]]))?self::$typeHintDefine[$parts[1]]:$parts[1];
                } else {
                    $typeHint = '\w+';
                }
                $params[] = $captureName;
                return "(".$typeHint.")";
            },
            str_replace(
                '/',
                '\/',
                $pattern
            )
        );
        //pattern has no param declare
        if (!$params) {
            //add empty group for placeholder
            $regexPattern .= '()';
        }
        $this->combinePatterns[] = $regexPattern;
        $this->findTable[$this->paramOffset] = [$handler, $params];
        $this->paramOffset += ($params)?count($params):1;
        $this->regex = null;
    }

    public function find($path)
    {
        if (!preg_match($this->getRegex(), $path, $matches)) {
            return null;
        }

        for ($i = 1; $i < count($matches) and '' === $matches[$i]; $i++) {
        }

        list($handler, $paramNames) = ($i < count($matches))?$this->findTable[$i]:$this->findTable[--$i];

        $params = [];
        foreach ($paramNames as $paramName) {
            $params[$paramName] = $matches[$i++];
        }
        return [$handler, $params];
    }

    private function getRegex()
    {
        if (!$this->regex) {
            $this->regex = '/^(?:' . join('|', $this->combinePatterns) . ')$/x';
        }
        return $this->regex;
    }
}
