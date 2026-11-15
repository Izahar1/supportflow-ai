<?php

namespace App\Http\Controllers;

use App\Models\Workspace;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class BillingController extends Controller
{
    private function authorizeBilling(Request $r): void
    {
        abort_unless($r->attributes->get('workspace_role') === 'owner', 403);
    }

    public function index(Request $r, Workspace $workspace)
    {
        $this->authorizeBilling($r);

        return Inertia::render('Billing', ['workspace' => $workspace, 'product' => config('product'), 'plan' => DB::table('plans')->find($workspace->plan_id), 'configured' => false]);
    }

    public function checkout(Request $r, Workspace $workspace)
    {
        $this->authorizeBilling($r);
        abort(503, 'Paid checkout is disabled until webhook reconciliation and Stripe sandbox acceptance tests are complete.');
    }

    public function portal(Request $r, Workspace $workspace)
    {
        $this->authorizeBilling($r);
        abort_unless(config('cashier.secret') && $workspace->stripe_id, 503, 'Stripe customer is not configured.');

        return $workspace->redirectToBillingPortal(url('/app/'.$workspace->slug.'/billing'));
    }
}
