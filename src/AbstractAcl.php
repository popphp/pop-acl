<?php
/**
 * Pop PHP Framework (http://www.popphp.org/)
 *
 * @link       https://github.com/popphp/popphp-framework
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2019 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 */

/**
 * @namespace
 */
namespace Pop\Acl;

/**
 * Abstract ACL role class
 *
 * @category   Pop
 * @package    Pop\Acl
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2019 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    3.2.0
 */
abstract class AbstractAcl implements \ArrayAccess
{

    /**
     * Role name
     * @var string
     */
    protected $name = null;

    /**
     * Role data
     * @var array
     */
    protected $data = [];

    /**
     * Constructor
     *
     * Instantiate the acl role object
     *
     * @param  string $name
     * @param  array  $data
     */
    public function __construct($name, array $data = [])
    {
        $this->setName($name);
        $this->setData($data);
    }

    /**
     * Set the acl role name
     *
     * @param  string $name
     * @return AbstractAcl
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Set the acl role data
     *
     * @param  array $data
     * @return AbstractAcl
     */
    public function setData(array $data)
    {
        $this->data = $data;
        return $this;
    }

    /**
     * Get the acl role name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get the acl role data
     *
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Return the string value of the name of the acl role
     *
     * @return string
     */
    public function __toString()
    {
        return $this->name;
    }

    /**
     * Get method to return the value of data[$name].
     *
     * @param  string $name
     * @return mixed
     */
    public function __get($name)
    {
        return (isset($this->data[$name])) ? $this->data[$name] : null;
    }

    /**
     * Set method to set the property to the value of data[$name].
     *
     * @param  string $name
     * @param  mixed $value
     * @return mixed
     */
    public function __set($name, $value)
    {
        $this->data[$name] = $value;
        return $this;
    }

    /**
     * Return the isset value of data[$name].
     *
     * @param  string $name
     * @return boolean
     */
    public function __isset($name)
    {
        return isset($this->data[$name]);
    }

    /**
     * Unset data[$name].
     *
     * @param  string $name
     * @return void
     */
    public function __unset($name)
    {
        unset($this->data[$name]);
    }

    /**
     * ArrayAccess offsetGet
     *
     * @param  mixed $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->__get($offset);
    }

    /**
     * ArrayAccess offsetSet
     *
     * @param  mixed $offset
     * @param  mixed $value
     * @throws Exception
     * @return mixed
     */
    public function offsetSet($offset, $value)
    {
        return $this->__set($offset, $value);
    }

    /**
     * ArrayAccess offsetExists
     *
     * @param  mixed $offset
     * @return boolean
     */
    public function offsetExists($offset)
    {
        return $this->__isset($offset);
    }

    /**
     * ArrayAccess offsetUnset
     *
     * @param  mixed $offset
     * @throws Exception
     * @return void
     */
    public function offsetUnset($offset)
    {
        $this->__unset($offset);
    }

}