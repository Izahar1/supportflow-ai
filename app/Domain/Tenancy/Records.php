<?php

namespace App\Domain\Tenancy;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

final class Records
{
    public function query(string $table): Builder
    {
        $c = app(WorkspaceContext::class);

        return DB::connection($c->connection())->table($table)->where('workspace_id', $c->id());
    }

    public function insert(string $table, array $values): int
    {
        $c = app(WorkspaceContext::class);

        return $this->query($table)->insertGetId([...$values, 'workspace_id' => $c->id(), 'created_at' => now(), 'updated_at' => now()]);
    }

    public function transaction(callable $callback): mixed
    {
        return DB::connection(app(WorkspaceContext::class)->connection())->transaction($callback);
    }

    public function event(string $type, string $table, int $id, array $payload = []): void
    {
        $this->insert('domain_events', ['event_type' => $type, 'subject_type' => $table, 'subject_id' => $id, 'actor_id' => auth()->id(), 'payload' => json_encode($payload, JSON_THROW_ON_ERROR)]);
    }
}
