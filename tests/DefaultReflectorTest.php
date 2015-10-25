<?php

namespace BinSoul\Test\Reflection;

use BinSoul\Reflection\DefaultReflector;

require_once 'Model.php';

class DefaultReflectorTest extends \PHPUnit_Framework_TestCase
{
    public function test_indicates_is_instantiable()
    {
        $reflector = new DefaultReflector();

        $this->assertTrue($reflector->isInstantiable(ClassA::class));
        $this->assertTrue($reflector->isInstantiable(\ReflectionClass::class));
        $this->assertFalse($reflector->isInstantiable(\Serializable::class));
        $this->assertFalse($reflector->isInstantiable(FooTrait::class));
    }

    public function test_indicates_is_user_defined()
    {
        $reflector = new DefaultReflector();

        $this->assertTrue($reflector->isUserDefined(ClassA::class));
        $this->assertFalse($reflector->isUserDefined(\ReflectionClass::class));
        $this->assertFalse($reflector->isUserDefined(\Serializable::class));
        $this->assertTrue($reflector->isUserDefined(FooTrait::class));
    }

    public function test_returns_parents()
    {
        $reflector = new DefaultReflector();

        $this->assertEquals([ClassD::class], $reflector->getParents(ClassE::class));
        $this->assertEquals([], $reflector->getParents(\ReflectionClass::class));
        $this->assertEquals([], $reflector->getParents(\Serializable::class));
        $this->assertEquals([], $reflector->getParents(FooTrait::class));
        $this->assertEquals([], $reflector->getParents(BazInterface::class));
    }

    public function test_returns_interfaces()
    {
        $reflector = new DefaultReflector();

        $this->assertEquals([FooInterface::class, \Serializable::class], $reflector->getInterfaces(ClassA::class));
        $this->assertEquals([BazInterface::class,
                             BarInterface::class, \Serializable::class, ], $reflector->getInterfaces(ClassB::class));
        $this->assertEquals([], $reflector->getInterfaces(ClassC::class));
        $this->assertEquals([], $reflector->getInterfaces(FooTrait::class));
        $this->assertEquals([BarInterface::class], $reflector->getInterfaces(BazInterface::class));
    }

    public function test_resolves_optional_parameters()
    {
        $reflector = new DefaultReflector();

        $parameters = $reflector->resolveParameters(ClassA::class, '__construct', []);
        $this->assertEquals(1, count($parameters));
        $this->assertTrue($parameters[0]->isAvailable);
        $this->assertTrue($parameters[0]->isOptional);
        $this->assertEquals('optional', $parameters[0]->name);
        $this->assertEquals('optional', $parameters[0]->value);
    }

    public function test_resolves_arguments_by_name()
    {
        $reflector = new DefaultReflector();

        $parameters = $reflector->resolveParameters(ClassA::class, '__construct', ['optional' => 'provided']);
        $this->assertEquals(1, count($parameters));
        $this->assertTrue($parameters[0]->isAvailable);
        $this->assertTrue($parameters[0]->isOptional);
        $this->assertEquals('optional', $parameters[0]->name);
        $this->assertEquals('provided', $parameters[0]->value);
    }

    public function test_resolves_arguments_by_index()
    {
        $reflector = new DefaultReflector();

        $parameters = $reflector->resolveParameters(ClassA::class, '__construct', ['provided']);
        $this->assertEquals(1, count($parameters));
        $this->assertTrue($parameters[0]->isAvailable);
        $this->assertTrue($parameters[0]->isOptional);
        $this->assertEquals('optional', $parameters[0]->name);
        $this->assertEquals('provided', $parameters[0]->value);
    }

    public function test_resolves_class_parameters()
    {
        $reflector = new DefaultReflector();

        $parameters = $reflector->resolveParameters(ClassB::class, '__construct', []);
        $this->assertEquals(1, count($parameters));
        $this->assertFalse($parameters[0]->isAvailable);
        $this->assertFalse($parameters[0]->isOptional);
        $this->assertEquals('a', $parameters[0]->name);
        $this->assertEquals(ClassA::class, $parameters[0]->value);
    }

    public function test_resolves_interface_parameters()
    {
        $reflector = new DefaultReflector();

        $parameters = $reflector->resolveParameters(ClassC::class, '__construct', []);
        $this->assertEquals(3, count($parameters));
        $this->assertFalse($parameters[0]->isAvailable);
        $this->assertFalse($parameters[0]->isOptional);
        $this->assertEquals('i', $parameters[0]->name);
        $this->assertEquals(FooInterface::class, $parameters[0]->value);

        $this->assertFalse($parameters[1]->isAvailable);
        $this->assertFalse($parameters[1]->isOptional);
        $this->assertEquals('b', $parameters[1]->name);
        $this->assertEquals(ClassB::class, $parameters[1]->value);

        $this->assertFalse($parameters[2]->isAvailable);
        $this->assertTrue($parameters[2]->isOptional);
        $this->assertEquals('d', $parameters[2]->name);
        $this->assertEquals(ClassD::class, $parameters[2]->value);
    }

    public function test_resolves_missing_simple_parameters()
    {
        $reflector = new DefaultReflector();

        $parameters = $reflector->resolveParameters(ClassD::class, '__construct', []);
        $this->assertEquals(2, count($parameters));
        $this->assertFalse($parameters[1]->isAvailable);
        $this->assertFalse($parameters[1]->isOptional);
        $this->assertEquals('required', $parameters[1]->name);
        $this->assertEquals(null, $parameters[1]->value);
    }

    public function test_counts_reflection()
    {
        $reflector = new DefaultReflector();

        $this->assertEquals(0, $reflector->getReflectionCount());
        $reflector->isInstantiable(FooTrait::class);
        $this->assertEquals(1, $reflector->getReflectionCount());
        $reflector->isUserDefined(ClassA::class);
        $this->assertEquals(2, $reflector->getReflectionCount());
        $reflector->getParents(ClassA::class);
        $this->assertEquals(3, $reflector->getReflectionCount());
        $reflector->getInterfaces(ClassA::class);
        $this->assertEquals(4, $reflector->getReflectionCount());
        $reflector->resolveParameters(ClassA::class, '__construct', []);
        $this->assertEquals(5, $reflector->getReflectionCount());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function test_throws_exception_for_invalid_type()
    {
        $reflector = new DefaultReflector();
        $reflector->isInstantiable('\foobar');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function test_throws_exception_for_invalid_method()
    {
        $reflector = new DefaultReflector();
        $reflector->resolveParameters(ClassA::class, 'foobar', []);
    }
}
