<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    protected function syncBackendAcl(): void
    {
        $this->artisan('acl:sync-backend', ['--no-interaction' => true])->assertExitCode(0);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function createBackendAdmin(array $attributes = []): User
    {
        $admin = User::factory()->admin()->create($attributes);

        $this->syncBackendAcl();

        return $admin->refresh();
    }
}
