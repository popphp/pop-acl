pop-acl
=======

[![Build Status](https://travis-ci.org/popphp/pop-acl.svg?branch=master)](https://travis-ci.org/popphp/pop-acl)
[![Coverage Status](http://cc.popphp.org/coverage.php?comp=pop-acl)](http://cc.popphp.org/pop-acl/)

OVERVIEW
--------
`pop-acl` is a full-featured "hybrid" between the standard ACL and RBAC user access concepts.
Beyond allowing or denying basic user access, it provides support for roles, resources,
permissions as well as assertions and policies for fine-grain access-control.

`pop-acl` is a component of the [Pop PHP Framework](http://www.popphp.org/).

INSTALL
-------

Install `pop-acl` using Composer.

    composer require popphp/pop-acl

BASIC USAGE
-----------

```php
use Pop\Acl\Acl;
use Pop\Acl\AclRole as Role;
use Pop\Acl\AclResource as Resource;

$acl = new Acl();

$admin  = new Role('admin');
$editor = new Role('editor');
$reader = new Role('reader');

$page = new Resource('page');

$acl->addRoles([$admin, $editor, $reader]);
$acl->addResource($page);

$acl->allow('admin', 'page')           // Admin can do anything to a page
    ->allow('editor', 'page', 'edit')  // Editor can only edit a page
    ->allow('reader', 'page', 'read'); // Editor can only edit a page

if ($acl->isAllowed('admin', 'page', 'add'))   { } // Returns true
if ($acl->isAllowed('editor', 'page', 'edit')) { } // Returns true
if ($acl->isAllowed('editor', 'page', 'add'))  { } // Returns false
if ($acl->isAllowed('reader', 'page', 'edit')) { } // Returns false
if ($acl->isAllowed('reader', 'page', 'read')) { } // Returns true
```

ROLE INHERITANCE
----------------

You can have roles inherit access rules as well.

```php
use Pop\Acl\Acl;
use Pop\Acl\AclRole as Role;
use Pop\Acl\AclResource as Resource;

$acl = new Acl();

$editor = new Role('editor');
$reader = new Role('reader');

// Add the $reader role as a child role of $editor.
// The role $reader will now inherit the access rules
// of the role $editor, unless explicitly overridden.
$editor->addChild($reader);

$page = new Resource('page');

$acl->addRoles([$editor, $reader]);
$acl->addResource($page);

// Neither the editor or reader can add a page
$acl->deny('editor', 'page', 'add');

// The editor can edit a page
$acl->allow('editor', 'page', 'edit');

// Both the editor or reader can read a page
$acl->allow('editor', 'page', 'read');

// Over-riding deny rule so that a reader cannot edit a page
$acl->deny('reader', 'page', 'edit');

if ($acl->isAllowed('editor', 'page', 'add'))  { } // Returns false
if ($acl->isAllowed('reader', 'page', 'add'))  { } // Returns false
if ($acl->isAllowed('editor', 'page', 'edit')) { } // Returns true
if ($acl->isAllowed('reader', 'page', 'edit')) { } // Returns false
if ($acl->isAllowed('editor', 'page', 'read')) { } // Returns true
if ($acl->isAllowed('reader', 'page', 'read')) { } // Returns true
```

USING ASSERTIONS
----------------

If you want more fine-grain control over permissions and who is allowed to do what, you can use assertions.
First, define the assertion class, which implements the `Pop\Acl\Assertion\AssertionInterface`. In this example,
we want to check that the user "owns" the resource via a matching user ID.

```php
use Pop\Acl\Acl;
use Pop\Acl\AclRole;
use Pop\Acl\AclResource;
use Pop\Acl\Assertion\AssertionInterface;

class UserCanEditPage implements AssertionInterface
{

    public function assert(
        Acl $acl, AclRole $role,
        AclResource $resource = null,
        $permission = null
    )
    {
        return ((null !== $resource) && ($role->id == $resource->user_id));
    }

}
```

Then, within the application, you can use the assertions like this:

```php
use Pop\Acl\Acl;
use Pop\Acl\AclRole as Role;
use Pop\Acl\AclResource as Resource;

$acl = new Acl();

$admin  = new Role('admin');
$editor = new Role('editor');

$page = new Resource('page');

$admin->id     = 1001;
$editor->id    = 1002;
$page->user_id = 1001;

$acl->addRoles([$admin, $editor]);
$acl->addResource($page);

$acl->allow('admin', 'page', 'add')
    ->allow('admin', 'page', 'edit', new UserCanEditPage())
    ->allow('editor', 'page', 'edit', new UserCanEditPage())

// Returns true because the assertion passes,
// the admin's ID matches the page's user ID
if ($acl->isAllowed('admin', 'page', 'edit')) { }

// Although editors can edit pages, this returns false
// because the assertion fails, as this editor's ID
// does not match the page's user ID
if ($acl->isAllowed('editor', 'page', 'edit')) { }
```

USING POLICIES
--------------

An alternate way to achieve even more specific fine-grain control is to use policies.
Similar to assertions, you have to write the policy class and it needs to use the
`Pop\Acl\Policy\PolicyTrait`. Unlike assertions that are centered around the single
`assert()` method, policies allow you to write separate methods that will be called and
evaluated via the `can()` method in the `PolicyTrait`. Consider the following simple
policy class:

```php
use Pop\Acl\AclResource;

class User
{

    use Pop\Acl\Policy\PolicyTrait;

    public $id      = null;
    public $isAdmin = null;

    public function __construct($id, $isAdmin)
    {
        $this->id      = (int)$id;
        $this->isAdmin = (bool)$isAdmin;
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

```

The above policy class can enforce whether or not a user can create, update or delete a page resource.

```php
$page   = new AclResource('page', ['id' => 2001, 'user_id' => 1002]);
$admin  = new User(1001, true);
$editor = new User(1002, false);

var_dump($admin->can('create', $page));   // Returns true, because the user is an admin
var_dump($editor->can('create', $page));  // Returns false, because the user is an editor (not an admin)
var_dump($admin->can('update', $page));   // Returns false, because the admin doesn't "own" the page
var_dump($editor->can('update', $page));  // Returns true, because the editor does "own" the page

```

For a more advanced example, the policy class can be a role class, which extends the `Pop\Acl\AclRole`
class. This allows the main Acl object to evaluate any policies added to it.

```php
use Pop\Acl\Acl;
use Pop\Acl\AclRole;
use Pop\Acl\AclResource;

class User extends AclRole
{

    use Pop\Acl\Policy\PolicyTrait;

    public function __construct($name, $id, $isAdmin)
    {
        parent::__construct($name, ['id' => $id, 'isAdmin' => $isAdmin]);
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

```

Then the user role and policy can be added to the main Acl object:

```php
$page   = new AclResource('page', ['id' => 2001, 'user_id' => 1002]);
$admin  = new User('admin', 1001, true);
$editor = new User('editor', 1002, false);

$acl = new Acl();
$acl->addRoles([$admin, $editor]);
$acl->addResource($page);
$acl->addPolicy('create', 'admin', 'page');
$acl->addPolicy('create', 'editor', 'page');
$acl->addPolicy('update', 'admin', 'page');
$acl->addPolicy('update', 'editor', 'page');

// Returns true, because the user is an admin
var_dump($acl->evaluatePolicy('create', 'admin', 'page'));  

// Returns false, because the user is an editor (not an admin)
var_dump($acl->evaluatePolicy('create', 'editor', 'page')); 

// Returns false, because the admin doesn't "own" the page
var_dump($acl->evaluatePolicy('update', 'admin', 'page'));  

// Returns true, because the editor does "own" the page
var_dump($acl->evaluatePolicy('update', 'editor', 'page')); 

```

The above example demonstrates a direct evaluation of policies by calling the `evaluatePolicy()` method. However,
if the Acl object has policies added to it, they will be evaluated when the `isAllowed()` and `isDenied()`
methods are called, based on the role and resource being passed into those methods. Using the same set up as
above, you can call the following and it will behave similarly as above:

```php
// Returns true, because the user is an admin
var_dump($acl->isAllowed('admin', 'page', 'create'));  

// Returns false, because the user is an editor (not an admin)
var_dump($acl->isAllowed('editor', 'page', 'create')); 

// Returns false, because the admin doesn't "own" the page
var_dump($acl->isAllowed('admin', 'page', 'update'));  

// Returns true, because the editor does "own" the page
var_dump($acl->isAllowed('editor', 'page', 'update')); 

```

    