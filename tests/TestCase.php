<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Pages render the same whether or not a Vite dev server is running on this machine.
        $this->withoutVite();
    }
}
