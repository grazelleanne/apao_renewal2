<?php
// app/Http/Controllers/AuditLogController.php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'action' => ['sometimes', 'nullable', 'string', 'max:100'],
            'user_name' => ['sometimes', 'nullable', 'string', 'max:100'],
            'date_from' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
            'date_to' => ['sometimes', 'nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
        ]);

        $query = AuditLog::orderBy('created_at', 'desc');

        if (!empty($filters['action']))    $query->where('action', $filters['action']);
        if (!empty($filters['user_name'])) $query->where('user_name', 'like', "%{$filters['user_name']}%");
        if (!empty($filters['date_from'])) $query->whereDate('created_at', '>=', $filters['date_from']);
        if (!empty($filters['date_to']))   $query->whereDate('created_at', '<=', $filters['date_to']);

        return response()->json([
            'success' => true,
            'data'    => $query->limit(500)->get()->map->toApiArray(),
        ]);
    }
}
