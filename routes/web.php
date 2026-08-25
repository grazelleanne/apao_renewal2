<?php

use App\Http\Controllers\EmailsController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Http\Controllers\ForgotPasswordController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\ParController;
use App\Http\Controllers\ProfileController;
use App\Models\Inspection;

if (!function_exists('inspectionPartsForFirearm')) {
    function inspectionPartsForFirearm(?string $firearm): array
    {
        $glock17 = [
            'barrel', 'slide', 'recoil_spring_assembly', 'firing_pin', 'spacer_sleeve',
            'firing_pin_spring', 'spring_cups', 'firing_pin_safety', 'firing_pin_safety_spring',
            'extractor', 'extractor_depressor_plunger', 'extractor_depressor_plunger_spring',
            'trigger_loaded_bearing', 'rear_sight', 'front_sight', 'front_sight_screw', 'frame',
            'magazine_catch_spring', 'magazine_catch', 'slide_lock', 'slide_cover_plate', 'connector',
            'trigger_mechanism_housing', 'trigger', 'trigger_spring', 'trigger_with_trigger_bar',
            'slide_stop_lever', 'trigger_pin', 'trigger_housing_pin', 'locking_block_pin',
        ];
        $pistol9mm = [
            'barrel', 'slide', 'recoil_spring_assembly', 'firing_pin', 'extractor',
            'rear_sight', 'front_sight', 'frame', 'magazine_catch', 'trigger',
            'trigger_spring', 'slide_stop_lever', 'trigger_pin', 'trigger_housing_pin',
            'locking_block_pin',
        ];

        return str_contains(strtolower((string) $firearm), 'glock 17') ? $glock17 : $pistol9mm;
    }
}

// =====================================================
// ===== AUDIT LOG HELPER — must be first =============
// =====================================================
if (!function_exists('auditLog')) {
    function auditLog(string $action, ?string $target = null, array $details = []): void
    {
        try {
            $user = session('user');
            DB::table('audit_logs')->insert([
                'user_id'     => $user['id']   ?? null,
                'user_name'   => $user['name'] ?? 'System',
                'user_role'   => $user['role'] ?? 'unknown',
                'action'      => $action,
                'target'      => $target,
                // Production schema defines description as NOT NULL.
                // Store an empty JSON object for actions without extra details.
                'description' => json_encode((object) $details, JSON_UNESCAPED_SLASHES),
                'ip_address'  => request()->ip(),
                'created_at'  => now(),
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('AUDIT LOG INSERT FAILED: ' . $e->getMessage());
        }
    }
}

/**
 * Derive the RPCSP firearm condition from the LATEST Admin inspection.
 *
 * Priority:
 *   Unserviceable > For Replacement > For Repair > Serviceable
 *
 * The overall inspection workflow can remain "under" while the firearm
 * is being repaired/re-inspected. RPCSP condition is derived from the
 * checklist itself, not from the workflow status.
 */
if (!function_exists('rpcspRemarkFromInspection')) {
function rpcspRemarkFromInspection($inspection): string
{
    if (!$inspection) {
        return 'Not Yet Inspected';
    }

    $parts = inspectionPartsForFirearm($inspection->pistol_type ?? 'Glock 17');

    $hasRepair = false;
    $hasReplacement = false;

    foreach ($parts as $part) {
        $value = strtolower(trim((string) ($inspection->$part ?? 'serviceable')));

        if ($value === 'unserviceable') {
            return 'Unserviceable';
        }

        if ($value === 'replace') {
            $hasReplacement = true;
            continue;
        }

        if (in_array($value, ['repair', 'damaged', 'missing'], true)) {
            $hasRepair = true;
        }
    }

    if ($hasReplacement) {
        return 'For Replacement';
    }

    if ($hasRepair) {
        return 'For Repair';
    }

    return 'Serviceable';
}
}


// ===== PUBLIC =====
Route::get('/', fn() => redirect()->route('login'));
Route::get('/login',           fn() => view('login'))->name('login');
// Public account self-registration is disabled.
// User accounts are created only by an authenticated Admin from Manage Users.
Route::get('/register', function () {
    return redirect()->route('login');
})->name('register');

Route::get('/forgot-password', fn() => view('forgot_password'))->name('forgot.password');

Route::post('/register', function () {
    return response()->json([
        'success' => false,
        'message' => 'Public registration is disabled. Please contact the administrator.',
    ], 403);
})->name('register.post');

// ===== LOGIN =====
Route::post('/login', function (Request $request) {
    $request->validate([
        'email'    => 'required|email',
        'password' => 'required',
    ]);

    $email    = Str::lower(trim($request->email));
    $password = $request->password;

    // 5 wrong attempts = 5-minute temporary lock
    $maxAttempts = 5;
    $lockSeconds = 300;
    $throttleKey = 'login:' . $email . '|' . $request->ip();

    if (RateLimiter::tooManyAttempts($throttleKey, $maxAttempts)) {
        $seconds = RateLimiter::availableIn($throttleKey);
        $minutes = max(1, (int) ceil($seconds / 60));

        return response()->json([
            'success'     => false,
            'message'     => "Too many failed login attempts. Please try again in {$minutes} minute(s).",
            'retry_after' => $seconds,
        ], 429);
    }

    $user = DB::table('users')
        ->whereRaw('LOWER(email) = ?', [$email])
        ->first();

    // Generic response prevents account enumeration.
    if (!$user || !Hash::check($password, $user->password)) {
        RateLimiter::hit($throttleKey, $lockSeconds);
        $remaining = RateLimiter::remaining($throttleKey, $maxAttempts);

        try {
            DB::table('audit_logs')->insert([
                'user_id'     => $user->id ?? null,
                'user_name'   => $user->name ?? $email,
                'user_role'   => $user->role ?? 'unknown',
                'action'      => 'login_failed',
                'target'      => $email,
                'description' => json_encode([
                    'message'            => 'Failed login attempt.',
                    'remaining_attempts' => max(0, $remaining),
                ]),
                'ip_address'  => $request->ip(),
                'created_at'  => now(),
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error(
                'FAILED LOGIN AUDIT ERROR: ' . $e->getMessage()
            );
        }

        if ($remaining <= 0) {
            try {
                DB::table('audit_logs')->insert([
                    'user_id'     => $user->id ?? null,
                    'user_name'   => $user->name ?? $email,
                    'user_role'   => $user->role ?? 'unknown',
                    'action'      => 'login_temporarily_locked',
                    'target'      => $email,
                    'description' => json_encode([
                        'message'      => 'Login temporarily locked after repeated failed attempts.',
                        'lock_minutes' => 5,
                    ]),
                    'ip_address'  => $request->ip(),
                    'created_at'  => now(),
                ]);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error(
                    'LOGIN LOCK AUDIT ERROR: ' . $e->getMessage()
                );
            }

            return response()->json([
                'success' => false,
                'message' => 'Too many failed login attempts. Login has been temporarily locked for 5 minutes.',
            ], 429);
        }

        return response()->json([
            'success' => false,
            'message' => "Invalid email or password. {$remaining} attempt(s) remaining.",
        ], 401);
    }

    if (!($user->is_active ?? 1)) {
        RateLimiter::clear($throttleKey);

        try {
            DB::table('audit_logs')->insert([
                'user_id'     => $user->id,
                'user_name'   => $user->name,
                'user_role'   => $user->role,
                'action'      => 'login_blocked',
                'target'      => $user->email,
                'description' => json_encode([
                    'message' => 'Inactive account attempted to login.',
                ]),
                'ip_address'  => $request->ip(),
                'created_at'  => now(),
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error(
                'INACTIVE LOGIN AUDIT ERROR: ' . $e->getMessage()
            );
        }

        return response()->json([
            'success' => false,
            'message' => 'Your account is inactive. Please contact the administrator.',
        ], 403);
    }

    // Successful login
    RateLimiter::clear($throttleKey);
    $request->session()->regenerate();

    // Preserve the session format used by check.session middleware.
    session([
        'user' => (array) $user,
    ]);

    DB::table('users')
        ->where('id', $user->id)
        ->update([
            'last_login_at' => now(),
        ]);

    auditLog('login', $user->email, [
        'role' => $user->role,
    ]);

    $redirect = match ($user->role ?? '') {
        'admin' => route('admin.dashboard'),
        'staff' => route('staff.dashboard'),
        default => null,
    };

    if (!$redirect) {
        session()->forget('user');

        return response()->json([
            'success' => false,
            'message' => 'Your account does not have permission to access a dashboard.',
        ], 403);
    }

    return response()->json([
        'success'  => true,
        'redirect' => $redirect,
        'message'  => 'Welcome back, ' . $user->name . '!',
    ]);
})->name('login.post');

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

    Route::put('/profile', [ProfileController::class, 'update'])
        ->name('admin.profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])
        ->name('admin.profile.password');

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
            'photo'              => $p->photo                ?? null,
            'signature'          => $p->signature            ?? null,
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
        $rows = DB::table('personnel')
            ->whereNull('archived_at')
            ->orderBy('item_number')
            ->get();

        $latestInspections = DB::table('inspections')
            ->whereIn('item_number', $rows->pluck('item_number'))
            ->orderByDesc('id')
            ->get()
            ->unique('item_number')
            ->keyBy('item_number');

        $personnel = $rows->map(function ($p) use ($latestInspections) {
            $inspection = $latestInspections->get($p->item_number);

            return [
                'itemNumber'          => $p->item_number          ?? '',
                'dateOfValidity'      => $p->date_of_validity     ?? '',
                'rank'                => $p->rank                 ?? '',
                'lastName'            => $p->last_name            ?? '',
                'firstName'           => $p->first_name           ?? '',
                'middleName'          => $p->middle_name          ?? '',
                'afpSerialNumber'     => $p->afp_serial_number    ?? '',
                'afosMos'             => $p->afos_mos             ?? '',
                'branch'              => $p->branch               ?? '',
                'unit'                => $p->unit                 ?? '',
                'dateOfBirth'         => $p->date_of_birth        ?? '',
                'pistolNomenclature'  => $p->pistol_nomenclature  ?? '',
                'pistolSerialNumber'  => $p->pistol_serial_number ?? '',
                'qtyAmmo'             => $p->qty_ammo             ?? 0,
                'status'              => $p->status               ?? '',
                'approvedStatus'      => $p->approved_status      ?? 'pending',
                'email'               => $p->email                ?? '',
                'photo'               => $p->photo                ?? null,
                'signature'           => $p->signature            ?? null,

                // Latest Admin inspection information used by RPCSP.
                'inspectionStatus'    => $inspection->status      ?? null,
                'inspectionRemarks'   => $inspection->remarks     ?? '',
                'inspectionUpdatedAt' => $inspection->updated_at  ?? null,
                'rpcspRemark'         => rpcspRemarkFromInspection($inspection),
            ];
        });

        return response()->json([
            'success' => true,
            'data'    => $personnel,
        ]);
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
                'photo'                => $request->input('photo')      ?? null,
                'signature'            => $request->input('signature')  ?? null,
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
                'photo'              => $newRow->photo                ?? null,
                'signature'          => $newRow->signature            ?? null,
            ]]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    })->name('admin.personnel.store');

    Route::put('/personnel-data/{id}', function (Request $request, $id) {
        $currentPersonnel = DB::table('personnel')->where('item_number', $id)->first();

        if (!$currentPersonnel) {
            return response()->json([
                'success' => false,
                'error' => 'Personnel not found.',
            ], 404);
        }

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
            'photo'                => $request->has('photo')
                ? $request->input('photo')
                : ($currentPersonnel->photo ?? null),
            'signature'            => $request->has('signature')
                ? $request->input('signature')
                : ($currentPersonnel->signature ?? null),
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
            'photo'              => $updated->photo                ?? null,
            'signature'          => $updated->signature            ?? null,
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
        // Only validated scalar values are allowed to reach the query builder.
        // The builder binds these values as parameters; they are never SQL text.
        $filters = $request->validate([
            'action' => ['sometimes', 'nullable', 'string', 'max:100'],
            'user_name' => ['sometimes', 'nullable', 'string', 'max:100'],
            'date_from' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
            'date_to' => ['sometimes', 'nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
        ]);

        $query = DB::table('audit_logs')->orderBy('created_at', 'desc');
        if (!empty($filters['action']))    { $query->where('action', $filters['action']); }
        if (!empty($filters['user_name'])) { $query->where('user_name', 'like', '%' . $filters['user_name'] . '%'); }
        if (!empty($filters['date_from'])) { $query->whereDate('created_at', '>=', $filters['date_from']); }
        if (!empty($filters['date_to']))   { $query->whereDate('created_at', '<=', $filters['date_to']); }
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

    // ---- USERS / ACCOUNT SECURITY ----
    Route::get('/users', function () {
        $user = (object) session('user');
        return view('admin_users', compact('user'));
    })->name('admin.users');

    /*
    |--------------------------------------------------------------------------
    | USER ACCOUNT DATA
    |--------------------------------------------------------------------------
    | Returns only the account information needed by the Manage Users page.
    | Password hashes are NEVER returned to the browser.
    */
    Route::get('/users-data', function () {
        $users = DB::table('users')
            ->select(
                'id',
                'name',
                'email',
                'role',
                'is_active',
                'last_login_at',
                'created_at'
            )
            ->orderByRaw("CASE WHEN role = 'admin' THEN 0 ELSE 1 END")
            ->orderBy('name')
            ->get()
            ->map(fn($u) => [
                'id'          => $u->id,
                'username'    => $u->email,
                'fullName'    => $u->name,
                'role'        => ucfirst($u->role ?? 'staff'),
                'status'      => (bool) ($u->is_active ?? 1) ? 'Active' : 'Inactive',
                'lastLoginAt' => $u->last_login_at ?? null,
                'createdAt'   => $u->created_at ?? null,
            ]);

        return response()->json([
            'success' => true,
            'data'    => $users,
        ]);
    })->middleware('throttle:60,1')->name('admin.users.data');

    /*
    |--------------------------------------------------------------------------
    | CREATE USER
    |--------------------------------------------------------------------------
    | Security:
    | - Admin must confirm their own password.
    | - New account password must be strong.
    | - Email is normalized before saving.
    | - Every creation is written to the Audit Log.
    */
    Route::post('/users-store', function (Request $request) {
        $request->merge([
            'username' => Str::lower(trim((string) $request->input('username'))),
            'role'     => Str::lower(trim((string) $request->input('role'))),
        ]);

        $request->validate([
            'username'      => ['required', 'email', 'max:255', 'unique:users,email'],
            'fullName'      => ['required', 'string', 'max:255'],
            'role'          => ['required', 'in:admin,staff'],
            'password'      => [
                'required',
                'string',
                'min:10',
                'max:255',
                'regex:/[A-Z]/',
                'regex:/[a-z]/',
                'regex:/[0-9]/',
                'regex:/[^A-Za-z0-9]/',
            ],
            'status'        => ['nullable', 'in:Active,Inactive'],
            'adminPassword' => ['required', 'string'],
        ], [
            'password.regex' => 'Password must contain uppercase, lowercase, number, and special character.',
        ]);

        $sessionUser = session('user');
        $adminId = is_array($sessionUser)
            ? ($sessionUser['id'] ?? null)
            : ($sessionUser->id ?? null);

        $currentAdmin = $adminId
            ? DB::table('users')->where('id', $adminId)->first()
            : null;

        if (
            !$currentAdmin ||
            Str::lower((string) $currentAdmin->role) !== 'admin' ||
            !Hash::check($request->adminPassword, $currentAdmin->password)
        ) {
            auditLog('admin_password_confirmation_failed', $request->username, [
                'operation' => 'create_user',
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Administrator password is incorrect.',
            ], 403);
        }

        $newUserId = DB::table('users')->insertGetId([
            'name'          => trim($request->fullName),
            'email'         => $request->username,
            'password'      => Hash::make($request->password),
            'role'          => $request->role,
            'is_active'     => ($request->status ?? 'Active') === 'Active' ? 1 : 0,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        auditLog('user_created', $request->username, [
            'user_id' => $newUserId,
            'role'    => $request->role,
            'status'  => $request->status ?? 'Active',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'User account created successfully.',
        ]);
    })->middleware('throttle:10,1')->name('admin.users.store');

    /*
    |--------------------------------------------------------------------------
    | UPDATE USER / CHANGE ROLE / CHANGE STATUS / RESET PASSWORD
    |--------------------------------------------------------------------------
    |
    | The same secured endpoint is used by the improved Manage Users page.
    |
    | Sensitive changes:
    | - role change
    | - activate/deactivate
    | - password reset
    |
    | Sensitive changes require the CURRENT administrator's password.
    |
    | Additional protections:
    | - Admin cannot deactivate their own current account.
    | - Admin cannot demote their own current account.
    | - The last active Admin cannot be deactivated or demoted.
    */
    Route::put('/users-update', function (Request $request) {
        $request->merge([
            'username' => Str::lower(trim((string) $request->input('username'))),
            'role'     => Str::lower(trim((string) $request->input('role'))),
        ]);

        $request->validate([
            'username'      => ['required', 'email', 'max:255'],
            'fullName'      => ['required', 'string', 'max:255'],
            'role'          => ['required', 'in:admin,staff'],
            'status'        => ['required', 'in:Active,Inactive'],
            'adminPassword' => ['nullable', 'string'],
            'newPassword'   => [
                'nullable',
                'string',
                'min:10',
                'max:255',
                'regex:/[A-Z]/',
                'regex:/[a-z]/',
                'regex:/[0-9]/',
                'regex:/[^A-Za-z0-9]/',
            ],
        ], [
            'newPassword.regex' => 'New password must contain uppercase, lowercase, number, and special character.',
        ]);

        $targetUser = DB::table('users')
            ->whereRaw('LOWER(email) = ?', [$request->username])
            ->first();

        if (!$targetUser) {
            return response()->json([
                'success' => false,
                'message' => 'User account not found.',
            ], 404);
        }

        $sessionUser = session('user');
        $adminId = is_array($sessionUser)
            ? ($sessionUser['id'] ?? null)
            : ($sessionUser->id ?? null);

        $currentAdmin = $adminId
            ? DB::table('users')->where('id', $adminId)->first()
            : null;

        if (!$currentAdmin || Str::lower((string) $currentAdmin->role) !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Administrator session could not be verified.',
            ], 403);
        }

        $oldRole   = Str::lower((string) ($targetUser->role ?? 'staff'));
        $newRole   = $request->role;
        $oldActive = (bool) ($targetUser->is_active ?? 1);
        $newActive = $request->status === 'Active';

        $roleChanged     = $oldRole !== $newRole;
        $statusChanged   = $oldActive !== $newActive;
        $passwordChanged = $request->filled('newPassword');
        $nameChanged     = trim((string) $targetUser->name) !== trim((string) $request->fullName);

        $sensitiveChange = $roleChanged || $statusChanged || $passwordChanged;

        // Require administrator password for every sensitive account operation.
        if ($sensitiveChange) {
            if (
                !$request->filled('adminPassword') ||
                !Hash::check($request->adminPassword, $currentAdmin->password)
            ) {
                auditLog('admin_password_confirmation_failed', $targetUser->email, [
                    'operation' => $passwordChanged
                        ? 'password_reset'
                        : ($roleChanged ? 'role_change' : 'status_change'),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Administrator password is incorrect.',
                ], 403);
            }
        }

        $isCurrentAdmin = (int) $targetUser->id === (int) $currentAdmin->id;

        // Never allow the signed-in Admin to lock themselves out.
        if ($isCurrentAdmin && !$newActive) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot deactivate your own administrator account.',
            ], 403);
        }

        if ($isCurrentAdmin && $newRole !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'You cannot change your own administrator role to Staff.',
            ], 403);
        }

        // Never allow removal of the final active administrator.
        $wouldRemoveActiveAdmin =
            $oldRole === 'admin' &&
            $oldActive &&
            ($newRole !== 'admin' || !$newActive);

        if ($wouldRemoveActiveAdmin) {
            $activeAdminCount = DB::table('users')
                ->where('role', 'admin')
                ->where('is_active', 1)
                ->count();

            if ($activeAdminCount <= 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'This account is the last active administrator and cannot be deactivated or changed to Staff.',
                ], 403);
            }
        }

        $updates = [
            'name'       => trim($request->fullName),
            'role'       => $newRole,
            'is_active'  => $newActive ? 1 : 0,
            'updated_at' => now(),
        ];

        if ($passwordChanged) {
            $updates['password'] = Hash::make($request->newPassword);
        }

        DB::table('users')
            ->where('id', $targetUser->id)
            ->update($updates);

        // If the current Admin only changed their own display name,
        // keep the session copy synchronized.
        if ($isCurrentAdmin) {
            $freshAdmin = DB::table('users')->where('id', $currentAdmin->id)->first();
            session(['user' => (array) $freshAdmin]);
        }

        if ($roleChanged) {
            auditLog('user_role_changed', $targetUser->email, [
                'old_role' => $oldRole,
                'new_role' => $newRole,
            ]);
        }

        if ($statusChanged) {
            auditLog('user_status_changed', $targetUser->email, [
                'old_status' => $oldActive ? 'Active' : 'Inactive',
                'new_status' => $newActive ? 'Active' : 'Inactive',
            ]);
        }

        if ($passwordChanged) {
            auditLog('user_password_reset', $targetUser->email, [
                'reset_by_admin' => $currentAdmin->email,
            ]);
        }

        if ($nameChanged && !$roleChanged && !$statusChanged && !$passwordChanged) {
            auditLog('user_updated', $targetUser->email, [
                'field'    => 'name',
                'old_name' => $targetUser->name,
                'new_name' => trim($request->fullName),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => $passwordChanged
                ? 'Password reset successfully.'
                : 'User account updated successfully.',
        ]);
    })->middleware('throttle:15,1')->name('admin.users.update');

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

        // Add the derived firearm condition from each latest inspection.
        $latestInspectionMap = DB::table('inspections')
            ->whereIn('item_number', $personnel->pluck('itemNumber'))
            ->orderByDesc('id')
            ->get()
            ->unique('item_number')
            ->keyBy('item_number');

        $personnel = $personnel->map(function ($p) use ($latestInspectionMap) {
            $inspection = $latestInspectionMap->get($p['itemNumber']);
            $p['rpcspRemark'] = rpcspRemarkFromInspection($inspection);
            $p['inspectionRemarks'] = $inspection->remarks ?? '';
            return $p;
        });

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

    $parts = inspectionPartsForFirearm($latest->pistol_type ?? $p->pistol_nomenclature ?? 'Glock 17');

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
        'checklistParts' => $parts,
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
        $defaultSignatories = [
            'inspected' => ['name' => 'Rennan F. Maglasang Jr', 'rank' => 'Cpl (OS) PA', 'position' => 'Armaments NCO', 'signature' => '/images/maglasang.png'],
            'witnessed' => ['name' => 'Marcelito H. Anino', 'rank' => 'MAJ (QMS) PA', 'position' => '901BDE, 9ID, PA', 'signature' => '/images/anino.png'],
            'approved'  => ['name' => 'Wenlie B. Enriola', 'rank' => 'CPT (OS) PA', 'position' => 'CO, Maintenance Coy', 'signature' => '/images/enriola.png'],
            'noted'     => ['name' => 'Darrell P. Mariano', 'rank' => 'LTC OS (GSC) PA', 'position' => 'CO, 10FSSU, SPTCOM, PA', 'signature' => '/images/mariano.png'],
        ];
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

        // The persisted personnel nomenclature is authoritative so a client cannot
        // submit a different firearm model or checklist.
        $firearmType = $p->pistol_nomenclature ?? 'Glock 17';
        $parts = inspectionPartsForFirearm($firearmType);
        $request->validate(array_fill_keys(
            $parts,
            'required|in:serviceable,repair,replace,unserviceable,na,missing,damaged'
        ));

        $data = [
            'personnel_id'          => $p->id,
            'item_number'           => $request->itemNumber,
            'afp_serial_number'     => $p->afp_serial_number,
            'pistol_type'           => $firearmType,
            'date_registered'       => now()->toDateString(),
            'status'                => $request->status,
            'remarks'               => $request->remarks               ?? null,
            'inspected_by_name'     => $request->inspectedByName       ?? $defaultSignatories['inspected']['name'],
            'inspected_by_rank'     => $request->inspectedByRank       ?? $defaultSignatories['inspected']['rank'],
            'inspected_by_position' => $request->inspectedByPosition   ?? $defaultSignatories['inspected']['position'],
            'inspected_by_sig'      => $request->inspectedBySig          ?? $defaultSignatories['inspected']['signature'],
            'witnessed_by_name'     => $request->witnessedByName       ?? $defaultSignatories['witnessed']['name'],
            'witnessed_by_rank'     => $request->witnessedByRank       ?? $defaultSignatories['witnessed']['rank'],
            'witnessed_by_position' => $request->witnessedByPosition   ?? $defaultSignatories['witnessed']['position'],
            'witnessed_by_sig'      => $request->witnessedBySig          ?? $defaultSignatories['witnessed']['signature'],
            'approved_by_name'      => $request->approvedByName        ?? $defaultSignatories['approved']['name'],
            'approved_by_rank'      => $request->approvedByRank        ?? $defaultSignatories['approved']['rank'],
            'approved_by_position'  => $request->approvedByPosition    ?? $defaultSignatories['approved']['position'],
            'approved_by_sig'       => $request->approvedBySig           ?? $defaultSignatories['approved']['signature'],
            'noted_by_name'         => $request->notedByName           ?? $defaultSignatories['noted']['name'],
            'noted_by_rank'         => $request->notedByRank           ?? $defaultSignatories['noted']['rank'],
            'noted_by_position'     => $request->notedByPosition       ?? $defaultSignatories['noted']['position'],
            'noted_by_sig'          => $request->notedBySig              ?? $defaultSignatories['noted']['signature'],
            'inspected_by_user_id'  => $user['id']                     ?? null,
            'inspected_at'          => now(),
            'created_at'            => now(),
            'updated_at'            => now(),
            
        ];

        foreach ($parts as $part) {
            $data[$part] = $request->input($part);
        }

        // Derive the physical condition directly from this checklist.
        $rpcspRemark = rpcspRemarkFromInspection((object) $data);

        // Repair / damaged firearms stay in the MAIN "under" workflow.
        // The checklist condition carries "For Repair", "For Replacement",
        // or "Unserviceable" for RPCSP and re-inspection purposes.
        if ($request->status === 'needs_repair') {
            $data['status'] = 'under';
        }

        // Do not allow an inspection with defects to be marked for renewal.
        if ($request->status === 'approved' && $rpcspRemark !== 'Serviceable') {
            return response()->json([
                'success' => false,
                'error'   => "Cannot mark this firearm for renewal. Current inspection condition: {$rpcspRemark}. Repair/correct the firearm and perform re-inspection first.",
                'rpcspRemark' => $rpcspRemark,
            ], 422);
        }

        $id = DB::table('inspections')->insertGetId($data);

        if ($request->status === 'approved') {
    // IMPORTANT: capture the OLD validity before updating personnel.
    $previousValidity = $p->date_of_validity ?? null;
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

    // Save renewal history using the validity captured BEFORE the update.
    DB::table('renewal_history')->insert([
        'item_number'       => $request->itemNumber,
        'action'            => 'renewed',
        'date_of_validity'  => $newValidity,
        'previous_validity' => $previousValidity,
        'inspected_by'      => $request->inspectedByName ?? ($user['name'] ?? 'Admin'),
        'remarks'           => $request->remarks ?? null,
        'created_at'        => now(),
        'updated_at'        => now(),
    ]);
}

        $name = trim(($p->rank ?? '') . ' ' . ($p->last_name ?? '') . ', ' . ($p->first_name ?? ''));
        auditLog('inspection_saved', $name, [
            'item_number'      => $request->itemNumber,
            'status'           => $data['status'],
            'firearm_condition'=> $rpcspRemark,
        ]);

        DB::table('notifications')->insert([
            'type'           => 'inspection_saved',
            'title'          => 'Inspection Saved',
            'message'        => "Inspection for {$name} saved. Workflow: {$data['status']}. Firearm condition: {$rpcspRemark}.",
            'personnel_name' => $name,
            'personnel_id'   => $request->itemNumber,
            'read_by_admin'  => true,
            'read_by_staff'  => false,
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        return response()->json([
            'success'     => true,
            'id'          => $id,
            'status'      => $data['status'],
            'rpcspRemark' => $rpcspRemark,
        ]);
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
        $userId = is_object($sessionUser)
            ? ($sessionUser->id ?? null)
            : ($sessionUser['id'] ?? null);

        if (!$userId) {
            return redirect()->route('login');
        }

        $user = DB::table('users')->where('id', $userId)->first();

        if (!$user) {
            session()->forget('user');
            return redirect()->route('login');
        }

        session(['user' => (array) $user]);

        return view('staff_dashboard', compact('user'));
    })->name('staff.dashboard');

    Route::put('/profile', [ProfileController::class, 'update'])->name('staff.profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('staff.profile.password');

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
            $rpcspRemark = rpcspRemarkFromInspection($inspection);

            $inspectionResult = match (true) {
                !$inspection => null,
                $inspection->status === 'approved' => 'Passed',
                in_array($rpcspRemark, ['For Repair', 'For Replacement', 'Unserviceable'], true) => $rpcspRemark,
                $inspection->status === 'under' => 'In Progress',
                $inspection->status === 'needs_repair' => 'For Repair',
                $inspection->status === 'pending' => 'Pending',
                default => null,
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

            // Digital signature captured during New Registration.
            // PAR and ICS use this as the personnel / receiver signature.
            'signature'          => $p->signature            ?? null,

            'icsStatus'           => $p->ics_status           ?? 'inspection',
            'inspectionResult'    => $inspectionResult,
            'inspectionUpdatedAt' => $inspection?->updated_at,
            'inspectionRemarks'   => $inspection?->remarks ?? '',
            'rpcspRemark'         => $rpcspRemark,
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
                'photo'              => $newRow->photo                ?? null,
                'signature'          => $newRow->signature            ?? null,
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

