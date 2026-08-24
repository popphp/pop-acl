pop-acl
=======

[![Build Status](https://github.com/popphp/pop-acl/workflows/phpunit/badge.svg)](https://github.com/popphp/pop-acl/actions)
[![Coverage Status](https://cc.popphp.org/coverage.php?comp=pop-acl)](https://cc.popphp.org/pop-acl/)

[![Join the chat at https://discord.gg/TZjgT74U7E](https://media.popphp.org/img/discord.svg)](https://discord.gg/TZjgT74U7E)

* [Overview](#overview)
* [Install](#install)
* [Quickstart](#quickstart)
* [Roles](#roles)
* [Resources](#resources)
* [Strict](#strict)
* [Multiple Roles](#multiple-roles)
  - [Multi-Strict](#multi-strict)
* [Inheritance](#inheritance)
  - [Parent-Strict](#parent-strict)
* [Removing Allow/Deny Rules](#removing-allowdeny-rules)
* [Removing Roles and Resources](#removing-roles-and-resources)
* [Wildcard Permissions](#wildcard-permissions)
* [Inspecting Effective Permissions](#inspecting-effective-permissions)
* [Assertions](#assertions)
* [Policies](#policies)

Overview
--------
`pop-acl` is a full-featured component that supports ACL/RBAC user access concepts.
Beyond allowing or denying basic user access, it provides support for roles, resources,
permissions as well as assertions and policies for fine-grain access-control.

`pop-acl` is a component of the [Pop PHP Framework](https://www.popphp.org/).

[Top](#pop-acl)

Install
-------

Install `pop-acl` using Composer.

    composer require popphp/pop-acl

Or, require it in your composer.json file

    "require": {
        "popphp/pop-acl" : "^5.0.0"
    }

[Top](#pop-acl)

Quickstart
----------

The basic concepts involve role and resource objects and then defining what permissions
are allowed (or denied) between them. The main ACL object will determine if
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
    ->allow('reader', 'page', 'read'); // Reader can only read a page

$acl->setStrict(); // Deny anything without an explicit allow rule. See [Strict](#strict).

var_dump($acl->isAllowed($admin, $page, 'add'));   // true
var_dump($acl->isAllowed($editor, $page, 'edit')); // true
var_dump($acl->isAllowed($editor, $page, 'add'));  // false
var_dump($acl->isAllowed($reader, $page, 'edit')); // false
var_dump($acl->isAllowed($reader, $page, 'read')); // true
```

Without that `setStrict()` call, an `Acl` is **permissive**: a check for which no rule
exists returns `true`, so `isAllowed($editor, $page, 'add')` would be `true` rather than
`false`. See [Strict](#strict) for the full explanation.

The above also works with the string value names of the roles and resources:

```php
var_dump($acl->isAllowed('admin', 'page', 'add'));   // true
var_dump($acl->isAllowed('editor', 'page', 'edit')); // true
var_dump($acl->isAllowed('editor', 'page', 'add'));  // false
var_dump($acl->isAllowed('reader', 'page', 'edit')); // false
var_dump($acl->isAllowed('reader', 'page', 'read')); // true
```

Roles and resources can also be passed directly into the `Acl` constructor instead of (or alongside)
`addRoles()`/`addResource()` — individually, as arrays, or a mix of both, in any order:

```php
$acl = new Acl($admin, $editor, $reader, $page);
// or
$acl = new Acl([$admin, $editor, $reader], $page);
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

Strict
------

Setting the `strict` flag strictly enforces any permissions that have been set and requires
permissions to be explicitly set. If the `strict` flag is set to `false`, then ACL checks may pass
as `true` if a rule is not explicitly set. Consider the following examples:

```php
use Pop\Acl\Acl;
use Pop\Acl\AclRole as Role;
use Pop\Acl\AclResource as Resource;

$acl = new Acl();

$admin  = new Role('admin');
$editor = new Role('editor');
$page   = new Resource('page');

$acl->addRoles([$admin, $editor]);
$acl->addResource($page);

$acl->allow($admin, $page)           // Admin can do anything to a page
    ->allow($editor, $page, 'edit'); // Editor can edit a page

var_dump($acl->isAllowed($admin, $page, 'add'));  // bool(true)
var_dump($acl->isAllowed($editor, $page, 'add')); // bool(true)
```

Both evaluations result in `true`, as there is no explicit rule preventing the editor from adding a page.
In order to prevent the editor from adding a page, you would either have to set a deny rule:

```php
use Pop\Acl\Acl;
use Pop\Acl\AclRole as Role;
use Pop\Acl\AclResource as Resource;

$acl = new Acl();

$admin  = new Role('admin');
$editor = new Role('editor');
$page   = new Resource('page');

$acl->addRoles([$admin, $editor]);
$acl->addResource($page);

$acl->allow($admin, $page)           // Admin can do anything to a page
    ->allow($editor, $page, 'edit'); // Editor can edit a page

$acl->deny($editor, $page, 'add');

var_dump($acl->isAllowed($admin, $page, 'add'));  // bool(true)
var_dump($acl->isAllowed($editor, $page, 'add')); // bool(false)
```

Or, set the ACL to strict:

```php
use Pop\Acl\Acl;
use Pop\Acl\AclRole as Role;
use Pop\Acl\AclResource as Resource;

$acl = new Acl();
$acl->setStrict();

$admin  = new Role('admin');
$editor = new Role('editor');
$page   = new Resource('page');

$acl->addRoles([$admin, $editor]);
$acl->addResource($page);

$acl->allow($admin, $page)           // Admin can do anything to a page
    ->allow($editor, $page, 'edit'); // Editor can edit a page

var_dump($acl->isAllowed($admin, $page, 'add'));  // bool(true)
var_dump($acl->isAllowed($editor, $page, 'add')); // bool(false)
```

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

we can then call the `isAllowedMulti()` method to evaluate multiple roles at once:

```php
var_dump($acl->isAllowedMulti([$admin, $editor], $page, 'add'));  // true
var_dump($acl->isAllowedMulti([$admin, $editor], $page, 'edit')); // true
```

If one of the roles is permitted to perform the requested action on the resource, it will
pass as `true`.

### Multi-Strict

When evaluating multiple roles at once, if the requirement is such that all roles must be permitted
to perform the requested action on the resource, using the `multi-strict` flag will ensure that.

```php
$acl->setMultiStrict(true);

var_dump($acl->isAllowedMulti([$admin, $editor], $page, 'add'));  // false
var_dump($acl->isAllowedMulti([$admin, $editor], $page, 'edit')); // true
```

`isAllowedMultiStrict()` is a shorthand for the same thing — it sets the `multi-strict` flag on the `Acl`
object and then calls `isAllowedMulti()`:

```php
var_dump($acl->isAllowedMultiStrict([$admin, $editor], $page, 'add'));  // false
var_dump($acl->isAllowedMultiStrict([$admin, $editor], $page, 'edit')); // true
```

There are equivalent `isDeniedMulti()` and `isDeniedMultiStrict()` methods for checking denial across
multiple roles at once. By default (loose), it passes as `true` if *any* of the roles is denied; with
`multi-strict` (either via `setMultiStrict(true)` or the `isDeniedMultiStrict()` shorthand), *all* of the
roles must be denied:

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

$acl->deny('admin', 'page', 'add')
    ->deny('editor', 'page', 'edit');

var_dump($acl->isDeniedMulti([$admin, $editor], $page, 'add'));  // true, admin is denied
var_dump($acl->isDeniedMulti([$admin, $editor], $page, 'read')); // false, neither is denied

var_dump($acl->isDeniedMultiStrict([$admin, $editor], $page, 'add')); // false, editor isn't denied 'add'
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

// Equivalent to the above, from the other direction:
// $reader->setParent($editor);

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

### Parent-Strict

In [strict](#strict) mode, an *inherited* rule (one defined on a parent role, not the role being checked
directly) is treated more loosely by default: any explicit resource/permission entry on a parent is enough
to pass the check, regardless of which specific permission was requested. Setting `parent-strict` requires
an inherited rule to match the exact permission requested, just like a rule defined directly on the role:

```php
use Pop\Acl\Acl;
use Pop\Acl\AclRole as Role;
use Pop\Acl\AclResource as Resource;

$acl = new Acl();
$acl->setStrict();

$editor = new Role('editor');
$reader = new Role('reader');
$editor->addChild($reader);
$page = new Resource('page');

$acl->addRoles([$editor, $reader]);
$acl->addResource($page);

$acl->allow($editor, $page, 'edit'); // editor (the parent) can edit

// Default parent-strict is false: reader inherits *any* explicit rule from
// editor, regardless of which specific permission was requested
var_dump($acl->isAllowed($reader, $page, 'delete')); // bool(true)

$acl->setParentStrict();

// With parent-strict enabled, an inherited rule must match the exact
// permission requested
var_dump($acl->isAllowed($reader, $page, 'delete')); // bool(false)
var_dump($acl->isAllowed($reader, $page, 'edit'));   // bool(true)
```

[Top](#pop-acl)

Removing Allow/Deny Rules
-------------------------

`removeAllowRule()` and `removeDenyRule()` revoke a previously-set rule without removing the role or
resource itself. Each accepts increasingly broad arguments: a specific permission, an entire resource
(every permission on it), or just a role (every rule for that role, on any resource):

```php
use Pop\Acl\Acl;
use Pop\Acl\AclRole as Role;
use Pop\Acl\AclResource as Resource;

$acl    = new Acl();
$editor = new Role('editor');
$page   = new Resource('page');
$post   = new Resource('post');

$acl->addRole($editor);
$acl->addResources([$page, $post]);
$acl->setStrict();

$acl->allow($editor, $page, ['edit', 'delete']);
$acl->allow($editor, $post, 'edit');

// Revoke just one permission on one resource
$acl->removeAllowRule($editor, $page, 'delete');
var_dump($acl->isAllowed($editor, $page, 'edit'));   // bool(true)
var_dump($acl->isAllowed($editor, $page, 'delete')); // bool(false)

// Revoke every permission on one resource
$acl->removeAllowRule($editor, $page);
var_dump($acl->isAllowed($editor, $page, 'edit')); // bool(false)
var_dump($acl->isAllowed($editor, $post, 'edit')); // bool(true), unaffected

// Revoke every rule for the role entirely
$acl->removeAllowRule($editor);
var_dump($acl->isAllowed($editor, $post, 'edit')); // bool(false)
```

`removeDenyRule()` works identically for `deny()` rules.

> **Note:** because an empty permission list means "unrestricted" (see [Wildcard Permissions](#wildcard-permissions)),
> removing the *last* remaining rule for a role/resource pair with `removeAllowRule()`/`removeDenyRule()`
> can leave that resource (or role) registered but with no explicit rules left — which, in strict mode, is
> indistinguishable from an intentional blanket `allow($role, $resource)`/`deny($role, $resource)`. If your
> intent is "this role should end up with zero access," prefer [`removeRole()`](#removing-roles-and-resources)
> or [`removeResource()`](#removing-roles-and-resources), which clean up that empty state as part of the removal.

[Top](#pop-acl)

Removing Roles and Resources
----------------------------

Roles and resources can be removed from the ACL object. Removing a role reparents any of its child
roles onto its own parent (or makes them root roles if it had none), and removing either a role or a
resource also purges any allow/deny rules, assertions and policies that referenced it — so a new
role or resource added later with the same name starts with a clean slate.

```php
use Pop\Acl\Acl;
use Pop\Acl\AclRole as Role;
use Pop\Acl\AclResource as Resource;

$acl = new Acl();

$admin = new Role('admin');
$page  = new Resource('page');

$acl->addRole($admin);
$acl->addResource($page);
$acl->allow($admin, $page, 'edit');

$acl->removeRole($admin);
$acl->removeResource($page);

var_dump($acl->hasRole('admin'));     // bool(false)
var_dump($acl->hasResource('page'));  // bool(false)

var_dump($acl->hasRoles());     // bool(false), no roles registered at all
var_dump($acl->hasResources()); // bool(false), no resources registered at all
```

`hasRoles()`/`hasResources()` answer "are there any roles/resources registered at all," as distinct from
`hasRole($name)`/`hasResource($name)`, which check for one specific one.

[Top](#pop-acl)

Wildcard Permissions
--------------------

The permission `'*'` is reserved and means "any permission." It can be used with `allow()` or `deny()`
to grant or block everything on a resource, and is combinable with a more specific rule — deny always
takes precedence over allow, so a wildcard allow can still be narrowed by a specific deny:

```php
use Pop\Acl\Acl;
use Pop\Acl\AclRole as Role;
use Pop\Acl\AclResource as Resource;

$acl   = new Acl();
$admin = new Role('admin');
$page  = new Resource('page');

$acl->addRole($admin);
$acl->addResource($page);

$acl->allow($admin, $page, '*')       // Admin can do anything to a page...
    ->deny($admin, $page, 'delete');  // ...except delete it

var_dump($acl->isAllowed($admin, $page, 'edit'));   // bool(true)
var_dump($acl->isAllowed($admin, $page, 'delete')); // bool(false)
```

Checking if the `'*'` permission is allowed (rather than a specific permission) still checks only whether
that permission is allowed or denied, not whether all permissions are allowed:

```php
var_dump($acl->isAllowed($admin, $page, '*')); // bool(true), because '*' itself is not denied (only 'delete' is)
```

[Top](#pop-acl)

Inspecting Effective Permissions
--------------------------------

`getAllowedPermissions()` and `getDeniedPermissions()` report the explicit, effective permission set
for a role on a resource, merged with any inherited roles. They return `['*']` if access is
unrestricted at any level (an empty permission list or an explicit `'*'`), or `[]` if no rule exists
at all. These report the explicit rule set only — they do not apply the `strict`/`multiStrict`/`parentStrict`
fallback behavior, so the result can differ from what `isAllowed()`/`isDenied()` actually return for the
same role and resource. For example, on a default (non-strict) `Acl`, a role with no rules at all will
have `getAllowedPermissions()` return `[]` while `isAllowed()` returns `true` for any permission, because
of the permissive default:

```php
use Pop\Acl\Acl;
use Pop\Acl\AclRole as Role;
use Pop\Acl\AclResource as Resource;

$acl    = new Acl();
$editor = new Role('editor');
$reader = new Role('reader');
$page   = new Resource('page');

$editor->addChild($reader);
$acl->addRole($editor);
$acl->addResource($page);

$acl->allow($editor, $page, 'read');
$acl->allow($reader, $page, 'comment');

print_r($acl->getAllowedPermissions($reader, $page)); // ['read', 'comment'] (order not guaranteed)
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
        ?AclResource $resource = null,
        mixed $permission = null
    ): bool
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

Under the hood, an assertion passed to the 4th argument of `allow()`/`deny()` is stored via `createAssertion()`
and keyed off the role/resource/permission it was registered against. `hasAssertionKey()` and `getAssertionKey()`
let you check whether an assertion is registered for a given combination, and `deleteAssertion()` removes one
directly — most applications won't need these directly, since `removeAllowRule()`/`removeDenyRule()`/`removeRole()`/
`removeResource()` already call `deleteAssertion()` for you as part of removing the rule it was attached to.

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
to the main ACL object:

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
`isAllowed()` or `isDenied()` method calls. Note that `can()` throws `Pop\Acl\Policy\Exception` if the
requested policy method doesn't exist or isn't callable on the role, so `isAllowed()`, `isDenied()`,
`evaluatePolicy()` and `evaluatePolicies()` can throw that exception too whenever policies are in use:

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
```

Which, in turn, the `evaluatePolicy()` method calls are calling the `can()` method on the
actual policy objects themselves:

```php
var_dump($admin->can('create', $page));  // true, because the user is an admin
var_dump($editor->can('create', $page)); // false, because the user is an editor (not an admin)
var_dump($admin->can('update', $page));  // false, because the admin doesn't "own" the page
var_dump($editor->can('update', $page)); // true, because the editor does "own" the page
```

`can()` also accepts a comma-separated list of methods, evaluated in order and short-circuiting on the
first one that returns `false`:

```php
// false: create() passes (admin), but update() fails (admin doesn't "own" the page)
var_dump($admin->can('create,update', $page));

// false: create() fails immediately (not an admin) -- update() is never even evaluated
var_dump($editor->can('create,update', $page));
```

[Top](#pop-acl)
