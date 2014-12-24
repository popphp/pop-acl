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
 * ACL class
 *
 * @category   Pop
 * @package    Pop_Acl
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2014 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    2.0.0a
 */
class Acl
{

    /**
     * Array of roles
     * @var array
     */
    protected $roles = [];

    /**
     * Array of resources
     * @var array
     */
    protected $resources = [];

    /**
     * Array of allowed roles, resources and permissions
     * @var array
     */
    protected $allowed = [];

    /**
     * Array of denied roles, resources and permissions
     * @var array
     */
    protected $denied = [];

    /**
     * Constructor
     *
     * Instantiate the ACL object
     *
     * @param  \Pop\Acl\Role     $role
     * @param  \Pop\Acl\Resource $resource
     * @return Acl
     */
    public function __construct(Role $role = null, Resource $resource = null)
    {
        if (null !== $role) {
            $this->addRole($role);
        }

        if (null !== $resource) {
            $this->addResource($resource);
        }
    }

    /**
     * Get a role
     *
     * @param  string $role
     * @return Role
     */
    public function getRole($role)
    {
        return (isset($this->roles[$role])) ? $this->roles[$role] : null;
    }

    /**
     * Get roles
     *
     * @return array
     */
    public function getRoles()
    {
        return $this->roles;
    }

    /**
     * See if a role has been added
     *
     * @param  string $role
     * @return boolean
     */
    public function hasRole($role)
    {
        return (isset($this->roles[$role]));
    }

    /**
     * Add a role
     *
     * @param  Role $role
     * @return Acl
     */
    public function addRole(Role $role)
    {
        if (!isset($this->roles[$role->getName()])) {
            $this->roles[$role->getName()] = $role;

            // Traverse up if role has parents
            while ($role->hasParent()) {
                $role = $role->getParent();
                $this->roles[$role->getName()] = $role;
            }

            // Traverse down if the role has children
            if ($role->hasChildren()) {
                $this->traverseChildren($role->getChildren());
            }
        }
        return $this;
    }

    /**
     * Add roles
     *
     * @param  array $roles
     * @throws Exception
     * @return Acl
     */
    public function addRoles(array $roles)
    {
        foreach ($roles as $role) {
            $this->addRole($role);
        }

        return $this;
    }

    /**
     * Get a resource
     *
     * @param  string $resource
     * @return Resource
     */
    public function getResource($resource)
    {
        return (isset($this->resources[$resource])) ? $this->resources[$resource] : null;
    }

    /**
     * See if a resource has been added
     *
     * @param  string $resource
     * @return boolean
     */
    public function hasResource($resource)
    {
        return (isset($this->resources[$resource]));
    }

    /**
     * Add a resource
     *
     * @param \Pop\Acl\Resource $resource
     * @return Acl
     */
    public function addResource(Resource $resource)
    {
        $this->resources[$resource->getName()] = $resource;
        return $this;
    }

    /**
     * Get resources
     *
     * @return array
     */
    public function getResources()
    {
        return $this->resources;
    }

    /**
     * Add resources
     *
     * @param  array $resources
     * @throws Exception
     * @return Acl
     */
    public function addResources(array $resources)
    {
        foreach ($resources as $resource) {
            $this->addResource($resource);
        }

        return $this;
    }

    /**
     * Allow a user role permission to a resource or resources
     *
     * @param  mixed $role
     * @param  mixed $resource
     * @param  mixed $permission
     * @throws Exception
     * @return Acl
     */
    public function allow($role, $resource = null, $permission = null)
    {
        // Check if the role has been added
        if (!isset($this->roles[(string)$role])) {
            throw new Exception('Error: That role has not been added.');
        }

        if (!isset($this->allowed[(string)$role])) {
            $this->allowed[(string)$role] = [];
        }

        // Check if the resource(s) have been added
        if (null !== $resource) {
            if (!isset($this->resources[(string)$resource])) {
                $this->addResource(new Resource((string)$resource));
            }
            if (!isset($this->allowed[(string)$role][(string)$resource])) {
                $this->allowed[(string)$role][(string)$resource] = [];
            }
            if (null != $permission) {
                $this->allowed[(string)$role][(string)$resource][] = $permission;
            }
        }

        return $this;
    }

    /**
     * Remove an allow rule
     *
     * @param  mixed $role
     * @param  mixed $resource
     * @param  mixed $permission
     * @throws Exception
     * @return Acl
     */
    public function removeAllow($role, $resource = null, $permission = null)
    {
        // Check if the role has been added
        if (!isset($this->roles[(string)$role])) {
            throw new Exception('Error: That role has not been added.');
        }

        if (!isset($this->allowed[(string)$role])) {
            throw new Exception('Error: That role has no allow rules associated with it.');
        }

        // Check if the resource(s) have been added
        if (null !== $resource) {
            if (!isset($this->resources[(string)$resource])) {
                $this->addResource(new Resource((string)$resource));
            }
            if (isset($this->allowed[(string)$role][(string)$resource])) {
                if (null != $permission) {
                    if (in_array($permission, $this->allowed[(string)$role][(string)$resource])) {
                        $key = array_search($permission, $this->allowed[(string)$role][(string)$resource]);
                        unset($this->allowed[(string)$role][(string)$resource][$key]);
                    }
                } else {
                    unset($this->allowed[(string)$role][(string)$resource]);
                }
            }
        } else {
            unset($this->allowed[(string)$role]);
        }

        return $this;
    }

    /**
     * Deny a user role permission to a resource or resources
     *
     * @param  mixed $role
     * @param  mixed $resource
     * @param  mixed $permission
     * @throws Exception
     * @return Acl
     */
    public function deny($role, $resource = null, $permission = null)
    {
        // Check if the roles has been added
        if (!isset($this->roles[(string)$role])) {
            throw new Exception('Error: That role has not been added.');
        }

        if (!isset($this->denied[(string)$role])) {
            $this->denied[(string)$role] = [];
        }

        // Check if the resource(s) have been added
        if (null !== $resource) {
            if (!isset($this->resources[(string)$resource])) {
                $this->addResource(new Resource((string)$resource));
            }
            if (!isset($this->denied[(string)$role][(string)$resource])) {
                $this->denied[(string)$role][(string)$resource] = [];
            }
            if (null != $permission) {
                $this->denied[(string)$role][(string)$resource][] = $permission;
            }
        }

        return $this;
    }

    /**
     * Remove a deny rule
     *
     * @param  mixed $role
     * @param  mixed $resource
     * @param  mixed $permission
     * @throws Exception
     * @return Acl
     */
    public function removeDeny($role, $resource = null, $permission = null)
    {
        // Check if the roles has been added
        if (!isset($this->roles[(string)$role])) {
            throw new Exception('Error: That role has not been added.');
        }

        if (!isset($this->denied[(string)$role])) {
            throw new Exception('Error: That role has no allow rules associated with it.');
        }

        // Check if the resource(s) have been added
        if (null !== $resource) {
            if (!isset($this->resources[(string)$resource])) {
                $this->addResource(new Resource((string)$resource));
            }
            if (isset($this->denied[(string)$role][(string)$resource])) {
                if (null != $permission) {
                    if (in_array($permission, $this->denied[(string)$role][(string)$resource])) {
                        $key = array_search($permission, $this->denied[(string)$role][(string)$resource]);
                        unset($this->denied[(string)$role][(string)$resource][$key]);
                    }
                } else {
                    unset($this->denied[(string)$role][(string)$resource]);
                }
            }
        } else {
            unset($this->denied[(string)$role]);
        }

        return $this;
    }

    /**
     * Determine if the user is allowed
     *
     * @param  mixed $role
     * @param  mixed $resource
     * @param  mixed $permission
     * @throws Exception
     * @return boolean
     */
    public function isAllowed($role, $resource = null, $permission = null)
    {
        $result = false;

        // Check if the roles has been added
        if (!isset($this->roles[(string)$role])) {
            throw new Exception('Error: That role has not been added.');
        }

        $role = $this->roles[(string)$role];

        if ((null !== $resource) && !isset($this->resources[(string)$resource])) {
            $this->addResource(new Resource((string)$resource));
        }

        if (!$this->isDenied($role, $resource, $permission)) {
            $roleToCheck = $role;
            while (null !== $roleToCheck) {
                if (isset($this->allowed[(string)$roleToCheck])) {
                    // No explicit resources or permissions
                    if (count($this->allowed[(string)$roleToCheck]) == 0) {
                        $result = true;
                    // Resource set, but no explicit permissions
                    } else if ((null !== $resource) && isset($this->allowed[(string)$roleToCheck][(string)$resource]) &&
                        (count($this->allowed[(string)$roleToCheck][(string)$resource]) == 0)) {
                        $result = true;
                    // Else, has resource and permissions set
                    } else if ((null !== $resource) && (null !== $permission) &&
                        isset($this->allowed[(string)$roleToCheck][(string)$resource]) &&
                        (count($this->allowed[(string)$roleToCheck][(string)$resource]) > 0)) {
                        if (!is_array($permission)) {
                            $permission = [$permission];
                        }
                        $result = (count(array_intersect($permission, $this->allowed[(string)$roleToCheck][(string)$resource])) == count($permission));
                    }
                }
                $roleToCheck = $roleToCheck->getParent();
            }
        }

        return $result;
    }

    /**
     * Determine if the user is denied
     *
     * @param  mixed $role
     * @param  mixed $resource
     * @param  mixed $permission
     * @throws Exception
     * @return boolean
     */
    public function isDenied($role, $resource = null, $permission = null)
    {
        $result = false;

        // Check if the roles has been added
        if (!isset($this->roles[(string)$role])) {
            throw new Exception('Error: That role has not been added.');
        }

        $role = $this->roles[(string)$role];

        if ((null !== $resource) && !isset($this->resources[(string)$resource])) {
            $this->addResource(new Resource((string)$resource));
        }

        // Check if the user, resource and/or permission is denied
        $roleToCheck = $role;
        while (null !== $roleToCheck) {
            if (isset($this->denied[(string)$roleToCheck])) {
                if (count($this->denied[(string)$roleToCheck]) > 0) {
                    if ((null !== $resource) && array_key_exists((string)$resource, $this->denied[(string)$roleToCheck])) {
                        if (count($this->denied[(string)$roleToCheck][(string)$resource]) > 0) {
                            if (null !== $permission) {
                                if (!is_array($permission)) {
                                    $permission = [$permission];
                                }
                                foreach ($permission as $p) {
                                    if (in_array($p, $this->denied[(string)$roleToCheck][(string)$resource])) {
                                        $result = true;
                                    }
                                }
                            }
                        } else {
                            $result = true;
                        }
                    }
                } else {
                    $result = true;
                }
            }
            $roleToCheck = $roleToCheck->getParent();
        }

        return $result;
    }

    /**
     * Traverse child roles to add them to the ACL object
     *
     * @param  array $roles
     * @return void
     */
    protected function traverseChildren(array $roles)
    {
        foreach ($roles as $role) {
            $this->addRole($role);
            if ($role->hasChildren()) {
                $this->traverseChildren($role->getChildren());
            }
        }
    }

}
