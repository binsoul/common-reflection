<?php

namespace BinSoul\Common\Reflection;

/**
 * Provides a default implementation of the {@see Reflector} interface.
 */
class DefaultReflector implements Reflector
{
    /** Type is a class */
    const TYPE_CLASS = 1;
    /** Type is an interface */
    const TYPE_INTERFACE = 2;
    /** Type is a trait */
    const TYPE_TRAIT = 2;

    /** @var int counts how many times a reflection class was created*/
    protected $reflectionCount;

    /**
     * Returns how many times data was not found in the internal cache.
     *
     * @return int
     */
    public function getReflectionCount()
    {
        return $this->reflectionCount;
    }

    public function isInstantiable($type)
    {
        return $this->reflectType($type)['isInstantiable'];
    }

    public function isUserDefined($type)
    {
        return $this->reflectType($type)['isUserDefined'];
    }

    public function getParents($type)
    {
        return $this->reflectType($type)['parents'];
    }

    public function getInterfaces($type)
    {
        return $this->reflectType($type)['interfaces'];
    }

    public function resolveMethodParameters($type, $method, array $arguments)
    {
        $data = $this->reflectMethod($type, $method);

        return $this->resolveParameters($data['parameters'], $arguments);
    }

    public function resolveFunctionParameters($function, array $arguments)
    {
        $data = $this->reflectFunction($function);

        return $this->resolveParameters($data['parameters'], $arguments);
    }

    /**
     * Builds an array of resolved parameters.
     *
     * @param mixed[] $parameters
     * @param mixed[] $arguments
     *
     * @return ResolvedParameter[]
     */
    protected function resolveParameters($parameters, array $arguments)
    {
        $hasArguments = count($arguments) > 0;

        $result = [];
        foreach ($parameters as $index => $parameter) {
            $resolved = new ResolvedParameter();

            $resolved->type = $parameter['isSimple'] ? ResolvedParameter::TYPE_SIMPLE : ResolvedParameter::TYPE_OBJECT;
            $resolved->name = $parameter['name'];
            $resolved->value = null;
            $resolved->isOptional = $parameter['isOptional'];

            if ($hasArguments && array_key_exists($resolved->name, $arguments)) {
                $resolved->value = $arguments[$resolved->name];
                $resolved->isAvailable = true;
                $result[] = $resolved;

                continue;
            } elseif ($hasArguments && array_key_exists($index, $arguments)) {
                $resolved->value = $arguments[$index];
                $resolved->isAvailable = true;
                $result[] = $resolved;

                continue;
            }

            if (!$parameter['isSimple']) {
                $resolved->value = $parameter['value'];
                $resolved->isAvailable = false;
            } elseif (isset($parameter['default'])) {
                $resolved->value = $parameter['default'];
                $resolved->isAvailable = true;
            } else {
                $resolved->isAvailable = false;
            }

            $result[] = $resolved;
        }

        return $result;
    }

    /**
     * Returns an array of reflection data for the given type.
     *
     * @param string|object $type
     *
     * @return mixed[]
     */
    protected function reflectType($type)
    {
        $reflection = $this->buildReflectionClass($type);

        $data = [
            'type' => self::TYPE_CLASS,
            'isUserDefined' => $reflection->isUserDefined(),
            'isInstantiable' => $reflection->isInstantiable(),
            'parents' => [],
            'interfaces' => [],
        ];

        if ($reflection->isInterface()) {
            $data['type'] = self::TYPE_INTERFACE;
        } else {
            if ($reflection->isTrait()) {
                $data['type'] = self::TYPE_TRAIT;
            }
        }

        $parents = class_parents($type, true);
        if (is_array($parents)) {
            $data['parents'] = array_values($parents);
        }

        $interfaces = class_implements($type, true);
        if (is_array($interfaces)) {
            $data['interfaces'] = array_values($interfaces);
        }

        return $data;
    }

    /**
     * Returns an array of reflection data for the given method of the given type.
     *
     * @param string|object $type
     * @param string        $method
     *
     * @return mixed[]
     */
    protected function reflectMethod($type, $method)
    {
        $reflectionClass = $this->buildReflectionClass($type);

        if (!$reflectionClass->hasMethod($method)) {
            throw new \InvalidArgumentException(sprintf('The type "%s" has no method "%s".', $type, $method));
        }

        $reflection = $reflectionClass->getMethod($method);

        return [
            'isInvokable' => !$reflection->isAbstract() && !$reflection->isDestructor(),
            'isPublic' => $reflection->isPublic(),
            'parameters' => $this->reflectParameters($reflection->getParameters()),
        ];
    }

    /**
     * Returns an array of reflection data for the given function.
     *
     * @param string|\Closure $function
     *
     * @return mixed[]
     */
    protected function reflectFunction($function)
    {
        $reflection = $this->buildReflectionFunction($function);

        return [
            'isClosure' => $reflection->isClosure(),
            'parameters' => $this->reflectParameters($reflection->getParameters()),
        ];
    }

    /**
     * Returns an array of reflection data for the given parameters.
     *
     * @param \ReflectionParameter[] $parameters
     *
     * @return mixed[]
     */
    protected function reflectParameters(array $parameters)
    {
        $result = [];
        foreach ($parameters as $parameter) {
            $param = [
                'isSimple' => true,
                'isOptional' => $parameter->isOptional(),
                'name' => $parameter->getName(),
            ];

            $dependency = $parameter->getClass();
            if ($dependency !== null) {
                $param['isSimple'] = false;
                $param['value'] = $dependency->getName();
            }

            if ($parameter->isDefaultValueAvailable()) {
                $param['default'] = $parameter->getDefaultValue();
            }

            $result[] = $param;
        }

        return $result;
    }

    /**
     * Builds a reflection class for the given type.
     *
     * @param string $type
     *
     * @return \ReflectionClass
     */
    private function buildReflectionClass($type)
    {
        try {
            ++$this->reflectionCount;

            return new \ReflectionClass($type);
        } catch (\ReflectionException $e) {
            throw new \InvalidArgumentException(sprintf('The type "%s" does not exist.', $type), 0, $e);
        }
    }

    /**
     * Builds a reflection class for the given function.
     *
     * @param string $function
     *
     * @return \ReflectionFunction
     */
    private function buildReflectionFunction($function)
    {
        try {
            return new \ReflectionFunction($function);
        } catch (\ReflectionException $e) {
            throw new \InvalidArgumentException(sprintf('The function "%s" does not exist.', $function), 0, $e);
        }
    }
}
