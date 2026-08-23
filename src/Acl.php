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

use Pop\Acl\Assertion\AssertionInterface;
use InvalidArgumentException;

/**
 * ACL class
 *
 * @category   Pop
 * @package    Pop\Acl
 * @author     Nick Sagona, III <nick@popphp.org>
 * @copyright  Copyright (c) 2009-2027 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
 * @version    5.0.0
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
     * Has roles
     *
     * @return bool
     */
    public function hasRoles(): bool
    {
        return !empty($this->roles);
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
     * Remove a role
     *
     * Children are reparented onto the removed role's own parent (or become
     * root roles if it had none). All allow/deny rules, assertions and
     * policies referencing this role are purged.
     *
     * @param  mixed $role
     * @throws Exception
     * @return Acl
     */
    public function removeRole(mixed $role): Acl
    {
        $this->verifyRole($role);

        $roleObj  = $this->roles[(string)$role];
        $roleName = (string)$roleObj;
        $parent   = $roleObj->getParent();

        foreach ($roleObj->getChildren() as $child) {
            if ($parent !== null) {
                $child->setParent($parent);
            } else {
                $child->clearParent();
            }
        }

        if ($parent !== null) {
            $parent->removeChild($roleObj);
        }

        foreach (['allowed', 'denied'] as $type) {
            if (isset($this->{$type}[$roleName])) {
                foreach ($this->{$type}[$roleName] as $resourceName => $permissions) {
                    $this->deleteRuleAssertions($type, $roleName, $resourceName, $permissions);
                }
                unset($this->{$type}[$roleName]);
            }
        }

        $this->policies = array_values(array_filter(
            $this->policies,
            fn($policy) => (string)$policy['role'] !== $roleName
        ));

        unset($this->roles[$roleName]);

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
     * Has resources
     *
     * @return bool
     */
    public function hasResources(): bool
    {
        return !empty($this->resources);
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
     * Remove a resource
     *
     * All allow/deny rules, assertions and policies referencing this
     * resource (across every role) are purged.
     *
     * @param  mixed $resource
     * @throws Exception
     * @return Acl
     */
    public function removeResource(mixed $resource): Acl
    {
        $this->verifyResource($resource);

        $resourceObj  = $this->resources[(string)$resource];
        $resourceName = (string)$resourceObj;

        foreach (['allowed', 'denied'] as $type) {
            foreach ($this->{$type} as $roleName => $resources) {
                if (isset($resources[$resourceName])) {
                    $this->deleteRuleAssertions($type, $roleName, $resourceName, $resources[$resourceName]);
                    unset($this->{$type}[$roleName][$resourceName]);
                    // Remove the role entry if it has no more resources
                    if (count($this->{$type}[$roleName]) === 0) {
                        unset($this->{$type}[$roleName]);
                    }
                }
            }
        }

        $this->policies = array_values(array_filter(
            $this->policies,
            fn($policy) => ($policy['resource'] === null) || ((string)$policy['resource'] !== $resourceName)
        ));

        unset($this->resources[$resourceName]);

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
        return $this->setRule('allowed', $role, $resource, $permission, $assertion);
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
        return $this->removeRule('allowed', $role, $resource, $permission);
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
        return $this->setRule('denied', $role, $resource, $permission, $assertion);
    }

    /**
     * Shared implementation for allow()/deny()
     *
     * @param  string              $type
     * @param  mixed               $role
     * @param  mixed               $resource
     * @param  mixed               $permission
     * @param  ?AssertionInterface $assertion
     * @throws Exception
     * @return Acl
     */
    protected function setRule(
        string $type, mixed $role, mixed $resource = null, mixed $permission = null, ?AssertionInterface $assertion = null
    ): Acl
    {
        if ($this->verifyRole($role)) {
            $role = $this->roles[(string)$role];

            if (!isset($this->{$type}[(string)$role])) {
                $this->{$type}[(string)$role] = [];
            }

            if (($resource !== null) && ($this->verifyResource($resource))) {
                $resource = $this->resources[(string)$resource];

                if (!isset($this->{$type}[(string)$role][(string)$resource])) {
                    $this->{$type}[(string)$role][(string)$resource] = [];
                }
                if ($permission !== null) {
                    if (!is_array($permission)) {
                        $permission = [$permission];
                    }
                    foreach ($permission as $perm) {
                        $this->{$type}[(string)$role][(string)$resource][] = $perm;
                        if ($assertion !== null) {
                            $this->createAssertion($assertion, $type, $role, $resource, $perm);
                        }
                    }
                } else {
                    if ($assertion !== null) {
                        $this->createAssertion($assertion, $type, $role, $resource);
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
        return $this->removeRule('denied', $role, $resource, $permission);
    }

    /**
     * Shared implementation for removeAllowRule()/removeDenyRule()
     *
     * @param  string $type
     * @param  mixed  $role
     * @param  mixed  $resource
     * @param  mixed  $permission
     * @throws Exception
     * @return Acl
     */
    protected function removeRule(string $type, mixed $role, mixed $resource = null, mixed $permission = null): Acl
    {
        if (($this->verifyRole($role)) && isset($this->{$type}[(string)$role])) {
            // If only role passed
            if (($resource === null) && ($permission === null)) {
                unset($this->{$type}[(string)$role]);
                $this->deleteAssertion($type, $role);
            // If role & resource passed
            } else if (($resource !== null) && ($permission === null) && ($this->verifyResource($resource)) &&
                isset($this->{$type}[(string)$role][(string)$resource])) {
                unset($this->{$type}[(string)$role][(string)$resource]);
                $this->deleteAssertion($type, $role, $resource);
            // If role, resource & permission passed
            } else {
                if (!is_array($permission)) {
                    $permission = [$permission];
                }
                foreach ($permission as $perm) {
                    if (($this->verifyResource($resource)) && isset($this->{$type}[(string)$role][(string)$resource]) &&
                        in_array($perm, $this->{$type}[(string)$role][(string)$resource])) {
                        $key = array_search($perm, $this->{$type}[(string)$role][(string)$resource]);
                        unset($this->{$type}[(string)$role][(string)$resource][$key]);
                    }
                    $this->deleteAssertion($type, $role, $resource, $perm);
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

        // Evaluated once and shared with the internal deny check below,
        // instead of letting isDenied() re-run evaluatePolicies() itself.
        $policyResult = $this->hasPolicies() ? $this->evaluatePolicies($role, $resource, $permission) : null;

        if ($this->verifyRole($role)) {
            if ($resource !== null) {
                $this->verifyResource($resource);
            }

            // If is not denied
            if (!$this->resolveDenied($role, $resource, $permission, $policyResult)) {
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
                                    (count($permissions) == count($permissionsToCheck)) ||
                                    in_array('*', $allowedPermissions, true));
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
        if ($result) {
            $assertionKey = $this->getAssertionKey('allowed', $role, $resource, $permission);
            if ($assertionKey !== null) {
                $assertionRole     = $this->roles[(string)$role];
                $assertionResource = ($resource !== null) ?  $this->resources[(string)$resource] : null;
                $result            =
                    $this->assertions['allowed'][$assertionKey]->assert($this, $assertionRole, $assertionResource, $permission);
            }
        }

        // Check for policies
        if ($policyResult !== null) {
            $result = $policyResult;
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
        $policyResult = $this->hasPolicies() ? $this->evaluatePolicies($role, $resource, $permission) : null;

        return $this->resolveDenied($role, $resource, $permission, $policyResult);
    }

    /**
     * Shared deny evaluation used by isDenied() and, internally, isAllowed()
     * (which passes in an already-evaluated policy result to avoid evaluating
     * policies twice per isAllowed() call).
     *
     * @param  mixed     $role
     * @param  mixed     $resource
     * @param  mixed     $permission
     * @param  bool|null $policyResult
     * @throws Exception
     * @return bool
     */
    protected function resolveDenied(mixed $role, mixed $resource, mixed $permission, bool|null $policyResult): bool
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
                                    $deniedPermissions = $this->denied[(string)$roleToCheck][(string)$resource];
                                    if (in_array('*', $deniedPermissions, true)) {
                                        $result = true;
                                    } else {
                                        $permissions = (!is_array($permission)) ? [$permission] : $permission;
                                        foreach ($permissions as $p) {
                                            if (in_array($p, $deniedPermissions)) {
                                                $result = true;
                                            }
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
        $assertionKey = $this->getAssertionKey('denied', $role, $resource, $permission);
        if ($assertionKey !== null) {
            $assertionRole     = $this->roles[(string)$role];
            $assertionResource = ($resource !== null) ?  $this->resources[(string)$resource] : null;
            $result            =
                $this->assertions['denied'][$assertionKey]->assert($this, $assertionRole, $assertionResource, $permission);
        }

        // Check for policies
        if ($policyResult !== null) {
            $result = !$policyResult;
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
     * Get the effective allowed permissions for a role on a resource,
     * merged with inheritance. Returns ['*'] if access is unrestricted
     * (either via an explicit '*' permission or an empty permission list
     * at any level of the role's parent chain), or [] if no rule exists.
     *
     * @param  mixed $role
     * @param  mixed $resource
     * @throws Exception
     * @return array
     */
    public function getAllowedPermissions(mixed $role, mixed $resource): array
    {
        return $this->getEffectivePermissions('allowed', $role, $resource);
    }

    /**
     * Get the effective denied permissions for a role on a resource,
     * merged with inheritance. Returns ['*'] if denial is unrestricted
     * (either via an explicit '*' permission or an empty permission list
     * at any level of the role's parent chain), or [] if no rule exists.
     *
     * @param  mixed $role
     * @param  mixed $resource
     * @throws Exception
     * @return array
     */
    public function getDeniedPermissions(mixed $role, mixed $resource): array
    {
        return $this->getEffectivePermissions('denied', $role, $resource);
    }

    /**
     * Shared walk for getAllowedPermissions()/getDeniedPermissions()
     *
     * @param  string $type
     * @param  mixed  $role
     * @param  mixed  $resource
     * @throws Exception
     * @return array
     */
    protected function getEffectivePermissions(string $type, mixed $role, mixed $resource): array
    {
        $this->verifyRole($role);
        $this->verifyResource($resource);

        $resourceName = (string)$this->resources[(string)$resource];
        $roleToCheck  = $this->roles[(string)$role];
        $permissions  = [];

        while ($roleToCheck !== null) {
            $roleRules = $this->{$type}[(string)$roleToCheck] ?? null;
            if ($roleRules !== null) {
                if (array_key_exists($resourceName, $roleRules)) {
                    if (count($roleRules[$resourceName]) === 0) {
                        return ['*'];
                    }
                    $permissions = array_merge($permissions, $roleRules[$resourceName]);
                } else if (count($roleRules) === 0) {
                    return ['*'];
                }
            }
            $roleToCheck = $roleToCheck->getParent();
        }

        if (in_array('*', $permissions, true)) {
            return ['*'];
        }

        return array_values(array_unique($permissions));
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
     * Delete the assertions for a role/resource's full permission set,
     * shared by removeRole() and removeResource()
     *
     * @param  string $type
     * @param  string $roleName
     * @param  string $resourceName
     * @param  array  $permissions
     * @return void
     */
    protected function deleteRuleAssertions(string $type, string $roleName, string $resourceName, array $permissions): void
    {
        if (count($permissions) === 0) {
            $this->deleteAssertion($type, $roleName, $resourceName);
        } else {
            foreach ($permissions as $perm) {
                $this->deleteAssertion($type, $roleName, $resourceName, $perm);
            }
        }
    }

    /**
     * Has assertion key
     *
     * @param  string $type
     * @param  mixed  $role
     * @param  mixed  $resource
     * @param  mixed  $permission
     * @throws InvalidArgumentException
     * @return bool
     */
    public function hasAssertionKey(string $type, mixed $role, mixed $resource = null, mixed $permission = null): bool
    {
        return ($this->getAssertionKey($type, $role, $resource, $permission) !== null);
    }

    /**
     * Get assertion key
     *
     * @param  string $type
     * @param  mixed  $role
     * @param  mixed  $resource
     * @param  mixed  $permission
     * @throws InvalidArgumentException
     * @return string|null
     */
    public function getAssertionKey(string $type, mixed $role, mixed $resource = null, mixed $permission = null): string|null
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
     * @throws \Pop\Acl\Policy\Exception if a policy method isn't callable on the role
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
     * @throws \Pop\Acl\Policy\Exception if a policy method isn't callable on the role
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
     * @param  mixed $role
     * @param  mixed $resource
     * @param  mixed $permission
     * @return string
     */
    protected function generateAssertionKey(mixed $role, mixed $resource = null, mixed $permission = null): string
    {
        $key = (string)$role;

        if ($resource !== null) {
            $key .= '-' . (string)$resource;
        }
        if ($permission !== null) {
            $key .= '-' . (is_array($permission) ? implode(',', $permission) : (string)$permission);
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
