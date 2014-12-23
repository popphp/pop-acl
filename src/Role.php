<?php
/**
 * Pop PHP Framework (http://www.popphp.org/)
 *
 * @link       https://github.com/popphp/popphp
 * @category   Pop
 * @package    Pop_Acl
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2014 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 */

/**
 * @namespace
 */
namespace Pop\Acl;

/**
 * Acl role class
 *
 * @category   Pop
 * @package    Pop_Acl
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2014 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    2.0.0a
 */
class Role
{

    /**
     * Role name
     * @var string
     */
    protected $name = null;

    /**
     * Role children
     * @var array
     */
    protected $children = [];

    /**
     * Role parent
     * @var Role
     */
    protected $parent = null;

    /**
     * Constructor
     *
     * Instantiate the role object
     *
     * @param  string $name
     * @return Role
     */
    public function __construct($name)
    {
        $this->setName($name);
    }

    /**
     * Set the role name
     *
     * @param  string $name
     * @return Role
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Get the role name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Add a child role
     *
     * @param  Role $child
     * @return Role
     */
    public function addChild(Role $child)
    {
        if ($child->getName() !== $this->getName()) {
            if (!in_array($child, $this->children, true)) {
                $this->children[] = $child;
            }
            if (null === $child->getParent()) {
                $child->setParent($this);
            }
        }
        return $this;
    }

    /**
     * Has child roles
     *
     * @return boolean
     */
    public function hasChildren()
    {
        return (count($this->children) > 0);
    }

    /**
     * Get child roles
     *
     * @return array
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * Set the parent role
     *
     * @param  Role $parent
     * @return Role
     */
    public function setParent(Role $parent)
    {
        if ($parent->getName() !== $this->getName()) {
            $this->parent = $parent;
            $this->parent->addChild($this);
        }
        return $this;
    }

    /**
     * Get the role parent
     *
     * @return Role
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * See if the role has a parent
     *
     * @return Role
     */
    public function hasParent()
    {
        return (null !== $this->parent);
    }

    /**
     * Return the string value of the name of the role
     *
     * @return string
     */
    public function __toString()
    {
        return $this->name;
    }

}
