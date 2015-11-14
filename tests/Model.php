<?php

namespace BinSoul\Test\Common\Reflection;

trait FooTrait
{
    public function serialize()
    {
    }

    public function unserialize($serialized)
    {
    }
}

trait BarTrait
{
    use FooTrait;
}

interface FooInterface extends \Serializable
{
}

interface BarInterface
{
}

interface BazInterface extends BarInterface
{
}

class ClassA implements FooInterface
{
    use FooTrait;

    /** @var string */
    public $optional;

    public function __construct($optional = 'optional')
    {
        $this->optional = $optional;
    }
}

class ClassB implements BazInterface, \Serializable
{
    use BarTrait;

    /** @var ClassA */
    public $a;

    public function __construct(ClassA $a)
    {
        $this->a = $a;
    }
}

class ClassC
{
    /** @var ClassB */
    public $b;
    /** @var ClassD */
    public $d;
    /** @var FooInterface */
    public $i;

    public function __construct(FooInterface $i, ClassB $b, ClassD $d = null)
    {
        $this->i = $i;
        $this->b = $b;
        $this->d = $d;
    }

    public function set(FooInterface $i, ClassB $b, ClassD $d = null)
    {
        $this->i = $i;
        $this->b = $b;
        $this->d = $d;
    }
}

class ClassD
{
    /** @var ClassC */
    public $c;
    /** @var string */
    public $required;

    public function __construct(ClassC $c, $required)
    {
        $this->c = $c;
        $this->required = $required;
    }
}

class ClassE extends ClassD
{
}

class ClassF extends ClassA
{
}

class CircularDependency1
{
    public function __construct(CircularDependency2 $c)
    {
    }
}

class CircularDependency2
{
    public function __construct(CircularDependency1 $c)
    {
    }
}

class CircularDependencyStarter
{
    public function __construct(CircularDependency1 $c)
    {
    }
}
