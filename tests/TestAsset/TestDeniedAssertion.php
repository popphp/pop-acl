<?php

namespace Pop\Acl\Test\TestAsset;

use Pop\Acl\Acl;
use Pop\Acl\Role\AbstractRole;
use Pop\Acl\Resource\AbstractResource;
use Pop\Acl\Assertion\AssertionInterface;

class TestDeniedAssertion implements AssertionInterface
{

    public function assert(Acl $acl, AbstractRole $role, AbstractResource $resource = null, $permission = null)
    {
        return !isset($role->id);
    }

}
