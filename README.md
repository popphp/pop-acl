pop-acl
=======

[![Build Status](https://travis-ci.org/popphp/pop-acl.svg?branch=master)](https://travis-ci.org/popphp/pop-acl)
[![Coverage Status](http://cc.popphp.org/coverage.php?comp=pop-acl)](http://cc.popphp.org/pop-acl/)

OVERVIEW
--------
`pop-acl` is a full-featured "hybrid" between the standard ACL and RBAC user access concepts.
Beyond granting or denying basic user access, it provides support for roles, resources,
inherited permissions and also assertions for fine-grain access-control.

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
First, define the assertion class, which implements the AssertionInterface. In this example, we want to check
that the user "owns" the resource via a matching user ID.

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
