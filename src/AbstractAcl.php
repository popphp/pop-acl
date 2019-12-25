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
namespace Pop\Acl;

use Pop\Utils;

/**
 * Abstract ACL role class
 *
 * @category   Pop
 * @package    Pop\Acl
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2020 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    3.3.0
 */
abstract class AbstractAcl extends Utils\ArrayObject
{

    /**
     * Role name
     * @var string
     */
    protected $name = null;

    /**
     * Constructor
     *
     * Instantiate the acl role object
     *
     * @param  string $name
     * @param  array  $data
     */
    public function __construct($name, array $data = [])
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
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Get the acl role name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Return the string value of the name of the acl role
     *
     * @return string
     */
    public function __toString()
    {
        return $this->name;
    }

}