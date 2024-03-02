<?php
namespace My\Space;

class Base
{
    protected const BAR = 'bar_base';
}

class Foo extends Base
{
    protected const BAR = 'bar_foo';

    public function getParentBar()
    {
        return parent::BAR;
    }

    public function getSelfBar()
    {
        return self::BAR;
    }

    public function getStaticBar()
    {
        return static::BAR;
    }

    public function getFooBar()
    {
        return Foo::BAR;
    }
}
