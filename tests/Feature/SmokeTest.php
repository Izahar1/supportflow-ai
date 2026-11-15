<?php

namespace Tests\Feature;

use App\Domain\Tenancy\Records;
use Tests\TestCase;

class SmokeTest extends TestCase
{
    public function test_public_pages_render_without_database(): void
    {
        $this->get('/')->assertOk();
        $this->get('/login')->assertOk();
        $this->get('/register')->assertOk();
        $this->get('/up')->assertOk();
    }

    public function test_private_routes_require_authentication(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
        $this->get('/super-admin')->assertRedirect('/login');
        $this->get('/app/unknown/dashboard')->assertRedirect('/login');
    }

    public function test_paid_access_cannot_be_enabled_by_a_success_redirect(): void
    {
        $this->get('/billing/success?paid=1')->assertNotFound();
    }

    public function test_business_queries_fail_without_workspace_context(): void
    {
        $this->expectException(\LogicException::class);
        app(Records::class)->query('domain_events');
    }
}
