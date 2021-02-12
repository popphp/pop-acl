<?php
/**
 * Pop PHP Framework (http://www.popphp.org/)
 *
 * @link       https://github.com/popphp/popphp-framework
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2021 NOLA Interactive, LLC. (http://www.nolainteractive.com)
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
 * @copyright  Copyright (c) 2009-2021 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    3.3.0
 */
trait PolicyTrait
{

    /**
     * Evaluate policy
     *
     * @param  string      $method
     * @param  AclResource $resource
     * @return boolean|null
     */
    public function can($method, AclResource $resource = null)
    {
        $result  = null;
        $methods = (strpos($method, ',') !== false) ?
            array_map('trim', explode(',', $method)) : [$method];

        foreach ($methods as $method) {
            if (is_callable([$this, $method])) {
                $result = $this->{$method}($this, $resource);
            }

            if ($result === false) {
                return false;
            }
        }

        return $result;
    }

}