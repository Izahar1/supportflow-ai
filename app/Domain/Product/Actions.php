<?php

namespace App\Domain\Product;

use App\Domain\Tenancy\Records;
use Illuminate\Http\Request;

class Actions
{
    public function __construct(private Records $records, private Handoff $handoff) {}

    public function execute(string $module, int $id, string $action, Request $r): array
    {
        abort_unless(($module === 'conversations' && in_array($action, ['reply', 'note', 'handoff', 'close', 'reopen'])) || ($module === 'knowledge_articles' && $action === 'publish'), 404);

        return $this->records->transaction(function () use ($module, $id, $action, $r) {
            $this->records->query('workspace_locks')->lockForUpdate()->first();
            $row = $this->records->query($module)->where('id', $id)->lockForUpdate()->first();
            abort_unless($row, 404);
            if (in_array($action, ['reply', 'note'])) {
                $v = $r->validate(['body' => 'required|string|max:10000', 'idempotency_key' => 'required|uuid']);
                abort_if($row->status === 'closed', 422, 'Reopen the conversation first.');
                $hash = hash('sha256', json_encode(['conversation_id' => $id, 'kind' => $action, 'body' => $v['body']], JSON_THROW_ON_ERROR));
                $existing = $this->records->query('messages')->where('idempotency_key', $v['idempotency_key'])->first();
                if ($existing) {
                    abort_unless($existing->conversation_id === $id && hash_equals($existing->request_hash, $hash), 409, 'Idempotency key was used with different input.');

                    return ['message' => 'Existing message returned.', 'message_id' => $existing->id];
                }
                $message = $this->records->insert('messages', ['conversation_id' => $id, 'actor_id' => $r->user()->id, 'kind' => $action, 'body' => $v['body'], 'idempotency_key' => $v['idempotency_key'], 'request_hash' => $hash]);
                $this->records->event('message.saved', 'messages', $message, ['kind' => $action]);

                return ['message' => 'Message saved locally. External delivery is not configured.', 'message_id' => $message];
            }
            $values = match ($action) {
                'handoff' => $this->handoff->request(),'close' => ['status' => 'closed', 'ai_mode' => 'disabled'],'reopen' => ['status' => 'open', 'ai_mode' => 'disabled'],'publish' => ['status' => 'published']
            };
            $this->records->query($module)->where('id', $id)->update([...$values, 'updated_at' => now()]);
            $this->records->event($action, $module, $id);

            return ['message' => ucfirst($action).' completed.'];
        });
    }
}
