pop-acl
=======

[![Build Status](https://github.com/popphp/pop-acl/workflows/phpunit/badge.svg)](https://github.com/popphp/pop-acl/actions)
[![Coverage Status](http://cc.popphp.org/coverage.php?comp=pop-acl)](http://cc.popphp.org/pop-acl/)

[![Join the chat at https://popphp.slack.com](https://media.popphp.org/img/slack.svg)](https://popphp.slack.com)
[![Join the chat at https://discord.gg/D9JBxPa5](https://media.popphp.org/img/discord.svg)](https://discord.gg/D9JBxPa5)

* [Overview](#overview)
* [Install](#install)
* [Quickstart](#quickstart)
* [Roles](#roles)
* [Resources](#resources)
* [Multiple Roles](#multiple-roles)
  - [Strict](#strict)
* [Inheritance](#inheritance)
* [Assertions](#assertions)
* [Policies](#policies)

Overview
--------
`pop-acl` is a full-featured component that supports ACL/RBAC user access concepts.
Beyond allowing or denying basic user access, it provides support for roles, resources,
permissions as well as assertions and policies for fine-grain access-control.

`pop-acl` is a component of the [Pop PHP Framework](http://www.popphp.org/).

[Top](#pop-acl)

Install
-------

Install `pop-acl` using Composer.

    composer require popphp/pop-acl

Or, require it in your composer.json file

    "require": {
        "popphp/pop-acl" : "^4.0.0"
    }

[Top](#pop-acl)

Quickstart
----------

The basic concepts involve role and resource objects and then defining what permissions
are allowed (or disallowed) between them. The main ACL object will determine if
the requested action by a role on a resource is permitted or not.

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

var_dump($acl->isAllowed($admin, $page, 'add'));   // true
var_dump($acl->isAllowed($editor, $page, 'edit')); // true
var_dump($acl->isAllowed($editor, $page, 'add'));  // false
var_dump($acl->isAllowed($reader, $page, 'edit')); // false
var_dump($acl->isAllowed($reader, $page, 'read')); // true
```

The above also works with the string value names of the roles and resources:

```php
var_dump($acl->isAllowed('admin', 'page', 'add'));   // true
var_dump($acl->isAllowed('editor', 'page', 'edit')); // true
var_dump($acl->isAllowed('editor', 'page', 'add'));  // false
var_dump($acl->isAllowed('reader', 'page', 'edit')); // false
var_dump($acl->isAllowed('reader', 'page', 'read')); // true
```

[Top](#pop-acl)

Roles
-----

Besides being a store for a role name, a role object serves as a simple data object,
should additional data need to be stored about the role or the user currently assigned
to the role.

```php
use Pop\Acl\AclRole as Role;

$admin = new Role('admin');

$admin->id      = 1; // Define the role ID
$admin->user_id = 2; // Define the current user ID
```

This is useful for deeper evaluations like [assertions](#assertions) and [policies](#policies).

[Top](#pop-acl)

Resources
---------

Like roles, the resource object serves as a simple data object to store additional data that
may be needed.

```php
use Pop\Acl\AclResource as Resource;

$page = new Resource('page');

$page->id      = 1; // Define the role ID
$page->user_id = 2; // Define the page owner user ID
```

This is useful for deeper evaluations like [assertions](#assertions) and [policies](#policies).

[Top](#pop-acl)

Multiple Roles
--------------

If a user is assigned multiple roles at one time, those roles can all be evaluated at the same time.
If we wire up a similar example from above: 

```php
use Pop\Acl\Acl;
use Pop\Acl\AclRole as Role;
use Pop\Acl\AclResource as Resource;

$acl = new Acl();

$admin  = new Role('admin');
$editor = new Role('editor');
$page   = new Resource('page');

$acl->addRoles([$admin, $editor])
    ->addResource($page);

$acl->allow('admin', 'page')           // Admin can do anything to a page
    ->allow('editor', 'page', 'edit')  // Editor can only edit a page
```

we can then call the `isAllowedMany()` method to evaluate multiple roles at once:

```php
var_dump($acl->isAllowedMany([$admin, $editor], $page, 'add'));  // true
var_dump($acl->isAllowedMany([$admin, $editor], $page, 'edit')); // true
```

If one of the roles is permitted to perform the requested action on the resource, it will
pass as `true`.

### Strict

When evaluating multiple roles at once, if the requirement is such that all roles must be permitted
to perform the requested action on the resource, using the `strict` flag will ensure that.

```php
$acl->setStrict(true);

// Returns false because the editor isn't allowed to add pages
var_dump($acl->isAllowedMany([$admin, $editor], $page, 'add')); // false 
```

[Top](#pop-acl)

Inheritance
-----------

Roles can be constructed to inherit rules from other roles.

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

var_dump($acl->isAllowed('editor', 'page', 'add'));  // false
var_dump($acl->isAllowed('reader', 'page', 'add'));  // false
var_dump($acl->isAllowed('editor', 'page', 'edit')); // true
var_dump($acl->isAllowed('reader', 'page', 'edit')); // false
var_dump($acl->isAllowed('editor', 'page', 'read')); // true
var_dump($acl->isAllowed('reader', 'page', 'read')); // true
```

[Top](#pop-acl)

Assertions
----------

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
        // Check that the resource owner (user_id) is the same as the current role user (user_id)
        return ((null !== $resource) && ($resource->user_id == $role->user_id));
    }

}
```

Then, within the application, you can use assertions like this:

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

// Define the assertion(s) to use in the 4th parameter of the allow/deny method
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

[Top](#pop-acl)

Policies
--------

An alternate way to achieve even more specific fine-grain control is to use policies.
Similar to assertions, you have to write the policy class and it needs to use the
`Pop\Acl\Policy\PolicyTrait`. Unlike assertions that are centered around the single
`assert()` method, policies allow you to write separate methods that will be called and
evaluated via the `can()` method in the `PolicyTrait`. Consider the following example
policy class:


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

It defines specific evaluations that are required for three different actions
`create()`, `update()` and `delete()`. Then the user role and policy can be added
to the main Acl object:

```php
$page   = new AclResource('page', ['id' => 2001, 'user_id' => 1002]);
$admin  = new User('admin', 1001, true);
$editor = new User('editor', 1002, false);

$acl = new Acl();
$acl->addRoles([$admin, $editor]);
$acl->addResource($page);
$acl->addPolicy('create', $admin, $page);
$acl->addPolicy('create', $editor, $page);
$acl->addPolicy('update', $admin, $page);
$acl->addPolicy('update', $editor, $page);
```

Once the polices are added to the ACL object, they will be automatically evaluated on the
`isAllowed()` or `isDenied()` method calls:

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

A deeper look into what is happening under the hood, the ACL object is calling the method
`evaluatePolicy()` to determine if the requested action is allowed:

```php
// Returns true, because the user is an admin
var_dump($acl->evaluatePolicy('create', 'admin', 'page'));  

// Returns false, because the user is an editor (not an admin)
var_dump($acl->evaluatePolicy('create', 'editor', 'page')); 

// Returns false, because the admin doesn't "own" the page
var_dump($acl->evaluatePolicy('update', 'admin', 'page'));  

// Returns true, because the editor does "own" the page
var_dump($acl->evaluatePolicy('update', 'editor', 'page')); 
``````

Which, in turn, the `evaluatePolicy()` method calls are calling the `can()` method on the
actual policy objects themselves:

```php
var_dump($admin->can('create', $page));  // true, because the user is an admin
var_dump($editor->can('create', $page)); // false, because the user is an editor (not an admin)
var_dump($admin->can('update', $page));  // false, because the admin doesn't "own" the page
var_dump($editor->can('update', $page)); // true, because the editor does "own" the page
```

[Top](#pop-acl)
