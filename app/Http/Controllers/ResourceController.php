<?php

namespace App\Http\Controllers;

use App\Domain\Product\Actions;
use App\Domain\Tenancy\Records;
use App\Models\Workspace;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ResourceController extends Controller
{
    public function __construct(private Records $records) {}

    private function module(string $module): array
    {
        $config = config('product.modules.'.$module);
        abort_unless($config, 404);

        return $config;
    }

    public function dashboard(Request $r, Workspace $workspace)
    {
        $counts = [];
        foreach (config('product.modules') as $key => $module) {
            $counts[$key] = $this->records->query($key)->count();
        }

        return Inertia::render('WorkspaceDashboard', ['product' => config('product'), 'workspace' => $workspace, 'counts' => $counts, 'events' => $this->records->query('domain_events')->latest('id')->limit(10)->get(), 'role' => $r->attributes->get('workspace_role')]);
    }

    public function index(Request $r, Workspace $workspace, string $module)
    {
        $definition = $this->module($module);
        $q = $this->records->query($module);
        if (isset($definition['fields']['name']) && $r->filled('search')) {
            $q->where('name', 'like', '%'.mb_substr($r->string('search'), 0, 100).'%');
        }

        return Inertia::render('Records', ['product' => config('product'), 'workspace' => $workspace, 'module' => $module, 'definition' => $definition, 'records' => $q->orderByDesc('id')->paginate(20)->withQueryString(), 'role' => $r->attributes->get('workspace_role')]);
    }

    public function show(Request $r, Workspace $workspace, string $module, int $id)
    {
        $definition = $this->module($module);
        $record = $this->records->query($module)->where('id', $id)->first();
        abort_unless($record, 404);

        return Inertia::render('RecordDetail', ['product' => config('product'), 'workspace' => $workspace, 'module' => $module, 'definition' => $definition, 'record' => $record, 'role' => $r->attributes->get('workspace_role'), 'events' => $this->records->query('domain_events')->where('subject_type', $module)->where('subject_id', $id)->latest('id')->limit(50)->get(), 'messages' => $module === 'conversations' ? $this->records->query('messages')->where('conversation_id', $id)->orderBy('id')->limit(200)->get() : []]);
    }

    public function store(Request $r, Workspace $workspace, string $module)
    {
        $definition = $this->module($module);
        abort_if($definition['readonly'], 403);
        abort_unless(in_array($r->attributes->get('workspace_role'), ['owner', 'admin', 'editor']), 403);
        $rules = [];
        foreach ($definition['fields'] as $field => $type) {
            if (in_array($field, ['status', 'ai_mode'])) {
                continue;
            }
            $rules[$field] = match ($type) {
                'integer' => 'required|integer|min:1|max:1000000000','boolean' => 'required|boolean','json' => 'required|json|max:50000','date' => 'required|date_format:Y-m-d','text' => 'required|string|max:20000',default => 'required|string|max:255'
            };
        }
        if (isset($rules['email'])) {
            $rules['email'] = 'required|email|max:255';
        }
        $v = $r->validate($rules);
        foreach ($definition['fields'] as $field => $type) {
            if ($type === 'json' && isset($v[$field])) {
                abort_unless(is_array(json_decode($v[$field], true)), 422, 'Structured data must be a JSON array or object.');
            }
        }
        if (isset($definition['fields']['status'])) {
            $v['status'] = match ($module) {
                'conversations','tickets' => 'open','knowledge_articles' => 'draft','trips' => 'planned',default => 'draft'
            };
        }
        if (isset($definition['fields']['ai_mode'])) {
            $v['ai_mode'] = 'disabled';
        }
        // Foreign identifiers are resolved only through the active workspace.
        $references = ['company_id' => 'companies', 'customer_id' => 'customers', 'driver_id' => 'drivers', 'vehicle_id' => 'vehicles', 'contact_id' => 'contacts', 'branch_id' => 'restaurant_branches'];
        foreach ($references as $field => $table) {
            if (isset($v[$field])) {
                abort_unless($this->records->query($table)->where('id', $v[$field])->exists(), 422, 'Invalid '.$field);
            }
        }
        if (isset($v['currency'])) {
            $v['currency'] = strtoupper($v['currency']);
            abort_unless(preg_match('/^[A-Z]{3}$/', $v['currency']), 422, 'Use a three-letter currency.');
        }
        $this->records->transaction(function () use ($module, $v, $workspace) {
            // Lock a workspace row in the same business database through a dedicated quota row.
            $lock = $this->records->query('workspace_locks')->lockForUpdate()->first();
            abort_unless($lock, 503, 'Workspace initialization required.');
            $limits = json_decode(DB::table('plans')->where('id', $workspace->plan_id)->value('limits'), true);
            abort_if($this->records->query($module)->count() >= ($limits['records_per_module'] ?? 0), 422, 'Record limit reached.');
            $id = $this->records->insert($module, $v);
            $this->records->event('created', $module, $id);
        });

        return back();
    }

    public function action(Request $r, Workspace $workspace, string $module, int $id, string $action)
    {
        $this->module($module);
        abort_unless(in_array($r->attributes->get('workspace_role'), ['owner', 'admin', 'editor']), 403);
        $result = app(Actions::class)->execute($module, $id, $action, $r);

        return $r->expectsJson() ? response()->json($result) : back()->with('status',$result['message'] ?? 'Action completed.');
    }
}
