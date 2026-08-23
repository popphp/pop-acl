<?php
declare(strict_types=1);
/**
 * Pop PHP Framework (http://www.popphp.org/)
 *
 * @link       https://github.com/popphp/popphp-framework
 * @author     Nick Sagona, III <nick@popphp.org>
 * @copyright  Copyright (c) 2009-2027 NOLA Interactive, LLC.
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
 * @author     Nick Sagona, III <nick@popphp.org>
 * @copyright  Copyright (c) 2009-2027 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
 * @version    5.0.0
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
     * Remove a child role
     *
     * @param  AclRole $child
     * @return AclRole
     */
    public function removeChild(AclRole $child): AclRole
    {
        $key = array_search($child, $this->children, true);
        if ($key !== false) {
            unset($this->children[$key]);
            $this->children = array_values($this->children);
        }
        if ($child->getParent() === $this) {
            $child->clearParent();
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
     * Clear the parent role
     *
     * @return AclRole
     */
    public function clearParent(): AclRole
    {
        $this->parent = null;
        return $this;
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
