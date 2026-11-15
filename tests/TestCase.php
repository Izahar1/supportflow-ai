<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $database = config('database.connections.mysql.database');
        if (config('database.default') === 'mysql' && ! str_ends_with($database, '_test')) {
            throw new \RuntimeException('Tests require a dedicated *_test database.');
        }
    }
}
