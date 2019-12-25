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
namespace Pop\Acl\Policy;

use Pop\Acl\AclResource;

/**
 * Policy trait
 *
 * @category   Pop
 * @package    Pop\Acl
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2020 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    3.3.0
 */
interface PolicyInterface
{

    /**
     * Evaluate policy
     *
     * @param  string      $method
     * @param  AclResource $resource
     * @return boolean
     */
    public function can($method, AclResource $resource = null);

}