<?php

namespace Pop\Acl\Test;

use Pop\Acl\AclResource;
use PHPUnit\Framework\TestCase;

class ResourceTest extends TestCase
{

    public function testConstructor()
    {
        $resource = new AclResource('user', [
            'username' => 'admin',
            'id'       => 1001
        ]);

        $data = $resource->getData();

        $this->assertInstanceOf('Pop\Acl\AclResource', $resource);
        $this->assertEquals('user', $resource->getName());
        $this->assertEquals('admin', $resource->username);
        $this->assertEquals('admin', $resource['username']);
        $this->assertEquals('admin', $data['username']);
    }

    public function testMagicMethods()
    {
        $resource = new AclResource('user');
        $resource->username = 'admin';
        $this->assertEquals('admin', $resource->username);
        $this->assertTrue(isset($resource->username));
        unset($resource->username);
        $this->assertFalse(isset($resource->username));
    }

    public function testOffsetMethods()
    {
        $resource = new AclResource('user');
        $resource['username'] = 'admin';
        $this->assertEquals('admin', $resource['username']);
        $this->assertTrue(isset($resource['username']));
        unset($resource['username']);
        $this->assertFalse(isset($resource['username']));
    }

    public function testToString()
    {
        $resource = new AclResource('user');
        $this->assertEquals('user', (string)$resource);
    }

}