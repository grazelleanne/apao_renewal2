<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Models\Personnel;
use App\Models\SystemNotification;
use Carbon\Carbon;

class StaffController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        return view('staff.dashboard', compact('user'));
    }

    public function dashboardData()
    {
        $personnel = Personnel::all([
            'itemNumber', 'rank', 'lastName', 'firstName', 'middleName',
            'afpSerialNumber', 'afosMos', 'branch', 'unit',
            'dateOfValidity', 'dateOfBirth',
            'pistolNomenclature', 'pistolSerialNumber', 'qtyAmmo',
            'approvedStatus', 'email'
        ]);

        $today         = now();
        $totalNew      = 0;
        $totalRenewed  = 0;
        $withinRenewal = 0;
        $expired       = 0;
        $pending       = 0;

        foreach ($personnel as $p) {
            if ($p->approvedStatus === 'new')     { $totalNew++; continue; }
            if ($p->approvedStatus === 'pending')  { $pending++; continue; }
            if ($p->approvedStatus === 'renewed')  { $totalRenewed++; continue; }
            if ($p->approvedStatus === 'within')   { $withinRenewal++; continue; }
            if ($p->approvedStatus === 'expired')  { $expired++; continue; }

            // fallback: compute from dateOfValidity if status not yet set
            if (!$p->dateOfValidity) continue;
            $validity = Carbon::parse($p->dateOfValidity);
            $diffDays = $today->diffInDays($validity, false);

            if ($diffDays < 0)       { $expired++; }
            elseif ($diffDays <= 90) { $withinRenewal++; }
            else                     { $totalRenewed++; }
        }

        return response()->json([
            'success' => true,
            'metrics' => [
                'totalNew'      => $totalNew,
                'totalRenewed'  => $totalRenewed,
                'withinRenewal' => $withinRenewal,
                'expired'       => $expired,
                'pending'       => $pending,
            ],
            'personnel' => $personnel->map(function ($p) {
                return [
                    'itemNumber'         => $p->itemNumber,
                    'rank'               => $p->rank,
                    'lastName'           => $p->lastName,
                    'firstName'          => $p->firstName,
                    'middleName'         => $p->middleName,
                    'afpSerialNumber'    => $p->afpSerialNumber,
                    'afosMos'            => $p->afosMos,
                    'branch'             => $p->branch,
                    'unit'               => $p->unit,
                    'dateOfValidity'     => $p->dateOfValidity,
                    'dateOfBirth'        => $p->dateOfBirth,
                    'pistolNomenclature' => $p->pistolNomenclature,
                    'pistolSerialNumber' => $p->pistolSerialNumber,
                    'qtyAmmo'            => $p->qtyAmmo,
                    'approvedStatus'     => $p->approvedStatus ?? 'pending',
                    'email'              => $p->email ?? '',
                ];
            }),
        ]);
    }

    // ── NOTIFICATIONS ──────────────────────────────────────────────────────────

    public function notifications()
    {
        // Check if Notification model/table exists; if not, return empty gracefully
        if (!class_exists(\App\Models\SystemNotification::class)) {
            return response()->json([
                'success'       => true,
                'unreadCount'   => 0,
                'notifications' => [],
            ]);
        }

        $notifications = \App\Models\SystemNotification::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->take(30)
            ->get()
            ->map(fn($n) => [
                'title'     => $n->title,
                'message'   => $n->message,
                'type'      => $n->type,   // 'renewed' | 'expired' | 'within_renewal'
                'read'      => (bool) $n->is_read,
                'createdAt' => $n->created_at,
            ]);

        $unreadCount = \App\Models\SystemNotification::where('user_id', Auth::id())
            ->where('is_read', false)
            ->count();

        return response()->json([
            'success'       => true,
            'unreadCount'   => $unreadCount,
            'notifications' => $notifications,
        ]);
    }

    public function markNotificationsRead()
    {
        if (class_exists(\App\Models\SystemNotification::class)) {
            \App\Models\SystemNotification::where('user_id', Auth::id())
                ->where('is_read', false)
                ->update(['is_read' => true]);
        }

        return response()->json(['success' => true]);
    }

    // ── NOTIFY (manual email from Notify button) ───────────────────────────────

    public function notify(Request $request, $id)
    {
        $request->validate([
            'email'   => 'required|email',
            'message' => 'required|string',
        ]);

        $personnel = Personnel::where('itemNumber', $id)->firstOrFail();

        $status    = $personnel->approvedStatus ?? 'pending';
        $fullName  = trim(($personnel->rank ?? '') . ' ' . $personnel->lastName . ', ' . $personnel->firstName . ' ' . ($personnel->middleName ?? ''));

        $subject = match ($status) {
            'renewed' => '[AFP-PMS] License Renewal Approved — ' . $personnel->lastName . ', ' . $personnel->firstName,
            'expired' => '[AFP-PMS] License Expired — Immediate Renewal Required — ' . $personnel->lastName . ', ' . $personnel->firstName,
            'within'  => '[AFP-PMS] License Expiry Notice — ' . $personnel->lastName . ', ' . $personnel->firstName,
            default   => '[AFP-PMS] Personnel License Notification — ' . $personnel->lastName . ', ' . $personnel->firstName,
        };

        try {
            Mail::send([], [], function ($mail) use ($request, $subject, $fullName, $personnel) {
                $mail->to($request->email)
                     ->subject($subject)
                     ->html($this->buildEmailHtml($personnel, $request->message));
            });

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error'   => 'Failed to send email: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ── AUTO-NOTIFY when admin approves renewed ────────────────────────────────
    // Call this from AdminController after status update, or wire to a model event.

    public static function sendRenewedNotification(Personnel $personnel)
    {
        // 1. Create bell notification for the staff who registered this personnel
        if (class_exists(\App\Models\SystemNotification::class) && $personnel->registered_by) {
            \App\Models\SystemNotification::create([
                'user_id' => $personnel->registered_by,
                'title'   => 'License Renewed — ' . $personnel->lastName . ', ' . $personnel->firstName,
                'message' => 'Admin approved the renewal. Auto-email sent to ' . ($personnel->email ?? 'N/A') . '.',
                'type'    => 'renewed',
                'read'    => false,
            ]);
        }

        // 2. Auto-email the personnel
        if ($personnel->email) {
            $fullName = trim($personnel->lastName . ', ' . $personnel->firstName . ' ' . ($personnel->middleName ?? ''));
            $subject  = '[AFP-PMS] License Renewal Approved — ' . $fullName;
            $message  = "Dear {$personnel->firstName} {$personnel->lastName},\n\n"
                . "We are pleased to inform you that your pistol license has been officially RENEWED and approved by the Administrator. Your license is now active.\n\n"
                . "Please keep this as your official notification of renewal.\n\n"
                . "Regards,\nAPAO Renewal System";

            try {
                Mail::send([], [], function ($mail) use ($personnel, $subject, $message) {
                    $mail->to($personnel->email)
                         ->subject($subject)
                         ->html((new self)->buildEmailHtml($personnel, $message));
                });
            } catch (\Exception $e) {
                \Log::error('Auto-renewed email failed for personnel #' . $personnel->itemNumber . ': ' . $e->getMessage());
            }
        }
    }

    // ── STORE new personnel ────────────────────────────────────────────────────

    public function store(Request $request)
    {
        $validated = $request->validate([
            'lastName'           => 'required|string|max:255',
            'firstName'          => 'required|string|max:255',
            'middleName'         => 'nullable|string|max:255',
            'rank'               => 'required|string|max:50',
            'dateOfBirth'        => 'required|date',
            'afpSerialNumber'    => 'required|string|max:100',
            'afosMos'            => 'nullable|string|max:100',
            'branch'             => 'nullable|string|max:100',
            'unit'               => 'required|string|max:255',
            'dateOfValidity'     => 'nullable|date',
            'pistolNomenclature' => 'required|string|max:255',
            'pistolSerialNumber' => 'required|string|max:100',
            'qtyAmmo'            => 'nullable|integer|min:0',
            'email'              => 'required|email|max:255',
        ]);

        $validated['approvedStatus'] = 'new';
        $validated['registered_by']  = Auth::id(); // track who registered

        $record = Personnel::create($validated);

        return response()->json(['success' => true, 'data' => $record]);
    }

    // ── APPROVE (staff-side; for admin use a separate AdminController) ─────────

    public function approvePersonnel(Request $request, $id)
    {
        $request->validate([
            'approvedStatus' => 'required|in:pending,new,renewed,within,expired',
        ]);

        $personnel    = Personnel::where('itemNumber', $id)->firstOrFail();
        $oldStatus    = $personnel->approvedStatus;
        $newStatus    = $request->approvedStatus;

        $personnel->approvedStatus = $newStatus;
        $personnel->save();

        // Auto-notify when status changes TO renewed
        if ($newStatus === 'renewed' && $oldStatus !== 'renewed') {
            self::sendRenewedNotification($personnel);
        }

        return response()->json(['success' => true]);
    }

    // ── HELPER: build HTML email body ──────────────────────────────────────────

    private function buildEmailHtml(Personnel $personnel, string $message): string
    {
        $rows = [
            ['AFP Serial #',    $personnel->afpSerialNumber    ?? '—'],
            ['Nomenclature',    $personnel->pistolNomenclature ?? '—'],
            ['Pistol Serial #', $personnel->pistolSerialNumber ?? '—'],
            ['Qty Ammo',        ($personnel->qtyAmmo ?? '—') . ' rds'],
            ['Status',          strtoupper($personnel->approvedStatus ?? '—')],
            ['Date',            now()->format('F d, Y')],
        ];

        $tableRows = array_map(fn($r) =>
            "<tr>
                <td style='padding:6px 12px;border:1px solid #d1d5db;background:#f9fafb;font-weight:600;font-size:13px;color:#374151;width:40%;'>{$r[0]}</td>
                <td style='padding:6px 12px;border:1px solid #d1d5db;font-size:13px;color:#111827;'>{$r[1]}</td>
            </tr>",
        $rows);

        $bodyText = nl2br(htmlspecialchars($message));
        $tableHtml = implode('', $tableRows);

        return <<<HTML
        <!DOCTYPE html>
        <html>
        <body style="font-family:Arial,sans-serif;background:#f3f4f6;margin:0;padding:24px;">
          <div style="max-width:560px;margin:0 auto;background:#ffffff;border-radius:10px;overflow:hidden;border:1px solid #e5e7eb;">
            <div style="background:#085041;padding:20px 24px;">
              <p style="color:#ffffff;font-size:18px;font-weight:700;margin:0;">AFP Personnel Management System</p>
              <p style="color:rgba(255,255,255,0.7);font-size:12px;margin:4px 0 0;">Official Notification — Do not reply to this email</p>
            </div>
            <div style="padding:24px;">
              <p style="font-size:14px;color:#111827;line-height:1.7;">{$bodyText}</p>
              <table style="width:100%;border-collapse:collapse;margin:16px 0;">
                {$tableHtml}
              </table>
              <p style="font-size:12px;color:#9ca3af;margin-top:20px;">This is a system-generated email. Please do not reply.</p>
            </div>
            <div style="background:#f9fafb;padding:12px 24px;border-top:1px solid #e5e7eb;display:flex;justify-content:space-between;">
              <span style="font-size:11px;color:#9ca3af;">AFP-PMS &copy; Philippine Army</span>
              <span style="font-size:11px;color:#9ca3af;">Auto-generated &bull; Secure</span>
            </div>
          </div>
        </body>
        </html>
        HTML;
    }
}
