<?php

namespace Pop\Acl\Test;

use Pop\Acl\Resource\Resource;

class ResourceTest extends \PHPUnit_Framework_TestCase
{

    public function testConstructor()
    {
        $resource = new Resource('user', [
            'username' => 'admin',
            'id'       => 1001
        ]);

        $data = $resource->getData();

        $this->assertInstanceOf('Pop\Acl\Resource\Resource', $resource);
        $this->assertEquals('user', $resource->getName());
        $this->assertEquals('admin', $resource->username);
        $this->assertEquals('admin', $resource['username']);
        $this->assertEquals('admin', $data['username']);
    }

    public function testMagicMethods()
    {
        $resource = new Resource('user');
        $resource->username = 'admin';
        $this->assertEquals('admin', $resource->username);
        $this->assertTrue(isset($resource->username));
        unset($resource->username);
        $this->assertFalse(isset($resource->username));
    }

    public function testOffsetMethods()
    {
        $resource = new Resource('user');
        $resource['username'] = 'admin';
        $this->assertEquals('admin', $resource['username']);
        $this->assertTrue(isset($resource['username']));
        unset($resource['username']);
        $this->assertFalse(isset($resource['username']));
    }

    public function testToString()
    {
        $resource = new Resource('user');
        $this->assertEquals('user', (string)$resource);
    }

}