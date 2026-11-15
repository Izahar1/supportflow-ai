<?php

namespace App\Http\Middleware;

use App\Domain\Tenancy\WorkspaceContext;
use App\Models\Workspace;
use Closure;
use Illuminate\Http\Request;

final class ResolveWorkspace
{
    public function handle(Request $request, Closure $next)
    {
        $workspace = $request->route('workspace');
        if (! $workspace instanceof Workspace) {
            $workspace = Workspace::where('slug', $workspace)->firstOrFail();
        }
        $membership = $workspace->users()->where('users.id', $request->user()->id)->wherePivot('status', 'active')->first();
        abort_unless($membership, 404);
        // Suspension permits historical reads while blocking new mutations.
        if (! $request->isMethod('GET')) {
            abort_unless($workspace->status === 'active', 403, 'Workspace is restricted.');
        }
        $request->attributes->set('workspace_role', $membership->pivot->role);
        $context = app(WorkspaceContext::class);
        try {
            $context->initialize($workspace);

            return $next($request);
        } finally {
            $context->clear();
        }
    }
}
