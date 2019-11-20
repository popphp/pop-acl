<?php
/**
 * Pop PHP Framework (http://www.popphp.org/)
 *
 * @link       https://github.com/popphp/popphp-framework
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2020 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 */

/**
 * @namespace
 */
namespace Pop\Acl\Assertion;

use Pop\Acl\Acl;
use Pop\Acl\AclRole;
use Pop\Acl\AclResource;

/**
 * Assertion interface
 *
 * @category   Pop
 * @package    Pop\Acl
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2020 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    3.2.1
 */
interface AssertionInterface
{

    /**
     * Evaluate assertion
     *
     * @param  Acl         $acl
     * @param  AclRole     $role
     * @param  AclResource $resource
     * @param  mixed       $permission
     * @return boolean
     */
    public function assert(Acl $acl, AclRole $role, AclResource $resource = null, $permission = null);

}