<?php

namespace Rector\Tests\Order\Rector\Class_\OrderPrivateMethodsByUseRector\Fixture;

class SkipDifferentAmountOfMethods
{
    public function run()
    {
        $this->call1();
        $this->call2();
        $this->publicCall1();
    }

    public function publicCall1()
    {
    }

    private function call2()
    {
    }

    private function call1()
    {
    }

    private function call3()
    {

    }
}

?>
