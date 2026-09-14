<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * §68 — consultation du journal d'audit.
 */
class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $action = $request->query('action');

        $logs = AuditLog::query()
            ->with('admin:id,first_name,last_name,email')
            ->when($action, fn ($q) => $q->where('action', $action))
            ->when($request->query('admin_id'), fn ($q, $id) => $q->where('admin_id', $id))
            ->latest('created_at')
            ->paginate(50)
            ->withQueryString();

        $actions = AuditLog::query()->distinct()->orderBy('action')->pluck('action');

        return view('admin.audit-logs.index', compact('logs', 'actions', 'action'));
    }
}
