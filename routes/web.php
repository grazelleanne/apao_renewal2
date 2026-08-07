<?php

use App\Http\Controllers\EmailsController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Http\Controllers\ForgotPasswordController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\ParController;
use App\Models\Inspection;

// =====================================================
// ===== AUDIT LOG HELPER — must be first =============
// =====================================================
function auditLog(string $action, string $target = null, array $details = []): void
{
    try {
        $user = session('user');
        DB::table('audit_logs')->insert([
            'user_id'     => $user['id']   ?? null,
            'user_name'   => $user['name'] ?? 'System',
            'user_role'   => $user['role'] ?? 'unknown',
            'action'      => $action,
            'target'      => $target,
            'description' => !empty($details) ? json_encode($details) : null,
            'ip_address'  => request()->ip(),
            'created_at'  => now(),
        ]);
    } catch (\Exception $e) {
        \Illuminate\Support\Facades\Log::error('AUDIT LOG INSERT FAILED: ' . $e->getMessage());
    }
}

// ===== TEMPORARY DEBUG ROUTE — remove after fixing =====
Route::get('/debug-audit-insert', function () {
    try {
        DB::table('audit_logs')->insert([
            'user_name'   => 'DebugTest',
            'user_role'   => 'debug',
            'action'      => 'debug_test',
            'target'      => 'test',
            'description' => json_encode(['test' => true]),
            'ip_address'  => request()->ip(),
            'created_at'  => now(),
        ]);
        return response()->json(['success' => true, 'message' => 'Insert worked! Check audit_logs table.']);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error'   => $e->getMessage(),
        ], 500);
    }
});

// ===== PUBLIC =====
// ===== PUBLIC =====
Route::get('/', fn() => redirect()->route('login'));
Route::get('/login',           fn() => view('login'))->name('login');
Route::get('/register',        fn() => view('register'))->name('register');
Route::get('/forgot-password', fn() => view('forgot_password'))->name('forgot.password');

// ===== REGISTER =====
// ===== REGISTER =====
Route::post('/register', function (Request $request) {
    $validator = validator($request->all(), [
        'name'     => 'required|string|max:255',
        'email'    => 'required|email|unique:users,email',
        'password' => 'required|min:6',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'errors'  => $validator->errors(),
            'message' => $validator->errors()->first(),
        ], 422);
    }

    DB::table('users')->insert([
        'name'       => $request->name,
        'email'      => $request->email,
        'password'   => bcrypt($request->password),
        'role'       => 'staff',
        'status'     => 'Active',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return response()->json(['success' => true, 'message' => 'Registration successful']);
})->name('register.post');

// ===== LOGIN =====
Route::post('/login', function (Request $request) {
    $request->validate(['email' => 'required|email', 'password' => 'required']);
    $user = DB::table('users')->where('email', $request->email)->first();
    if (!$user) {
        return response()->json(['success' => false, 'message' => 'No account found with that email.'], 401);
    }
    if (!Hash::check($request->password, $user->password)) {
        return response()->json(['success' => false, 'message' => 'Incorrect password.'], 401);
    }
if (!($user->is_active ?? 1)) {
    return response()->json(['success' => false, 'message' => 'Your account is inactive. Please contact the administrator.'], 403);
}
    session(['user' => (array) $user]);
    auditLog('login', $user->email, ['role' => $user->role]);
    $redirect = match($user->role ?? '') {
        'admin' => route('admin.dashboard'),
        'staff' => route('staff.dashboard'),
        default => null,
    };
    if (!$redirect) {
        return response()->json(['success' => false, 'message' => 'Role "' . ($user->role ?? 'none') . '" has no dashboard. Contact admin.'], 403);
    }
    return response()->json(['success' => true, 'redirect' => $redirect, 'message' => 'Welcome back, ' . $user->name . '!']);
})->middleware('throttle:login')->name('login.post');

// ===== LOGOUT =====
Route::post('/logout', function (Request $request) {
    auditLog('logout');
    session()->forget('user');
    session()->invalidate();
    session()->regenerateToken();
    return redirect()->route('login');
})->name('logout');

// =====================================================
// ===== ADMIN ROUTES ==================================
// =====================================================
Route::middleware('check.session:admin')->prefix('admin')->group(function () {

    // ---- DASHBOARD ----
    Route::get('/dashboard', function () {
        $user = (object) session('user');
        return view('admin_dashboard', compact('user'));
    })->name('admin.dashboard');

    Route::get('/dashboard-data', function () {
        $all        = DB::table('personnel')->whereNull('archived_at')->get();
        $totalUsers = DB::table('users')->count();

        $totalNew      = $all->filter(fn($p) => ($p->approved_status ?? '') === 'new')->count();
        $totalRenewed  = $all->filter(fn($p) => ($p->approved_status ?? '') === 'renewed')->count();
        $withinRenewal = $all->filter(fn($p) => ($p->approved_status ?? '') === 'within')->count();
        $expired       = $all->filter(fn($p) => ($p->approved_status ?? '') === 'expired')->count();
        $pending       = $all->filter(fn($p) => ($p->approved_status ?? '') === 'pending')->count();
        $personnel = $all->map(fn($p) => [
            'itemNumber'         => $p->item_number          ?? '',
            'dateOfValidity'     => $p->date_of_validity     ?? '',
            'rank'               => $p->rank                 ?? '',
            'lastName'           => $p->last_name            ?? '',
            'firstName'          => $p->first_name           ?? '',
            'middleName'         => $p->middle_name          ?? '',
            'afpSerialNumber'    => $p->afp_serial_number    ?? '',
            'afosMos'            => $p->afos_mos             ?? '',
            'branch'             => $p->branch               ?? '',
            'unit'               => $p->unit                 ?? '',
            'dateOfBirth'        => $p->date_of_birth        ?? '',
            'pistolNomenclature' => $p->pistol_nomenclature  ?? '',
            'pistolSerialNumber' => $p->pistol_serial_number ?? '',
            'qtyAmmo'            => $p->qty_ammo             ?? 0,
            'approvedStatus'     => $p->approved_status      ?? 'pending',
            'email'              => $p->email                ?? '',
        ]);

        return response()->json([
            'success'    => true,
            'totalUsers' => $totalUsers,
            'metrics'    => compact('totalNew', 'totalRenewed', 'withinRenewal', 'expired', 'pending'),
            'personnel'  => $personnel,
        ]);
    })->name('admin.dashboard.data');

    // ---- PERSONNEL ----
    Route::get('/personnel', function () {
        $user = (object) session('user');
        return view('admin_personnel', compact('user'));
    })->name('admin.personnel');

    Route::get('/personnel-data', function () {
        $personnel = DB::table('personnel')->whereNull('archived_at')->get()
            ->map(fn($p) => [
    'itemNumber'         => $p->item_number          ?? '',
    'dateOfValidity'     => $p->date_of_validity     ?? '',
    'rank'               => $p->rank                 ?? '',
    'lastName'           => $p->last_name            ?? '',
    'firstName'          => $p->first_name           ?? '',
    'middleName'         => $p->middle_name          ?? '',
    'afpSerialNumber'    => $p->afp_serial_number    ?? '',
    'afosMos'            => $p->afos_mos             ?? '',
    'branch'             => $p->branch               ?? '',
    'unit'               => $p->unit                 ?? '',
    'dateOfBirth'        => $p->date_of_birth        ?? '',
    'pistolNomenclature' => $p->pistol_nomenclature  ?? '',
    'pistolSerialNumber' => $p->pistol_serial_number ?? '',
    'qtyAmmo'            => $p->qty_ammo             ?? 0,
    'status'             => $p->status               ?? '',
    'approvedStatus'     => $p->approved_status      ?? 'pending',
    'email'              => $p->email                ?? '',
    'photo'              => $p->photo                ?? null,  // ← ADD THIS
]);
        return response()->json(['success' => true, 'data' => $personnel]);
    })->name('admin.personnel.data');

    Route::post('/personnel-data', function (Request $request) {
        try {
            $lastItem = DB::table('personnel')->max('item_number') ?? 0;
            $id = DB::table('personnel')->insertGetId([
                'item_number'          => $lastItem + 1,
                'date_of_validity'     => $request->dateOfValidity     ?: null,
                'rank'                 => $request->rank               ?? '',
                'last_name'            => $request->lastName           ?? '',
                'first_name'           => $request->firstName          ?? '',
                'middle_name'          => $request->middleName         ?? '',
                'afp_serial_number'    => $request->afpSerialNumber    ?? '',
                'afos_mos'             => $request->afosMos            ?? '',
                'branch'               => $request->branch             ?? '',
                'unit'                 => $request->unit               ?? '',
                'date_of_birth'        => $request->dateOfBirth        ?: null,
                'pistol_nomenclature'  => $request->pistolNomenclature ?? '',
                'pistol_serial_number' => $request->pistolSerialNumber ?? '',
                'qty_ammo'             => (int) ($request->qtyAmmo     ?? 0),
                'email'                => $request->email              ?? null,
                'photo' => $request->input('photo') ?? null,
                'approved_status'      => 'new',
                'status'               => 'active',
                'created_at'           => now(),
                'updated_at'           => now(),
            ]);
            $newRow = DB::table('personnel')->where('id', $id)->first();
            $name   = trim(($newRow->rank ?? '') . ' ' . ($newRow->last_name ?? '') . ', ' . ($newRow->first_name ?? ''));
            auditLog('personnel_added', $name, ['item_number' => $newRow->item_number]);
            DB::table('notifications')->insert([
                'type' => 'personnel_added', 'title' => 'New Personnel Added',
                'message' => "Admin has added a new personnel record: {$name}.",
                'personnel_name' => $name, 'personnel_id' => $newRow->item_number,
                'read_by_admin' => true, 'read_by_staff' => false,
                'created_at' => now(), 'updated_at' => now(),
            ]);
            return response()->json(['success' => true, 'data' => [
                'itemNumber'         => $newRow->item_number,
                'dateOfValidity'     => $newRow->date_of_validity     ?? '',
                'rank'               => $newRow->rank                 ?? '',
                'lastName'           => $newRow->last_name            ?? '',
                'firstName'          => $newRow->first_name           ?? '',
                'middleName'         => $newRow->middle_name          ?? '',
                'afpSerialNumber'    => $newRow->afp_serial_number    ?? '',
                'afosMos'            => $newRow->afos_mos             ?? '',
                'branch'             => $newRow->branch               ?? '',
                'unit'               => $newRow->unit                 ?? '',
                'dateOfBirth'        => $newRow->date_of_birth        ?? '',
                'pistolNomenclature' => $newRow->pistol_nomenclature  ?? '',
                'pistolSerialNumber' => $newRow->pistol_serial_number ?? '',
                'qtyAmmo'            => $newRow->qty_ammo             ?? 0,
                'approvedStatus'     => $newRow->approved_status      ?? 'pending',
                'email'              => $newRow->email                ?? '',
            ]]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    })->name('admin.personnel.store');

    Route::put('/personnel-data/{id}', function (Request $request, $id) {
        DB::table('personnel')->where('item_number', $id)->update([
            'date_of_validity'     => $request->dateOfValidity     ?: null,
            'rank'                 => $request->rank               ?? '',
            'last_name'            => $request->lastName           ?? '',
            'first_name'           => $request->firstName          ?? '',
            'middle_name'          => $request->middleName         ?? '',
            'afp_serial_number'    => $request->afpSerialNumber    ?? '',
            'afos_mos'             => $request->afosMos            ?? '',
            'branch'               => $request->branch             ?? '',
            'unit'                 => $request->unit               ?? '',
            'date_of_birth'        => $request->dateOfBirth        ?: null,
            'pistol_nomenclature'  => $request->pistolNomenclature ?? '',
            'pistol_serial_number' => $request->pistolSerialNumber ?? '',
            'qty_ammo'             => (int) ($request->qtyAmmo     ?? 0),
            'email'                => $request->email              ?? null,
            'approved_status'      => $request->approvedStatus     ?? 'pending',
            'updated_at'           => now(),
        ]);
        $updated = DB::table('personnel')->where('item_number', $id)->first();
        $name    = trim(($updated->rank ?? '') . ' ' . ($updated->last_name ?? '') . ', ' . ($updated->first_name ?? ''));
        auditLog('personnel_updated', $name, ['item_number' => $id]);
        DB::table('notifications')->insert([
            'type' => 'personnel_updated', 'title' => 'Personnel Record Updated',
            'message' => "Admin has updated the record of {$name}.",
            'personnel_name' => $name, 'personnel_id' => $id,
            'read_by_admin' => true, 'read_by_staff' => false,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        return response()->json(['success' => true, 'data' => [
            'itemNumber'         => $updated->item_number,
            'dateOfValidity'     => $updated->date_of_validity     ?? '',
            'rank'               => $updated->rank                 ?? '',
            'lastName'           => $updated->last_name            ?? '',
            'firstName'          => $updated->first_name           ?? '',
            'middleName'         => $updated->middle_name          ?? '',
            'afpSerialNumber'    => $updated->afp_serial_number    ?? '',
            'afosMos'            => $updated->afos_mos             ?? '',
            'branch'             => $updated->branch               ?? '',
            'unit'               => $updated->unit                 ?? '',
            'dateOfBirth'        => $updated->date_of_birth        ?? '',
            'pistolNomenclature' => $updated->pistol_nomenclature  ?? '',
            'pistolSerialNumber' => $updated->pistol_serial_number ?? '',
            'qtyAmmo'            => $updated->qty_ammo             ?? 0,
            'approvedStatus'     => $updated->approved_status      ?? 'pending',
            'email'              => $updated->email                ?? '',
        ]]);
    })->name('admin.personnel.update');

    Route::delete('/personnel-data/{id}', function ($id) {
        $p = DB::table('personnel')->where('item_number', $id)->first();
        DB::table('personnel')->where('item_number', $id)->update(['archived_at' => now(), 'updated_at' => now()]);
        if ($p) {
            $name = trim(($p->rank ?? '') . ' ' . ($p->last_name ?? '') . ', ' . ($p->first_name ?? ''));
            auditLog('personnel_archived', $name, ['item_number' => $id]);
            DB::table('notifications')->insert([
                'type' => 'personnel_archived', 'title' => 'Personnel Archived',
                'message' => "Admin has archived the record of {$name}.",
                'personnel_name' => $name, 'personnel_id' => $id,
                'read_by_admin' => true, 'read_by_staff' => false,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
        return response()->json(['success' => true]);
    })->name('admin.personnel.delete');

    // ---- NOTIFICATIONS (ADMIN) ----
    Route::get('/notifications', function () {
        $notifications = DB::table('notifications')
            ->orderBy('created_at', 'desc')->limit(20)->get()
            ->map(fn($n) => [
                'id'            => $n->id,
                'type'          => $n->type,
                'title'         => $n->title,
                'message'       => $n->message,
                'personnelName' => $n->personnel_name,
                'read'          => (bool) $n->read_by_admin,
                'createdAt'     => $n->created_at,
            ]);
        $unreadCount = DB::table('notifications')->where('read_by_admin', false)->count();
        return response()->json(['success' => true, 'notifications' => $notifications, 'unreadCount' => $unreadCount]);
    })->name('admin.notifications');

    Route::post('/notifications/read', function () {
        DB::table('notifications')->update(['read_by_admin' => true]);
        return response()->json(['success' => true]);
    })->name('admin.notifications.read');

    // ---- AUDIT LOG ----
    Route::get('/audit-log', function () {
        $user = (object) session('user');
        return view('admin_audit', compact('user'));
    })->name('admin.audit');

   Route::get('/audit-log-data', function (Request $request) {
        $query = DB::table('audit_logs')->orderBy('created_at', 'desc');
        if ($request->filled('action'))    { $query->where('action', $request->action); }
        if ($request->filled('user_name')) { $query->where('user_name', 'like', '%' . $request->user_name . '%'); }
        if ($request->filled('date_from')) { $query->whereDate('created_at', '>=', $request->date_from); }
        if ($request->filled('date_to'))   { $query->whereDate('created_at', '<=', $request->date_to); }
        $logs = $query->limit(200)->get()->map(function ($l) {
            $decoded = json_decode($l->description ?? '', true);
            return [
                'id'        => $l->id,
                'userName'  => $l->user_name,
                'userRole'  => $l->user_role,
                'action'    => $l->action,
                'target'    => $l->target,
                'details'   => is_array($decoded) ? $decoded : null,
                'ipAddress' => $l->ip_address,
                'createdAt' => $l->created_at,
            ];
        });
        return response()->json(['success' => true, 'data' => $logs]);
    })->name('admin.audit.data');

    // ---- REPORTS ----
    Route::get('/reports', function () {
        $user = (object) session('user');
        return view('admin_reports', compact('user'));
    })->name('admin.reports');

    // ---- ARCHIVE ----
    Route::get('/archive', function () {
        $user = (object) session('user');
        return view('admin_archive', compact('user'));
    })->name('admin.archive');

    Route::get('/archive-data', function () {
        $archived = DB::table('personnel')->whereNotNull('archived_at')->get()
            ->map(fn($p) => [
                'itemNumber'   => $p->item_number ?? '',
                'rank'         => $p->rank        ?? '',
                'lastName'     => $p->last_name   ?? '',
                'firstName'    => $p->first_name  ?? '',
                'unit'         => $p->unit        ?? '',
                'dateArchived' => $p->archived_at ?? '',
            ]);
        return response()->json(['success' => true, 'data' => $archived]);
    })->name('admin.archive.data');

    Route::post('/archive-restore', function (Request $request) {
        DB::table('personnel')->where('item_number', $request->input('id'))->update(['archived_at' => null, 'updated_at' => now()]);
        auditLog('personnel_restored', null, ['item_number' => $request->input('id')]);
        return response()->json(['success' => true]);
    })->name('admin.archive.restore');

    Route::delete('/archive-data/{id}', function ($id) {
        $p = DB::table('personnel')->where('item_number', $id)->first();
        DB::table('personnel')->where('item_number', $id)->delete();
        if ($p) {
            $name = trim(($p->rank ?? '') . ' ' . ($p->last_name ?? '') . ', ' . ($p->first_name ?? ''));
            auditLog('personnel_deleted', $name, ['item_number' => $id]);
        }
        return response()->json(['success' => true]);
    })->name('admin.archive.delete');

    // ---- USERS ----
    Route::get('/users', function () {
        $user = (object) session('user');
        return view('admin_users', compact('user'));
    })->name('admin.users');

  Route::get('/users-data', function () {
    $users = DB::table('users')->select('id', 'name', 'email', 'role', 'is_active', 'created_at')->get()
        ->map(fn($u) => [
            'username' => $u->email,
            'fullName' => $u->name,
            'role'     => ucfirst($u->role ?? 'staff'),
            'status'   => $u->is_active ? 'Active' : 'Inactive',
        ]);
    return response()->json(['success' => true, 'data' => $users]);
})->name('admin.users.data');

    Route::post('/users-store', function (Request $request) {
        $request->validate([
            'username' => 'required|email|unique:users,email',
            'fullName' => 'required|string',
            'role'     => 'required|in:admin,staff',
            'password' => 'required|min:6',
            'status'   => 'nullable|in:Active,Inactive',
        ]);

        DB::table('users')->insert([
            'name'       => $request->fullName,
            'email'      => $request->username,
            'password'   => bcrypt($request->password),
            'role'       => $request->role,
            'is_active'  => $request->status !== 'Inactive',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        auditLog('user_created', $request->username, ['role' => $request->role]);
        return response()->json(['success' => true]);
    })->name('admin.users.store');

   Route::put('/users-update', function (Request $request) {
    DB::table('users')->where('email', $request->username)->update([
        'name'       => $request->fullName,
        'role'       => strtolower($request->role),
        'is_active'  => ($request->status ?? 'Active') === 'Active' ? 1 : 0,
        'updated_at' => now(),
    ]);
    auditLog('user_updated', $request->username, ['new_role' => $request->role]);
    return response()->json(['success' => true]);
})->name('admin.users.update');

    // ---- INSPECTION / RENEWAL ----
    Route::get('/inspection', function () {
        $user = (object) session('user');
        return view('admin_inspection', compact('user'));
    })->name('admin.inspection');

  Route::get('/inspection-data', function () {
        $personnel = DB::table('personnel as p')
            ->leftJoin('inspections as i', function ($join) {
                $join->on('p.item_number', '=', 'i.item_number')
                     ->whereRaw('i.id = (SELECT MAX(id) FROM inspections WHERE item_number = p.item_number)');
            })
            ->whereNull('p.archived_at')
            ->select(
                'p.id', 'p.item_number', 'p.rank', 'p.last_name', 'p.first_name', 'p.middle_name',
                'p.afp_serial_number', 'p.pistol_nomenclature', 'p.unit', 'p.date_of_validity', 'p.approved_status',
                'p.ics_status',
                'i.id as inspection_id', 'i.status as inspection_status', 'i.date_registered',
                DB::raw("COALESCE(i.status, 'pending') as current_status")
            )
            ->orderBy('p.item_number')
            ->get()
            // ── NEW: hide records staff hasn't sent for inspection yet ──
            ->filter(function ($p) {
                $status = $p->current_status ?? 'pending';
                // If inspection hasn't started/been touched yet (pending),
                // only show it once staff has sent it (ics_status = 'under').
                if ($status === 'pending') {
                    return ($p->ics_status ?? 'inspection') === 'under';
                }
                // 'under' and 'approved' statuses are always shown.
                return true;
            })
            ->map(fn($p) => [
                'id'               => $p->id,
                'itemNumber'       => $p->item_number,
                'rank'             => $p->rank               ?? '',
                'lastName'         => $p->last_name          ?? '',
                'firstName'        => $p->first_name         ?? '',
                'middleName'       => $p->middle_name        ?? '',
                'afpSerialNumber'  => $p->afp_serial_number  ?? '',
                'pistolType'       => $p->pistol_nomenclature ?? 'Glock 17',
                'unit'             => $p->unit               ?? '',
                'dateOfValidity'   => $p->date_of_validity   ?? '',
                'approvedStatus'   => $p->approved_status    ?? '',
                'icsStatus'        => $p->ics_status         ?? 'inspection',
                'inspectionId'     => $p->inspection_id      ?? null,
                'inspectionStatus' => $p->current_status     ?? 'pending',
                'dateRegistered'   => $p->date_registered    ?? now()->toDateString(),
            ])
            ->values();

        $pending  = $personnel->where('inspectionStatus', 'pending')->count();
        $under    = $personnel->where('inspectionStatus', 'under')->count();
        $approved = $personnel->where('inspectionStatus', 'approved')->count();

        return response()->json([
            'success'  => true,
            'data'     => $personnel,
            'pending'  => $pending,
            'under'    => $under,
            'approved' => $approved,
        ]);
    })->name('admin.inspection.data');

Route::get('/inspection/{itemNumber}/detail', function ($itemNumber) {
    $p = DB::table('personnel')->where('item_number', $itemNumber)->first();
    if (!$p) return response()->json(['success' => false, 'error' => 'Not found'], 404);

    $latest = DB::table('inspections')->where('item_number', $itemNumber)->orderByDesc('id')->first();
    $ics    = DB::table('ics_settings')->first();

    $parts = [
        'barrel', 'slide', 'recoil_spring_assembly', 'firing_pin', 'spacer_sleeve',
        'firing_pin_spring', 'spring_cups', 'firing_pin_safety', 'firing_pin_safety_spring',
        'extractor', 'extractor_depressor_plunger', 'extractor_depressor_plunger_spring',
        'trigger_loaded_bearing', 'rear_sight', 'front_sight',
        'front_sight_screw', 'frame', 'magazine_catch_spring', 'magazine_catch',
        'slide_lock', 'slide_cover_plate', 'connector', 'trigger_mechanism_housing',
        'trigger', 'trigger_spring', 'trigger_with_trigger_bar', 'slide_stop_lever',
        'trigger_pin', 'trigger_housing_pin', 'locking_block_pin',
    ];

    $inspectionData = null;
    if ($latest) {
        $inspectionData = [
            'id'     => $latest->id,
            'status' => $latest->status,
            'remarks'             => $latest->remarks               ?? '',
            'inspectedByName'     => $latest->inspected_by_name    ?? ($ics->chief_officer_name     ?? ''),
            'inspectedByRank'     => $latest->inspected_by_rank    ?? '',
            'inspectedByPosition' => $latest->inspected_by_position ?? ($ics->chief_officer_position ?? ''),
            'inspectedBySig'      => $latest->inspected_by_sig ?? '',
            'witnessedByName'     => $latest->witnessed_by_name    ?? '',
            'witnessedByRank'     => $latest->witnessed_by_rank    ?? '',
            'witnessedByPosition' => $latest->witnessed_by_position ?? '',
            'witnessedBySig'      => $latest->witnessed_by_sig ?? '',
            'approvedByName'      => $latest->approved_by_name     ?? '',
            'approvedByRank'      => $latest->approved_by_rank     ?? '',
            'approvedByPosition'  => $latest->approved_by_position ?? '',
            'approvedBySig'       => $latest->approved_by_sig ?? '',
            'notedByName'         => $latest->noted_by_name        ?? '',
            'notedByRank'         => $latest->noted_by_rank        ?? '',
            'notedByPosition'     => $latest->noted_by_position    ?? '',
            'notedBySig'          => $latest->noted_by_sig ?? '',
        ];
        foreach ($parts as $part) {
            $inspectionData[$part] = $latest->$part ?? 'serviceable';
        }
    }

    return response()->json([
        'success'    => true,
        'personnel'  => [
            'id'              => $p->id,
            'itemNumber'      => $p->item_number,
            'rank'            => $p->rank               ?? '',
            'lastName'        => $p->last_name          ?? '',
            'firstName'       => $p->first_name         ?? '',
            'middleName'      => $p->middle_name        ?? '',
            'afpSerialNumber' => $p->afp_serial_number  ?? '',
            'pistolType'      => $p->pistol_nomenclature ?? 'Glock 17',
            'unit'            => $p->unit               ?? '',
            'dateOfValidity'  => $p->date_of_validity   ?? '',
        ],
        'inspection' => $inspectionData,
        'ics' => $ics ? [
            'chiefOfficerName'     => $ics->chief_officer_name     ?? '',
            'chiefOfficerPosition' => $ics->chief_officer_position ?? '',
            'issuedByName'         => $ics->issued_by_name         ?? '',
            'issuedByPosition'     => $ics->issued_by_position     ?? '',
        ] : [],
    ]);
})->name('admin.inspection.detail');

    Route::post('/inspection/save', function (Request $request) {
        $request->validate([
            'itemNumber' => 'required|integer',
            'status'     => 'required|in:pending,under,approved,needs_repair',
        ]);

        $p = DB::table('personnel')->where('item_number', $request->itemNumber)->first();
        if (!$p) return response()->json(['success' => false, 'error' => 'Personnel not found'], 404);

        $user  = session('user');
        $ics   = DB::table('ics_settings')->latest('id')->first();
        $parts = [
            'barrel', 'slide', 'recoil_spring_assembly', 'firing_pin', 'spacer_sleeve',
            'firing_pin_spring', 'spring_cups', 'firing_pin_safety', 'firing_pin_safety_spring',
            'extractor', 'extractor_depressor_plunger', 'extractor_depressor_plunger_spring',
            'trigger_loaded_bearing', 'rear_sight', 'front_sight',
                // ↓ ADD THESE 15 — this is the missing right-hand column
    'front_sight_screw', 'frame', 'magazine_catch_spring', 'magazine_catch',
    'slide_lock', 'slide_cover_plate', 'connector', 'trigger_mechanism_housing',
    'trigger', 'trigger_spring', 'trigger_with_trigger_bar', 'slide_stop_lever',
    'trigger_pin', 'trigger_housing_pin', 'locking_block_pin',

        ];

        $data = [
            'personnel_id'          => $p->id,
            'item_number'           => $request->itemNumber,
            'afp_serial_number'     => $p->afp_serial_number,
            'pistol_type'           => $p->pistol_nomenclature ?? 'Glock 17',
            'date_registered'       => now()->toDateString(),
            'status'                => $request->status,
            'remarks'               => $request->remarks               ?? null,
            'inspected_by_name'     => $request->inspectedByName       ?? ($ics->chief_officer_name ?? ($user['name'] ?? null)),
            'inspected_by_rank'     => $request->inspectedByRank       ?? null,
            'inspected_by_position' => $request->inspectedByPosition   ?? ($ics->chief_officer_position ?? null),
            'inspected_by_sig'      => $request->inspectedBySig          ?? null,
            'witnessed_by_name'     => $request->witnessedByName       ?? null,
            'witnessed_by_rank'     => $request->witnessedByRank       ?? null,
            'witnessed_by_position' => $request->witnessedByPosition   ?? null,
            'witnessed_by_sig'      => $request->witnessedBySig          ?? null,
            'approved_by_name'      => $request->approvedByName        ?? ($ics->issued_by_name ?? null),
            'approved_by_rank'      => $request->approvedByRank        ?? null,
            'approved_by_position'  => $request->approvedByPosition    ?? ($ics->issued_by_position ?? null),
            'approved_by_sig'       => $request->approvedBySig           ?? null,
            'noted_by_name'         => $request->notedByName           ?? null,
            'noted_by_rank'         => $request->notedByRank           ?? null,
            'noted_by_position'     => $request->notedByPosition       ?? null,
            'noted_by_sig'          => $request->notedBySig              ?? null,
            'inspected_by_user_id'  => $user['id']                     ?? null,
            'inspected_at'          => now(),
            'created_at'            => now(),
            'updated_at'            => now(),
            
        ];

        foreach ($parts as $part) {
            $data[$part] = $request->input($part, 'serviceable');
        }

        $id = DB::table('inspections')->insertGetId($data);
if ($request->status === 'approved') {
    $newValidity = now()->addYear()->toDateString();

    DB::table('personnel')
        ->where('item_number', $request->itemNumber)
        ->update([
            'approved_status'  => 'renewed',
            'date_of_validity' => $newValidity,
            'ics_status'       => 'ready',
            'date_approved'    => now()->toDateString(),
            'updated_at'       => now(),
        ]);

    // Update the inspection record with next renewal date
    DB::table('inspections')
        ->where('id', $id)
        ->update([
            'next_renewal_date' => $newValidity,
            'updated_at'        => now(),
        ]);

    // Save renewal history
    $oldPersonnel = DB::table('personnel')
        ->where('item_number', $request->itemNumber)
        ->first();

    DB::table('renewal_history')->insert([
        'item_number'       => $request->itemNumber,
        'action'            => 'renewed',
        'date_of_validity'  => $newValidity,
        'previous_validity' => $oldPersonnel->date_of_validity ?? null,
        'inspected_by'      => $request->inspectedByName ?? ($user['name'] ?? 'Admin'),
        'remarks'           => $request->remarks ?? null,
        'created_at'        => now(),
        'updated_at'        => now(),
    ]);
}

        $name = trim(($p->rank ?? '') . ' ' . ($p->last_name ?? '') . ', ' . ($p->first_name ?? ''));
        auditLog('inspection_saved', $name, ['item_number' => $request->itemNumber, 'status' => $request->status]);

        DB::table('notifications')->insert([
            'type'           => 'inspection_saved',
            'title'          => 'Inspection Saved',
            'message'        => "Inspection for {$name} saved. Status: {$request->status}.",
            'personnel_name' => $name,
            'personnel_id'   => $request->itemNumber,
            'read_by_admin'  => true,
            'read_by_staff'  => false,
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        return response()->json(['success' => true, 'id' => $id]);
    })->name('admin.inspection.save');
    // ---- RENEWAL HISTORY ----
Route::get('/personnel/{itemNumber}/renewal-history', function ($itemNumber) {
    $history = DB::table('renewal_history')
        ->where('item_number', $itemNumber)
        ->orderByDesc('created_at')
        ->get()
        ->map(fn($h) => [
            'action'           => $h->action,
            'dateOfValidity'   => $h->date_of_validity,
            'previousValidity' => $h->previous_validity,
            'inspectedBy'      => $h->inspected_by,
            'remarks'          => $h->remarks,
            'date'             => $h->created_at,
        ]);
    return response()->json(['success' => true, 'history' => $history]);
})->name('admin.personnel.renewal.history');

   Route::get('/inspection/{itemNumber}/print', function ($itemNumber) {
     // 1. Fetch personnel — abort with clear message if not found
        $p = DB::table('personnel')->where('item_number', $itemNumber)->first();
        if (!$p) {
            abort(404, "Personnel with item number [{$itemNumber}] not found.");
        }
 
        // 2. Fetch latest inspection (nullable — blade handles missing gracefully)
        $inspection = DB::table('inspections')
            ->where('item_number', $itemNumber)
            ->orderByDesc('id')
            ->first();
 
        // 3. Build the PDF
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.inspection_report', [
            'p'          => $p,
            'inspection' => $inspection,
            'dateToday'  => now()->format('d F Y'),
        ]);
 
        // 4. Paper setup
        $pdf->setPaper('A4', 'portrait');
 
        // 5. Enable these options to fix common dompdf issues on Windows/XAMPP
       $pdf->setOptions([
    'isHtml5ParserEnabled' => true,
    'isRemoteEnabled'      => true,  // ← this must be true
    'defaultFont'          => 'Arial',
    'dpi'                  => 96,
]);
        // 6. Download
        $filename = 'inspection_report_' . $itemNumber . '_' . now()->format('Ymd') . '.pdf';
        return $pdf->download($filename);
 
    })->name('admin.inspection.print');
// ---- NOTIFY STAFF FOR ICS RENEWAL ----
Route::post('/inspection/notify-staff', function (Request $request) {
    $itemNumber = $request->input('itemNumber');
    $message    = $request->input('message');

    $p = DB::table('personnel')->where('item_number', $itemNumber)->first();
    if (!$p) {
        return response()->json(['success' => false, 'error' => 'Personnel not found.'], 404);
    }

    $name = trim(($p->rank ?? '') . ' ' . ($p->last_name ?? '') . ', ' . ($p->first_name ?? ''));

    DB::table('notifications')->insert([
        'type'           => 'ics_renewal',
        'title'          => 'ICS Renewal Required',
        'message'        => $message,
        'personnel_name' => $name,
        'personnel_id'   => $itemNumber,
        'read_by_admin'  => true,
        'read_by_staff'  => false,
        'created_at'     => now(),
        'updated_at'     => now(),
    ]);

    auditLog('notify_staff_ics_renewal', $name, [
        'item_number' => $itemNumber,
        'message'     => $message,
    ]);

    return response()->json(['success' => true]);
})->name('admin.inspection.notify-staff');
}); // END ADMIN GROUP

// =====================================================
// ===== STAFF ROUTES ==================================
// =====================================================
Route::middleware('check.session:staff')->prefix('staff')->group(function () {

    // ---- DASHBOARD ----
    Route::get('/dashboard', function () {
        $sessionUser = session('user');
        $userId = is_object($sessionUser) ? $sessionUser->id : ($sessionUser['id'] ?? null);
        $user = DB::table('users')->where('id', $userId)->first();
        session(['user' => (array) $user]);
        return view('staff_dashboard', compact('user'));
    })->name('staff.dashboard');

    Route::put('/profile', [StaffController::class, 'updateProfile'])->name('staff.profile.update');
    Route::put('/profile/password', [StaffController::class, 'updatePassword'])->name('staff.profile.password');

    // ---- PROPERTY ACKNOWLEDGEMENT RECEIPTS (PAR) ----
    Route::get('/par-dashboard', [ParController::class, 'dashboard'])->name('staff.par.dashboard');
    Route::get('/par-issuance', [ParController::class, 'issuance'])->name('staff.par.issuance');
    Route::get('/par', [ParController::class, 'index'])->name('staff.par.index');
    Route::post('/par', [ParController::class, 'store'])->name('staff.par.store');
    Route::get('/par/{par}', [ParController::class, 'show'])->whereNumber('par')->name('staff.par.show');
    Route::put('/par/{par}', [ParController::class, 'update'])->whereNumber('par')->name('staff.par.update');
    Route::post('/par/{par}/replace', [ParController::class, 'replace'])->whereNumber('par')->name('staff.par.replace');
    Route::get('/par/{par}/document', [ParController::class, 'document'])->whereNumber('par')->name('staff.par.document');
    Route::get('/par/{par}/pdf', [ParController::class, 'pdf'])->whereNumber('par')->name('staff.par.pdf');

    Route::get('/dashboard-data', function () {
        $all = DB::table('personnel')
            ->whereNull('archived_at')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        $totalNew      = $all->filter(fn($p) => ($p->approved_status ?? '') === 'new')->count();
        $totalRenewed  = $all->filter(fn($p) => ($p->approved_status ?? '') === 'renewed')->count();
        $withinRenewal = $all->filter(fn($p) => ($p->approved_status ?? '') === 'within')->count();
        $expired       = $all->filter(fn($p) => ($p->approved_status ?? '') === 'expired')->count();
        $pending       = $all->filter(fn($p) => ($p->approved_status ?? '') === 'pending')->count();

        $latestInspections = DB::table('inspections')
            ->whereIn('item_number', $all->pluck('item_number'))
            ->orderByDesc('id')
            ->get()
            ->unique('item_number')
            ->keyBy('item_number');

        $personnel = $all->map(function ($p) use ($latestInspections) {
            $inspection = $latestInspections->get($p->item_number);
            $inspectionResult = match ($inspection?->status) {
                'approved'     => 'Passed',
                'under'        => 'In Progress',
                'needs_repair' => 'Needs Repair',
                'pending'      => 'Pending',
                default        => null,
            };

            return [
            'itemNumber'         => $p->item_number          ?? '',
            'dateOfValidity'     => $p->date_of_validity     ?? '',
            'rank'               => $p->rank                 ?? '',
            'lastName'           => $p->last_name            ?? '',
            'firstName'          => $p->first_name           ?? '',
            'middleName'         => $p->middle_name          ?? '',
            'afpSerialNumber'    => $p->afp_serial_number    ?? '',
            'afosMos'            => $p->afos_mos             ?? '',
            'branch'             => $p->branch               ?? '',
            'unit'               => $p->unit                 ?? '',
            'dateOfBirth'        => $p->date_of_birth        ?? '',
            'pistolNomenclature' => $p->pistol_nomenclature  ?? '',
            'pistolSerialNumber' => $p->pistol_serial_number ?? '',
            'qtyAmmo'            => $p->qty_ammo             ?? 0,
            'approvedStatus'     => $p->approved_status      ?? 'pending',
            'email'              => $p->email                ?? '',
            'photo'              => $p->photo                ?? null,
            'icsStatus'           => $p->ics_status           ?? 'inspection',
            'inspectionResult'    => $inspectionResult,
            'inspectionUpdatedAt' => $inspection?->updated_at,
            ];
        });

        return response()->json([
            'success'   => true,
            'metrics'   => compact('totalNew', 'totalRenewed', 'withinRenewal', 'expired', 'pending'),
            'personnel' => $personnel,
        ]);
    })->name('staff.dashboard.data');

    // ---- REGISTER PERSONNEL (STAFF) ----
    Route::post('/personnel', function (Request $request) {
        try {
            $body  = json_decode($request->getContent(), true) ?? [];
            $email = $body['email'] ?? $request->input('email') ?? null;
            $id = DB::transaction(function () use ($body, $request, $email) {
              $lastItem = DB::table('personnel')->lockForUpdate()->max('item_number') ?? 0;
              $id = DB::table('personnel')->insertGetId([
                'item_number'          => $lastItem + 1,
                'date_of_validity'     => $body['dateOfValidity']     ?? $request->dateOfValidity     ?: null,
                'rank'                 => $body['rank']               ?? $request->rank               ?? '',
                'last_name'            => $body['lastName']           ?? $request->lastName           ?? '',
                'first_name'           => $body['firstName']          ?? $request->firstName          ?? '',
                'middle_name'          => $body['middleName']         ?? $request->middleName         ?? '',
                'afp_serial_number'    => $body['afpSerialNumber']    ?? $request->afpSerialNumber    ?? '',
                'afos_mos'             => $body['afosMos']            ?? $request->afosMos            ?? '',
                'branch'               => $body['branch']             ?? $request->branch             ?? '',
                'unit'                 => $body['unit']               ?? $request->unit               ?? '',
                'date_of_birth'        => $body['dateOfBirth']        ?? $request->dateOfBirth        ?: null,
                'pistol_nomenclature'  => $body['pistolNomenclature'] ?? $request->pistolNomenclature ?? '',
                'pistol_serial_number' => $body['pistolSerialNumber'] ?? $request->pistolSerialNumber ?? '',
                'qty_ammo'             => (int) ($body['qtyAmmo']     ?? $request->qtyAmmo            ?? 0),
                'email'                => $email,
                'photo'                => $body['photo'] ?? $request->input('photo') ?? null,
                'signature'            => $body['signature'] ?? null,
                'approved_status'      => 'new',
                'ics_status'           => 'inspection',
                'status'               => 'active',
                'created_at'           => now(),
                'updated_at'           => now(),
              ]);
              return $id;
            });
            $newRow = DB::table('personnel')->where('id', $id)->first();
            $name   = trim(($newRow->rank ?? '') . ' ' . ($newRow->last_name ?? '') . ', ' . ($newRow->first_name ?? ''));
            auditLog('personnel_added_by_staff', $name, ['item_number' => $newRow->item_number]);
            DB::table('notifications')->insert([
                'type' => 'personnel_added', 'title' => 'New Personnel Added by Staff',
                'message' => "Staff has registered a new personnel: {$name}. Awaiting admin inspection.",
                'personnel_name' => $name, 'personnel_id' => $newRow->item_number,
                'read_by_admin' => false, 'read_by_staff' => true,
                'created_at' => now(), 'updated_at' => now(),
            ]);
            return response()->json(['success' => true, 'data' => [
                'itemNumber'         => $newRow->item_number,
                'dateOfValidity'     => $newRow->date_of_validity     ?? '',
                'rank'               => $newRow->rank                 ?? '',
                'lastName'           => $newRow->last_name            ?? '',
                'firstName'          => $newRow->first_name           ?? '',
                'middleName'         => $newRow->middle_name          ?? '',
                'afpSerialNumber'    => $newRow->afp_serial_number    ?? '',
                'afosMos'            => $newRow->afos_mos             ?? '',
                'branch'             => $newRow->branch               ?? '',
                'unit'               => $newRow->unit                 ?? '',
                'dateOfBirth'        => $newRow->date_of_birth        ?? '',
                'pistolNomenclature' => $newRow->pistol_nomenclature  ?? '',
                'pistolSerialNumber' => $newRow->pistol_serial_number ?? '',
                'qtyAmmo'            => $newRow->qty_ammo             ?? 0,
                'approvedStatus'     => $newRow->approved_status      ?? 'new',
                'email'              => $newRow->email                ?? '',
            ]]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    })->name('staff.personnel.store');

    // ---- NOTIFICATIONS (STAFF) ----
    Route::get('/notifications', function () {
        $notifications = DB::table('notifications')
            ->orderBy('created_at', 'desc')->limit(20)->get()
            ->map(fn($n) => [
                'id'            => $n->id,
                'type'          => $n->type,
                'title'         => $n->title,
                'message'       => $n->message,
                'personnelName' => $n->personnel_name,
                'read'          => (bool) $n->read_by_staff,
                'createdAt'     => $n->created_at,
            ]);
        $unreadCount = DB::table('notifications')->where('read_by_staff', false)->count();
        return response()->json(['success' => true, 'notifications' => $notifications, 'unreadCount' => $unreadCount]);
    })->name('staff.notifications');

    Route::post('/notifications/read', function () {
        DB::table('notifications')->update(['read_by_staff' => true]);
        return response()->json(['success' => true]);
    })->name('staff.notifications.read');

    // ---- APPROVAL ----
    Route::post('/personnel/{id}/approve', function (Request $request, $id) {
        $request->validate(['approvedStatus' => 'required|in:pending,renewed,within,expired']);
        DB::table('personnel')->where('item_number', $id)->update(['approved_status' => $request->approvedStatus, 'updated_at' => now()]);
        $p    = DB::table('personnel')->where('item_number', $id)->first();
        $name = trim(($p->rank ?? '') . ' ' . ($p->last_name ?? '') . ', ' . ($p->first_name ?? ''));
        $statusLabels = ['renewed' => 'Renewed', 'within' => 'Within Renewal Period', 'expired' => 'Expired', 'pending' => 'Pending'];
        $statusLabel  = $statusLabels[$request->approvedStatus] ?? $request->approvedStatus;
        auditLog('approval_changed', $name, ['item_number' => $id, 'new_status' => $request->approvedStatus]);
        DB::table('notifications')->where('personnel_id', $id)->where('type', 'approval_changed')->delete();
        DB::table('notifications')->insert([
            'type' => 'approval_changed', 'title' => 'Approval Status Updated',
            'message' => "Staff has set {$name}'s approval status to {$statusLabel}.",
            'personnel_name' => $name, 'personnel_id' => $id,
            'read_by_admin' => false, 'read_by_staff' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        return response()->json(['success' => true]);
    })->middleware('throttle:approve')->name('staff.personnel.approve');
// ---- ICS SEND FOR INSPECTION ----
    Route::post('/ics/{id}/send-inspection', function (Request $request, $id) {
        $p = DB::table('personnel')->where('item_number', $id)->first();
        if (!$p) return response()->json(['success' => false, 'error' => 'Personnel not found.'], 404);

        DB::table('personnel')->where('item_number', $id)->update([
            'ics_status' => 'under',
            'updated_at' => now(),
        ]);

        $name = trim(($p->rank ?? '') . ' ' . ($p->last_name ?? '') . ', ' . ($p->first_name ?? ''));
        auditLog('ics_sent_for_inspection', $name, ['item_number' => $id]);

        DB::table('notifications')->insert([
            'type'           => 'ics_sent',
            'title'          => 'ICS Sent for Inspection',
            'message'        => "Staff has sent {$name}'s ICS for admin inspection.",
            'personnel_name' => $name,
            'personnel_id'   => $id,
            'read_by_admin'  => false,
            'read_by_staff'  => true,
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        return response()->json(['success' => true]);
    })->name('staff.ics.send-inspection');
    // ---- EMAIL NOTIFY ----
    Route::post('/personnel/{id}/notify', function (Request $request, $id) {
        $request->validate(['email' => 'required|email', 'message' => 'required|string|max:2000']);
        $personnel = DB::table('personnel')->where('item_number', $id)->first();
        if (!$personnel) return response()->json(['success' => false, 'error' => 'Personnel not found.'], 404);
        if (empty($personnel->email)) {
            DB::table('personnel')->where('item_number', $id)->update(['email' => $request->email, 'updated_at' => now()]);
        }
        $name = trim(($personnel->rank ?? '') . ' ' . ($personnel->last_name ?? '') . ', ' . ($personnel->first_name ?? ''));
        try {
           \Illuminate\Support\Facades\Mail::to($request->email)->send(new \App\Mail\PersonnelNotifyMail($name, $request->message));
            auditLog('email_sent', $name, ['item_number' => $id, 'email' => $request->email]);
            DB::table('notifications')->insert([
                'type' => 'email_sent', 'title' => 'Notification Email Sent',
                'message' => "Staff has sent a renewal notification email to {$name}.",
                'personnel_name' => $name, 'personnel_id' => $id,
                'read_by_admin' => false, 'read_by_staff' => true,
                'created_at' => now(), 'updated_at' => now(),
            ]);
            return response()->json(['success' => true, 'message' => 'Email sent successfully.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    })->middleware('throttle:notify')->name('staff.personnel.notify');

}); // END STAFF GROUP

// ===== FORGOT PASSWORD =====
Route::post('/forgot-password/send-otp',   [ForgotPasswordController::class, 'sendOtp']);
Route::post('/forgot-password/verify-otp', [ForgotPasswordController::class, 'verifyOtp']);
Route::post('/forgot-password/reset',      [ForgotPasswordController::class, 'resetPassword']);
Route::get('send-mail', [EmailsController::class, 'renewalMail']);

// Add to web.php temporarily
Route::post('/staff/personnel-debug', function (Request $request) {
    $body = json_decode($request->getContent(), true) ?? [];
    return response()->json([
        'received_keys' => array_keys($body),
        'has_photo'     => isset($body['photo']),
        'photo_length'  => isset($body['photo']) ? strlen($body['photo']) : 0,
    ]);
});

