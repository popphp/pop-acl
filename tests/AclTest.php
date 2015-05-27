<?php

namespace Pop\Acl\Test;

use Pop\Acl\Acl;
use Pop\Acl\Role\Role;
use Pop\Acl\Resource\Resource;

class AclTest extends \PHPUnit_Framework_TestCase
{

    public function testConstructor()
    {
        $acl = new Acl(new Role('editor'), new Resource('page'));
        $this->assertInstanceOf('Pop\Acl\Acl', $acl);
        $this->assertEquals('editor', $acl->getRole('editor')->getName());
        $this->assertEquals(1, count($acl->getRoles()));
        $this->assertTrue($acl->hasRole('editor'));
        $this->assertEquals('page', $acl->getResource('page')->getName());
        $this->assertEquals(1, count($acl->getResources()));
        $this->assertTrue($acl->hasResource('page'));
    }

    public function testAddRole()
    {
        $editor = new Role('editor');
        $reader = new Role('reader');
        $editor->addChild($reader);
        $acl = new Acl();
        $acl->addRole($editor);
        $this->assertTrue($acl->hasRole('editor'));
    }

    public function testAddRoles()
    {
        $editor = new Role('editor');
        $reader = new Role('reader');
        $acl = new Acl();
        $acl->addRoles([$editor, $reader]);
        $this->assertTrue($acl->hasRole('editor'));
        $this->assertTrue($acl->hasRole('reader'));
    }

    public function testAddResources()
    {
        $page = new Resource('page');
        $user = new Resource('user');
        $acl = new Acl();
        $acl->addResources([$page, $user]);
        $this->assertTrue($acl->hasResource('page'));
        $this->assertTrue($acl->hasResource('user'));
    }

}