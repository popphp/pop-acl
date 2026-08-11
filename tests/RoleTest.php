<?php

namespace Pop\Acl\Test;

use Pop\Acl\AclRole;
use PHPUnit\Framework\TestCase;

class RoleTest extends TestCase
{

    public function testConstructor()
    {
        $role = new AclRole('editor', [
            'username' => 'syseditor',
            'id'       => 1001
        ]);

        $data = $role->toArray();
        
        $this->assertInstanceOf('Pop\Acl\AclRole', $role);
        $this->assertEquals('editor', $role->getName());
        $this->assertEquals('syseditor', $role->username);
        $this->assertEquals('syseditor', $role['username']);
        $this->assertEquals('syseditor', $data['username']);
    }

    public function testMagicMethods()
    {
        $role = new AclRole('editor');
        $role->username = 'editor';
        $this->assertEquals('editor', $role->username);
        $this->assertTrue(isset($role->username));
        unset($role->username);
        $this->assertFalse(isset($role->username));
    }

    public function testOffsetMethods()
    {
        $role = new AclRole('editor');
        $role['username'] = 'editor';
        $this->assertEquals('editor', $role['username']);
        $this->assertTrue(isset($role['username']));
        unset($role['username']);
        $this->assertFalse(isset($role['username']));
    }

    public function testToString()
    {
        $role = new AclRole('editor');
        $this->assertEquals('editor', (string)$role);
    }

    public function testAddChild()
    {
        $editor = new AclRole('editor');
        $reader = new AclRole('reader');
        $editor->addChild($reader);
        $this->assertTrue($editor->hasChildren());
        $this->assertEquals(1, count($editor->getChildren()));
        $this->assertTrue($reader->hasParent());
        $this->assertTrue(($reader->getParent() === $editor));
    }

    public function testRemoveChild()
    {
        $editor = new AclRole('editor');
        $reader = new AclRole('reader');
        $editor->addChild($reader);

        $editor->removeChild($reader);

        $this->assertFalse($editor->hasChildren());
        $this->assertEquals(0, count($editor->getChildren()));
        $this->assertFalse($reader->hasParent());
        $this->assertNull($reader->getParent());
    }

    public function testRemoveChildNotPresentIsNoOp()
    {
        $editor  = new AclRole('editor');
        $unknown = new AclRole('unknown');

        $editor->removeChild($unknown);

        $this->assertFalse($editor->hasChildren());
    }

    public function testClearParent()
    {
        $editor = new AclRole('editor');
        $reader = new AclRole('reader');
        $editor->addChild($reader);

        $reader->clearParent();

        $this->assertFalse($reader->hasParent());
        $this->assertNull($reader->getParent());
        // clearParent() only affects the child's own pointer, not the parent's list
        $this->assertTrue($editor->hasChildren());
    }

}