<?php

namespace Tests;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

trait CreatesApplication
{
    public function createApplication(): Application
    {
        $app = require __DIR__ . '/../vendor/autoload.php';

        $app = require_once __DIR__ . '/../bootstrap/app.php';

        $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

        return $app;
    }
}