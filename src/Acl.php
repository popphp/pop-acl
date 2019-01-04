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

use Pop\Acl\Assertion\AssertionInterface;

/**
 * ACL class
 *
 * @category   Pop
 * @package    Pop\Acl
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2019 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    3.2.0
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
     * Array of assertions
     * @var array
     */
    protected $assertions = [
        'allowed' => [],
        'denied'  => []
    ];

    /**
     * Constructor
     *
     * Instantiate the ACL object
     */
    public function __construct()
    {
        $args = func_get_args();

        foreach ($args as $arg) {
            if (is_array($arg)) {
                foreach ($arg as $a) {
                    if ($a instanceof AclRole) {
                        $this->addRole($a);
                    } else if ($a instanceof AclResource) {
                        $this->addResource($a);
                    }
                }
            } else if ($arg instanceof AclRole) {
                $this->addRole($arg);
            } else if ($arg instanceof AclResource) {
                $this->addResource($arg);
            }
        }
    }

    /**
     * Get a role
     *
     * @param  string $role
     * @return AclRole
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
     * @param  AclRole $role
     * @return Acl
     */
    public function addRole(AclRole $role)
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
     * @return AclResource
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
     * @param AclResource $resource
     * @return Acl
     */
    public function addResource(AclResource $resource)
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
     * @param  mixed              $role
     * @param  mixed              $resource
     * @param  mixed              $permission
     * @param  AssertionInterface $assertion
     * @throws Exception
     * @return Acl
     */
    public function allow($role, $resource = null, $permission = null, AssertionInterface $assertion = null)
    {
        if ($this->verifyRole($role)) {
            $role = $this->roles[(string)$role];

            if (!isset($this->allowed[(string)$role])) {
                $this->allowed[(string)$role] = [];
            }

            if ((null !== $resource) && ($this->verifyResource($resource))) {
                $resource = $this->resources[(string)$resource];

                if (!isset($this->allowed[(string)$role][(string)$resource])) {
                    $this->allowed[(string)$role][(string)$resource] = [];
                }
                if (null !== $permission) {
                    $this->allowed[(string)$role][(string)$resource][] = $permission;
                }
            }

            // If an assertion has been passed
            if (null !== $assertion) {
                $this->createAssertion($assertion, 'allowed', $role, $resource, $permission);
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
    public function removeAllowRule($role, $resource = null, $permission = null)
    {
        if (($this->verifyRole($role)) && isset($this->allowed[(string)$role])) {
            // If only role passed
            if ((null === $resource) && (null === $permission)) {
                unset($this->allowed[(string)$role]);
            // If role & resource passed
            } else if ((null !== $resource) && (null === $permission) && ($this->verifyResource($resource)) &&
                isset($this->allowed[(string)$role][(string)$resource])) {
                unset($this->allowed[(string)$role][(string)$resource]);
            // If role, resource & permission passed
            } else {
                if (($this->verifyResource($resource)) && isset($this->allowed[(string)$role][(string)$resource]) &&
                    in_array($permission, $this->allowed[(string)$role][(string)$resource])) {
                    $key = array_search($permission, $this->allowed[(string)$role][(string)$resource]);
                    unset($this->allowed[(string)$role][(string)$resource][$key]);
                }
            }

            $this->deleteAssertion('allowed', $role, $resource, $permission);
        }

        return $this;
    }

    /**
     * Deny a user role permission to a resource or resources
     *
     * @param  mixed              $role
     * @param  mixed              $resource
     * @param  mixed              $permission
     * @param  AssertionInterface $assertion
     * @throws Exception
     * @return Acl
     */
    public function deny($role, $resource = null, $permission = null, AssertionInterface $assertion = null)
    {
        if ($this->verifyRole($role)) {
            $role = $this->roles[(string)$role];

            if (!isset($this->denied[(string)$role])) {
                $this->denied[(string)$role] = [];
            }

            if ((null !== $resource) && ($this->verifyResource($resource))) {
                $resource = $this->resources[(string)$resource];

                if (!isset($this->denied[(string)$role][(string)$resource])) {
                    $this->denied[(string)$role][(string)$resource] = [];
                }
                if (null !== $permission) {
                    $this->denied[(string)$role][(string)$resource][] = $permission;
                }
            }

            // If an assertion has been passed
            if (null !== $assertion) {
                $this->createAssertion($assertion, 'denied', $role, $resource, $permission);
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
    public function removeDenyRule($role, $resource = null, $permission = null)
    {
        if (($this->verifyRole($role)) && isset($this->denied[(string)$role])) {
            // If only role passed
            if ((null === $resource) && (null === $permission)) {
                unset($this->denied[(string)$role]);
            // If role & resource passed
            } else if ((null !== $resource) && (null === $permission) && ($this->verifyResource($resource)) &&
                isset($this->denied[(string)$role][(string)$resource])) {
                unset($this->denied[(string)$role][(string)$resource]);
            // If role, resource & permission passed
            } else {
                if (($this->verifyResource($resource)) && isset($this->denied[(string)$role][(string)$resource]) &&
                    in_array($permission, $this->denied[(string)$role][(string)$resource])) {
                    $key = array_search($permission, $this->denied[(string)$role][(string)$resource]);
                    unset($this->denied[(string)$role][(string)$resource][$key]);
                }
            }

            $this->deleteAssertion('denied', $role, $resource, $permission);
        }

        return $this;
    }

    /**
     * Determine if the user is allowed
     *
     * @param  mixed $role
     * @param  mixed $resource
     * @param  mixed $permission
     * @return boolean
     */
    public function isAllowed($role, $resource = null, $permission = null)
    {
        $result = false;

        if ($this->verifyRole($role)) {
            if (null !== $resource) {
                $this->verifyResource($resource);
            }

            // Check role
            if (!$this->isDenied($role, $resource, $permission)) {
                $roleToCheck = $this->roles[(string)$role];
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
                            $permissions = (!is_array($permission)) ? [$permission] : $permission;
                            $result      = (count(
                                array_intersect($permissions, $this->allowed[(string)$roleToCheck][(string)$resource])) == count($permissions)
                            );
                        }
                    }
                    $roleToCheck = $roleToCheck->getParent();
                }
            }
        }

        // Check for assertion
        if (($result) && ($this->hasAssertionKey('allowed', $role, $resource, $permission))) {
            $assertionKey      = $this->getAssertionKey('allowed', $role, $resource, $permission);
            $assertionRole     = $this->roles[(string)$role];
            $assertionResource = (null !== $resource) ?  $this->resources[(string)$resource] : null;
            $result            =
                $this->assertions['allowed'][$assertionKey]->assert($this, $assertionRole, $assertionResource, $permission);
        }

        return $result;
    }

    /**
     * Determine if a user that is assigned many roles is allowed
     * If one of the roles is allowed, then the user will be allowed (return true)
     *
     * @param  array $roles
     * @param  mixed $resource
     * @param  mixed $permission
     * @throws Exception
     * @return boolean
     */
    public function isAllowedMany(array $roles, $resource = null, $permission = null)
    {
        $result = false;

        foreach ($roles as $role) {
            if ($this->isAllowed($role, $resource, $permission)) {
                $result = true;
                break;
            }
        }

        return $result;
    }

    /**
     * Determine if a user that is assigned many roles is allowed
     * All of the roles must be allowed to allow the user (return true)
     *
     * @param  array $roles
     * @param  mixed $resource
     * @param  mixed $permission
     * @throws Exception
     * @return boolean
     */
    public function isAllowedManyStrict(array $roles, $resource = null, $permission = null)
    {
        $result = true;

        foreach ($roles as $role) {
            if (!$this->isAllowed($role, $resource, $permission)) {
                $result = false;
                break;
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

        if ($this->verifyRole($role)) {
            if (null !== $resource) {
                $this->verifyResource($resource);
            }

            // Check if the user, resource and/or permission is denied
            $roleToCheck = $this->roles[(string)$role];
            while (null !== $roleToCheck) {
                if (isset($this->denied[(string)$roleToCheck])) {
                    if (count($this->denied[(string)$roleToCheck]) > 0) {
                        if ((null !== $resource) && array_key_exists((string)$resource, $this->denied[(string)$roleToCheck])) {
                            if (count($this->denied[(string)$roleToCheck][(string)$resource]) > 0) {
                                if (null !== $permission) {
                                    $permissions = (!is_array($permission)) ? [$permission] : $permission;
                                    foreach ($permissions as $p) {
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
        }

        // Check for assertion
        if ($this->hasAssertionKey('denied', $role, $resource, $permission)) {
            $assertionKey      = $this->getAssertionKey('denied', $role, $resource, $permission);
            $assertionRole     = $this->roles[(string)$role];
            $assertionResource = (null !== $resource) ?  $this->resources[(string)$resource] : null;
            $result            =
                $this->assertions['denied'][$assertionKey]->assert($this, $assertionRole, $assertionResource, $permission);
        }

        return $result;
    }

    /**
     * Determine if a user that is assigned many roles is denied
     * If one of the roles is denied, then the user will be denied (return true)
     *
     * @param  array $roles
     * @param  mixed $resource
     * @param  mixed $permission
     * @throws Exception
     * @return boolean
     */
    public function isDeniedMany(array $roles, $resource = null, $permission = null)
    {
        $result = false;

        foreach ($roles as $role) {
            if ($this->isDenied($role, $resource, $permission)) {
                $result = true;
                break;
            }
        }

        return $result;
    }

    /**
     * Determine if a user that is assigned many roles is denied
     * All of the roles must be denied to deny the user (return true)
     *
     * @param  array $roles
     * @param  mixed $resource
     * @param  mixed $permission
     * @throws Exception
     * @return boolean
     */
    public function isDeniedManyStrict(array $roles, $resource = null, $permission = null)
    {
        $result = true;

        foreach ($roles as $role) {
            if (!$this->isDenied($role, $resource, $permission)) {
                $result = false;
                break;
            }
        }

        return $result;
    }

    /**
     * Verify role
     *
     * @param  mixed $role
     * @throws Exception
     * @return boolean
     */
    protected function verifyRole($role)
    {
        if (!is_string($role) && !($role instanceof AclRole)) {
            throw new \InvalidArgumentException('Error: The role must be a string or an instance of Role.');
        }
        if (!isset($this->roles[(string)$role])) {
            throw new Exception("Error: The role '" . (string)$role . "' has not been added.");
        }

        return true;
    }

    /**
     * Verify resource
     *
     * @param  mixed $resource
     * @throws Exception
     * @return boolean
     */
    protected function verifyResource($resource)
    {
        if (!is_string($resource) && !($resource instanceof AclResource)) {
            throw new \InvalidArgumentException('Error: The resource must be a string or an instance of Resource.');
        }
        if (!isset($this->resources[(string)$resource])) {
            throw new Exception("Error: The resource '" . (string)$resource . "' has not been added.");
        }

        return true;
    }

    /**
     * Create assertion
     *
     * @param  AssertionInterface $assertion
     * @param  string             $type
     * @param  mixed              $role
     * @param  mixed              $resource
     * @param  string             $permission
     * @throws \InvalidArgumentException
     * @return void
     */
    protected function createAssertion(AssertionInterface $assertion, $type, $role, $resource = null, $permission = null)
    {
        $key = $this->generateAssertionKey($role, $resource, $permission);

        if (($type != 'allowed') && ($type != 'denied')) {
            throw new \InvalidArgumentException("Error: The assertion type must be either 'allowed' or 'denied'.");
        }
        $this->assertions[$type][$key] = $assertion;
    }

    /**
     * Delete assertion
     *
     * @param  string $type
     * @param  mixed  $role
     * @param  mixed  $resource
     * @param  string $permission
     * @return void
     */
    protected function deleteAssertion($type, $role, $resource = null, $permission = null)
    {
        $key = $this->generateAssertionKey($role, $resource, $permission);

        if (isset($this->assertions[$type][$key])) {
            unset($this->assertions[$type][$key]);
        }
    }

    /**
     * Has assertion key
     *
     * @param  string $type
     * @param  mixed  $role
     * @param  mixed  $resource
     * @param  string $permission
     * @throws \InvalidArgumentException
     * @return boolean
     */
    protected function hasAssertionKey($type, $role, $resource = null, $permission = null)
    {
        $key = $this->generateAssertionKey($role, $resource, $permission);

        if (($type != 'allowed') && ($type != 'denied')) {
            throw new \InvalidArgumentException("Error: The assertion type must be either 'allowed' or 'denied'.");
        }

        return (isset($this->assertions[$type][$key]));
    }

    /**
     * Get assertion key
     *
     * @param  string $type
     * @param  mixed  $role
     * @param  mixed  $resource
     * @param  string $permission
     * @throws \InvalidArgumentException
     * @return string
     */
    protected function getAssertionKey($type, $role, $resource = null, $permission = null)
    {
        $key = $this->generateAssertionKey($role, $resource, $permission);

        if (($type != 'allowed') && ($type != 'denied')) {
            throw new \InvalidArgumentException("Error: The assertion type must be either 'allowed' or 'denied'.");
        }

        return (isset($this->assertions[$type][$key])) ? $key : null;
    }

    /**
     * Generate assertion key
     *
     * @param  mixed  $role
     * @param  mixed  $resource
     * @param  string $permission
     * @return string
     */
    protected function generateAssertionKey($role, $resource = null, $permission = null)
    {
        $key = (string)$role;

        if (null !== $resource) {
            $key .= '-' . (string)$resource;
        }
        if (null !== $permission) {
            $key .= '-' . (string)$permission;
        }

        return $key;
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
