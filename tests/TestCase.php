<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Database\Seeders\DatabaseSeeder;

abstract class TestCase extends BaseTestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();

        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('users') && !\App\Models\User::where('email', 'test@example.com')->exists()) {
                $this->seed(DatabaseSeeder::class);
            }
        } catch (\Throwable $e) {
            // Ignore teardown seeder exceptions
        }
    }
}
