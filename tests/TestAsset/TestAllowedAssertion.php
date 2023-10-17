<?php

namespace Pop\Acl\Test\TestAsset;

use Pop\Acl\Acl;
use Pop\Acl\AclRole;
use Pop\Acl\AclResource;
use Pop\Acl\Assertion\AssertionInterface;

class TestAllowedAssertion implements AssertionInterface
{

    public function assert(Acl $acl, AclRole $role, ?AclResource $resource = null, mixed $permission = null): bool
    {
        return ($role->id == 1000);
    }

}
