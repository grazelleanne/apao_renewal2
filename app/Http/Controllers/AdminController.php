<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Models\Personnel;
use App\Models\Notification;
use Carbon\Carbon;

class AdminController extends Controller
{
    // ── DASHBOARD VIEW ─────────────────────────────────────────────────────────

    public function index()
    {
        $user = Auth::user();
        return view('admin.dashboard', compact('user'));
    }

    // ── DASHBOARD DATA (metrics + personnel list) ──────────────────────────────

    public function dashboardData()
    {
        $personnel = Personnel::all([
            'itemNumber', 'rank', 'lastName', 'firstName', 'middleName',
            'afpSerialNumber', 'afosMos', 'branch', 'unit',
            'dateOfValidity', 'dateOfBirth',
            'pistolNomenclature', 'pistolSerialNumber', 'qtyAmmo',
            'approvedStatus', 'email', 'registered_by',
        ]);

        $totalNew      = 0;
        $totalRenewed  = 0;
        $withinRenewal = 0;
        $expired       = 0;
        $pending       = 0;

        foreach ($personnel as $p) {
            match ($p->approvedStatus) {
                'new'     => $totalNew++,
                'pending' => $pending++,
                'renewed' => $totalRenewed++,
                'within'  => $withinRenewal++,
                'expired' => $expired++,
                default   => $pending++,
            };
        }

        return response()->json([
            'success' => true,
            'metrics' => [
                'totalNew'      => $totalNew,
                'totalRenewed'  => $totalRenewed,
                'withinRenewal' => $withinRenewal,
                'expired'       => $expired,
                'pending'       => $pending,
                'total'         => $personnel->count(),
            ],
            'personnel' => $personnel->map(fn($p) => [
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
                'registered_by'      => $p->registered_by,
            ]),
        ]);
    }

    // ── LIST ALL PERSONNEL ─────────────────────────────────────────────────────

    public function personnelIndex()
    {
        $personnel = Personnel::orderByDesc('itemNumber')->get();
        return response()->json(['success' => true, 'data' => $personnel]);
    }

    // ── UPDATE STATUS (core method — triggers auto-notification + email) ────────

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:new,pending,renewed,within,expired',
        ]);

        $personnel = Personnel::where('itemNumber', $id)->firstOrFail();
        $oldStatus = $personnel->approvedStatus;
        $newStatus = $request->status;

        $personnel->approvedStatus = $newStatus;
        $personnel->save();

        // ── AUTO-NOTIFY: status changed TO renewed ─────────────────────────────
        if ($newStatus === 'renewed' && $oldStatus !== 'renewed') {
            $this->handleRenewedNotification($personnel);
        }

        // ── AUTO-NOTIFY: status changed TO expired ─────────────────────────────
        if ($newStatus === 'expired' && $oldStatus !== 'expired') {
            $this->handleExpiredNotification($personnel);
        }

        // ── AUTO-NOTIFY: status changed TO within (renewal period) ────────────
        if ($newStatus === 'within' && $oldStatus !== 'within') {
            $this->handleWithinRenewalNotification($personnel);
        }

        return response()->json([
            'success'    => true,
            'oldStatus'  => $oldStatus,
            'newStatus'  => $newStatus,
            'itemNumber' => $personnel->itemNumber,
        ]);
    }

    // ── DELETE PERSONNEL ───────────────────────────────────────────────────────

    public function destroy($id)
    {
        $personnel = Personnel::where('itemNumber', $id)->firstOrFail();
        $personnel->delete();

        return response()->json(['success' => true, 'message' => 'Personnel record deleted.']);
    }

    // ── ARCHIVE PERSONNEL ──────────────────────────────────────────────────────

    public function archive($id)
    {
        $personnel = Personnel::where('itemNumber', $id)->firstOrFail();

        // If you have a soft-delete or archived_at column:
        // $personnel->archived_at = now();
        // $personnel->save();

        // Or simply delete for now:
        $personnel->delete();

        return response()->json(['success' => true, 'message' => 'Personnel archived.']);
    }

    // ── NOTIFICATIONS (admin bell) ─────────────────────────────────────────────

    public function notifications()
    {
        $notifications = Notification::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->take(30)
            ->get()
            ->map(fn($n) => [
                'title'     => $n->title,
                'message'   => $n->message,
                'type'      => $n->type,
                'read'      => (bool) $n->read,
                'createdAt' => $n->created_at,
            ]);

        $unreadCount = Notification::where('user_id', Auth::id())
            ->where('read', false)
            ->count();

        return response()->json([
            'success'       => true,
            'unreadCount'   => $unreadCount,
            'notifications' => $notifications,
        ]);
    }

    public function markNotificationsRead()
    {
        Notification::where('user_id', Auth::id())
            ->where('read', false)
            ->update(['read' => true]);

        return response()->json(['success' => true]);
    }

    // ── MANUAL NOTIFY (admin sends email manually) ─────────────────────────────

    public function notify(Request $request, $id)
    {
        $request->validate([
            'email'   => 'required|email',
            'message' => 'required|string',
        ]);

        $personnel = Personnel::where('itemNumber', $id)->firstOrFail();
        $status    = $personnel->approvedStatus ?? 'pending';

        $subject = match ($status) {
            'renewed' => '[AFP-PMS] License Renewal Approved — ' . $personnel->lastName . ', ' . $personnel->firstName,
            'expired' => '[AFP-PMS] License Expired — Action Required — ' . $personnel->lastName . ', ' . $personnel->firstName,
            'within'  => '[AFP-PMS] License Expiry Notice — ' . $personnel->lastName . ', ' . $personnel->firstName,
            default   => '[AFP-PMS] Personnel License Notification — ' . $personnel->lastName . ', ' . $personnel->firstName,
        };

        try {
            Mail::send([], [], function ($mail) use ($request, $subject, $personnel) {
                $mail->to($request->email)
                     ->subject($subject)
                     ->html($this->buildEmailHtml($personnel, $request->message));
            });

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('Admin manual notify failed for #' . $id . ': ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error'   => 'Failed to send email: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ══════════════════════════════════════════════════════════════════════════
    // PRIVATE HELPERS
    // ══════════════════════════════════════════════════════════════════════════

    // ── Renewed: bell notification + auto email to personnel ──────────────────

    private function handleRenewedNotification(Personnel $personnel): void
    {
        $fullName = $personnel->lastName . ', ' . $personnel->firstName . ' ' . ($personnel->middleName ?? '');

        // Bell notification → goes to the Staff user who registered this personnel
        if ($personnel->registered_by) {
            Notification::create([
                'user_id' => $personnel->registered_by,
                'title'   => 'License Renewed — ' . trim($fullName),
                'message' => 'Admin has approved the renewal. Auto-email sent to ' . ($personnel->email ?? 'N/A') . '.',
                'type'    => 'renewed',
                'read'    => false,
            ]);
        }

        // Auto-email → goes to the personnel's registered email
        if ($personnel->email) {
            $message = "Dear {$personnel->firstName} {$personnel->lastName},\n\n"
                . "We are pleased to inform you that your pistol license has been officially RENEWED and approved by the Administrator. Your license is now active.\n\n"
                . "Please keep this as your official notification of renewal. If you have any questions, please coordinate with your unit's Staff officer.\n\n"
                . "Regards,\nAPAO Renewal System\nPhilippine Army";

            $this->sendAutoEmail($personnel, $message, 'renewed');
        }
    }

    // ── Expired: bell notification + auto email to personnel ──────────────────

    private function handleExpiredNotification(Personnel $personnel): void
    {
        $fullName = $personnel->lastName . ', ' . $personnel->firstName . ' ' . ($personnel->middleName ?? '');

        if ($personnel->registered_by) {
            Notification::create([
                'user_id' => $personnel->registered_by,
                'title'   => 'License Expired — ' . trim($fullName),
                'message' => 'Personnel license has been marked expired. Auto-email sent to ' . ($personnel->email ?? 'N/A') . '.',
                'type'    => 'expired',
                'read'    => false,
            ]);
        }

        if ($personnel->email) {
            $message = "Dear {$personnel->firstName} {$personnel->lastName},\n\n"
                . "This is to inform you that your pistol license has been marked as EXPIRED. "
                . "Please coordinate immediately with the Property Accountability Office to process your renewal at the earliest possible time.\n\n"
                . "Failure to renew may result in administrative action.\n\n"
                . "Regards,\nAPAO Renewal System\nPhilippine Army";

            $this->sendAutoEmail($personnel, $message, 'expired');
        }
    }

    // ── Within Renewal: bell notification + auto email to personnel ───────────

    private function handleWithinRenewalNotification(Personnel $personnel): void
    {
        $fullName = $personnel->lastName . ', ' . $personnel->firstName . ' ' . ($personnel->middleName ?? '');

        // Calculate days remaining
        $daysLeft = $personnel->dateOfValidity
            ? (int) now()->diffInDays(Carbon::parse($personnel->dateOfValidity), false)
            : null;

        $daysText = $daysLeft !== null ? " ({$daysLeft} days remaining)" : '';

        if ($personnel->registered_by) {
            Notification::create([
                'user_id' => $personnel->registered_by,
                'title'   => 'License Expiring Soon — ' . trim($fullName),
                'message' => 'Personnel license is within the renewal period' . $daysText . '. Auto-email sent to ' . ($personnel->email ?? 'N/A') . '.',
                'type'    => 'within_renewal',
                'read'    => false,
            ]);
        }

        if ($personnel->email) {
            $message = "Dear {$personnel->firstName} {$personnel->lastName},\n\n"
                . "This is a reminder that your pistol license is within the renewal period and will expire soon{$daysText}. "
                . "Please process your renewal before the expiration date to avoid any complications.\n\n"
                . "Please coordinate with the Property Accountability Office at the soonest possible time.\n\n"
                . "Regards,\nAPAO Renewal System\nPhilippine Army";

            $this->sendAutoEmail($personnel, $message, 'within');
        }
    }

    // ── Send auto email (shared by all three handlers) ────────────────────────

    private function sendAutoEmail(Personnel $personnel, string $message, string $status): void
    {
        $subject = match ($status) {
            'renewed' => '[AFP-PMS] License Renewal Approved — ' . $personnel->lastName . ', ' . $personnel->firstName,
            'expired' => '[AFP-PMS] License Expired — Action Required — ' . $personnel->lastName . ', ' . $personnel->firstName,
            'within'  => '[AFP-PMS] License Expiry Notice — ' . $personnel->lastName . ', ' . $personnel->firstName,
            default   => '[AFP-PMS] Personnel License Notification',
        };

        try {
            Mail::send([], [], function ($mail) use ($personnel, $subject, $message) {
                $mail->to($personnel->email)
                     ->subject($subject)
                     ->html($this->buildEmailHtml($personnel, $message));
            });
        } catch (\Exception $e) {
            Log::error('Auto-email failed for personnel #' . $personnel->itemNumber . ': ' . $e->getMessage());
        }
    }

    // ── Build branded HTML email ───────────────────────────────────────────────

    private function buildEmailHtml(Personnel $personnel, string $message): string
    {
        $status    = $personnel->approvedStatus ?? 'pending';
        $headerBg  = match ($status) {
            'renewed' => '#085041',
            'expired' => '#7f1d1d',
            'within'  => '#78350f',
            default   => '#1e3a5f',
        };
        $statusLabel = match ($status) {
            'renewed' => '&#10003; Renewed',
            'expired' => '&#10007; Expired',
            'within'  => '&#8987; Within Renewal Period',
            default   => ucfirst($status),
        };

        $rows = [
            ['Full Name',       strtoupper(trim($personnel->lastName . ', ' . $personnel->firstName . ' ' . ($personnel->middleName ?? '')))],
            ['AFP Serial #',    $personnel->afpSerialNumber    ?? '—'],
            ['Nomenclature',    $personnel->pistolNomenclature ?? '—'],
            ['Pistol Serial #', $personnel->pistolSerialNumber ?? '—'],
            ['Qty Ammo',        ($personnel->qtyAmmo ?? '—') . ' rounds'],
            ['Status',          $statusLabel],
            ['Date Notified',   now()->format('F d, Y \a\t h:i A')],
        ];

        $tableRows = implode('', array_map(fn($r) =>
            "<tr>
                <td style='padding:7px 12px;border:1px solid #d1d5db;background:#f9fafb;
                           font-weight:600;font-size:12px;color:#374151;width:38%;'>{$r[0]}</td>
                <td style='padding:7px 12px;border:1px solid #d1d5db;
                           font-size:12px;color:#111827;'>{$r[1]}</td>
            </tr>",
        $rows));

        $bodyText = nl2br(htmlspecialchars($message));

        return <<<HTML
        <!DOCTYPE html>
        <html>
        <body style="font-family:Arial,sans-serif;background:#f3f4f6;margin:0;padding:24px;">
          <div style="max-width:580px;margin:0 auto;background:#ffffff;
                      border-radius:10px;overflow:hidden;border:1px solid #e5e7eb;">

            <div style="background:{$headerBg};padding:22px 26px;">
              <p style="color:#fff;font-size:11px;margin:0 0 6px;opacity:.75;
                        text-transform:uppercase;letter-spacing:.08em;">
                AFP Personnel Management System
              </p>
              <p style="color:#fff;font-size:17px;font-weight:700;margin:0;">
                Personnel License Notification
              </p>
              <p style="color:rgba(255,255,255,.65);font-size:11px;margin:4px 0 0;">
                Official notification — do not reply to this email
              </p>
            </div>

            <div style="padding:24px 26px;">
              <p style="font-size:13px;color:#111827;line-height:1.75;
                        margin:0 0 18px;">{$bodyText}</p>

              <table style="width:100%;border-collapse:collapse;margin:0 0 20px;">
                {$tableRows}
              </table>

              <p style="font-size:11px;color:#9ca3af;margin:0;line-height:1.6;">
                This is a system-generated email.<br>
                For concerns, please coordinate with your unit's Staff officer or the APAO directly.
              </p>
            </div>

            <div style="background:#f9fafb;padding:12px 26px;
                        border-top:1px solid #e5e7eb;
                        display:flex;justify-content:space-between;">
              <span style="font-size:11px;color:#9ca3af;">
                AFP-PMS &copy; Philippine Army
              </span>
              <span style="font-size:11px;color:#9ca3af;">
                Auto-generated &bull; Secure
              </span>
            </div>
          </div>
        </body>
        </html>
        HTML;
    }
}