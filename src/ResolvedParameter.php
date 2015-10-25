<?php

namespace BinSoul\Reflection;

/**
 * Represents an resolved parameter.
 */
class ResolvedParameter
{
    /** Parameter is a simple value */
    const TYPE_SIMPLE = 1;
    /** Parameter is an object or an interface */
    const TYPE_OBJECT = 2;

    /** @var int type of the parameter */
    public $type;
    /** @var string name of the parameter */
    public $name;
    /** @var mixed value of the parameter */
    public $value;
    /** @var bool indicates if the parameter is optional */
    public $isOptional;
    /** @var bool indicates if the a value could be resolved */
    public $isAvailable;
}
