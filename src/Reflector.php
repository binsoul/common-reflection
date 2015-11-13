<?php

namespace BinSoul\Common\Reflection;

/**
 * Provides methods to introspect classes, interfaces and traits.
 */
interface Reflector
{
    /**
     * @param string $type
     *
     * @return bool
     */
    public function isInstantiable($type);

    /**
     * Indicates if the given type was defined by the user or if it is an PHP built-in type.
     *
     * @param string $type
     *
     * @return bool
     */
    public function isUserDefined($type);

    /**
     * Returns the list of parent classes of the given type.
     *
     * @param string $type
     *
     * @return string[]
     */
    public function getParents($type);

    /**
     * Returns the list of interfaces implemented by the given type or it's parent classes.
     *
     * @param string $type
     *
     * @return string[]
     */
    public function getInterfaces($type);

    /**
     * Builds an array of resolved parameters for the given method of the given type.
     *
     * @param string  $type
     * @param string  $method
     * @param mixed[] $arguments
     *
     * @return ResolvedParameter[]
     */
    public function resolveMethodParameters($type, $method, array $arguments);

    /**
     * Builds an array of resolved parameters for the function.
     *
     * @param string|\Closure $function
     * @param mixed[]         $arguments
     *
     * @return ResolvedParameter[]
     */
    public function resolveFunctionParameters($function, array $arguments);
}
