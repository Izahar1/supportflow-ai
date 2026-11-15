<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_member_cannot_access_workspace(): void
    {
        $this->seed();
        $owner = User::factory()->create();
        $outsider = User::factory()->create();
        $workspace = Workspace::create(['name' => 'Private organization', 'slug' => 'private-org', 'owner_user_id' => $owner->id, 'plan_id' => DB::table('plans')->value('id'), 'provisioning_status' => 'pending']);
        $workspace->users()->attach($owner->id, ['role' => 'owner', 'status' => 'active']);
        $this->actingAs($outsider)->get('/app/private-org/dashboard')->assertNotFound();
        $this->actingAs($outsider)->postJson('/app/private-org/billing/checkout')->assertNotFound();
    }

    public function test_platform_admin_does_not_inherit_tenant_membership(): void
    {
        $this->seed();
        $owner = User::factory()->create();
        $admin = User::factory()->create(['is_platform_admin' => true]);
        $workspace = Workspace::create(['name' => 'Private', 'slug' => 'private', 'owner_user_id' => $owner->id, 'plan_id' => DB::table('plans')->value('id'), 'provisioning_status' => 'pending']);
        $this->actingAs($admin)->get('/super-admin')->assertOk();
        $this->actingAs($admin)->get('/app/private/dashboard')->assertNotFound();
    }

    public function test_ordinary_user_cannot_access_platform_directory(): void
    {
        $this->actingAs(User::factory()->create())->get('/super-admin')->assertForbidden();
    }
}
