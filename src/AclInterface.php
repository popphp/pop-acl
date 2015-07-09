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
namespace Pop\Acl;

use Pop\Acl\Assertion\AssertionInterface;
use Pop\Acl\Role;
use Pop\Acl\Resource;

/**
 * Acl interface
 *
 * @category   Pop
 * @package    Pop_Acl
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2015 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    2.0.0
 */
interface AclInterface
{

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
    public function allow($role, $resource = null, $permission = null, AssertionInterface $assertion = null);

    /**
     * Remove an allow rule
     *
     * @param  mixed              $role
     * @param  mixed              $resource
     * @param  mixed              $permission
     * @param  AssertionInterface $assertion
     * @throws Exception
     * @return Acl
     */
    public function removeAllowRule($role, $resource = null, $permission = null, AssertionInterface $assertion = null);

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
    public function deny($role, $resource = null, $permission = null, AssertionInterface $assertion = null);

    /**
     * Remove a deny rule
     *
     * @param  mixed              $role
     * @param  mixed              $resource
     * @param  mixed              $permission
     * @param  AssertionInterface $assertion
     * @throws Exception
     * @return Acl
     */
    public function removeDenyRule($role, $resource = null, $permission = null, AssertionInterface $assertion = null);

    /**
     * Determine if the user is allowed
     *
     * @param  mixed $role
     * @param  mixed $resource
     * @param  mixed $permission
     * @throws Exception
     * @return boolean
     */
    public function isAllowed($role, $resource = null, $permission = null);

    /**
     * Determine if the user is denied
     *
     * @param  mixed $role
     * @param  mixed $resource
     * @param  mixed $permission
     * @throws Exception
     * @return boolean
     */
    public function isDenied($role, $resource = null, $permission = null);

}