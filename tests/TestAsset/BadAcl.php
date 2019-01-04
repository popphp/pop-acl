<?php

namespace Pop\Acl\Test\TestAsset;

use Pop\Acl\Acl;

class BadAcl extends Acl
{

    public function badAllowedAssertion()
    {
        $this->createAssertion(new TestAllowedAssertion(), 'bad-allowed', 'editor');
    }

    public function badDeniedAssertion()
    {
        $this->createAssertion(new TestAllowedAssertion(), 'bad-denied', 'editor');
    }

    public function badGetAssertionKey()
    {
        return $this->getAssertionKey('bad-allowed', 'editor');
    }

    public function badHasAssertionKey()
    {
        return $this->hasAssertionKey('bad-allowed', 'editor');
    }

}
