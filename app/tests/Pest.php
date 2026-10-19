<?php

use Laravel\Pest\Laravel\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class)->in('Modules', 'Feature', 'Unit');