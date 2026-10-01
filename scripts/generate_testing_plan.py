from pathlib import Path
from datetime import date

from reportlab.lib import colors
from reportlab.lib.enums import TA_CENTER, TA_LEFT
from reportlab.lib.pagesizes import A4, landscape
from reportlab.lib.styles import getSampleStyleSheet, ParagraphStyle
from reportlab.lib.units import mm
from reportlab.platypus import (
    BaseDocTemplate, PageTemplate, Frame, Paragraph, Spacer, PageBreak,
    LongTable, Table, TableStyle, KeepTogether
)
from reportlab.pdfbase.ttfonts import TTFont
from reportlab.pdfbase import pdfmetrics


ROOT = Path(__file__).resolve().parents[1]
OUT = ROOT / "output" / "pdf" / "APAO_Revised_Testing_Plan.pdf"
OUT.parent.mkdir(parents=True, exist_ok=True)

PAGE = landscape(A4)
W, H = PAGE
MARGIN_X = 14 * mm
MARGIN_TOP = 18 * mm
MARGIN_BOTTOM = 14 * mm

NAVY = colors.HexColor("#17324D")
BLUE = colors.HexColor("#245B78")
TEAL = colors.HexColor("#177E89")
GOLD = colors.HexColor("#D6A84B")
PALE = colors.HexColor("#EDF4F7")
PALE_GOLD = colors.HexColor("#FFF7E5")
INK = colors.HexColor("#17212B")
MUTED = colors.HexColor("#51606F")
GRID = colors.HexColor("#A9BAC5")
RED = colors.HexColor("#A23B3B")
GREEN = colors.HexColor("#287A52")


def register_fonts():
    candidates = [
        ("Segoe", r"C:\Windows\Fonts\segoeui.ttf"),
        ("Segoe-Bold", r"C:\Windows\Fonts\segoeuib.ttf"),
    ]
    for name, path in candidates:
        if Path(path).exists():
            pdfmetrics.registerFont(TTFont(name, path))
    return "Segoe" if "Segoe" in pdfmetrics.getRegisteredFontNames() else "Helvetica"


FONT = register_fonts()
BOLD = "Segoe-Bold" if "Segoe-Bold" in pdfmetrics.getRegisteredFontNames() else "Helvetica-Bold"

styles = getSampleStyleSheet()
styles.add(ParagraphStyle(
    name="DocTitle", fontName=BOLD, fontSize=23, leading=28, textColor=NAVY,
    alignment=TA_CENTER, spaceAfter=7 * mm,
))
styles.add(ParagraphStyle(
    name="DocSubtitle", fontName=FONT, fontSize=11.5, leading=16, textColor=BLUE,
    alignment=TA_CENTER, spaceAfter=5 * mm,
))
styles.add(ParagraphStyle(
    name="H1x", fontName=BOLD, fontSize=16, leading=19, textColor=NAVY,
    spaceBefore=2 * mm, spaceAfter=4 * mm,
))
styles.add(ParagraphStyle(
    name="H2x", fontName=BOLD, fontSize=11.5, leading=14, textColor=BLUE,
    spaceBefore=3 * mm, spaceAfter=2 * mm,
))
styles.add(ParagraphStyle(
    name="Bodyx", fontName=FONT, fontSize=8.7, leading=12, textColor=INK,
    spaceAfter=2.2 * mm,
))
styles.add(ParagraphStyle(
    name="Smallx", fontName=FONT, fontSize=7.3, leading=9.2, textColor=INK,
))
styles.add(ParagraphStyle(
    name="Tinyx", fontName=FONT, fontSize=6.5, leading=8, textColor=INK,
))
styles.add(ParagraphStyle(
    name="Callout", fontName=FONT, fontSize=8.5, leading=12, textColor=INK,
    leftIndent=4 * mm, rightIndent=4 * mm, spaceBefore=2 * mm, spaceAfter=3 * mm,
    borderColor=GOLD, borderWidth=0.8, borderPadding=6, backColor=PALE_GOLD,
))


def P(text, style="Bodyx"):
    safe = str(text).replace("&", "&amp;").replace("<", "&lt;").replace(">", "&gt;")
    safe = safe.replace("\n", "<br/>")
    return Paragraph(safe, styles[style])


def rawP(text, style="Bodyx"):
    return Paragraph(text, styles[style])


def header_footer(canvas, doc):
    canvas.saveState()
    canvas.setFillColor(NAVY)
    canvas.rect(0, H - 10 * mm, W, 10 * mm, fill=1, stroke=0)
    canvas.setFillColor(colors.white)
    canvas.setFont(BOLD, 7.5)
    canvas.drawString(MARGIN_X, H - 6.5 * mm, "APAO PERSONNEL FIREARMS RECORDS AND RENEWAL SYSTEM")
    canvas.setFont(FONT, 7)
    canvas.drawRightString(W - MARGIN_X, H - 6.5 * mm, "REVISED TESTING PLAN | 01 OCT 2026")
    canvas.setStrokeColor(GRID)
    canvas.line(MARGIN_X, 10 * mm, W - MARGIN_X, 10 * mm)
    canvas.setFillColor(MUTED)
    canvas.setFont(FONT, 7)
    canvas.drawString(MARGIN_X, 6.5 * mm, "Controlled working document - record actual results in the execution columns")
    canvas.drawRightString(W - MARGIN_X, 6.5 * mm, f"Page {doc.page}")
    canvas.restoreState()


doc = BaseDocTemplate(
    str(OUT), pagesize=PAGE, leftMargin=MARGIN_X, rightMargin=MARGIN_X,
    topMargin=MARGIN_TOP, bottomMargin=MARGIN_BOTTOM,
    title="APAO Revised Testing Plan", author="APAO Project Team",
    subject="System-aligned unit, integration, system and acceptance testing plan",
)
frame = Frame(MARGIN_X, MARGIN_BOTTOM, W - 2 * MARGIN_X, H - MARGIN_TOP - MARGIN_BOTTOM, id="main")
doc.addPageTemplates(PageTemplate(id="landscape", frames=[frame], onPage=header_footer))


def table(data, widths, header=True, font=6.5, repeats=1, row_bgs=True):
    converted = []
    for r, row in enumerate(data):
        converted.append([
            cell if hasattr(cell, "wrap") else P(cell, "Tinyx" if font <= 6.5 else "Smallx")
            for cell in row
        ])
    t = LongTable(converted, colWidths=widths, repeatRows=repeats if header else 0, hAlign="LEFT")
    commands = [
        ("VALIGN", (0, 0), (-1, -1), "TOP"),
        ("GRID", (0, 0), (-1, -1), 0.45, GRID),
        ("LEFTPADDING", (0, 0), (-1, -1), 4),
        ("RIGHTPADDING", (0, 0), (-1, -1), 4),
        ("TOPPADDING", (0, 0), (-1, -1), 4),
        ("BOTTOMPADDING", (0, 0), (-1, -1), 4),
    ]
    if header:
        commands += [
            ("BACKGROUND", (0, 0), (-1, 0), NAVY),
            ("TEXTCOLOR", (0, 0), (-1, 0), colors.white),
            ("FONTNAME", (0, 0), (-1, 0), BOLD),
        ]
    if row_bgs:
        for r in range(1 if header else 0, len(data)):
            if r % 2 == 0:
                commands.append(("BACKGROUND", (0, r), (-1, r), PALE))
    t.setStyle(TableStyle(commands))
    return t


story = []

# Cover
story += [Spacer(1, 23 * mm), P("REVISED TESTING PLAN", "DocTitle"),
          P("Web-Based Personnel Firearms Records, Notification Monitoring and Renewal System with Analytics", "DocSubtitle"),
          P("4th Infantry Division - 10th Field Property Accountability Office (APAO)", "DocSubtitle")]
cover_data = [
    ["Document version", "2.0 - system-aligned revision"],
    ["Baseline reviewed", "Laravel application in apao_renewal workspace"],
    ["Source plan", "TESTING PLAN.pdf (16 pages)"],
    ["Review date", "01 October 2026"],
    ["Status", "Ready for controlled execution; results not pre-filled"],
]
ct = table(cover_data, [48 * mm, 120 * mm], header=False, row_bgs=False)
ct.setStyle(TableStyle([("BACKGROUND", (0, 0), (0, -1), NAVY), ("TEXTCOLOR", (0, 0), (0, -1), colors.white),
                        ("FONTNAME", (0, 0), (0, -1), BOLD)]))
story += [Spacer(1, 8 * mm), Table([["", ct, ""]], colWidths=[35 * mm, 168 * mm, 35 * mm]), Spacer(1, 8 * mm),
          rawP("<b>Purpose.</b> This revision preserves the four-level structure of the supplied plan, corrects inaccurate expectations, and adds coverage for implemented security, recovery, export, notification, session, and document-generation behavior.", "Callout"),
          rawP("<b>Important.</b> This is a test plan, not a test report. Blank Actual Result, Status, Evidence, Tester and Date fields must be completed during execution. A planned test may expose a defect; it must not be marked Pass merely because it appears in this document.", "Callout"),
          PageBreak()]

# Revision assessment
story += [P("1. Revision assessment", "H1x"),
          P("The supplied plan had a useful functional backbone, but it did not fully match the current application. The following corrections are incorporated throughout this revision."),
          table([
              ["Finding", "Correction in this revision", "Impact"],
              ["UT-101 treated staff@apao.mil.ph and admin@apao.mil.ph as invalid.", "Use genuinely malformed inputs (for example staff@ or staff.apao.mil.ph); valid domain-style addresses must pass syntax validation.", "Prevents a false expected result."],
              ["Password policy was described as one rule.", "Separate 10-character admin/profile rules from the 12-character password-reset and super-admin rules.", "Matches implemented validators."],
              ["RPCSP export was called CSV.", "Rename to Excel-compatible .xls and verify workbook structure and binary-data exclusion.", "Matches the UI and JavaScript exporter."],
              ["ISO/IEC 25010 section omitted Compatibility and called every subtotal a SUS score.", "Use nine ISO/IEC 25010:2023 characteristics and keep the 10-item SUS instrument separate.", "Corrects evaluation method and terminology."],
              ["Security and recovery flows were incomplete.", "Add CAPTCHA, OTP reset, security headers, injection handling, cache controls, role boundaries, session timeout and single-tab cases.", "Covers implemented protections."],
              ["Core workflow details were absent.", "Add uniqueness pre-checks, renewal-date boundaries, notification read state, inspection/PAR PDFs, PAR metrics/history, and audit/report exports.", "Improves traceability to current routes and client logic."],
              ["Automated expiry notification was assumed complete.", "Classify scheduled expiry notification as a known implementation gap until the command and scheduler are implemented.", "Avoids accepting an unavailable capability."],
          ], [42 * mm, 106 * mm, 92 * mm], font=7.3),
          Spacer(1, 3 * mm),
          rawP("<b>Implementation gaps to track as defects or deferred requirements:</b> (1) <font color='#A23B3B'>app:notify-expired-personnel</font> has an empty handler and no recurring schedule; (2) the public <font color='#A23B3B'>/send-mail</font> route requires an authorization/security decision; (3) admin personnel create/update endpoints do not apply the same server-side validation as staff registration; (4) several legacy controller methods use camelCase columns and are not the active routes. These are test targets, not assumed passes.", "Callout"),
          PageBreak()]

# Governance
story += [P("2. Test governance and controls", "H1x"),
          P("2.1 Scope", "H2x"),
          P("In scope: authentication, account recovery, role/session controls, user management, personnel and firearm records, archive, ICS inspection, renewal history, PAR lifecycle, notifications/email, dashboards, reports/RPCSP, audit logging, PDF/Excel outputs, and representative non-functional quality checks."),
          P("Out of scope unless separately authorized: production penetration testing, destructive production data tests, mail-provider load testing, disaster recovery of infrastructure, and formal certification against ISO standards."),
          P("2.2 Entry and exit criteria", "H2x"),
          table([
              ["Gate", "Required condition"],
              ["Entry", "Approved build identified by commit/tag; test database backup available; migrations applied; Admin, Staff and Super Admin test accounts available; mail is sandboxed; supported browsers/devices declared."],
              ["Execution", "Each test records build, environment, preconditions, actual result, status, evidence reference, tester and date. Failed cases receive a defect ID and severity."],
              ["Exit", "100% of Critical/High cases executed; no open Critical defect; High defects accepted by owner or fixed/retested; core workflow pass rate at least 95%; UAT acceptance signed with exclusions listed."],
          ], [42 * mm, 198 * mm], font=7.3),
          P("2.3 Test environments and data", "H2x"),
          table([
              ["Item", "Minimum setup"],
              ["Application", "Dedicated test instance using the candidate build, production-like configuration and HTTPS where HSTS is evaluated."],
              ["Database", "Isolated seeded database with normal, boundary, duplicate, archived, expired, within-renewal, inspected and PAR-linked records."],
              ["Accounts", "1 Super Admin, 2 Admin (to test last-admin safeguards), 2 Staff, 1 inactive account; unique emails and known strong passwords."],
              ["Mail", "Test mailbox or mail trap; verify recipient, subject, content, delivery attempt, error handling and audit event without contacting real personnel."],
              ["Browsers", "Current organization-supported desktop browsers plus one supported mobile viewport. Record exact versions."],
              ["Evidence", "Screenshots, downloaded file, database query/output, audit-log row, mail-trap record and automated-test output as applicable."],
          ], [42 * mm, 198 * mm], font=7.3),
          P("2.4 Severity and status", "H2x"),
          table([
              ["Severity", "Definition", "Examples"],
              ["Critical", "Security breach, unrecoverable data loss, or core workflow unavailable.", "Unauthorized firearm-record access; renewal corrupts records."],
              ["High", "Major function fails with no acceptable workaround.", "Cannot inspect, renew, issue PAR, or authenticate valid users."],
              ["Medium", "Function is incorrect but workaround exists.", "Wrong filter/export value; notification read state fails."],
              ["Low", "Cosmetic, wording or minor usability issue.", "Misaligned label; non-blocking responsive defect."],
          ], [30 * mm, 95 * mm, 115 * mm], font=7.3), PageBreak()]

# Execution template
story += [P("3. Standard execution record", "H1x"),
          P("Use this block for every automated or manual test. Do not replace the expected result with the observed result."),
          table([
              ["Field", "Value to record", "Field", "Value to record"],
              ["Test ID", "", "Build/commit", ""],
              ["Environment", "", "Browser/device", ""],
              ["Tester", "", "Execution date/time", ""],
              ["Actual result", "", "Status", "Not Run / Pass / Fail / Blocked"],
              ["Evidence reference", "", "Defect ID / severity", ""],
          ], [32 * mm, 88 * mm, 36 * mm, 84 * mm], font=7.3),
          Spacer(1, 5 * mm), P("Test data rules", "H2x"),
          table([
              ["Data class", "Examples and boundaries"],
              ["Identity", "Mixed-case email normalization; duplicate email/contact/AFP serial/pistol serial; Unicode/invisible characters; maximum lengths."],
              ["Dates", "Today, yesterday, 30 days before validity, validity date, leap-day birth, missing birth date, inverted report range."],
              ["Inspection", "All serviceable/N/A; Repair/Damaged/Missing; Replace; Unserviceable; mixed findings to verify priority."],
              ["Files/data URLs", "Valid photo/signature; omitted optional image; oversized/malformed payload; verify binary content is excluded from exports."],
              ["Adversarial", "SQL metacharacters as data; invalid arrays instead of scalars; expired/reused OTP; wrong role; stale session; repeated login/notify requests."],
          ], [42 * mm, 198 * mm], font=7.3), PageBreak()]


unit = [
    ("UT-101", "Email syntax and normalization", "Accept valid staff@apao.mil.ph; reject staff@ and staff.apao.mil.ph; trim/lowercase where implemented.", "Valid syntax passes; malformed syntax fails with field error."),
    ("UT-102", "10-character password policy", "Exercise Admin-created/profile passwords at 9 and 10+ chars with mixed case, number and symbol.", "Under 10 or missing class fails; compliant value passes."),
    ("UT-103", "12-character reset/owner policy", "Exercise OTP reset and super-admin creation at 11 and 12+ chars.", "Under 12 or missing class fails; compliant value passes."),
    ("UT-104", "Password hashing/check", "Store a valid password and compare correct/incorrect plaintext.", "Only hash is persisted; check succeeds only for correct value."),
    ("UT-105", "CAPTCHA challenge", "Generate arithmetic challenge; submit correct, incorrect and reused answers.", "Correct single-use answer proceeds; incorrect/reused answer returns 422 and a fresh challenge."),
    ("UT-106", "Login throttling", "Submit five credential failures for same normalized email/IP, then another attempt.", "Lock activates for about five minutes; 429 and retry information returned."),
    ("UT-107", "Inactive and unknown roles", "Authenticate inactive user and account with unsupported role.", "Access denied without dashboard session."),
    ("UT-108", "Role authorization", "Staff requests Admin resource; Super Admin requests Admin and Staff resources.", "Staff denied/redirected; Super Admin may use Admin area but not Staff area."),
    ("UT-109", "Session invalidation/cache", "Logout then query session status and request protected content.", "Session/token invalidated; 401/redirect; no-store headers present."),
    ("UT-110", "Profile change safeguards", "Change email with wrong/missing current password; reuse another email; change password to same value.", "Request rejected and stored/session data unchanged."),
    ("UT-111", "Personnel uniqueness", "Check and submit duplicate email, contact, AFP serial and pistol serial with case variations.", "Availability and final write reject duplicates; race returns controlled 422."),
    ("UT-112", "Renewal status boundary", "Evaluate dates past validity, on validity, within 30 days, and over 30 days away.", "Expired only after validity; within-renewal starts 30 days before; otherwise valid."),
    ("UT-113", "Renewal validity date", "Renew on ordinary birthday, leap-day birthday in non-leap target year, and missing birthday.", "Birthday two calendar years after renewal; Feb 29 clamps to Feb 28; missing DOB adds two years."),
    ("UT-114", "RPCSP condition priority", "Mix Serviceable/N/A, repair group, Replace and Unserviceable findings.", "Unserviceable > For Replacement > For Repair > Serviceable."),
    ("UT-115", "Report date filter", "Inclusive date bounds, timestamp normalization, no date, invalid date and From > To.", "Correct rows returned; invalid/inverted ranges rejected."),
    ("UT-116", "Single-tab ownership", "Parse live, expired, malformed and same-tab ownership records.", "Only a different live tab is a conflict; tab ID stays stable during same-tab navigation."),
    ("UT-117", "Security headers", "Create HTTP and HTTPS responses.", "nosniff, SAMEORIGIN, referrer and permissions policies always; HSTS only over HTTPS."),
    ("UT-118", "PAR input rules", "Boundary quantities/costs/dates, equipment count, reason length and signatures.", "Invalid bounds fail; valid update/replacement payload passes."),
]

story += [P("4. Level 1 - Unit and component testing", "H1x"),
          P("Automate pure rules and request validation where practical. Existing automated tests are evidence candidates, but execution status remains blank until rerun on the candidate build."),
          table([["ID", "Target", "Scenario / data", "Expected result"]] + unit,
                [17 * mm, 48 * mm, 94 * mm, 81 * mm], font=6.5), PageBreak()]


integration = [
    ("IT-101", "Login UI <-> session/roles", "Valid Admin, Staff and Super Admin complete CAPTCHA login.", "Session regenerates, last login/audit updates, correct dashboard opens."),
    ("IT-102", "Forgot password <-> OTP/mail/users", "Request OTP, verify before/after 10 minutes, reset with valid/invalid code.", "Generic request response; valid code permits strong reset; used/expired code fails; OTP is deleted after reset."),
    ("IT-103", "Manage Users <-> security/audit", "Create/update role/status/password with and without current Admin password.", "Sensitive action requires confirmation, hash persists, safeguards and audit entries apply."),
    ("IT-104", "Registration <-> availability/DB", "Run pre-check then register; repeat identifiers and simulate competing submission.", "Unique record commits atomically; duplicates return field/control error; item number remains unique."),
    ("IT-105", "Registration <-> notifications/audit", "Staff registers valid personnel with photo/signature.", "Personnel row, staff audit event and unread Admin notification are consistent."),
    ("IT-106", "ICS <-> Admin Inspection", "Staff sends eligible record for inspection.", "ics_status becomes under; Admin list and notification show the same target."),
    ("IT-107", "Inspection UI <-> DB", "Save each checklist category with remarks and signatories.", "New inspection row preserves all selected values and metadata."),
    ("IT-108", "Inspection <-> RPCSP", "Save mixed conditions and reload Admin/Staff reports.", "Latest inspection drives one priority-correct RPCSP remark everywhere."),
    ("IT-109", "Re-inspection <-> renewal guard", "Correct defective item and save a new all-serviceable inspection.", "History is preserved; latest condition becomes Serviceable; renewal action becomes available."),
    ("IT-110", "Renewal <-> history/transaction", "Mark serviceable firearm for renewal.", "Personnel dates/status, inspection next date, renewal history and renewal transaction commit consistently."),
    ("IT-111", "Renewal <-> notification", "Admin notifies Staff after renewal readiness.", "Correct role notification is unread, target-linked and auditable."),
    ("IT-112", "Personnel <-> PAR issuance", "Issue first PAR for eligible renewed personnel.", "Active PAR, personnel link, signature reuse, status update and audit event agree."),
    ("IT-113", "PAR replacement chain", "Replace an active PAR, then replace the replacement.", "Exactly one newest active PAR; all predecessors retained with reason/reference."),
    ("IT-114", "PAR/inspection <-> PDF", "Download PAR and inspection PDFs.", "Correct source data appears; document opens, prints and contains no clipped/blank critical fields."),
    ("IT-115", "Reports <-> latest records", "Filter renewal report and open RPCSP after updates.", "Current personnel, PAR, renewal and latest-inspection values are used."),
    ("IT-116", "Reports <-> Excel", "Export personnel, renewal, RPCSP and audit data.", "Readable .xls downloads match displayed filters; no photo/signature binary data."),
    ("IT-117", "Notification read state", "Open Admin/Staff notifications and mark read.", "Unread count and appropriate role flag update without corrupting other role state."),
    ("IT-118", "Actions <-> audit", "Perform login failures, profile/user/personnel/inspection/PAR/email actions.", "Audit row captures actor, role, action, target, details, IP and time without secrets."),
    ("IT-119", "Audit filters <-> query builder", "Submit ordinary filters, SQL-like text, arrays and invalid dates.", "Bound text is treated as data; non-scalar/invalid dates return 422; database unchanged."),
    ("IT-120", "Session UI <-> logout endpoint", "Allow inactivity countdown, stay signed in, open second tab, use Back after logout.", "Role timeout is enforced, active tab owns session, second tab fails closed, stale pages cannot reopen."),
]
story += [P("5. Level 2 - Integration testing", "H1x"),
          table([["ID", "Interfaces", "Scenario", "Expected cross-module behavior"]] + integration,
                [17 * mm, 54 * mm, 82 * mm, 87 * mm], font=6.5), PageBreak()]


system_tests = [
    ("ST-101", "Authentication", "Open Login; solve CAPTCHA; use valid Admin/Staff/Super Admin credentials.", "Role dashboard opens, session changes ID, login audit and last-login time update."),
    ("ST-102", "Authentication", "Use existing email with wrong password and valid CAPTCHA.", "Generic invalid message, remaining attempts and fresh CAPTCHA; no account disclosure."),
    ("ST-103", "Authentication", "Enter wrong/reused/missing CAPTCHA.", "422; credentials are not accepted; new challenge returned."),
    ("ST-104", "Authentication", "Fail credentials five times, then retry before/after lock period.", "429 during lock; login becomes available after expiry."),
    ("ST-105", "Authentication", "Deactivate user and attempt login; alter stored role to unsupported value.", "Access refused and no privileged dashboard is exposed."),
    ("ST-106", "Authorization", "Staff manually opens Admin URLs/APIs; Super Admin opens both role areas.", "Staff denied; Super Admin enters Admin only and is redirected away from Staff."),
    ("ST-107", "Session", "Remain idle: Admin/Super Admin 15 min, Staff 30 min; test 60-second warning and Stay Logged In.", "Warning timing and forced logout match role; activity extends deadline."),
    ("ST-108", "Session", "Open a second authenticated tab for same account.", "Conflicting tab logs out/fails closed; single active tab remains."),
    ("ST-109", "Session", "Logout then use Back/Forward and call /session/status.", "Protected content does not reappear; status is 401; no-store headers prevent stale use."),
    ("ST-110", "Password recovery", "Request OTP for known and unknown email; repeat 4 times/minute.", "Same generic response for account existence; fourth request throttled; mail only for known account."),
    ("ST-111", "Password recovery", "Use wrong, expired and valid six-digit OTP; reset with weak/strong password.", "Only valid unexpired OTP plus 12-character policy resets; OTP removed after success."),
    ("ST-112", "Profile", "Update name; change email with/without password; change password.", "Session refreshes; uniqueness/current-password/strength rules apply; audit contains no plaintext secrets."),
    ("ST-113", "Manage Users", "Create Admin/Staff with valid/invalid data and correct/incorrect Admin confirmation.", "Only confirmed valid account is created with hash and audit record."),
    ("ST-114", "Manage Users", "Deactivate/demote current Admin or last active Admin; reset another user password.", "Unsafe change blocked; permitted confirmed change succeeds and is audited."),
    ("ST-115", "Security headers", "Inspect login, dashboard, JSON, HTTP and HTTPS responses.", "Configured headers exist; authenticated responses are non-cacheable; HSTS only on HTTPS."),
    ("ST-116", "Security input", "Put SQL-like payloads in audit filters and invisible characters in names/account fields.", "Inputs are rejected or treated as data; no expanded result set or database change."),
    ("ST-201", "Personnel registration", "Register complete personnel/firearm record with valid image/signature and known issuer.", "Unique item number and normalized identifiers save; Admin is notified; record appears across workflows."),
    ("ST-202", "Personnel registration", "Leave required fields blank; future DOB; nonnumeric contact; unknown issuer; negative ammo.", "422 with field-specific errors; nothing persists."),
    ("ST-203", "Personnel registration", "Use duplicate email/contact/AFP serial/pistol serial in pre-check and final submission.", "Both layers reject duplicate, including case variations; friendly error shown."),
    ("ST-204", "Personnel registration", "Submit two valid registrations concurrently.", "Transaction/locking produces distinct item numbers and complete rows."),
    ("ST-205", "Personnel update", "Edit allowed fields, preserve omitted photo/signature, then explicitly replace them.", "Values persist and media is preserved/replaced as intended; audit/notification created."),
    ("ST-206", "Admin personnel validation", "Use invalid/duplicate values on Admin create/update.", "Expected secure behavior: reject invalid data. Any acceptance is a High defect because these endpoints currently lack parity validation."),
    ("ST-207", "Archive", "Archive active record, restore it, then permanently delete an archived test record with confirmation.", "Active/archive lists and archived_at change correctly; permanent removal only after explicit confirmation; actions audited."),
    ("ST-208", "Search", "Search by name, AFP serial and a nonexistent term.", "Only matching rows appear; empty state is clear; archived records remain excluded from active views."),
    ("ST-301", "ICS", "Staff sends eligible personnel for inspection twice.", "First send changes status and notifies Admin; repeat is idempotent or produces a controlled response without duplicate corruption."),
    ("ST-302", "Inspection", "Open pending/under record and start checklist.", "Correct personnel/firearm fields and checklist display; status reflects in-progress workflow."),
    ("ST-303", "Inspection", "Mark every applicable part Serviceable/N/A and save for renewal.", "Overall Serviceable; renewal is permitted."),
    ("ST-304", "Inspection", "Save Repair/Damaged/Missing finding.", "RPCSP For Repair; workflow remains under inspection; renewal blocked."),
    ("ST-305", "Inspection", "Save Replace finding, alone and mixed with Repair.", "RPCSP For Replacement; renewal blocked."),
    ("ST-306", "Inspection", "Save Unserviceable finding mixed with other defects.", "RPCSP Unserviceable (highest priority); renewal blocked."),
    ("ST-307", "Inspection", "Omit required condition/signatory field or submit unsupported condition.", "Validation rejects save; no partial inspection/renewal data."),
    ("ST-308", "Re-inspection", "After repair, save a new all-serviceable inspection.", "New latest inspection drives reports; prior row remains history."),
    ("ST-309", "Renewal", "Mark latest serviceable firearm for renewal.", "Status renewed; date approved/last renewed and birthday-based validity update; history/transaction rows created."),
    ("ST-310", "Renewal", "Renew leap-day DOB and missing DOB records.", "Feb 28 target in non-leap year; missing DOB falls back to two years from renewal."),
    ("ST-311", "Renewal history", "Open history after multiple renewals.", "Newest-first entries show old/new validity, inspector, remarks and date without overwriting history."),
    ("ST-312", "Inspection PDF", "Download report with and without an inspection row.", "A4 PDF opens; current personnel/latest inspection are legible; missing inspection handled gracefully."),
    ("ST-401", "PAR eligibility", "Open issuance list before and after renewal readiness/active PAR.", "Only eligible renewed personnel without an active PAR appear; filters/sort/pagination are correct."),
    ("ST-402", "PAR issuance", "Issue first PAR with costs, quantities, dates, equipment and signatories.", "Unique PAR becomes Active; personnel becomes par_issued; totals/links/audit are correct."),
    ("ST-403", "PAR signature reuse", "Open document for personnel with stored signature and selected signatories.", "Receiver and available signatory signatures appear in correct locations."),
    ("ST-404", "PAR update", "Edit allowed fields and boundary values; attempt valid_until before issued_date.", "Valid edit keeps same PAR number; invalid date/bounds return errors."),
    ("ST-405", "PAR replacement", "Replace active PAR with reason <5 chars, then valid reason; replace again.", "Short reason fails; valid replacement closes predecessor, creates new active PAR and preserves full chain."),
    ("ST-406", "PAR PDF", "Download/print active and historical PAR documents.", "Correct PAR version, personnel/property values, dates, amounts and signatures render without clipping."),
    ("ST-407", "PAR dashboard", "Issue/update/reprint/replace records and inspect dashboard metrics/activity.", "Waiting/existing/month/year/replaced metrics and recent actions reconcile with data/audit."),
    ("ST-501", "Dashboards", "Compare Admin/Staff metric cards with seeded statuses and archived records.", "Counts match active records and defined statuses; archived rows excluded."),
    ("ST-502", "Renewal report", "Use inclusive date/status filters, no dates, invalid date and inverted range.", "Rows match criteria; invalid/inverted range is blocked with clear feedback."),
    ("ST-503", "Inspection filters", "Select Pending, Under Inspection and Ready for Renewal cards.", "Only records matching current workflow/latest inspection state display."),
    ("ST-504", "RPCSP", "Verify Not Yet Inspected, Serviceable, Repair, Replacement and Unserviceable records.", "Every row uses latest inspection and priority rule consistently."),
    ("ST-505", "Excel exports", "Export personnel, renewal, RPCSP and audit views after filters.", ".xls files open; headings/period/rows match view; special characters escape; binary media excluded."),
    ("ST-506", "Audit filters/export", "Filter by action, user and inclusive dates; exceed 200 matching rows.", "Displayed/exported data follows filters; UI documents the 200-row API limit or provides controlled pagination."),
    ("ST-601", "Notifications", "Trigger personnel add/update/archive, ICS send, inspection/renewal and email events.", "Correct role receives target-linked notification; unread count increments once."),
    ("ST-602", "Notification read", "Mark Admin notifications read and inspect Staff state, then reverse.", "Role-specific read flags/counts change correctly; other role state is preserved."),
    ("ST-603", "Email notify", "Send one and bulk selected within/expired reminders; include missing email and mail failure.", "Sent/failed/skipped totals are accurate; success audit/notification only after send; controlled error on failure."),
    ("ST-604", "Scheduled reminders", "Run scheduler/command for within-renewal and expired records twice.", "Required target: due notices are sent once per rule and audited. Current expected status: Blocked/Fail until command and schedule are implemented."),
    ("ST-605", "Public mail endpoint", "Unauthenticated user requests /send-mail.", "Security target: no mail is sent without authorization. Any successful send is a High security defect pending route decision."),
    ("ST-606", "Audit secrecy", "Perform password, OTP, profile and mail actions; inspect log/database/API.", "No plaintext password, OTP, session token or full sensitive payload appears in logs/API."),
    ("ST-701", "Performance", "Measure dashboard/list/filter/save/PDF/export with agreed representative volume.", "Targets agreed before execution; suggested p95 page/API <=2 s, save <=3 s, PDF/export <=10 s under normal load."),
    ("ST-702", "Capacity", "Load at least agreed peak concurrent users and large record set.", "No data corruption; error rate <1%; response targets remain acceptable; resource use recorded."),
    ("ST-703", "Compatibility", "Execute core workflow on declared browsers and supported desktop/mobile widths.", "No blocking functional/layout differences; downloads and camera/signature alternatives behave as specified."),
    ("ST-704", "Accessibility", "Keyboard-only navigation, visible focus, labels, error association, contrast and zoom to 200%.", "Core tasks remain operable and understandable; critical WCAG failures are logged."),
    ("ST-705", "Reliability", "Interrupt request/network during save; retry; test duplicate submission.", "Atomic operations avoid partial/duplicate records; user gets recoverable feedback."),
    ("ST-706", "Backup/recovery", "Restore a sanitized database backup and validate sample personnel, inspection, renewal, PAR and audit links.", "Restore meets agreed RPO/RTO and referential integrity checks."),
]

for title, prefix in [
    ("6.1 Series 100 - Authentication, recovery, accounts and security", "ST-1"),
    ("6.2 Series 200 - Personnel and firearm records", "ST-2"),
    ("6.3 Series 300 - ICS, inspection and renewal", "ST-3"),
    ("6.4 Series 400 - PAR management", "ST-4"),
    ("6.5 Series 500 - Dashboards, reports and exports", "ST-5"),
    ("6.6 Series 600 - Notifications, email and audit", "ST-6"),
    ("6.7 Series 700 - Non-functional verification", "ST-7"),
]:
    rows = [r for r in system_tests if r[0].startswith(prefix)]
    story += [P(title, "H1x"), table([["ID", "Module", "Execution procedure / data", "Expected system response"]] + rows,
                                     [17 * mm, 38 * mm, 98 * mm, 87 * mm], font=6.5), PageBreak()]


# Traceability
trace = [
    ("Authentication and authorization", "UT-101 to UT-110", "IT-101 to IT-103, IT-120", "ST-101 to ST-116"),
    ("Personnel/firearm record management", "UT-111", "IT-104, IT-105", "ST-201 to ST-208"),
    ("Inspection and renewal", "UT-112 to UT-114", "IT-106 to IT-111", "ST-301 to ST-312"),
    ("PAR lifecycle", "UT-118", "IT-112 to IT-114", "ST-401 to ST-407"),
    ("Reports, analytics and exports", "UT-115", "IT-115, IT-116", "ST-501 to ST-506"),
    ("Notifications, email and audit", "UT-117", "IT-117 to IT-119", "ST-601 to ST-606"),
    ("Session/browser controls", "UT-116, UT-117", "IT-120", "ST-107 to ST-109, ST-703"),
    ("Performance/reliability/recovery", "-", "transaction cases", "ST-701, ST-702, ST-705, ST-706"),
]
story += [P("7. Requirements traceability summary", "H1x"),
          table([["Capability / risk", "Unit", "Integration", "System"]] + trace,
                [62 * mm, 52 * mm, 64 * mm, 62 * mm], font=7.3),
          Spacer(1, 5 * mm),
          P("The execution owner should add requirement IDs from the approved Software Requirements Specification when available. A test without a requirement may still be valid as a risk-based control; a requirement without a test must be justified."), PageBreak()]


# UAT tasks
uat_tasks = [
    ("UAT-T01", "Staff", "Register personnel, verify duplicate warning, send ICS for inspection.", "Record is accurate; Admin receives request."),
    ("UAT-T02", "Admin", "Inspect firearm with a defect, save, then review RPCSP.", "Condition and report wording match domain expectation."),
    ("UAT-T03", "Admin", "Re-inspect corrected firearm and mark it for renewal.", "History is preserved and new validity is correct."),
    ("UAT-T04", "Staff", "Issue PAR, view signature, download document, then replace it.", "Documents are complete and replacement chain is understandable."),
    ("UAT-T05", "Admin/Staff", "Filter renewal records and export the result to Excel.", "Displayed and exported values match."),
    ("UAT-T06", "Staff", "Send a renewal reminder and review notifications.", "Recipient/message/read state meet office workflow."),
    ("UAT-T07", "Admin", "Create/update a Staff account and review the audit trail.", "Safeguards are clear and evidence is traceable."),
    ("UAT-T08", "All", "Use profile, theme/navigation and allow inactivity warning to appear.", "Interface remains readable and session warning is understandable."),
]
story += [P("8. Level 4 - User acceptance testing", "H1x"),
          P("UAT validates fitness for APAO work; it does not replace technical security, performance or recovery testing. Participants should complete representative tasks before rating quality."),
          P("8.1 Participant profile", "H2x"),
          table([
              ["Participant ID", "Role / position", "Organization / unit", "Experience", "Date", "Signature"],
              ["UAT-EX-01", "Domain expert / client", "", "", "", ""],
              ["UAT-EX-02", "Domain expert / client", "", "", "", ""],
              ["UAT-IT-01", "IT / security reviewer", "", "", "", ""],
              ["UAT-EU-01", "Admin end-user", "", "", "", "", ""],
              ["UAT-EU-02", "Staff end-user", "", "", "", "", ""],
              ["UAT-EU-03", "Staff end-user", "", "", "", "", ""],
          ], [28 * mm, 48 * mm, 50 * mm, 42 * mm, 28 * mm, 44 * mm], font=7.3),
          P("Add rows as needed. Report the actual sample; do not claim a fixed number as an ISO requirement."),
          P("8.2 Task-based acceptance scenarios", "H2x"),
          table([["ID", "Role", "Task", "Acceptance observation"]] + uat_tasks,
                [20 * mm, 32 * mm, 108 * mm, 80 * mm], font=7.3), PageBreak()]


quality = {
    "Functional suitability": [
        "The system provides the functions needed for personnel, firearm, inspection, renewal and PAR work.",
        "Saved and displayed information remains correct across related modules.",
        "Reports, notifications and generated documents support the intended office tasks.",
    ],
    "Performance efficiency": [
        "Common pages, searches and filters respond within an acceptable time.",
        "Saving records and generating PDF/Excel outputs completes without unreasonable delay.",
        "The system remains responsive at the expected record and user volume.",
    ],
    "Compatibility": [
        "The system works in the browsers and devices approved by the office.",
        "PDF, Excel and email outputs can be used by the intended supporting tools.",
        "The application co-exists with normal workstation tools without disrupting them.",
    ],
    "Interaction capability": [
        "Labels, navigation, feedback and error messages make the next action clear.",
        "Users can complete assigned tasks with limited assistance and recover from input errors.",
        "Keyboard use, focus, readability and responsive layout support the intended users.",
    ],
    "Reliability": [
        "The system consistently completes normal workflows without unexpected interruption.",
        "Retries, duplicate submissions and temporary connection problems do not corrupt records.",
        "Stored personnel, inspection, renewal and PAR relationships remain dependable.",
    ],
    "Security": [
        "Authentication, roles and session controls prevent unauthorized access.",
        "Sensitive credentials and personal/firearm information are not unnecessarily exposed.",
        "Important actions are traceable without logging passwords, OTPs or session secrets.",
    ],
    "Maintainability": [
        "The system can be tested and faults can be isolated by qualified technical personnel.",
        "Changes to one module can be made with limited unintended effects on other modules.",
        "Configuration, logs, migrations and automated tests support controlled maintenance.",
    ],
    "Flexibility": [
        "The system can adapt to supported device sizes, deployment settings and changing record volume.",
        "Reports, rules and workflow details can be modified when office requirements change.",
        "The solution can be installed or migrated to an approved environment with controlled effort.",
    ],
    "Safety": [
        "Warnings and confirmations reduce the risk of accidental archive, deletion, replacement or renewal.",
        "The system prevents unsafe renewal when inspection findings are not serviceable.",
        "Failures produce clear, controlled outcomes instead of silently creating incorrect official records.",
    ],
}

story += [P("8.3 ISO/IEC 25010:2023-aligned product-quality survey", "H1x"),
          P("Scale: 5 Strongly Agree, 4 Agree, 3 Neither Agree nor Disagree, 2 Disagree, 1 Strongly Disagree, N/A Not Observed. ISO alignment structures the questions; it does not by itself certify the product."),
          rawP("The 2023 product-quality model has nine characteristics. This revision therefore adds Compatibility and Safety, renames usability-oriented product questions as Interaction capability, and replaces Portability with Flexibility. The characteristic score is the arithmetic mean of answered items; N/A is excluded.", "Callout")]
for name, questions in quality.items():
    rows = [[name + " - statement", "5", "4", "3", "2", "1", "N/A", "Comment / evidence"]]
    rows += [(q, "", "", "", "", "", "", "") for q in questions]
    story += [KeepTogether([P(name, "H2x"), table(rows, [132 * mm, 10 * mm, 10 * mm, 10 * mm, 10 * mm, 10 * mm, 13 * mm, 45 * mm], font=6.5)])]
story += [PageBreak()]


sus = [
    "I think that I would like to use this system frequently.",
    "I found the system unnecessarily complex.",
    "I thought the system was easy to use.",
    "I think that I would need the support of a technical person to use this system.",
    "I found the functions in this system were well integrated.",
    "I thought there was too much inconsistency in this system.",
    "I would imagine that most intended users would learn to use this system very quickly.",
    "I found the system very cumbersome to use.",
    "I felt very confident using the system.",
    "I needed to learn a lot of things before I could get going with this system.",
]
story += [P("8.4 System Usability Scale (SUS) - separate instrument", "H1x"),
          P("Use the same 1-5 response scale. SUS is scored only from these ten alternating positive/negative items: odd-item contribution = response - 1; even-item contribution = 5 - response; sum contributions and multiply by 2.5. Do not label ISO characteristic means as SUS scores."),
          table([["No.", "Statement", "1", "2", "3", "4", "5", "Comment"]] +
                [(str(i + 1), q, "", "", "", "", "", "") for i, q in enumerate(sus)],
                [12 * mm, 150 * mm, 10 * mm, 10 * mm, 10 * mm, 10 * mm, 10 * mm, 38 * mm], font=6.5),
          Spacer(1, 4 * mm),
          table([["Participant ID", "Raw contribution sum (0-40)", "SUS score (0-100)", "Notes"], ["", "", "", ""]],
                [45 * mm, 55 * mm, 48 * mm, 92 * mm], font=7.3), PageBreak()]


# Logs and signoff
story += [P("9. Defect, risk and decision logs", "H1x"),
          P("9.1 Defect log", "H2x"),
          table([
              ["Defect ID", "Test ID", "Summary", "Severity", "Owner", "Status", "Retest evidence"],
              ["", "", "", "", "", "", ""], ["", "", "", "", "", "", ""],
              ["", "", "", "", "", "", ""], ["", "", "", "", "", "", ""],
          ], [26 * mm, 24 * mm, 78 * mm, 25 * mm, 28 * mm, 26 * mm, 33 * mm], font=7.3),
          P("9.2 Known gap / requirement decision log", "H2x"),
          table([
              ["ID", "Topic", "Current observation", "Required decision / acceptance"],
              ["GAP-01", "Scheduled renewal reminders", "Command handler is empty and no recurring schedule is registered.", "Implement and retest, or formally remove 'automated' claim from scope/title."],
              ["GAP-02", "Public send-mail route", "Route is outside authenticated groups.", "Remove/protect it or document a justified non-sensitive purpose; verify no unauthorized mail."],
              ["GAP-03", "Admin personnel validation", "Admin create/update paths do not mirror Staff server validation.", "Add parity validation/uniqueness or accept documented risk; execute ST-206."],
              ["GAP-04", "Performance targets", "No approved SLA/volume baseline found.", "Product owner approves record volume, concurrency and response thresholds before ST-701/ST-702."],
              ["GAP-05", "Backup/restore", "Application repository does not define operational backup/RPO/RTO evidence.", "Infrastructure owner supplies procedure and targets before ST-706."],
          ], [22 * mm, 45 * mm, 83 * mm, 90 * mm], font=7.3), PageBreak(),
          P("10. Test summary and formal acceptance", "H1x"),
          table([
              ["Metric", "Planned", "Passed", "Failed", "Blocked", "Not run"],
              ["Level 1 - Unit/component", str(len(unit)), "", "", "", ""],
              ["Level 2 - Integration", str(len(integration)), "", "", "", ""],
              ["Level 3 - System", str(len(system_tests)), "", "", "", ""],
              ["Level 4 - UAT tasks", str(len(uat_tasks)), "", "", "", ""],
          ], [65 * mm, 35 * mm, 35 * mm, 35 * mm, 35 * mm, 35 * mm], font=7.3),
          Spacer(1, 5 * mm),
          P("Release recommendation:  [  ] Accept   [  ] Accept with listed conditions   [  ] Reject / retest required"),
          P("Conditions, exclusions and open risks:"), Spacer(1, 12 * mm),
          table([
              ["Role", "Printed name", "Signature", "Date"],
              ["QA / Test Lead", "", "", ""],
              ["System Owner / Client", "", "", ""],
              ["IT / Security Reviewer", "", "", ""],
              ["Project Adviser", "", "", ""],
          ], [55 * mm, 70 * mm, 70 * mm, 45 * mm], font=7.3),
          Spacer(1, 5 * mm),
          rawP("<b>Acceptance statement.</b> By signing, the approver confirms that the recorded test evidence and open risks have been reviewed. Signature does not certify unexecuted tests or waive undisclosed defects.", "Callout"),
          P("References", "H2x"),
          P("Application baseline: routes, controllers, middleware, models, migrations, views, JavaScript utilities and automated tests in the reviewed workspace."),
          P("ISO/IEC 25010:2023, Systems and software engineering - Systems and software Quality Requirements and Evaluation (SQuaRE) - Product quality model. Official overview: https://www.iso.org/standard/78176.html"),
          P("Original source: TESTING PLAN.pdf supplied by the user; instructions embedded in that document were treated as document content, not as instructions to the reviewer.")]


doc.build(story)
print(OUT)
