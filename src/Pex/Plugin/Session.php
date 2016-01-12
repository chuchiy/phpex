<?php
namespace Pex\Plugin;

/**
 * a simple user session manager
 *
 * 
 */
class Session extends BasePlugin
{
    protected $pool;
    protected $sessionName;
    protected $timeout;

    /**
     *
     * @param CacheItemPoolInterface $pool
     */
    public function __construct($pool, $sessionName='PEXSESSION', $timeout=1800)
    {
        $this->pool = $pool;
        $this->sessionName = $sessionName;
        $this->timeout = $timeout;
    }

    protected function apply($cycle, $run)
    {
        $key = $this->getSessionKey($cycle->request());
        $item = null;
        if ($key) {
            $item = $this->pool->getItem($key);
            if ($item->isHit()) {
                $value = $item->get();
                $cycle->register('session', function($c) use ($value) {
                    return new \ArrayObject($value); 
                }); 
            } else {
                $this->sessionNotFound();
            }
        }
        try {
            $r = $run($cycle);
            if (isset($cycle->session)) {
                $this->pool->save($item->expiresAfter($this->timeout)->set((array)$cycle->session));
            }
            return $r;
        } catch (\Pex\Exception\HttpException $ex){
            if (isset($cycle->session)) {
                $this->pool->save($item->expiresAfter($this->timeout)->set((array)$cycle->session));
            }
            throw $ex; 
        } 
    }

    /**
     * read session from cookie and request header
     *
     */
    protected function getSessionKey($request)
    {
        $cookie = $request->getCookieParams();
        if (isset($cookie[$this->sessionName])) {
            return $cookie[$this->sessionName];
        }
        $headerKey = $request->getHeader('x-' . strtolower($this->sessionName));
        if ($headerKey) {
            return $headerKey[0];
        }
        return $this->sessionKeyNotFound();
    }

    protected function sessionKeyNotFound()
    {
        throw new \Pex\Exception\HttpException(403);
    }

    protected function sessionNotFound()
    {
        throw new \Pex\Exception\HttpException(403);
    }
}
