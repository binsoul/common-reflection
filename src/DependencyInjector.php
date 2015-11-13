<?php

namespace BinSoul\Common\Reflection;

/**
 * Instantiates objects and provides their dependencies.
 */
interface DependencyInjector
{
    /**
     * Registers a object.
     *
     * @param object $object
     */
    public function registerObject($object);

    /**
     * Registers an implementation for the given interface.
     *
     * @param string $interface
     * @param string $class
     */
    public function registerImplementation($interface, $class);

    /**
     * Registers a factory function for the given type.
     *
     * @param string   $type
     * @param callable $factory
     */
    public function registerFactory($type, callable $factory);

    /**
     * Creates a new object on every call.
     *
     * Dependencies of the created object are treated as singletons.
     *
     * @param string  $type
     * @param mixed[] $arguments
     *
     * @return object
     */
    public function newInstance($type, array $arguments = []);

    /**
     * Returns an existing object or creates a new one.
     *
     * @param string  $type
     * @param mixed[] $arguments
     *
     * @return object
     */
    public function newSingleton($type, array $arguments = []);
}
