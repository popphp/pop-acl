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
     * Role permissions
     * @var array
     */
    protected $permissions = [];

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
     * @param  array  $permissions
     * @return Role
     */
    public function __construct($name, array $permissions = null)
    {
        $this->setName($name);
        if (null !== $permissions) {
            $this->setPermissions($permissions);
        }
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
     * Get role permissions
     *
     * @param  array $permissions
     * @return Role
     */
    public function setPermissions(array $permissions)
    {
        foreach ($permissions as $permission) {
            $this->addPermission($permission);
        }
        return $this;
    }

    /**
     * Add a permission to the role
     *
     * @param  string $permission
     * @return Role
     */
    public function addPermission($permission)
    {
        if (!$this->hasPermission($permission)) {
            $this->permissions[] = $permission;
        }
        return $this;
    }

    /**
     * Get role permissions
     *
     * @return array
     */
    public function getPermissions()
    {
        return $this->permissions;
    }

    /**
     * Remove a permission from the role
     *
     * @param  string $permission
     * @return Role
     */
    public function removePermission($permission)
    {
        if (isset($this->permissions[$permission])) {
            unset($this->permissions[$permission]);
        }
        return $this;
    }

    /**
     * Check if a role has a permission
     *
     * @param  string $permission
     * @return boolean
     */
    public function hasPermission($permission)
    {
        return in_array($permission, $this->permissions);
    }

    /**
     * Add a child role
     *
     * @param  Role $role
     * @return Role
     */
    public function addChild(Role $role)
    {
        $this->children[] = $role;
        if ($role->getName() !== $this) {
            $role->setParent($this);
        }
        return $this;
    }

    /**
     * Set the inherited role
     *
     * @param  Role $parent
     * @return Role
     */
    public function inheritsFrom(Role $parent)
    {
        $this->setParent($parent);
        $this->parent->addChild($this);
        return $this;
    }

    /**
     * Set the parent role
     *
     * @param  Role $parent
     * @return Role
     */
    public function setParent(Role $parent)
    {
        $this->parent      = $parent;
        $permissions = $parent->getPermissions();
        foreach ($permissions as $permission) {
            $this->addPermission($permission);
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
