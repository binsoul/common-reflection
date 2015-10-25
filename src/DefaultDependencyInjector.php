<?php

namespace BinSoul\Reflection;

/**
 * Provides a default implementation of the {@see DependencyInjector} interface.
 */
class DefaultDependencyInjector implements DependencyInjector
{
    /** @var Reflector */
    private $reflector;
    /** @var object[] */
    private $objects = [];
    /** @var string[] */
    private $interfaces = [];
    /** @var callable[] */
    private $factories = [];

    /** @var bool[] */
    private $processing = [];

    /**
     * Constructs an instance of this class.
     *
     * @param Reflector  $reflector
     * @param array      $objects
     * @param string[]   $interfaces
     * @param callable[] $factories
     */
    public function __construct(
        Reflector $reflector,
        array $objects = [],
        array $interfaces = [],
        array $factories = []
    ) {
        $this->reflector = $reflector;
        $this->interfaces = $interfaces;
        $this->factories = $factories;

        foreach ($objects as $object) {
            $this->registerObject($object);
        }

        $this->objects[$this->buildKey(self::class)] = $this;
        $this->interfaces[DependencyInjector::class] = self::class;
    }

    public function registerObject($object)
    {
        $this->registerInstance($this->buildKey(get_class($object)), $object);
    }

    public function registerImplementation($interface, $class)
    {
        $this->interfaces[$interface] = $class;
    }

    public function registerFactory($type, callable $factory)
    {
        $this->factories[$type] = $factory;
    }

    public function newInstance($type, array $arguments = [])
    {
        return $this->buildInstance($type, $arguments, false);
    }

    public function newSingleton($type, array $arguments = [])
    {
        return $this->buildInstance($type, $arguments, true);
    }

    /**
     * Registers an instance.
     *
     * @param string $key
     * @param object $instance
     */
    private function registerInstance($key, $instance)
    {
        $this->objects[$key] = $instance;

        $class = get_class($instance);

        $parents = $this->reflector->getParents($class);
        foreach ($parents as $parent) {
            if (isset($this->objects[$parent])) {
                continue;
            }

            if ($this->reflector->isUserDefined($parent)) {
                $this->objects[$parent] = $instance;
            }
        }

        $interfaces = $this->reflector->getInterfaces($class);
        foreach ($interfaces as $interface) {
            if (isset($this->interfaces[$interface])) {
                continue;
            }

            if ($this->reflector->isUserDefined($interface)) {
                $this->interfaces[$interface] = $class;
            }
        }
    }

    /**
     * Builds an instance of the given type.
     *
     * @param string  $type
     * @param mixed[] $arguments
     * @param bool    $isSingleton
     *
     * @return object
     */
    private function buildInstance($type, array $arguments = [], $isSingleton = true)
    {
        $key = $this->buildKey($type, $arguments);
        if ($isSingleton && isset($this->objects[$key])) {
            return $this->objects[$type];
        }

        if (isset($this->factories[$type])) {
            $result = $this->factories[$type]($arguments);
            if ($isSingleton) {
                $this->registerInstance($key, $result);
            }

            return $result;
        }

        if (!$this->reflector->isInstantiable($type)) {
            throw new \RuntimeException(sprintf('"%s" is not instantiable.', $type));
        }

        if (isset($this->processing[$type])) {
            return;
        }

        $this->processing[$type] = true;

        $parameters = [];
        foreach ($this->reflector->resolveParameters($type, '__construct', $arguments) as $parameter) {
            $name = $parameter->name;
            $value = $parameter->value;

            if ($parameter->isAvailable) {
                $parameters[] = $value;

                continue;
            }

            if ($parameter->type == ResolvedParameter::TYPE_SIMPLE) {
                throw new \RuntimeException(
                    sprintf(
                        'Constructor parameter "%s" of type "%s" has no default value.',
                        $name,
                        $type
                    )
                );
            }

            $class = $value;
            if (isset($this->interfaces[$value])) {
                $class = $this->interfaces[$value];
            }

            $parameterKey = $this->buildKey($class);
            if (isset($this->objects[$parameterKey])) {
                $parameters[] = $this->objects[$parameterKey];

                continue;
            }

            try {
                $previousException = null;
                $instance = $this->buildInstance($class);
            } catch (\RuntimeException $e) {
                $previousException = $e;
                $instance = null;
            }

            if ($instance === null && !$parameter->isOptional) {
                if ($previousException !== null) {
                    throw $previousException;
                }

                throw new \RuntimeException(
                    sprintf(
                        'Circular dependency detected for constructor parameter "%s" of type "%s".',
                        $name,
                        $type
                    )
                );
            }

            $parameters[] = $instance;
        }

        $result = new $type(...$parameters);
        if ($isSingleton) {
            $this->registerInstance($key, $result);
        }

        unset($this->processing[$type]);

        return $result;
    }

    /**
     * Builds an unique key for the given type and arguments.
     *
     * @param string  $type
     * @param mixed[] $arguments
     *
     * @return string
     */
    private function buildKey($type, array $arguments = [])
    {
        $result = $type;
        if (count($arguments) > 0) {
            $result .= '_'.md5(serialize($arguments));
        }

        return $result;
    }
}
