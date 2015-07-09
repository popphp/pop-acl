<?php
/**
 * Pop PHP Framework (http://www.popphp.org/)
 *
 * @link       https://github.com/popphp/popphp
 * @category   Pop
 * @package    Pop_Acl
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2015 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 */

/**
 * @namespace
 */
namespace Pop\Acl\Role;

/**
 * Acl role class
 *
 * @category   Pop
 * @package    Pop_Acl
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2015 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    2.0.0
 */
class Role extends AbstractRole
{

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


}
