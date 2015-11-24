<?php

namespace BinSoul\Test\Common\Reflection;

use BinSoul\Common\Reflection\DefaultDependencyInjector;
use BinSoul\Common\Reflection\DefaultReflector;

require_once 'Model.php';

class DefaultDependencyInjectorTest extends \PHPUnit_Framework_TestCase
{
    public function test_registers_object()
    {
        $injector = new DefaultDependencyInjector(new DefaultReflector());

        $a = new ClassA();
        $injector->registerObject($a);
        $b = $injector->newSingleton(ClassB::class);
        $this->assertSame($a, $b->a);

        $f = new ClassF();
        $injector->registerObject($f);
        $b = $injector->newSingleton(ClassB::class);
    }

    public function test_registers_object_parents()
    {
        $injector = new DefaultDependencyInjector(new DefaultReflector());

        $f = new ClassF();
        $injector->registerObject($f);
        $b = $injector->newSingleton(ClassB::class);
        $this->assertSame($f, $b->a);
    }

    public function test_registers_objects_in_constructor()
    {
        $f = new ClassF();

        $injector = new DefaultDependencyInjector(new DefaultReflector(), [$f]);

        $b = $injector->newSingleton(ClassB::class);
        $this->assertSame($f, $b->a);
    }

    public function test_registers_implementation()
    {
        $injector = new DefaultDependencyInjector(new DefaultReflector());

        $injector->registerImplementation(FooInterface::class, ClassA::class);
        $c = $injector->newInstance(ClassC::class);
        $this->assertInstanceOf(ClassA::class, $c->i);
    }

    public function test_uses_arguments()
    {
        $injector = new DefaultDependencyInjector(new DefaultReflector());

        $injector->registerImplementation(FooInterface::class, ClassA::class);
        $d = $injector->newInstance(ClassD::class, ['required' => 'required']);
        $c = $injector->newInstance(ClassC::class, ['d' => $d]);

        $this->assertEquals('required', $d->required);
        $this->assertSame($d, $c->d);
    }

    public function test_uses_factories()
    {
        $injector = new DefaultDependencyInjector(new DefaultReflector());
        $injector->registerFactory(FooInterface::class, function () {
            return new ClassA();
        });

        $c = $injector->newInstance(ClassC::class);
        $this->assertInstanceOf(ClassA::class, $c->i);
    }

    public function test_injects_dependencies_into_factories()
    {
        $injector = new DefaultDependencyInjector(new DefaultReflector());
        $injector->registerFactory(BazInterface::class, function (ClassA $a) {
            return new ClassB($a);
        });

        $b = $injector->newInstance(BazInterface::class);
        $this->assertInstanceOf(ClassA::class, $b->a);
    }

    public function test_returns_singletons()
    {
        $injector = new DefaultDependencyInjector(new DefaultReflector());
        $a = $injector->newSingleton(ClassA::class);
        $b = $injector->newSingleton(ClassA::class);

        $this->assertSame($a, $b);
    }

    /**
     * @expectedException \RuntimeException
     */
    public function test_detects_circular_dependecies()
    {
        $injector = new DefaultDependencyInjector(new DefaultReflector());

        $injector->newInstance(CircularDependencyStarter::class);
    }

    /**
     * @expectedException \RuntimeException
     */
    public function test_throws_exception_for_missing_parameter()
    {
        $injector = new DefaultDependencyInjector(new DefaultReflector());

        $injector->registerImplementation(FooInterface::class, ClassA::class);
        $injector->newInstance(ClassD::class);
    }

    /**
     * @expectedException \RuntimeException
     */
    public function test_throws_exception_for_missing_interface()
    {
        $injector = new DefaultDependencyInjector(new DefaultReflector());

        $injector->newInstance(ClassC::class);
    }
}
