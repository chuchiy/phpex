<?php
namespace Pex;

class ViewRender extends \ArrayObject
{
    protected $stringify;

    public function __construct($stringify, $context)
    {
        parent::__construct($context);
        $this->stringify = $stringify;
    }

    public function __invoke()
    {
        return call_user_func($this->stringify, $this);
    }
}
