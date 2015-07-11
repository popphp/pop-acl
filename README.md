Pop ACL
=======

[![Build Status](https://travis-ci.org/popphp/pop-acl.svg?branch=master)](https://travis-ci.org/popphp/pop-acl)

OVERVIEW
--------
Pop ACL is a component of the Pop PHP Framework 2. It is a full-featured "hybrid" between the standard
ACL and RBAC user access concepts. Beyond granting or denying basic user access, it provides support
for roles, resources, inherited permissions and also assertions for fine-grain access-control.

INSTALL
-------

Install `Pop ACL` using Composer.

    composer require popphp/pop-acl

BASIC USAGE
-----------

```php
use Pop\Acl\Acl;
use Pop\Acl\Role\Role;
use Pop\Acl\Resource\Resource;

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
use Pop\Acl\Role\Role;
use Pop\Acl\Resource\Resource;

$acl = new Acl();

$editor = new Role('editor');
$reader = new Role('reader');

$editor->addChild($reader);

$page = new Resource('page');

$acl->addRoles([$editor, $reader]);
$acl->addResource($page);

$acl->deny('editor', 'page', 'add')   // Neither the editor or reader can add a page
    ->allow('editor', 'page', 'edit') // The editor can edit a page
    ->allow('editor', 'page', 'read') // Both the editor or reader can read a page
    ->deny('reader', 'page', 'edit'); // Over-riding deny rule so that a reader cannot edit a page

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
First, define the assertion class, which extends the AssertionInterface. In this example, we want to check
that the user "owns" the resource via a matching user ID.

```php
use Pop\Acl\Acl;
use Pop\Acl\Role\AbstractRole;
use Pop\Acl\Resource\AbstractResource;

class UserCanEditPage implements AssertionInterface
{

    public function assert(
        Acl $acl, AbstractRole $role, AbstractResource $resource = null, $permission = null
    )
    {
        return ((null !== $resource) && ($role->id == $resource->user_id));
    }

}
```

Then, within the application, you can use the assertions like this:

```php
use Pop\Acl\Acl;
use Pop\Acl\Role\Role;
use Pop\Acl\Resource\Resource;

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

// Returns true because the assertion passes, the admin's ID matches the page's user ID
if ($acl->isAllowed('admin', 'page', 'edit')) { }

// Returns false because the assertion fails, the editor's ID does not match the page's user ID
if ($acl->isAllowed('editor', 'page', 'edit')) { }
```