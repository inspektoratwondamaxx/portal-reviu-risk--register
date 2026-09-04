<?php

namespace App\Http\Controllers\Sistem;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Sistem > Audit Log (Bab 22.2 kajian): siapa -> apa -> kapan -> sebelum -> sesudah. */
class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $logs = AuditLog::query()
            ->with(['user', 'opd'])
            ->when($request->filled('model'), fn ($q) => $q->where('model_type', 'like', '%'.$request->string('model').'%'))
            ->when($request->filled('aksi'), fn ($q) => $q->where('action', $request->string('aksi')))
            ->when($request->filled('user_id'), fn ($q) => $q->where('user_id', $request->integer('user_id')))
            ->latest()
            ->paginate(30)->withQueryString();

        return view('sistem.audit-log.index', ['logs' => $logs]);
    }
}
