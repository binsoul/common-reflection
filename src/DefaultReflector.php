<?php

namespace BinSoul\Reflection;

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

    /**
     * Builds an array of resolved parameters.
     *
     * @param string  $type
     * @param string  $method
     * @param mixed[] $arguments
     *
     * @return ResolvedParameter[]
     */
    public function resolveParameters($type, $method, array $arguments)
    {
        $data = $this->reflectMethod($type, $method);
        $parameters = $data['parameters'];
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
     * @param string|object $type
     * @param string        $method
     *
     * @return mixed[]
     */
    protected function reflectMethod($type, $method)
    {
        $reflection = $this->buildReflectionClass($type);

        if (!$reflection->hasMethod($method)) {
            throw new \InvalidArgumentException(sprintf('The type "%s" has no method "%s".', $type, $method));
        }

        $methodReflection = $reflection->getMethod($method);
        $data = [
            'isInvokable' => !$methodReflection->isAbstract() && !$methodReflection->isDestructor(),
            'isPublic' => $methodReflection->isPublic(),
            'parameters' => [],
        ];

        foreach ($methodReflection->getParameters() as $index => $parameter) {
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

            $data['parameters'][] = $param;
        }

        return $data;
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
}
