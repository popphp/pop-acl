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
namespace Pop\Acl\Policy;

use Pop\Acl\AclResource;

/**
 * Policy trait
 *
 * @category   Pop
 * @package    Pop\Acl
 * @author     Nick Sagona, III <nick@popphp.org>
 * @copyright  Copyright (c) 2009-2027 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
 * @version    5.0.0
 * @phpstan-require-implements PolicyInterface
 */
trait PolicyTrait
{

    /**
     * Evaluate policy
     *
     * @param  string       $method
     * @param  ?AclResource $resource
     * @throws Exception
     * @return bool|null
     */
    public function can(string $method, ?AclResource $resource = null): bool|null
    {
        $result  = null;
        $methods = (str_contains($method, ',')) ?
            array_map('trim', explode(',', $method)) : [$method];

        // Validate all methods are callable first
        foreach ($methods as $method) {
            if (!is_callable([$this, $method])) {
                throw new Exception(
                    "Error: The policy method '" . $method . "' is not callable on '" . static::class . "'."
                );
            }
        }

        // Now call all methods
        foreach ($methods as $method) {
            $result = $this->{$method}($this, $resource);

            if ($result === false) {
                return false;
            }
        }

        return $result;
    }

}
