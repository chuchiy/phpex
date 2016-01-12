<?php
namespace Pex;
class AnnotationParser 
{

	const ANNOTATION_REGEX = '/@(\w+)(?:\s*(?:\(\s*)?(.*?)(?:\s*\))?)??\s*(?:\n|\*\/)/S';
    const PARAMETERS_REGEX = '/[\"\']([^\"\']*)[\"\'],?\s*/S';

    public static function parseAll($docComment) 
    {
 		$hasAnnotations = preg_match_all( self::ANNOTATION_REGEX, $docComment, $matches, PREG_SET_ORDER);

		if (!$hasAnnotations) {
			return NULL;
		}
		$annos = [];
		foreach ($matches as $match) {
            $name = $match[1];
            $paramsResult = null;

			if (isset($match[2])) {
                $paramspart = $match[2];
                $params = [];
                $hasParams = preg_match_all(self::PARAMETERS_REGEX, $paramspart, $params, PREG_SET_ORDER);
                if ($hasParams) {
                    foreach ($params as $param) {
                        $paramsResult[] = $param[1];
                    }
                } 
            }
            $annos[$name][] = $paramsResult;
		}
		return $annos;
   
    }


    public static function parse($docComment) 
    {
		$hasAnnotations = preg_match_all( self::ANNOTATION_REGEX, $docComment, $matches, PREG_SET_ORDER);

		if (!$hasAnnotations) {
			return NULL;
		}
		$annos = [];
		foreach ($matches as $match) {
            $name = $match[1];
            $params = null;

			if (isset($match[2])) {
                $paramspart = $match[2];
                $params = function() use ($paramspart) {
                    $params = [];
                    $rs = [];
                    $hasParams = preg_match_all(self::PARAMETERS_REGEX, $paramspart, $params, PREG_SET_ORDER);
                    if ($hasParams) {
                        foreach ($params as $param) {
                            $rs[] = $param[1];
                        }
                    } 
                    return $rs;
                };
            }
            $annos[$name][] = $params;
		}
		return $annos;
	}
}
