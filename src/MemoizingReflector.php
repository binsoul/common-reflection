<?php

namespace BinSoul\Reflection;

/**
 * Stores reflection data in an internal array which can be retrieved.
 */
class MemoizingReflector extends DefaultReflector
{
    /** @var mixed[] */
    private $data;

    /**
     * Constructs an instance of this class.
     *
     * @param mixed[] $data initial internal data
     */
    public function __construct(array $data = [])
    {
        $this->setData($data);
    }

    /**
     * Returns the internal data.
     *
     * @return mixed[]
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Sets the internal data.
     *
     * @param mixed[] $data
     */
    public function setData($data)
    {
        $this->data = $data;
        $this->reflectionCount = 0;
    }

    protected function reflectType($type)
    {
        $key = $type;
        if (is_object($type)) {
            $key = get_class($type);
        }

        if (isset($this->data[$key])) {
            return $this->data[$key];
        }

        $data = parent::reflectType($type);

        $this->data[$key] = $data;

        return $data;
    }

    protected function reflectMethod($type, $method)
    {
        $key = $type;
        if (is_object($type)) {
            $key = get_class($type);
        }

        if (isset($this->data[$key]['methods'][$method])) {
            return $this->data[$key]['methods'][$method];
        }

        $this->reflectType($type);
        $data = parent::reflectMethod($type, $method);

        if (!isset($this->data[$key]['methods'])) {
            $this->data[$key]['methods'] = [];
        }

        $this->data[$key]['methods'][$method] = $data;

        return $data;
    }
}
