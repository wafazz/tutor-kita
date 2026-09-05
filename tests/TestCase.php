<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Inertia pages resolve through the Vite manifest, which only exists
        // after a build. Without this, page-rendering tests pass or fail
        // depending on whether `npm run dev` happens to be running.
        $this->withoutVite();
    }
}
