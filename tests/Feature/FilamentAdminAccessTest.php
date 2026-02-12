<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FilamentAdminAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_filament_dashboard(): void
    {
        $adminRole = Role::findOrCreate('admin', 'web');

        $user = User::factory()->create([
            // `password` is casted as "hashed" in the User model.
            'password' => 'password',
        ]);
        $user->assignRole($adminRole);

        $this->actingAs($user)
            ->get('/admin')
            ->assertOk();
    }

    public function test_customer_cannot_access_filament_dashboard(): void
    {
        $user = User::factory()->create([
            'password' => 'password',
        ]);

        $this->actingAs($user)
            ->get('/admin')
            ->assertForbidden();
    }
}

