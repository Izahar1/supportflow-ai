<?php

namespace App\Domain\Tenancy;

use App\Models\Workspace;
use Illuminate\Support\Facades\DB;

final class WorkspaceContext
{
    public ?Workspace $workspace = null;

    public function initialize(Workspace $workspace): void
    {
        $this->workspace = $workspace;
        if (config('product.database_per_tenant')) {
            abort_unless($workspace->provisioning_status === 'ready' && preg_match('/^enterprise_erp_tenant_[0-9]+$/', $workspace->database_name ?? ''), 503, 'Workspace database is not ready.');
            config(['database.connections.tenant' => array_merge(config('database.connections.mysql'), ['database' => $workspace->database_name])]);
            DB::purge('tenant');
        }
    }

    public function connection(): string
    {
        return config('product.database_per_tenant') ? 'tenant' : config('database.default');
    }

    public function id(): int
    {
        if (! $this->workspace) {
            throw new \LogicException('Workspace context required.');
        }

return $this->workspace->id;
    }

    public function clear(): void
    {
        if (config('product.database_per_tenant')) {
            DB::purge('tenant');
        } $this->workspace = null;
    }
}
