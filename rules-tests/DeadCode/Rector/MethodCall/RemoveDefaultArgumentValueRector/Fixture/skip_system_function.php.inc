<?php

namespace Rector\Tests\DeadCode\Rector\MethodCall\RemoveDefaultArgumentValueRector\Fixture;

class SkipSystemFunction
{
    public function run()
    {
        trigger_error('Error message', E_USER_NOTICE);
    }
}
