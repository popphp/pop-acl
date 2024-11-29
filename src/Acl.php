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

use Pop\Acl\Assertion\AssertionInterface;
use InvalidArgumentException;

/**
 * ACL class
 *
 * @category   Pop
 * @package    Pop\Acl
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2025 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
 * @version    4.1.1
 */
class Acl
{

    /**
     * Array of roles
     * @var array
     */
    protected array $roles = [];

    /**
     * Array of resources
     * @var array
     */
    protected array $resources = [];

    /**
     * Array of allowed roles, resources and permissions
     * @var array
     */
    protected array $allowed = [];

    /**
     * Array of denied roles, resources and permissions
     * @var array
     */
    protected array $denied = [];

    /**
     * Array of assertions
     * @var array
     */
    protected array $assertions = [
        'allowed' => [],
        'denied'  => []
    ];

    /**
     * Array of policies
     * @var array
     */
    protected array $policies = [];

    /**
     * Strict flag
     * @var bool
     */
    protected bool $strict = false;

    /**
     * Multi strict flag
     * @var bool
     */
    protected bool $multiStrict = false;

    /**
     * Parent strict flag
     * @var bool
     */
    protected bool $parentStrict = false;

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
     * @return AclRole|null
     */
    public function getRole(string $role): AclRole|null
    {
        return $this->roles[$role] ?? null;
    }

    /**
     * Get roles
     *
     * @return array
     */
    public function getRoles(): array
    {
        return $this->roles;
    }

    /**
     * See if a role has been added
     *
     * @param  string $role
     * @return bool
     */
    public function hasRole(string $role): bool
    {
        return (isset($this->roles[$role]));
    }

    /**
     * Add a role
     *
     * @param  AclRole $role
     * @return Acl
     */
    public function addRole(AclRole $role): Acl
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
     * @return Acl
     */
    public function addRoles(array $roles): Acl
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
     * @return AclResource|null
     */
    public function getResource(string $resource): AclResource|null
    {
        return $this->resources[$resource] ?? null;
    }

    /**
     * See if a resource has been added
     *
     * @param  string $resource
     * @return bool
     */
    public function hasResource(string $resource): bool
    {
        return (isset($this->resources[$resource]));
    }

    /**
     * Add a resource
     *
     * @param AclResource $resource
     * @return Acl
     */
    public function addResource(AclResource $resource): Acl
    {
        $this->resources[$resource->getName()] = $resource;
        return $this;
    }

    /**
     * Get resources
     *
     * @return array
     */
    public function getResources(): array
    {
        return $this->resources;
    }

    /**
     * Add resources
     *
     * @param  array $resources
     * @return Acl
     */
    public function addResources(array $resources): Acl
    {
        foreach ($resources as $resource) {
            $this->addResource($resource);
        }

        return $this;
    }

    /**
     * Set strict
     *
     * @param  bool      $strict
     * @param  bool|null $multiStrict
     * @return Acl
     */
    public function setStrict(bool $strict = true, bool|null $multiStrict = null): Acl
    {
        $this->strict = $strict;
        if ($multiStrict !== null) {
            $this->multiStrict = $multiStrict;
        }
        return $this;
    }

    /**
     * See if ACL object is set to strict
     *
     * @return bool
     */
    public function isStrict(): bool
    {
        return $this->strict;
    }

    /**
     * Set multi strict
     *
     * @param  bool $multiStrict
     * @return Acl
     */
    public function setMultiStrict(bool $multiStrict = true): Acl
    {
        $this->multiStrict = $multiStrict;
        return $this;
    }

    /**
     * See if ACL object is set to multi strict
     *
     * @return bool
     */
    public function isMultiStrict(): bool
    {
        return $this->multiStrict;
    }

    /**
     * Set parent strict
     *
     * @param  bool $parentStrict
     * @return Acl
     */
    public function setParentStrict(bool $parentStrict = true): Acl
    {
        $this->parentStrict = $parentStrict;
        return $this;
    }

    /**
     * See if ACL object is set to parent strict
     *
     * @return bool
     */
    public function isParentStrict(): bool
    {
        return $this->parentStrict;
    }

    /**
     * Allow a role permission to a resource or resources
     *
     * @param  mixed               $role
     * @param  mixed               $resource
     * @param  mixed               $permission
     * @param  ?AssertionInterface $assertion
     * @throws Exception
     * @return Acl
     */
    public function allow(
        mixed $role, mixed $resource = null, mixed $permission = null, ?AssertionInterface $assertion = null
    ): Acl
    {
        if ($this->verifyRole($role)) {
            $role = $this->roles[(string)$role];

            if (!isset($this->allowed[(string)$role])) {
                $this->allowed[(string)$role] = [];
            }

            if (($resource !== null) && ($this->verifyResource($resource))) {
                $resource = $this->resources[(string)$resource];

                if (!isset($this->allowed[(string)$role][(string)$resource])) {
                    $this->allowed[(string)$role][(string)$resource] = [];
                }
                if ($permission !== null) {
                    if (!is_array($permission)) {
                        $permission = [$permission];
                    }
                    foreach ($permission as $perm) {
                        $this->allowed[(string)$role][(string)$resource][] = $perm;
                        if ($assertion !== null) {
                            $this->createAssertion($assertion, 'allowed', $role, $resource, $perm);
                        }
                    }
                } else {
                    if ($assertion !== null) {
                        $this->createAssertion($assertion, 'allowed', $role, $resource);
                    }
                }
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
    public function removeAllowRule(mixed $role, mixed $resource = null, mixed $permission = null): Acl
    {
        if (($this->verifyRole($role)) && isset($this->allowed[(string)$role])) {
            // If only role passed
            if (($resource === null) && ($permission === null)) {
                unset($this->allowed[(string)$role]);
                $this->deleteAssertion('allowed', $role);
            // If role & resource passed
            } else if (($resource !== null) && ($permission === null) && ($this->verifyResource($resource)) &&
                isset($this->allowed[(string)$role][(string)$resource])) {
                unset($this->allowed[(string)$role][(string)$resource]);
                $this->deleteAssertion('allowed', $role, $resource);
            // If role, resource & permission passed
            } else {
                if (!is_array($permission)) {
                    $permission = [$permission];
                }
                foreach ($permission as $perm) {
                    if (($this->verifyResource($resource)) && isset($this->allowed[(string)$role][(string)$resource]) &&
                        in_array($perm, $this->allowed[(string)$role][(string)$resource])) {
                        $key = array_search($perm, $this->allowed[(string)$role][(string)$resource]);
                        unset($this->allowed[(string)$role][(string)$resource][$key]);
                    }
                    $this->deleteAssertion('allowed', $role, $resource, $perm);
                }
            }
        }

        return $this;
    }

    /**
     * Deny a role permission to a resource or resources
     *
     * @param  mixed               $role
     * @param  mixed               $resource
     * @param  mixed               $permission
     * @param  ?AssertionInterface $assertion
     * @throws Exception
     * @return Acl
     */
    public function deny(
        mixed $role, mixed $resource = null, mixed $permission = null, ?AssertionInterface $assertion = null
    ): Acl
    {
        if ($this->verifyRole($role)) {
            $role = $this->roles[(string)$role];

            if (!isset($this->denied[(string)$role])) {
                $this->denied[(string)$role] = [];
            }

            if (($resource !== null) && ($this->verifyResource($resource))) {
                $resource = $this->resources[(string)$resource];

                if (!isset($this->denied[(string)$role][(string)$resource])) {
                    $this->denied[(string)$role][(string)$resource] = [];
                }
                if ($permission !== null) {
                    if (!is_array($permission)) {
                        $permission = [$permission];
                    }
                    foreach ($permission as $perm) {
                        $this->denied[(string)$role][(string)$resource][] = $perm;
                        if ($assertion !== null) {
                            $this->createAssertion($assertion, 'denied', $role, $resource, $perm);
                        }
                    }
                } else {
                    if ($assertion !== null) {
                        $this->createAssertion($assertion, 'denied', $role, $resource);
                    }
                }
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
    public function removeDenyRule(mixed $role, mixed $resource = null, mixed $permission = null): Acl
    {
        if (($this->verifyRole($role)) && isset($this->denied[(string)$role])) {
            // If only role passed
            if (($resource === null) && ($permission === null)) {
                unset($this->denied[(string)$role]);
                $this->deleteAssertion('denied', $role);
            // If role & resource passed
            } else if (($resource !== null) && ($permission === null) && ($this->verifyResource($resource)) &&
                isset($this->denied[(string)$role][(string)$resource])) {
                unset($this->denied[(string)$role][(string)$resource]);
                $this->deleteAssertion('denied', $role, $resource);
            // If role, resource & permission passed
            } else {
                if (!is_array($permission)) {
                    $permission = [$permission];
                }
                foreach ($permission as $perm) {
                    if (($this->verifyResource($resource)) && isset($this->denied[(string)$role][(string)$resource]) &&
                        in_array($perm, $this->denied[(string)$role][(string)$resource])) {
                        $key = array_search($perm, $this->denied[(string)$role][(string)$resource]);
                        unset($this->denied[(string)$role][(string)$resource][$key]);
                    }
                    $this->deleteAssertion('denied', $role, $resource, $perm);
                }
            }
        }

        return $this;
    }

    /**
     * Determine if the role is allowed
     *
     * @param  mixed $role
     * @param  mixed $resource
     * @param  mixed $permission
     * @throws Exception
     * @return bool
     */
    public function isAllowed(mixed $role, mixed $resource = null, mixed $permission = null): bool
    {
        $result   = false;
        $isParent = false;

        if ($this->verifyRole($role)) {
            if ($resource !== null) {
                $this->verifyResource($resource);
            }

            // If is not denied
            if (!$this->isDenied($role, $resource, $permission)) {
                // If not strict, pass
                if ((!$this->strict) && (!$this->multiStrict)) {
                    $result = true;
                // If strict, check for explicit allow rule
                } else {
                    $roleToCheck = $this->roles[(string)$role];
                    while ($roleToCheck !== null) {
                        if (isset($this->allowed[(string)$roleToCheck])) {
                            // No explicit resources or permissions
                            if (count($this->allowed[(string)$roleToCheck]) == 0) {
                                $result = true;
                            // Resource set, but no explicit permissions
                            } else if (($resource !== null) && isset($this->allowed[(string)$roleToCheck][(string)$resource]) &&
                                (count($this->allowed[(string)$roleToCheck][(string)$resource]) == 0)) {
                                $result = true;
                            // Else, has resource and permissions set
                            } else if (($resource !== null) && ($permission !== null) &&
                                isset($this->allowed[(string)$roleToCheck][(string)$resource]) &&
                                (count($this->allowed[(string)$roleToCheck][(string)$resource]) > 0)) {
                                $permissionsToCheck = (!is_array($permission)) ? [$permission] : $permission;
                                $allowedPermissions = $this->allowed[(string)$roleToCheck][(string)$resource];
                                $permissions        = array_intersect($permissionsToCheck, $allowedPermissions);

                                $result = ((($isParent) && (!$this->parentStrict)) ||
                                    (count($permissions) == count($permissionsToCheck)));
                            }
                        }

                        // Traverse up through the parent roles
                        $roleToCheck = $roleToCheck->getParent();
                        $isParent    = true;
                    }
                }
            }
        }

        // Check for assertions
        if (($result) && ($this->hasAssertionKey('allowed', $role, $resource, $permission))) {
            $assertionKey      = $this->getAssertionKey('allowed', $role, $resource, $permission);
            $assertionRole     = $this->roles[(string)$role];
            $assertionResource = ($resource !== null) ?  $this->resources[(string)$resource] : null;
            $result            =
                $this->assertions['allowed'][$assertionKey]->assert($this, $assertionRole, $assertionResource, $permission);
        }

        // Check for policies
        if ($this->hasPolicies()) {
            $result = $this->evaluatePolicies($role, $resource, $permission);
        }

        return $result;
    }

    /**
     * Determine if multiple roles are allowed
     *
     * @param  array $roles
     * @param  mixed $resource
     * @param  mixed $permission
     * @throws Exception
     * @return bool
     */
    public function isAllowedMulti(array $roles, mixed $resource = null, mixed $permission = null): bool
    {
        // If strict, all roles must be allowed
        if ($this->multiStrict) {
            $result = true;
            foreach ($roles as $role) {
                if (!$this->isAllowed($role, $resource, $permission)) {
                    $result = false;
                    break;
                }
            }
        // Else, evaluate loosely
        } else {
            // Check for any explicitly set denied rules for any of the roles
            foreach ($roles as $role) {
                if ($this->isDenied($role, $resource, $permission)) {
                    return false;
                }
            }

            // Else, evaluate if any of the roles are allowed
            $result = false;
            foreach ($roles as $role) {
                if ($this->isAllowed($role, $resource, $permission)) {
                    $result = true;
                    break;
                }
            }
        }

        return $result;
    }

    /**
     * Determine if multiple roles are allowed using the strict parameter
     * All of the roles must be allowed to return true, otherwise it will return false
     *
     * @param  array $roles
     * @param  mixed $resource
     * @param  mixed $permission
     * @throws Exception
     * @return bool
     */
    public function isAllowedMultiStrict(array $roles, mixed $resource = null, mixed $permission = null): bool
    {
        $this->multiStrict = true;
        return $this->isAllowedMulti($roles, $resource, $permission);
    }

    /**
     * Determine if a role is denied
     *
     * @param  mixed $role
     * @param  mixed $resource
     * @param  mixed $permission
     * @throws Exception
     * @return bool
     */
    public function isDenied(mixed $role, mixed $resource = null, mixed $permission = null): bool
    {
        $result = false;

        if ($this->verifyRole($role)) {
            if ($resource !== null) {
                $this->verifyResource($resource);
            }

            // Check if the user, resource and/or permission is denied
            $roleToCheck = $this->roles[(string)$role];
            while ($roleToCheck !== null) {
                if (isset($this->denied[(string)$roleToCheck])) {
                    if (count($this->denied[(string)$roleToCheck]) > 0) {
                        if (($resource !== null) && array_key_exists((string)$resource, $this->denied[(string)$roleToCheck])) {
                            if (count($this->denied[(string)$roleToCheck][(string)$resource]) > 0) {
                                if ($permission !== null) {
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

        // Check for assertions
        if ($this->hasAssertionKey('denied', $role, $resource, $permission)) {
            $assertionKey      = $this->getAssertionKey('denied', $role, $resource, $permission);
            $assertionRole     = $this->roles[(string)$role];
            $assertionResource = ($resource !== null) ?  $this->resources[(string)$resource] : null;
            $result            =
                $this->assertions['denied'][$assertionKey]->assert($this, $assertionRole, $assertionResource, $permission);
        }

        // Check for policies
        if ($this->hasPolicies()) {
            $result = (!$this->evaluatePolicies($role, $resource, $permission));
        }

        return $result;
    }

    /**
     * Determine if multiple roles are denied
     *
     * @param  array $roles
     * @param  mixed $resource
     * @param  mixed $permission
     * @throws Exception
     * @return bool
     */
    public function isDeniedMulti(array $roles, mixed $resource = null, mixed $permission = null): bool
    {
        // If strict, all roles must be denied
        if ($this->multiStrict) {
            $result = true;
            foreach ($roles as $role) {
                if (!$this->isDenied($role, $resource, $permission)) {
                    $result = false;
                    break;
                }
            }
        // Else, evaluate loosely
        } else {
            // Else, evaluate if any of the roles are denied
            $result = false;
            foreach ($roles as $role) {
                if ($this->isDenied($role, $resource, $permission)) {
                    $result = true;
                    break;
                }
            }
        }

        return $result;
    }

    /**
     * Determine if multiple roles are denied using the strict parameter
     *  All of the roles must be denied to return true, otherwise it will return false
     *
     * @param  array $roles
     * @param  mixed $resource
     * @param  mixed $permission
     * @return bool
     *@throws Exception
     */
    public function isDeniedMultiStrict(array $roles, mixed $resource = null, mixed $permission = null): bool
    {
        $this->multiStrict = true;
        return $this->isDeniedMulti($roles, $resource, $permission);
    }

    /**
     * Create assertion
     *
     * @param  AssertionInterface $assertion
     * @param  string             $type
     * @param  mixed              $role
     * @param  mixed              $resource
     * @param  ?string            $permission
     * @throws InvalidArgumentException
     * @return void
     */
    public function createAssertion(
        AssertionInterface $assertion, string $type, mixed $role, mixed $resource = null, ?string $permission = null
    ): void
    {
        $key = $this->generateAssertionKey($role, $resource, $permission);

        if (($type != 'allowed') && ($type != 'denied')) {
            throw new InvalidArgumentException("Error: The assertion type must be either 'allowed' or 'denied'.");
        }
        $this->assertions[$type][$key] = $assertion;
    }

    /**
     * Delete assertion
     *
     * @param  string  $type
     * @param  mixed   $role
     * @param  mixed   $resource
     * @param  ?string $permission
     * @return void
     */
    public function deleteAssertion(string $type, mixed $role, mixed $resource = null, ?string $permission = null): void
    {
        $key = $this->generateAssertionKey($role, $resource, $permission);

        if (isset($this->assertions[$type][$key])) {
            unset($this->assertions[$type][$key]);
        }
    }

    /**
     * Has assertion key
     *
     * @param  string  $type
     * @param  mixed   $role
     * @param  mixed   $resource
     * @param  ?string $permission
     * @throws InvalidArgumentException
     * @return bool
     */
    public function hasAssertionKey(string $type, mixed $role, mixed $resource = null, ?string $permission = null): bool
    {
        $key = $this->generateAssertionKey($role, $resource, $permission);

        if (($type != 'allowed') && ($type != 'denied')) {
            throw new InvalidArgumentException("Error: The assertion type must be either 'allowed' or 'denied'.");
        }

        return (isset($this->assertions[$type][$key]));
    }

    /**
     * Get assertion key
     *
     * @param  string  $type
     * @param  mixed   $role
     * @param  mixed   $resource
     * @param  ?string $permission
     * @throws InvalidArgumentException
     * @return string|null
     */
    public function getAssertionKey(string $type, mixed $role, mixed $resource = null, ?string $permission = null): string|null
    {
        $key = $this->generateAssertionKey($role, $resource, $permission);

        if (($type != 'allowed') && ($type != 'denied')) {
            throw new InvalidArgumentException("Error: The assertion type must be either 'allowed' or 'denied'.");
        }

        return (isset($this->assertions[$type][$key])) ? $key : null;
    }

    /**
     * Add policy
     *
     * @param  string $method
     * @param  mixed  $role
     * @param  mixed  $resource
     * @return Acl
     */
    public function addPolicy(string $method, mixed $role, mixed $resource = null): Acl
    {
        $this->policies[] = [
            'method'   => $method,
            'role'     => $role,
            'resource' => $resource
        ];

        return $this;
    }

    /**
     * Has policies
     *
     * @return bool
     */
    public function hasPolicies(): bool
    {
        return (count($this->policies) > 0);
    }

    /**
     * Evaluate policies
     *
     * @param  mixed $role
     * @param  mixed $resource
     * @param  mixed $permission
     * @throws Exception
     * @return bool|null
     */
    public function evaluatePolicies(mixed $role = null, mixed $resource = null, mixed $permission = null): bool|null
    {
        $result = null;

        if (($role === null) && ($resource === null) && ($permission === null)) {
            foreach ($this->policies as $policy) {
                $result = $this->evaluatePolicy($policy['method'], $policy['role'], $policy['resource']);
                if ($result === false) {
                    return false;
                }
            }
        } else {
            $policyRole     = null;
            $policyResource = null;
            $policyMethod   = ($permission !== null) ? $permission : null;

            if ($role !== null) {
                $this->verifyRole($role);
                $policyRole = ($role instanceof AclRole) ? $role->getName() : $role;
            }
            if ($resource !== null) {
                $this->verifyResource($resource);
                $policyResource = ($resource instanceof AclResource) ? $resource->getName() : $resource;
            }

            foreach ($this->policies as $policy) {
                if ((($policyRole === null) || ($policyRole == $policy['role'])) &&
                    (($policyResource === null) || ($policyResource == $policy['resource'])) &&
                    (($policyMethod === null) || ($policyMethod == $policy['method']))) {
                    $result = $this->evaluatePolicy($policy['method'], $policy['role'], $policy['resource']);
                    if ($result === false) {
                        return false;
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Evaluate policy
     *
     * @param  string $method
     * @param  mixed  $role
     * @param  mixed  $resource
     * @throws Exception
     * @return bool|null
     */
    public function evaluatePolicy(string $method, mixed $role, mixed $resource = null): bool|null
    {
        if (is_string($role) && ($this->verifyRole($role))) {
            $role = $this->roles[(string)$role];
        }

        if (!in_array('Pop\Acl\Policy\PolicyTrait', class_uses($role))) {
            throw new Exception('Error: The role must use Pop\Acl\Policy\PolicyTrait.');
        }

        if ($resource !== null) {
            $this->verifyResource($resource);
            $resource = $this->resources[(string)$resource];
        }

        return $role->can($method, $resource);
    }

    /**
     * Verify role
     *
     * @param  mixed $role
     * @throws Exception
     * @return bool
     */
    protected function verifyRole(mixed $role): bool
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
     * @return bool
     */
    protected function verifyResource(mixed $resource): bool
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
     * Generate assertion key
     *
     * @param  mixed   $role
     * @param  mixed   $resource
     * @param  ?string $permission
     * @return string
     */
    protected function generateAssertionKey(mixed $role, mixed $resource = null, ?string $permission = null): string
    {
        $key = (string)$role;

        if ($resource !== null) {
            $key .= '-' . (string)$resource;
        }
        if ($permission !== null) {
            $key .= '-' . (string)$permission;
        }

        return $key;
    }

    /**
     * Traverse child roles to add them to the ACL object
     *
     * @param  array $childRoles
     * @return void
     */
    protected function traverseChildren(array $childRoles): void
    {
        foreach ($childRoles as $childRole) {
            $this->addRole($childRole);
            if ($childRole->hasChildren()) {
                $this->traverseChildren($childRole->getChildren());
            }
        }
    }

}
