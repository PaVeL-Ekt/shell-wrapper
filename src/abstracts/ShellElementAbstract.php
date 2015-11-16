<?php

namespace PavelEkt\Wrappers\Shell\Abstracts;

abstract class ShellElementAbstract
{
    /**
     * @var mixed[] $_params Component params.
     */
    protected $_params = [];

    /**
     * @var \ReflectionClass $_reflection Component reflection.
     */
    protected $_reflection;

    /**
     * Component constructor.
     * @param mixed[] $params Initialize component params.
     * @return ShellElementAbstract
     */
    public function __construct($params)
    {
        if (is_array($params)) {
            $this->_params = $params;
        }
        $this->_reflection = new \ReflectionClass($this);
        return $this;
    }

    /**
     * Getter
     * @param string $name AttributeName
     * @return mixed
     */
    public function __get($name)
    {
        if ($this->_reflection->hasMethod('get' . $name)) {
            /** @var \ReflectionMethod $method */
            $method = $this->_reflection->getMethod('get' . $name);
            $method->invoke($this);
        }
    }

    /**
     * Setter
     * @param string $name AttributeName
     * @param mixed $value AttributeValue
     */
    public function __set($name, $value)
    {

    }
}
