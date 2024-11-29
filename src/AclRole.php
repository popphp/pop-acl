<?php
/**
 * Pop PHP Framework (http://www.popphp.org/)
 *
 * @link       https://github.com/popphp/popphp-framework
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2025 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
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
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2025 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
 * @version    4.1.1
 */
class AclRole extends AbstractAcl
{

    /**
     * Role children
     * @var array
     */
    protected array $children = [];

    /**
     * Role parent
     * @var ?AclRole
     */
    protected ?AclRole $parent = null;

    /**
     * Add a child role
     *
     * @param  AclRole $child
     * @return AclRole
     */
    public function addChild(AclRole $child): AclRole
    {
        if ($child->getName() !== $this->getName()) {
            if (!in_array($child, $this->children, true)) {
                $this->children[] = $child;
            }
            if ($child->getParent() === null) {
                $child->setParent($this);
            }
        }
        return $this;
    }

    /**
     * Has child roles
     *
     * @return bool
     */
    public function hasChildren(): bool
    {
        return (count($this->children) > 0);
    }

    /**
     * Get child roles
     *
     * @return array
     */
    public function getChildren(): array
    {
        return $this->children;
    }

    /**
     * Set the parent role
     *
     * @param  AclRole $parent
     * @return AclRole
     */
    public function setParent(AclRole $parent): AclRole
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
     * @return AclRole|null
     */
    public function getParent(): AclRole|null
    {
        return $this->parent;
    }

    /**
     * See if the role has a parent
     *
     * @return bool
     */
    public function hasParent(): bool
    {
        return ($this->parent !== null);
    }

}
