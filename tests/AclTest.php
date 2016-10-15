<?php

namespace Pop\Acl\Test;

use Pop\Acl\Acl;
use Pop\Acl\AclRole;
use Pop\Acl\AclResource;

class AclTest extends \PHPUnit_Framework_TestCase
{

    public function testConstructor()
    {
        $acl = new Acl(new AclRole('editor'), new AclResource('page'));
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
        $editor = new AclRole('editor');
        $reader = new AclRole('reader');
        $editor->addChild($reader);
        $acl = new Acl();
        $acl->addRole($editor);
        $this->assertTrue($acl->hasRole('editor'));
    }

    public function testAddRoles()
    {
        $editor = new AclRole('editor');
        $reader = new AclRole('reader');
        $acl = new Acl();
        $acl->addRoles([$editor, $reader]);
        $this->assertTrue($acl->hasRole('editor'));
        $this->assertTrue($acl->hasRole('reader'));
    }

    public function testAddResources()
    {
        $page = new AclResource('page');
        $user = new AclResource('user');
        $acl  = new Acl();
        $acl->addResources([$page, $user]);
        $this->assertTrue($acl->hasResource('page'));
        $this->assertTrue($acl->hasResource('user'));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testAllowBadRoleType()
    {
        $acl = new Acl();
        $acl->allow(['bad role']);
    }

    /**
     * @expectedException \Pop\Acl\Exception
     */
    public function testAllowRoleNotAdded()
    {
        $acl = new Acl();
        $acl->allow('editor');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testAllowBadResourceType()
    {
        $editor = new AclRole('editor');
        $acl    = new Acl($editor);
        $acl->allow('editor', ['bad resource']);
    }

    /**
     * @expectedException \Pop\Acl\Exception
     */
    public function testAllowResourceNotAdded()
    {
        $editor = new AclRole('editor');
        $acl    = new Acl($editor);
        $acl->allow('editor', 'page');
    }

    public function testIsAllowed()
    {
        $admin      = new AclRole('admin');
        $publisher  = new AclRole('publisher');
        $editor     = new AclRole('editor');
        $editor->id = 1000;
        $reader     = new AclRole('reader');
        $page       = new AclResource('page');
        $acl        = new Acl($editor, $page);
        $acl->addRole($admin);
        $acl->addRole($publisher);
        $acl->addRole($reader);
        $acl->allow('admin');
        $acl->allow('publisher', 'page');
        $acl->allow('editor', 'page', 'edit', new TestAsset\TestAllowedAssertion($acl, $editor, $page, 'edit'));
        $this->assertTrue($acl->isAllowed('editor', 'page', 'edit'));
        $this->assertTrue($acl->isAllowed('admin'));
        $this->assertTrue($acl->isAllowed('publisher', 'page'));
        $this->assertFalse($acl->isAllowed('reader', 'page', 'edit'));
    }

    public function testIsAllowedWithAssertionNoPermission()
    {
        $editor     = new AclRole('editor');
        $editor->id = 1000;
        $page       = new AclResource('page');
        $acl        = new Acl($editor, $page);
        $acl->allow('editor', 'page', null, new TestAsset\TestAllowedAssertion($acl, $editor, $page));
        $this->assertTrue($acl->isAllowed('editor', 'page'));
    }

    public function testIsAllowedWithAssertionNoResource()
    {
        $editor     = new AclRole('editor');
        $editor->id = 1000;
        $acl        = new Acl($editor);
        $acl->allow('editor', null, null, new TestAsset\TestAllowedAssertion($acl, $editor));
        $this->assertTrue($acl->isAllowed('editor'));
    }

    /**
     * @expectedException \Pop\Acl\Exception
     */
    public function testIsAllowedRoleNotAdded()
    {
        $acl = new Acl();
        $acl->isAllowed('editor');
    }

    /**
     * @expectedException \Pop\Acl\Exception
     */
    public function testIsAllowedResourceNotAdded()
    {
        $acl = new Acl(new AclRole('editor'));
        $acl->isAllowed('editor', 'page');
    }

    public function testRemoveAllowRule()
    {
        $editor     = new AclRole('editor');
        $editor->id = 1000;
        $page       = new AclResource('page');
        $acl        = new Acl($editor, $page);
        $acl->allow('editor', 'page', 'edit', new TestAsset\TestAllowedAssertion($acl, $editor, $page, 'edit'));
        $this->assertTrue($acl->isAllowed('editor', 'page', 'edit'));
        $acl->removeAllowRule('editor', 'page', 'edit', new TestAsset\TestAllowedAssertion($acl, $editor, $page, 'edit'));
        $acl->removeAllowRule('editor', 'page');
        $acl->removeAllowRule('editor');
        $this->assertFalse($acl->isAllowed('editor', 'page', 'edit'));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testRemoveAllowRuleBadRoleType()
    {
        $editor = new AclRole('editor');
        $page   = new AclResource('page');
        $acl    = new Acl($editor, $page);
        $acl->allow('editor', 'page', 'edit');
        $acl->removeAllowRule(['bad role']);
    }

    /**
     * @expectedException \Pop\Acl\Exception
     */
    public function testRemoveAllowRuleRoleNotAdded()
    {
        $editor = new AclRole('editor');
        $page   = new AclResource('page');
        $acl    = new Acl($editor, $page);
        $acl->allow('editor', 'page', 'edit');
        $acl->removeAllowRule('admin');
    }

    /**
     * @expectedException \Pop\Acl\Exception
     */
    public function testRemoveAllowRuleNoRule()
    {
        $editor = new AclRole('editor');
        $page   = new AclResource('page');
        $acl    = new Acl($editor, $page);
        $acl->removeAllowRule('editor');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testRemoveAllowRuleBadResourceType()
    {
        $editor = new AclRole('editor');
        $page   = new AclResource('page');
        $acl    = new Acl($editor, $page);
        $acl->allow('editor', 'page', 'edit');
        $acl->removeAllowRule('editor', ['bad resource']);
    }

    /**
     * @expectedException \Pop\Acl\Exception
     */
    public function testRemoveAllowRuleResourceNotAdded()
    {
        $editor = new AclRole('editor');
        $page   = new AclResource('page');
        $acl    = new Acl($editor, $page);
        $acl->allow('editor', 'page', 'edit');
        $acl->removeAllowRule('editor', 'user');
    }

    public function testIsNotAllowedPermission()
    {
        $reader = new AclRole('reader');
        $page   = new AclResource('page');
        $acl    = new Acl($reader, $page);
        $acl->allow('reader', 'page', 'read');
        $this->assertFalse($acl->isAllowed('reader', 'page', 'edit'));
    }

    public function testIsNotAllowedAssertion()
    {
        $editor     = new AclRole('editor');
        $editor->id = 1;
        $page       = new AclResource('page');
        $acl        = new Acl($editor, $page);
        $acl->allow('editor', 'page', 'edit', new TestAsset\TestAllowedAssertion($acl, $editor, $page, 'edit'));
        $this->assertFalse($acl->isAllowed('editor', 'page', 'edit'));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testDenyBadRoleType()
    {
        $acl = new Acl();
        $acl->deny(['bad role']);
    }

    /**
     * @expectedException \Pop\Acl\Exception
     */
    public function testDenyRoleNotAdded()
    {
        $acl = new Acl();
        $acl->deny('editor');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testDenyBadResourceType()
    {
        $editor = new AclRole('editor');
        $acl    = new Acl($editor);
        $acl->deny('editor', ['bad resource']);
    }

    /**
     * @expectedException \Pop\Acl\Exception
     */
    public function testDenyResourceNotAdded()
    {
        $editor = new AclRole('editor');
        $acl    = new Acl($editor);
        $acl->deny('editor', 'page');
    }

    public function testIsDenied()
    {
        $editor     = new AclRole('editor');
        $page       = new AclResource('page');
        $acl        = new Acl($editor, $page);
        $acl->deny('editor', 'page', 'edit');
        $this->assertTrue($acl->isDenied('editor', 'page', 'edit'));
    }

    public function testIsDeniedWithAssertion()
    {
        $editor     = new AclRole('editor');
        $page       = new AclResource('page');
        $acl        = new Acl($editor, $page);
        $acl->deny('editor', 'page', 'edit', new TestAsset\TestDeniedAssertion($acl, $editor, $page, 'edit'));
        $this->assertTrue($acl->isDenied('editor', 'page', 'edit'));
    }

    /**
     * @expectedException \Pop\Acl\Exception
     */
    public function testIsDeniedRoleNotAdded()
    {
        $acl = new Acl();
        $acl->isDenied('editor');
    }

    /**
     * @expectedException \Pop\Acl\Exception
     */
    public function testIsDeniedResourceNotAdded()
    {
        $acl = new Acl(new AclRole('editor'));
        $acl->isDenied('editor', 'page');
    }


    public function testIsDeniedWithAssertionNoPermission()
    {
        $editor     = new AclRole('editor');
        $page       = new AclResource('page');
        $acl        = new Acl($editor, $page);
        $acl->deny('editor', 'page', null, new TestAsset\TestDeniedAssertion($acl, $editor, $page));
        $this->assertTrue($acl->isDenied('editor', 'page'));
    }

    public function testIsDeniedWithAssertionNoResource()
    {
        $editor     = new AclRole('editor');
        $acl        = new Acl($editor);
        $acl->deny('editor', null, null, new TestAsset\TestDeniedAssertion($acl, $editor));
        $this->assertTrue($acl->isDenied('editor'));
    }

    public function testRemoveDenyRule()
    {
        $editor     = new AclRole('editor');
        $page       = new AclResource('page');
        $acl        = new Acl($editor, $page);
        $acl->deny('editor', 'page', 'edit', new TestAsset\TestDeniedAssertion($acl, $editor, $page, 'edit'));
        $this->assertTrue($acl->isDenied('editor', 'page', 'edit'));
        $acl->removeDenyRule('editor', 'page', 'edit', new TestAsset\TestAllowedAssertion($acl, $editor, $page, 'edit'));
        $acl->removeDenyRule('editor', 'page');
        $acl->removeDenyRule('editor');
        $this->assertFalse($acl->isDenied('editor', 'page', 'edit'));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testRemoveDenyRuleBadRoleType()
    {
        $editor = new AclRole('editor');
        $page   = new AclResource('page');
        $acl    = new Acl($editor, $page);
        $acl->deny('editor', 'page', 'edit');
        $acl->removeDenyRule(['bad role']);
    }

    /**
     * @expectedException \Pop\Acl\Exception
     */
    public function testRemoveDenyRuleRoleNotAdded()
    {
        $editor = new AclRole('editor');
        $page   = new AclResource('page');
        $acl    = new Acl($editor, $page);
        $acl->deny('editor', 'page', 'edit');
        $acl->removeDenyRule('admin');
    }

    /**
     * @expectedException \Pop\Acl\Exception
     */
    public function testRemoveDenyRuleNoRule()
    {
        $editor = new AclRole('editor');
        $page   = new AclResource('page');
        $acl    = new Acl($editor, $page);
        $acl->removeDenyRule('editor');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testRemoveDenyRuleBadResourceType()
    {
        $editor = new AclRole('editor');
        $page   = new AclResource('page');
        $acl    = new Acl($editor, $page);
        $acl->deny('editor', 'page', 'edit');
        $acl->removeDenyRule('editor', ['bad resource']);
    }

    /**
     * @expectedException \Pop\Acl\Exception
     */
    public function testRemoveDenyRuleResourceNotAdded()
    {
        $editor = new AclRole('editor');
        $page   = new AclResource('page');
        $acl    = new Acl($editor, $page);
        $acl->deny('editor', 'page', 'edit');
        $acl->removeDenyRule('editor', 'user');
    }

    public function testRoleWithChildren()
    {
        $acl       = new Acl();
        $page      = new AclResource('page');
        $admin     = new AclRole('admin');
        $publisher = new AclRole('publisher');
        $editor    = new AclRole('editor');
        $publisher->addChild($editor);
        $admin->addChild($publisher);

        $acl->addRoles([$admin, $publisher, $editor]);
        $acl->addResource($page);

        $acl->allow('admin', 'page', 'edit');
        $this->assertTrue($acl->isAllowed('admin', 'page', 'edit'));
        $this->assertTrue($acl->isAllowed('publisher', 'page', 'edit'));
        $this->assertTrue($acl->isAllowed('editor', 'page', 'edit'));
    }

}