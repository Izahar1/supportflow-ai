<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class WorkspaceController extends Controller
{
    public function index(Request $r)
    {
        return Inertia::render('Workspaces', ['product' => config('product'), 'workspaces' => Workspace::whereHas('users', fn ($q) => $q->where('users.id', $r->user()->id)->where('workspace_user.status', 'active'))->get(['id', 'name', 'slug', 'provisioning_status'])]);
    }

    public function store(Request $r)
    {
        $v = $r->validate(['name' => 'required|string|max:100', 'slug' => 'required|alpha_dash:ascii|max:60|unique:workspaces,slug']);
        $w = DB::transaction(function () use ($r, $v) {
            $plan = DB::table('plans')->where('code', 'free')->first();
            abort_unless($plan, 503, 'Run database seeders first.');
            $w = Workspace::create([...$v, 'owner_user_id' => $r->user()->id, 'plan_id' => $plan->id, 'provisioning_status' => config('product.database_per_tenant') ? 'pending' : 'ready']);
            $w->users()->attach($r->user()->id, ['role' => 'owner', 'status' => 'active']);
            if (! config('product.database_per_tenant')) {
                DB::table('workspace_locks')->insert(['workspace_id' => $w->id]);
            }

return $w;
        });
        if (config('product.database_per_tenant')) {
            return redirect('/dashboard')->with('status', 'Workspace created. Run php artisan workspace:provision '.$w->id.' to provision its database.');
        }

        return redirect('/app/'.$w->slug.'/dashboard');
    }

    public function team(Request $r, Workspace $workspace)
    {
        abort_unless(in_array($r->attributes->get('workspace_role'), ['owner', 'admin']), 403);

        return Inertia::render('Team', ['workspace' => $workspace, 'members' => $workspace->users()->get(['users.id', 'users.name', 'users.email']), 'product' => config('product')]);
    }

    public function addMember(Request $r, Workspace $workspace)
    {
        abort_unless($r->attributes->get('workspace_role') === 'owner', 403);
        $v = $r->validate(['email' => 'required|email', 'role' => ['required', Rule::in(['admin', 'editor', 'viewer'])]]);
        $user = User::where('email', $v['email'])->first();
        abort_unless($user, 422, 'The user must register first.');
        DB::transaction(function () use ($workspace, $user, $v) {
            $locked = Workspace::whereKey($workspace->id)->lockForUpdate()->firstOrFail();
            abort_if($locked->users()->where('users.id', $user->id)->exists(), 422, 'Already a member.');
            $limits = json_decode(DB::table('plans')->where('id', $locked->plan_id)->value('limits'), true);
            abort_if($locked->users()->count() >= ($limits['members'] ?? 0), 422, 'Member limit reached.');
            $locked->users()->attach($user->id, ['role' => $v['role'], 'status' => 'active']);
        });

        return back();
    }

    public function platform(Request $r)
    {
        abort_unless($r->user()->is_platform_admin, 403);

        return Inertia::render('Platform', ['product' => config('product'), 'workspaces' => Workspace::select('id','name','slug','status','provisioning_status')->paginate(30)]);
    }
}
