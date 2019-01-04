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
 * Acl role class
 *
 * @category   Pop
 * @package    Pop\Acl
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2019 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    3.2.0
 */
class AclRole extends AbstractAcl
{

    /**
     * Role children
     * @var array
     */
    protected $children = [];

    /**
     * Role parent
     * @var AclRole
     */
    protected $parent = null;

    /**
     * Add a child role
     *
     * @param  AclRole $child
     * @return AclRole
     */
    public function addChild(AclRole $child)
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
     * @param  AclRole $parent
     * @return AclRole
     */
    public function setParent(AclRole $parent)
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
     * @return AclRole
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * See if the role has a parent
     *
     * @return boolean
     */
    public function hasParent()
    {
        return (null !== $this->parent);
    }

}
