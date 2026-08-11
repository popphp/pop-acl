<?php

namespace Pop\Acl\Test;

use Pop\Acl\Acl;
use Pop\Acl\AclRole;
use Pop\Acl\AclResource;
use PHPUnit\Framework\TestCase;

class AclTest extends TestCase
{

    public function testConstructor()
    {
        $acl = new Acl([new AclRole('editor')], [new AclResource('page')]);
        $acl = new Acl(new AclRole('editor'), new AclResource('page'));
        $this->assertInstanceOf('Pop\Acl\Acl', $acl);
        $this->assertEquals('editor', $acl->getRole('editor')->getName());
        $this->assertEquals(1, count($acl->getRoles()));
        $this->assertTrue($acl->hasRole('editor'));
        $this->assertEquals('page', $acl->getResource('page')->getName());
        $this->assertEquals(1, count($acl->getResources()));
        $this->assertTrue($acl->hasResource('page'));
    }

    public function testSetStrict()
    {
        $acl = new Acl();
        $acl->setStrict();
        $this->assertTrue($acl->isStrict());
        $acl->setStrict(false, true);
        $this->assertFalse($acl->isStrict());
        $this->assertTrue($acl->isMultiStrict());
    }

    public function testSetMultiStrict()
    {
        $acl = new Acl();
        $acl->setMultiStrict();
        $this->assertTrue($acl->isMultiStrict());
        $acl->setMultiStrict(false);
        $this->assertFalse($acl->isMultiStrict());
    }

    public function testSetParentStrict()
    {
        $acl = new Acl();
        $acl->setParentStrict();
        $this->assertTrue($acl->isParentStrict());
        $acl->setParentStrict(false);
        $this->assertFalse($acl->isParentStrict());
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

    public function testHasRoles()
    {
        $acl = new Acl();
        $this->assertFalse($acl->hasRoles());
        $acl->addRole(new AclRole('editor'));
        $this->assertTrue($acl->hasRoles());
    }

    public function testHasResources()
    {
        $acl = new Acl();
        $this->assertFalse($acl->hasResources());
        $acl->addResource(new AclResource('page'));
        $this->assertTrue($acl->hasResources());
    }

    public function testGetRoleReturnsNullWhenNotFound()
    {
        $acl = new Acl();
        $this->assertNull($acl->getRole('ghost'));
    }

    public function testGetResourceReturnsNullWhenNotFound()
    {
        $acl = new Acl();
        $this->assertNull($acl->getResource('ghost'));
    }

    public function testAllowBadRoleType()
    {
        $this->expectException('InvalidArgumentException');

        $acl = new Acl();
        $acl->allow(['bad role']);
    }

    public function testAllowRoleNotAdded()
    {
        $this->expectException('Pop\Acl\Exception');

        $acl = new Acl();
        $acl->allow('editor');
    }

    public function testAllowBadResourceType()
    {
        $this->expectException('InvalidArgumentException');

        $editor = new AclRole('editor');
        $acl    = new Acl($editor);
        $acl->allow('editor', ['bad resource']);
    }

    public function testAllowResourceNotAdded()
    {
        $this->expectException('Pop\Acl\Exception');

        $editor = new AclRole('editor');
        $acl    = new Acl($editor);
        $acl->allow('editor', 'page');
    }

    public function testIsAllowed()
    {
        $publisher  = new AclRole('publisher');
        $page       = new AclResource('page');
        $acl        = new Acl($page);
        $acl->addRole($publisher);
        $acl->deny('publisher', 'page', 'delete');
        $this->assertTrue($acl->isAllowed('publisher', 'page', 'read'));
        $this->assertFalse($acl->isAllowed('publisher', 'page', 'delete'));
    }

    public function testIsAllowedStrict()
    {
        $admin      = new AclRole('admin');
        $publisher  = new AclRole('publisher');
        $editor     = new AclRole('editor');
        $editor->id = 1000;
        $reader     = new AclRole('reader');
        $page       = new AclResource('page');
        $acl        = new Acl($editor, $page);
        $acl->setStrict();
        $acl->addRole($admin);
        $acl->addRole($publisher);
        $acl->addRole($reader);
        $acl->allow('admin');
        $acl->allow('publisher', 'page');
        $acl->allow('editor', 'page', 'edit', new TestAsset\TestAllowedAssertion());
        $this->assertTrue($acl->isAllowed('editor', 'page', 'edit'));
        $this->assertTrue($acl->isAllowed('admin'));
        $this->assertTrue($acl->isAllowed('publisher', 'page'));
        $this->assertFalse($acl->isAllowed('reader', 'page', 'edit'));
    }

    public function testIsAllowedMulti1()
    {
        $acl = new Acl();

        $admin  = new AclRole('admin');
        $editor = new AclRole('editor');
        $reader = new AclRole('reader');

        $page = new AclResource('page');

        $acl->addRoles([$admin, $editor, $reader]);
        $acl->addResource($page);

        $acl->allow('admin', 'page', 'create')
            ->allow('editor', 'page', 'edit')
            ->allow('reader', 'page', 'read');

        $user = new \stdClass();
        $user->roles = [
            1 => 'admin',
            2 => 'editor'
        ];

        $this->assertTrue($acl->isAllowedMulti($user->roles, 'page', 'create'));
    }

    public function testIsAllowedMulti2()
    {
        $acl = new Acl();

        $admin  = new AclRole('admin');
        $editor = new AclRole('editor');
        $reader = new AclRole('reader');

        $page = new AclResource('page');

        $acl->addRoles([$admin, $editor, $reader]);
        $acl->addResource($page);

        $acl->allow('admin', 'page', 'create')
            ->deny('editor', 'page', 'create')
            ->allow('reader', 'page', 'read');

        $user = new \stdClass();
        $user->roles = [
            1 => 'admin',
            2 => 'editor'
        ];

        $this->assertFalse($acl->isAllowedMulti($user->roles, 'page', 'create'));
    }

    public function testIsAllowedMultiStrict()
    {
        $acl = new Acl();

        $admin  = new AclRole('admin');
        $editor = new AclRole('editor');
        $reader = new AclRole('reader');

        $page = new AclResource('page');

        $acl->addRoles([$admin, $editor, $reader]);
        $acl->addResource($page);

        $acl->allow('admin', 'page', 'create')
            ->allow('editor', 'page', 'edit')
            ->allow('reader', 'page', 'read');

        $user = new \stdClass();
        $user->roles = [
            1 => 'admin',
            2 => 'editor'
        ];

        $this->assertFalse($acl->isAllowedMultiStrict($user->roles, 'page', 'create'));
    }

    public function testIsDeniedMulti()
    {
        $acl = new Acl();

        $admin  = new AclRole('admin');
        $editor = new AclRole('editor');
        $reader = new AclRole('reader');

        $page = new AclResource('page');

        $acl->addRoles([$admin, $editor, $reader]);
        $acl->addResource($page);

        $acl->deny('admin', 'page', 'create')
            ->deny('editor', 'page', 'edit')
            ->deny('reader', 'page', 'read');

        $user = new \stdClass();
        $user->roles = [
            1 => 'admin',
            2 => 'editor'
        ];

        $this->assertTrue($acl->isDeniedMulti($user->roles, 'page', 'create'));
    }

    public function testIsDeniedMultiStrict1()
    {
        $acl = new Acl();

        $admin  = new AclRole('admin');
        $editor = new AclRole('editor');
        $reader = new AclRole('reader');

        $page = new AclResource('page');

        $acl->addRoles([$admin, $editor, $reader]);
        $acl->addResource($page);

        $acl->deny('admin', 'page', 'create')
            ->deny('editor', 'page', 'create')
            ->deny('reader', 'page', 'read');

        $user = new \stdClass();
        $user->roles = [
            1 => 'admin',
            2 => 'editor'
        ];

        $this->assertTrue($acl->isDeniedMultiStrict($user->roles, 'page', 'create'));
    }

    public function testIsDeniedMultiStrict2()
    {
        $acl = new Acl();

        $admin  = new AclRole('admin');
        $editor = new AclRole('editor');
        $reader = new AclRole('reader');

        $page = new AclResource('page');

        $acl->addRoles([$admin, $editor, $reader]);
        $acl->addResource($page);

        $acl->deny('admin', 'page', 'create')
            ->deny('editor', 'page', 'edit')
            ->deny('reader', 'page', 'read');

        $user = new \stdClass();
        $user->roles = [
            1 => 'admin',
            2 => 'editor'
        ];

        $this->assertFalse($acl->isDeniedMultiStrict($user->roles, 'page', 'create'));
    }

    public function testIsAllowedWithAssertionNoPermission()
    {
        $editor     = new AclRole('editor');
        $editor->id = 1000;
        $page       = new AclResource('page');
        $acl        = new Acl($editor, $page);
        $acl->allow('editor', 'page', null, new TestAsset\TestAllowedAssertion());
        $this->assertTrue($acl->isAllowed('editor', 'page'));
    }

    public function testIsAllowedWithAssertionNoResource()
    {
        $editor     = new AclRole('editor');
        $editor->id = 1000;
        $acl        = new Acl($editor);
        $acl->allow('editor', null, null, new TestAsset\TestAllowedAssertion());
        $this->assertTrue($acl->isAllowed('editor'));
    }

    public function testIsAllowedRoleNotAdded()
    {
        $this->expectException('Pop\Acl\Exception');

        $acl = new Acl();
        $acl->isAllowed('editor');
    }

    public function testIsAllowedResourceNotAdded()
    {
        $this->expectException('Pop\Acl\Exception');

        $acl = new Acl(new AclRole('editor'));
        $acl->isAllowed('editor', 'page');
    }

    public function testRemoveAllowRule()
    {
        $editor     = new AclRole('editor');
        $editor->id = 1000;
        $page       = new AclResource('page');
        $acl        = new Acl($editor, $page);
        $acl->setStrict();
        $acl->allow('editor', 'page', 'edit', new TestAsset\TestAllowedAssertion());
        $this->assertTrue($acl->isAllowed('editor', 'page', 'edit'));
        $acl->removeAllowRule('editor', 'page', 'edit');
        $acl->removeAllowRule('editor', 'page');
        $acl->removeAllowRule('editor');
        $this->assertFalse($acl->isAllowed('editor', 'page', 'edit'));
    }

    public function testRemoveAllowRuleBadRoleType()
    {
        $this->expectException('InvalidArgumentException');

        $editor = new AclRole('editor');
        $page   = new AclResource('page');
        $acl    = new Acl($editor, $page);
        $acl->allow('editor', 'page', 'edit');
        $acl->removeAllowRule(['bad role']);
    }

    public function testRemoveAllowRuleRoleNotAdded()
    {
        $this->expectException('Pop\Acl\Exception');

        $editor = new AclRole('editor');
        $page   = new AclResource('page');
        $acl    = new Acl($editor, $page);
        $acl->allow('editor', 'page', 'edit');
        $acl->removeAllowRule('admin');
    }

    public function testRemoveAllowRuleBadResourceType()
    {
        $this->expectException('InvalidArgumentException');

        $editor = new AclRole('editor');
        $page   = new AclResource('page');
        $acl    = new Acl($editor, $page);
        $acl->allow('editor', 'page', 'edit');
        $acl->removeAllowRule('editor', ['bad resource']);
    }

    public function testRemoveAllowRuleResourceNotAdded()
    {
        $this->expectException('Pop\Acl\Exception');

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
        $acl->setStrict();
        $acl->allow('reader', 'page', 'read');
        $this->assertFalse($acl->isAllowed('reader', 'page', 'edit'));
    }

    public function testIsNotAllowedAssertion()
    {
        $editor     = new AclRole('editor');
        $editor->id = 1;
        $page       = new AclResource('page');
        $acl        = new Acl($editor, $page);
        $acl->allow('editor', 'page', 'edit', new TestAsset\TestAllowedAssertion());
        $this->assertFalse($acl->isAllowed('editor', 'page', 'edit'));
    }

    public function testDenyBadRoleType()
    {
        $this->expectException('InvalidArgumentException');

        $acl = new Acl();
        $acl->deny(['bad role']);
    }

    public function testDenyRoleNotAdded()
    {
        $this->expectException('Pop\Acl\Exception');

        $acl = new Acl();
        $acl->deny('editor');
    }

    public function testDenyBadResourceType()
    {
        $this->expectException('InvalidArgumentException');

        $editor = new AclRole('editor');
        $acl    = new Acl($editor);
        $acl->deny('editor', ['bad resource']);
    }

    public function testDenyResourceNotAdded()
    {
        $this->expectException('Pop\Acl\Exception');

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
        $acl->deny('editor', 'page', 'edit', new TestAsset\TestDeniedAssertion());
        $this->assertTrue($acl->isDenied('editor', 'page', 'edit'));
    }

    public function testIsDeniedRoleNotAdded()
    {
        $this->expectException('Pop\Acl\Exception');

        $acl = new Acl();
        $acl->isDenied('editor');
    }

    public function testIsDeniedResourceNotAdded()
    {
        $this->expectException('Pop\Acl\Exception');

        $acl = new Acl(new AclRole('editor'));
        $acl->isDenied('editor', 'page');
    }

    public function testIsDeniedWithAssertionNoPermission()
    {
        $editor     = new AclRole('editor');
        $page       = new AclResource('page');
        $acl        = new Acl($editor, $page);
        $acl->deny('editor', 'page', null, new TestAsset\TestDeniedAssertion());
        $this->assertTrue($acl->isDenied('editor', 'page'));
    }

    public function testIsDeniedWithAssertionNoResource()
    {
        $editor     = new AclRole('editor');
        $acl        = new Acl($editor);
        $acl->deny('editor', null, null, new TestAsset\TestDeniedAssertion());
        $this->assertTrue($acl->isDenied('editor'));
    }

    public function testRemoveDenyRule()
    {
        $editor     = new AclRole('editor');
        $page       = new AclResource('page');
        $acl        = new Acl($editor, $page);
        $acl->deny('editor', 'page', 'edit', new TestAsset\TestDeniedAssertion());
        $this->assertTrue($acl->isDenied('editor', 'page', 'edit'));
        $acl->removeDenyRule('editor', 'page', 'edit');
        $acl->removeDenyRule('editor', 'page');
        $acl->removeDenyRule('editor');
        $this->assertFalse($acl->isDenied('editor', 'page', 'edit'));
    }

    public function testRemoveDenyRuleBadRoleType()
    {
        $this->expectException('InvalidArgumentException');

        $editor = new AclRole('editor');
        $page   = new AclResource('page');
        $acl    = new Acl($editor, $page);
        $acl->deny('editor', 'page', 'edit');
        $acl->removeDenyRule(['bad role']);
    }

    public function testRemoveDenyRuleRoleNotAdded()
    {
        $this->expectException('Pop\Acl\Exception');

        $editor = new AclRole('editor');
        $page   = new AclResource('page');
        $acl    = new Acl($editor, $page);
        $acl->deny('editor', 'page', 'edit');
        $acl->removeDenyRule('admin');
    }

    public function testRemoveDenyRuleBadResourceType()
    {
        $this->expectException('InvalidArgumentException');

        $editor = new AclRole('editor');
        $page   = new AclResource('page');
        $acl    = new Acl($editor, $page);
        $acl->deny('editor', 'page', 'edit');
        $acl->removeDenyRule('editor', ['bad resource']);
    }

    public function testRemoveDenyRuleResourceNotAdded()
    {
        $this->expectException('Pop\Acl\Exception');

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

    public function testBadAllowedAssertionKey()
    {
        $this->expectException('InvalidArgumentException');

        $acl = new TestAsset\BadAcl();
        $acl->badAllowedAssertion();
    }

    public function testBadDeniedAssertionKey()
    {
        $this->expectException('InvalidArgumentException');

        $acl = new TestAsset\BadAcl();
        $acl->badDeniedAssertion();
    }

    public function testBadGetAssertionKey()
    {
        $this->expectException('InvalidArgumentException');

        $acl = new TestAsset\BadAcl();
        $acl->badGetAssertionKey();
    }

    public function testBadHasAssertionKey()
    {
        $this->expectException('InvalidArgumentException');

        $acl = new TestAsset\BadAcl();
        $acl->badHasAssertionKey();
    }

    public function testEvaluatePolicyTrue()
    {
        $acl = new Acl();
        $acl->addRole(new TestAsset\User(1001, true));
        $acl->addResource(new AclResource('page', ['id' => 2001, 'user_id' => 1001]));
        $acl->addPolicy('create', 'user', 'page');

        $this->assertTrue($acl->evaluatePolicies());
    }

    public function testEvaluatePolicyFalse()
    {
        $acl = new Acl();
        $acl->addRole(new TestAsset\User(1001, false));
        $acl->addResource(new AclResource('page', ['id' => 2001, 'user_id' => 1001]));
        $acl->addPolicy('create', 'user', 'page');

        $this->assertFalse($acl->evaluatePolicies());
    }

    public function testEvaluatePolicyException()
    {
        $this->expectException('Pop\Acl\Exception');

        $acl = new Acl();
        $acl->addRole(new TestAsset\BadUser(1001, false));
        $acl->addResource(new AclResource('page', ['id' => 2001, 'user_id' => 1001]));
        $acl->addPolicy('create', 'user', 'page');
        $this->assertFalse($acl->evaluatePolicies());
    }

    public function testEvaluatePolicyDirect()
    {
        $acl   = new Acl();
        $admin = new TestAsset\User(1001, true);
        $page  = new AclResource('page', ['id' => 2001, 'user_id' => 1001]);

        $acl->addRole($admin);
        $acl->addResource($page);

        // No addPolicy() call needed -- evaluatePolicy() dispatches straight to
        // the role's can() method, independent of the registered-policies list.
        $this->assertTrue($acl->evaluatePolicy('create', $admin, $page));
        $this->assertTrue($acl->evaluatePolicy('create', 'user', 'page'));
    }

    public function testEvaluatePolicyIsAllowed()
    {
        $acl = new Acl();
        $acl->addRole(new TestAsset\User(1001, true));
        $acl->addResource(new AclResource('page', ['id' => 2001, 'user_id' => 1001]));
        $acl->addPolicy('create', 'user', 'page');

        $this->assertTrue($acl->isAllowed('user', 'page'));
    }

    public function testEvaluatePolicyIsDenied()
    {
        $acl = new Acl();
        $acl->addRole(new TestAsset\User(1001, false));
        $acl->addResource(new AclResource('page', ['id' => 2001, 'user_id' => 1001]));
        $acl->addPolicy('create', 'user', 'page');

        $this->assertTrue($acl->isDenied('user', 'page'));
    }

    public function testIsAllowedFallsBackToRulesWhenPolicyExistsForOtherRole()
    {
        $acl    = new Acl();
        $admin  = new AclRole('admin');
        $editor = new TestAsset\User(1001, false);
        $page   = new AclResource('page', ['id' => 2001, 'user_id' => 1001]);

        $acl->setStrict();
        $acl->addRoles([$admin, $editor]);
        $acl->addResource($page);
        $acl->allow($admin, $page, 'update');

        // Policy only registered for 'user', not 'admin'
        $acl->addPolicy('create', $editor, $page);

        $this->assertTrue($acl->isAllowed($admin, $page, 'update'));
        $this->assertFalse($acl->isAllowed($admin, $page, 'delete'));
    }

    public function testIsDeniedFallsBackToRulesWhenPolicyExistsForOtherRole()
    {
        $acl    = new Acl();
        $admin  = new AclRole('admin');
        $editor = new TestAsset\User(1001, false);
        $page   = new AclResource('page', ['id' => 2001, 'user_id' => 1001]);

        $acl->addRoles([$admin, $editor]);
        $acl->addResource($page);
        $acl->deny($admin, $page, 'update');

        // Policy only registered for 'user', not 'admin'
        $acl->addPolicy('create', $editor, $page);

        $this->assertTrue($acl->isDenied($admin, $page, 'update'));
        $this->assertFalse($acl->isDenied($admin, $page, 'delete'));
    }

    public function testRemoveRole()
    {
        $acl   = new Acl();
        $admin = new AclRole('admin');
        $page  = new AclResource('page');

        $acl->addRole($admin);
        $acl->addResource($page);
        $acl->allow($admin, $page, 'edit');

        $acl->removeRole($admin);

        $this->assertFalse($acl->hasRole('admin'));
    }

    public function testRemoveRoleThrowsForUnregisteredRole()
    {
        $this->expectException('Pop\Acl\Exception');

        $acl = new Acl();
        $acl->removeRole('ghost');
    }

    public function testRemoveRoleReparentsChildrenToGrandparent()
    {
        $acl     = new Acl();
        $admin   = new AclRole('admin');
        $editor  = new AclRole('editor');
        $reader  = new AclRole('reader');

        $admin->addChild($editor);
        $editor->addChild($reader);

        $acl->addRole($admin); // pulls in editor and reader via traverseChildren()

        $acl->removeRole($editor);

        $this->assertFalse($acl->hasRole('editor'));
        $this->assertTrue($reader->hasParent());
        $this->assertTrue(($reader->getParent() === $admin));
        $this->assertTrue($admin->hasChildren());
        $this->assertEquals(1, count($admin->getChildren()));
    }

    public function testRemoveRoleWithNoParentOrphansChildrenAsRoots()
    {
        $acl    = new Acl();
        $editor = new AclRole('editor');
        $reader = new AclRole('reader');

        $editor->addChild($reader);
        $acl->addRole($editor);

        $acl->removeRole($editor);

        $this->assertFalse($reader->hasParent());
        $this->assertNull($reader->getParent());
    }

    public function testRemoveRolePurgesRulesAssertionsAndPolicies()
    {
        $acl    = new Acl();
        $editor = new TestAsset\User(1001, true);
        $page   = new AclResource('page', ['id' => 2001, 'user_id' => 1001]);

        $acl->addRole($editor);
        $acl->addResource($page);
        $acl->allow($editor, $page, 'edit', new TestAsset\TestAllowedAssertion());
        $acl->addPolicy('create', $editor, $page);

        $acl->removeRole($editor);

        // Re-add a fresh role with the same name and confirm it starts clean
        $acl->addRole(new AclRole('user'));
        $acl->addResource($page);
        $acl->setStrict(); // so isAllowed() requires an explicit rule instead of its permissive default

        $this->assertFalse($acl->hasAssertionKey('allowed', 'user', 'page', 'edit'));
        $this->assertFalse($acl->isAllowed('user', 'page', 'create'));
    }

    public function testRemoveRolePurgesUnrestrictedResourceAssertion()
    {
        // allow($role, $resource) with no permission arg stores an empty permission
        // list for that resource -- a different purge branch in removeRole() than
        // the per-permission one exercised by testRemoveRolePurgesRulesAssertionsAndPolicies.
        $acl   = new Acl();
        $admin = new AclRole('admin');
        $page  = new AclResource('page');

        $acl->addRole($admin);
        $acl->addResource($page);
        $acl->allow($admin, $page, null, new TestAsset\TestAllowedAssertion());

        $this->assertTrue($acl->hasAssertionKey('allowed', 'admin', 'page'));

        $acl->removeRole($admin);

        $this->assertFalse($acl->hasAssertionKey('allowed', 'admin', 'page'));
    }

    public function testRemoveResource()
    {
        $acl   = new Acl();
        $admin = new AclRole('admin');
        $page  = new AclResource('page');

        $acl->addRole($admin);
        $acl->addResource($page);
        $acl->allow($admin, $page, 'edit');

        $acl->removeResource($page);

        $this->assertFalse($acl->hasResource('page'));
    }

    public function testRemoveResourceThrowsForUnregisteredResource()
    {
        $this->expectException('Pop\Acl\Exception');

        $acl = new Acl();
        $acl->removeResource('ghost');
    }

    public function testRemoveResourcePurgesRulesAssertionsAndPolicies()
    {
        $acl    = new Acl();
        $editor = new TestAsset\User(1001, true);
        $page   = new AclResource('page', ['id' => 2001, 'user_id' => 1001]);

        $acl->addRole($editor);
        $acl->addResource($page);
        $acl->allow($editor, $page, 'edit', new TestAsset\TestAllowedAssertion());
        $acl->addPolicy('create', $editor, $page);

        $acl->removeResource($page);

        // Re-add a fresh resource with the same name and confirm it starts clean
        $acl->addResource(new AclResource('page'));
        $acl->setStrict(); // so isAllowed() requires an explicit rule instead of its permissive default

        $this->assertFalse($acl->hasAssertionKey('allowed', 'user', 'page', 'edit'));
        $this->assertFalse($acl->isAllowed('user', 'page', 'create'));
    }

    public function testRemoveResourcePurgesUnrestrictedResourceAssertion()
    {
        // Same "empty permission list" purge branch as
        // testRemoveRolePurgesUnrestrictedResourceAssertion, but on the removeResource() side.
        $acl   = new Acl();
        $admin = new AclRole('admin');
        $page  = new AclResource('page');

        $acl->addRole($admin);
        $acl->addResource($page);
        $acl->allow($admin, $page, null, new TestAsset\TestAllowedAssertion());

        $this->assertTrue($acl->hasAssertionKey('allowed', 'admin', 'page'));

        $acl->removeResource($page);

        $this->assertFalse($acl->hasAssertionKey('allowed', 'admin', 'page'));
    }

    public function testAllowWildcardGrantsAnyPermission()
    {
        $acl   = new Acl();
        $admin = new AclRole('admin');
        $page  = new AclResource('page');

        $acl->addRole($admin);
        $acl->addResource($page);
        $acl->setStrict(); // force the explicit-rule-matching path; non-strict mode's permissive
                            // default would make this pass even without wildcard support
        $acl->allow($admin, $page, '*');

        $this->assertTrue($acl->isAllowed($admin, $page, 'edit'));
        $this->assertTrue($acl->isAllowed($admin, $page, 'delete'));
        $this->assertTrue($acl->isAllowed($admin, $page, ['edit', 'delete']));
    }

    public function testAllowWildcardStillLosesToSpecificDeny()
    {
        $acl   = new Acl();
        $admin = new AclRole('admin');
        $page  = new AclResource('page');

        $acl->addRole($admin);
        $acl->addResource($page);
        $acl->setStrict(); // see note in testAllowWildcardGrantsAnyPermission
        $acl->allow($admin, $page, '*');
        $acl->deny($admin, $page, 'delete');

        $this->assertTrue($acl->isAllowed($admin, $page, 'edit'));
        $this->assertFalse($acl->isAllowed($admin, $page, 'delete'));
    }

    public function testDenyWildcardDeniesAnyPermission()
    {
        $acl   = new Acl();
        $admin = new AclRole('admin');
        $page  = new AclResource('page');

        $acl->addRole($admin);
        $acl->addResource($page);
        $acl->deny($admin, $page, '*');

        $this->assertTrue($acl->isDenied($admin, $page, 'edit'));
        $this->assertTrue($acl->isDenied($admin, $page, 'delete'));
        $this->assertFalse($acl->isAllowed($admin, $page, 'edit'));
    }

    public function testGetAllowedPermissionsNoRule()
    {
        $acl   = new Acl();
        $admin = new AclRole('admin');
        $page  = new AclResource('page');

        $acl->addRole($admin);
        $acl->addResource($page);

        $this->assertEquals([], $acl->getAllowedPermissions($admin, $page));
    }

    public function testGetAllowedPermissionsDirect()
    {
        $acl   = new Acl();
        $admin = new AclRole('admin');
        $page  = new AclResource('page');

        $acl->addRole($admin);
        $acl->addResource($page);
        $acl->allow($admin, $page, ['edit', 'delete']);

        $result = $acl->getAllowedPermissions($admin, $page);
        sort($result);
        $this->assertEquals(['delete', 'edit'], $result);
    }

    public function testGetAllowedPermissionsMergesWithInheritance()
    {
        $acl    = new Acl();
        $editor = new AclRole('editor');
        $reader = new AclRole('reader');
        $page   = new AclResource('page');

        $editor->addChild($reader);
        $acl->addRole($editor);
        $acl->addResource($page);
        $acl->allow($editor, $page, 'read');
        $acl->allow($reader, $page, 'comment');

        $result = $acl->getAllowedPermissions($reader, $page);
        sort($result);
        $this->assertEquals(['comment', 'read'], $result);
    }

    public function testGetAllowedPermissionsUnrestrictedReturnsWildcardSentinel()
    {
        $acl   = new Acl();
        $admin = new AclRole('admin');
        $page  = new AclResource('page');

        $acl->addRole($admin);
        $acl->addResource($page);
        $acl->allow($admin, $page);

        $this->assertEquals(['*'], $acl->getAllowedPermissions($admin, $page));
    }

    public function testGetAllowedPermissionsExplicitWildcardReturnsWildcardSentinel()
    {
        $acl   = new Acl();
        $admin = new AclRole('admin');
        $page  = new AclResource('page');

        $acl->addRole($admin);
        $acl->addResource($page);
        $acl->allow($admin, $page, ['edit', '*']);

        $this->assertEquals(['*'], $acl->getAllowedPermissions($admin, $page));
    }

    public function testGetAllowedPermissionsWildcardWhenRoleHasNoResourcesRegisteredAtAll()
    {
        $acl   = new Acl();
        $admin = new AclRole('admin');
        $page  = new AclResource('page');

        $acl->addRole($admin);
        $acl->addResource($page);
        $acl->allow($admin); // no resource arg at all -- role has zero resources registered

        $this->assertEquals(['*'], $acl->getAllowedPermissions($admin, $page));
    }

    public function testGetDeniedPermissionsDirect()
    {
        $acl    = new Acl();
        $editor = new AclRole('editor');
        $page   = new AclResource('page');

        $acl->addRole($editor);
        $acl->addResource($page);
        $acl->deny($editor, $page, 'delete');

        $this->assertEquals(['delete'], $acl->getDeniedPermissions($editor, $page));
    }

    public function testGetDeniedPermissionsUnrestrictedReturnsWildcardSentinel()
    {
        // deny($role, $resource) with no permission arg must be the *first* call for that
        // role/resource pair to produce the empty-list "unrestricted" state — deny() only
        // initializes the permission list to [] the first time that resource key is set;
        // calling it again afterward with no permission is a no-op on an existing list.
        $acl    = new Acl();
        $editor = new AclRole('editor');
        $page   = new AclResource('page');

        $acl->addRole($editor);
        $acl->addResource($page);
        $acl->deny($editor, $page);

        $this->assertEquals(['*'], $acl->getDeniedPermissions($editor, $page));
    }

    public function testPolicyCanThrowsOnUnknownMethod()
    {
        $this->expectException('Pop\Acl\Policy\Exception');

        $user = new TestAsset\User(1001, true);
        $user->can('nonexistentMethod');
    }

    public function testPolicyCanThrowsOnUnknownMethodInCommaList()
    {
        $this->expectException('Pop\Acl\Policy\Exception');

        $user = new TestAsset\User(1001, true);
        $user->can('create,nonexistentMethod');
    }

    public function testPolicyCanEvaluatesMultipleValidMethodsInSequence()
    {
        $user = new TestAsset\User(1001, true); // admin AND owner (id matches page's user_id)
        $page = new AclResource('page', ['user_id' => 1001]);

        $this->assertTrue($user->can('create,update', $page));
    }

    public function testPolicyCanShortCircuitsOnFirstFalseMethod()
    {
        $user = new TestAsset\User(1001, false); // not admin, but is the owner
        $page = new AclResource('page', ['user_id' => 1001]);

        // update() alone would pass (ownership matches) -- proves it's never
        // reached once create() (evaluated first) returns false.
        $this->assertTrue($user->can('update', $page));
        $this->assertFalse($user->can('create', $page));
        $this->assertFalse($user->can('create,update', $page));
    }
}
