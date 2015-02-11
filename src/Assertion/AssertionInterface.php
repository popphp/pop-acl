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
namespace Pop\Acl\Assertion;

use Pop\Acl\Acl;
use Pop\Acl\Role\AbstractRole;
use Pop\Acl\Resource\AbstractResource;

/**
 * Assertion interface
 *
 * @category   Pop
 * @package    Pop_Acl
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2015 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    2.0.0a
 */
interface AssertionInterface
{

    /**
     * Evaluate assertion
     *
     * @param  Acl              $acl
     * @param  AbstractRole     $role
     * @param  AbstractResource $resource
     * @param  mixed            $permission
     * @return boolean
     */
    public function assert(Acl $acl, AbstractRole $role, AbstractResource $resource = null, $permission = null);

}