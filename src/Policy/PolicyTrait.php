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
namespace Pop\Acl\Policy;

use Pop\Acl\AclResource;

/**
 * Policy trait
 *
 * @category   Pop
 * @package    Pop\Acl
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2025 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
 * @version    4.1.1
 */
trait PolicyTrait
{

    /**
     * Evaluate policy
     *
     * @param  string       $method
     * @param  ?AclResource $resource
     * @return bool|null
     */
    public function can(string $method, ?AclResource $resource = null): bool|null
    {
        $result  = null;
        $methods = (str_contains($method, ',')) ?
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
