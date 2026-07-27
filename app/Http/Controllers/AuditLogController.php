<?php
// app/Http/Controllers/AuditLogController.php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $query = AuditLog::orderBy('created_at', 'desc');

        if ($action = $request->query('action'))    $query->where('action', $action);
        if ($user   = $request->query('user_name')) $query->where('user_name', 'like', "%{$user}%");
        if ($from   = $request->query('date_from')) $query->whereDate('created_at', '>=', $from);
        if ($to     = $request->query('date_to'))   $query->whereDate('created_at', '<=', $to);

        return response()->json([
            'success' => true,
            'data'    => $query->limit(500)->get()->map->toApiArray(),
        ]);
    }
}