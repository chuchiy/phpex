<?php
namespace Pex;

class Client 
{
    private $request;

    public function __construct($request)
    {
        $this->request = $request;
    }

    public function isAjax()
    {
        return $this->getHeader('x-requested-with') == 'XMLHttpRequest'; 
    }

    public function contentType()
    {
        return $this->getHeader('content-type');
    }

    public function ip()
    {
        return $this->request->getServerParams()['REMOTE_ADDR'];
    }

    public function publicIp()
    {
        $publicIpFlag = FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE;
        $ip = filter_var($this->ip(), FILTER_VALIDATE_IP, $publicIpFlag);
        if ($ip) {
            return $ip;
        }
        $ip = filter_var($this->getHeader('x-real-ip'), FILTER_VALIDATE_IP, $publicIpFlag);
        if ($ip) {
            return $ip;
        }
        $forwardedfor = $this->getHeader('x-forwarded-for');
        $ip = array_pop(preg_split("/;|,|\s/", $forwardedfor));
        $ip = filter_var($ip, FILTER_VALIDATE_IP, $publicIpFlag);
        if ($ip) {
            return $ip;
        }

        return null;
    }

    public function redirect($url)
    {
        throw new \Pex\Exception\HttpException(302, ['location' => $url]); 
    }

    public function referer()
    {
        return $this->getHeader('referer');
    }

    public function userAgent()
    {
        return $this->getHeader('user-agent');
    }

    protected function getHeader($name)
    {
        $header = $this->request->getHeader($name);
        if ($header) {
            return $header[0]; 
        } else {
            return null; 
        }
    }
}
