<?php

namespace Pop\Acl\Test;

use Pop\Acl\Role\Role;

class RoleTest extends \PHPUnit_Framework_TestCase
{

    public function testConstructor()
    {
        $role = new Role('editor', [
            'username' => 'syseditor',
            'id'       => 1001
        ]);

        $data = $role->getData();
        
        $this->assertInstanceOf('Pop\Acl\Role\Role', $role);
        $this->assertEquals('editor', $role->getName());
        $this->assertEquals('syseditor', $role->username);
        $this->assertEquals('syseditor', $role['username']);
        $this->assertEquals('syseditor', $data['username']);
    }

    public function testMagicMethods()
    {
        $role = new Role('editor');
        $role->username = 'editor';
        $this->assertEquals('editor', $role->username);
        $this->assertTrue(isset($role->username));
        unset($role->username);
        $this->assertFalse(isset($role->username));
    }

    public function testOffsetMethods()
    {
        $role = new Role('editor');
        $role['username'] = 'editor';
        $this->assertEquals('editor', $role['username']);
        $this->assertTrue(isset($role['username']));
        unset($role['username']);
        $this->assertFalse(isset($role['username']));
    }

    public function testToString()
    {
        $role = new Role('editor');
        $this->assertEquals('editor', (string)$role);
    }

    public function testAddChild()
    {
        $editor = new Role('editor');
        $reader = new Role('reader');
        $editor->addChild($reader);
        $this->assertTrue($editor->hasChildren());
        $this->assertEquals(1, count($editor->getChildren()));
        $this->assertTrue($reader->hasParent());
        $this->assertTrue(($reader->getParent() === $editor));
    }

}