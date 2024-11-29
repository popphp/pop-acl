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

use Pop\Utils;
use Pop\Utils\Exception;

/**
 * Abstract ACL role class
 *
 * @category   Pop
 * @package    Pop\Acl
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2025 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
 * @version    4.1.1
 */
abstract class AbstractAcl extends Utils\ArrayObject
{

    /**
     * Role name
     * @var ?string
     */
    protected ?string $name = null;

    /**
     * Constructor
     *
     * Instantiate the acl role object
     *
     * @param  string $name
     * @param  array $data
     * @throws Exception
     */
    public function __construct(string $name, array $data = [])
    {
        $this->setName($name);
        parent::__construct($data);
    }

    /**
     * Set the acl role name
     *
     * @param  string $name
     * @return AbstractAcl
     */
    public function setName(string $name): AbstractAcl
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Get the acl role name
     *
     * @return string|null
     */
    public function getName(): string|null
    {
        return $this->name;
    }

    /**
     * Return the string value of the name of the acl role
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->name;
    }

}
