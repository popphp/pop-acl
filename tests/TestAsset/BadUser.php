<?php

namespace Pop\Acl\Test\TestAsset;

use Pop\Acl\AclRole;
use Pop\Acl\AclResource;

class BadUser extends AclRole
{

    public function __construct($id, $isAdmin)
    {
        parent::__construct('user', ['id' => $id, 'isAdmin' => $isAdmin]);
    }

    public function create(User $user, AclResource $page)
    {
        return (($user->isAdmin) && ($page->getName() == 'page'));
    }

    public function update(User $user, AclResource $page)
    {
        return ($user->id === $page->user_id);
    }

    public function delete(User $user, AclResource $page)
    {
        return (($user->isAdmin) || ($user->id === $page->user_id));
    }

}