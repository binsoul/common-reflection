<?php

namespace BinSoul\Test\Common\Reflection;

use BinSoul\Common\Reflection\MemoizingReflector;

require_once 'Model.php';

class MemoizingReflectorTest extends \PHPUnit_Framework_TestCase
{
    public function test_uses_provided_data()
    {
        $reflector = new MemoizingReflector([ClassA::class => ['isInstantiable' => false]]);

        $this->assertFalse($reflector->isInstantiable(ClassA::class));
    }

    public function test_can_set_data()
    {
        $reflector = new MemoizingReflector();
        $this->assertTrue($reflector->isInstantiable(ClassA::class));
        $this->assertEquals(1, count($reflector->resolveMethodParameters(ClassA::class, '__construct', [])));
        $reflector->setData([ClassA::class => ['isInstantiable' => false]]);
        $this->assertFalse($reflector->isInstantiable(ClassA::class));
    }

    public function test_returns_data()
    {
        $reflector = new MemoizingReflector();
        $reflector->setData([ClassA::class => ['isInstantiable' => false]]);
        $this->assertFalse($reflector->isInstantiable(ClassA::class));
        $this->assertEquals([ClassA::class => ['isInstantiable' => false]], $reflector->getData());
    }

    public function test_memoizes_data()
    {
        $reflector = new MemoizingReflector();
        $this->assertTrue($reflector->isInstantiable(ClassA::class));
        $this->assertTrue($reflector->isInstantiable(ClassA::class));
        $this->assertEquals(1, count($reflector->resolveMethodParameters(ClassA::class, '__construct', [])));
        $this->assertEquals(1, count($reflector->resolveMethodParameters(ClassA::class, '__construct', [])));
        $this->assertEquals(3, count($reflector->resolveMethodParameters(ClassC::class, 'set', [])));
        $this->assertEquals(3, count($reflector->resolveMethodParameters(ClassC::class, 'set', [])));
    }

    public function test_works_with_objects()
    {
        $object = new ClassA();

        $reflector = new MemoizingReflector();
        $this->assertTrue($reflector->isInstantiable($object));
        $this->assertEquals(1, count($reflector->resolveMethodParameters($object, '__construct', [])));
    }
}
