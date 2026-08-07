<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReplaceParRequest;
use App\Http\Requests\UpdateParRequest;
use App\Models\Personnel;
use App\Models\PropertyAcknowledgementReceipt;
use App\Services\ParNumberGenerator;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ParController extends Controller
{
    public function dashboard(): JsonResponse
    {
        $waiting = Personnel::query()
            ->tap(fn ($query) => $this->parWaitingPersonnel($query))
            ->count();

        $monthStart = now()->startOfMonth();
        $auditBase = DB::table('audit_logs')
            ->where('model_type', PropertyAcknowledgementReceipt::class);
        $recent = (clone $auditBase)
            ->whereIn('action', ['par_issued', 'par_updated', 'par_pdf_generated'])
            ->latest('created_at')->limit(50)->get();
        $pars = PropertyAcknowledgementReceipt::with('personnel:id,rank,first_name,middle_name,last_name')
            ->whereIn('id', $recent->pluck('model_id')->filter())->get()->keyBy('id');

        return response()->json([
            'metrics' => [
                'waiting' => $waiting,
                'existing' => PropertyAcknowledgementReceipt::count(),
                'updated' => (clone $auditBase)->where('action', 'par_updated')->where('created_at', '>=', $monthStart)->count(),
                'reprinted' => (clone $auditBase)->where('action', 'par_pdf_generated')->where('created_at', '>=', $monthStart)->count(),
            ],
            'activity' => $recent->map(function ($log) use ($pars) {
                $par = $pars->get($log->model_id);
                return [
                    'date' => $log->created_at,
                    'personnel' => $par?->personnel?->full_name ?? 'Unknown personnel',
                    'action' => match ($log->action) {
                        'par_issued' => 'PAR Issued',
                        'par_updated' => 'PAR Updated',
                        default => 'PAR Reprinted',
                    },
                    'par_number' => $par?->par_number ?? $log->subject ?? '—',
                    'processed_by' => $log->user_name ?? 'System',
                ];
            }),
        ]);
    }

    public function issuance(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'unit' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'in:ready'],
            'sort' => ['nullable', 'in:registered_desc,registered_asc,name_asc,name_desc'],
            'per_page' => ['nullable', 'integer', 'in:10,25,50'],
        ]);

        $query = Personnel::query()
            ->tap(fn ($query) => $this->parWaitingPersonnel($query))
            ->when($validated['search'] ?? null, function ($query, $search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('afp_serial_number', 'like', "%{$search}%")
                        ->orWhere('unit', 'like', "%{$search}%")
                        ->orWhere('first_name', 'like', "%{$search}%")
                        ->orWhere('middle_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%");
                });
            })
            ->when($validated['unit'] ?? null, fn ($q, $unit) => $q->where('unit', $unit));

        match ($validated['sort'] ?? 'registered_desc') {
            'registered_asc' => $query->orderBy('created_at'),
            'name_asc' => $query->orderBy('last_name')->orderBy('first_name'),
            'name_desc' => $query->orderByDesc('last_name')->orderByDesc('first_name'),
            default => $query->orderByDesc('created_at'),
        };

        $page = $query->paginate($validated['per_page'] ?? 10);
        $monthStart = now()->startOfMonth();
        $yearStart = now()->startOfYear();
        $issuedThisMonth = PropertyAcknowledgementReceipt::with('personnel:id,rank,first_name,middle_name,last_name,afp_serial_number')
            ->where('issued_date', '>=', $monthStart->toDateString())
            ->latest('issued_date')
            ->limit(25)
            ->get();
        $issuedThisYear = PropertyAcknowledgementReceipt::with('personnel:id,rank,first_name,middle_name,last_name,afp_serial_number')
            ->where('issued_date', '>=', $yearStart->toDateString())
            ->latest('issued_date')
            ->limit(25)
            ->get();
        $replacedThisMonth = PropertyAcknowledgementReceipt::with('personnel:id,rank,first_name,middle_name,last_name,afp_serial_number')
            ->where('status', 'Replaced')
            ->where('replaced_at', '>=', $monthStart)
            ->latest('replaced_at')
            ->limit(25)
            ->get();

        return response()->json([
            'data' => collect($page->items())->map(fn ($person) => [
                'id' => $person->id,
                'personnel' => $person->full_name,
                'rank' => $person->rank,
                'photo' => $person->photo,
                'afp_serial_number' => $person->afp_serial_number,
                'unit' => $person->unit,
                'date_approved' => $person->date_approved?->toIso8601String() ?? $person->created_at?->toIso8601String(),
                'status' => 'Waiting for PAR Issuance',
                'firearm' => $person->pistol_nomenclature,
                'firearm_serial_number' => $person->pistol_serial_number,
                'ammunition_quantity' => (int) ($person->qty_ammo ?? 0),
            ]),
            'meta' => $this->paginationMeta($page),
            'metrics' => [
                'processing' => (clone $query)->reorder()->count(),
                'month' => PropertyAcknowledgementReceipt::where('issued_date', '>=', $monthStart->toDateString())->count(),
                'year' => PropertyAcknowledgementReceipt::where('issued_date', '>=', $yearStart->toDateString())->count(),
                'returned_replaced' => PropertyAcknowledgementReceipt::where('status', 'Replaced')->where('replaced_at', '>=', $monthStart)->count(),
            ],
            'metric_details' => [
            'processing' => collect($page->items())->map(fn ($person) => [
                    'date' => $person->created_at?->toIso8601String(),
                    'personnel' => $person->full_name,
                    'reference' => $person->afp_serial_number,
                    'status' => 'Waiting for PAR Issuance',
                ])->values(),
                'month' => $issuedThisMonth->map(fn ($par) => $this->metricDetail($par, 'PAR Issued')),
                'year' => $issuedThisYear->map(fn ($par) => $this->metricDetail($par, 'PAR Issued')),
                'returned_replaced' => $replacedThisMonth->map(fn ($par) => $this->metricDetail($par, 'Replaced')),
            ],
            'units' => Personnel::active()->whereNotNull('unit')->where('unit', '<>', '')->distinct()->orderBy('unit')->pluck('unit'),
        ]);
    }

    public function store(Request $request, ParNumberGenerator $numbers): JsonResponse
    {
        $data = $request->validate([
            'personnel_id' => ['required', 'integer', 'exists:personnel,id'],
            'firearm' => ['required', 'string', 'max:255'],
            'firearm_serial_number' => ['nullable', 'string', 'max:255'],
            'firearm_quantity' => ['required', 'integer', 'min:1', 'max:999'],
            'firearm_unit_cost' => ['required', 'numeric', 'min:0', 'max:999999999.99'],
            'ammunition_quantity' => ['required', 'integer', 'min:0', 'max:999999'],
            'ammunition_unit_cost' => ['required', 'numeric', 'min:0', 'max:999999999.99'],
            'unit' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'in:Active,Inactive'],
            'issued_date' => ['required', 'date'],
            'valid_until' => ['nullable', 'date', 'after_or_equal:issued_date'],
            'issued_by' => ['nullable', 'string', 'max:255'],
            'approved_by' => ['nullable', 'string', 'max:255'],
            'equipment_items' => ['nullable', 'array', 'max:100'],
            'equipment_items.*' => ['string', 'max:255'],
            'remarks' => ['nullable', 'string', 'max:3000'],
        ]);

        $par = DB::transaction(function () use ($data, $numbers) {
            $personnel = Personnel::query()
                ->whereKey($data['personnel_id'])
                ->tap(fn ($query) => $this->parWaitingPersonnel($query))
                ->lockForUpdate()
                ->firstOrFail();

            $par = PropertyAcknowledgementReceipt::create($data + [
                'par_number' => $numbers->next(),
                'personnel_id' => $personnel->id,
                'created_by' => $this->userId(),
                'updated_by' => $this->userId(),
            ]);

            $personnel->update(['approved_status' => 'renewed', 'ics_status' => 'par_issued']);

            return $par;
        });

        $this->log('par_issued', $par, ['par_number' => $par->par_number]);

        return response()->json([
            'message' => "PAR {$par->par_number} issued successfully.",
            'data' => $this->detail($par->load('personnel')),
        ], 201);
    }

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'unit' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'in:Active,Inactive,Replaced'],
            'per_page' => ['nullable', 'integer', 'in:10,25,50'],
        ]);

        $query = PropertyAcknowledgementReceipt::query()
            ->with('personnel:id,rank,first_name,middle_name,last_name,afp_serial_number,date_of_birth')
            ->when($validated['search'] ?? null, function ($query, $search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('par_number', 'like', "%{$search}%")
                        ->orWhere('firearm', 'like', "%{$search}%")
                        ->orWhereHas('personnel', function ($personnel) use ($search) {
                            $personnel->where('afp_serial_number', 'like', "%{$search}%")
                                ->orWhere('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%");
                        });
                });
            })
            ->when($validated['unit'] ?? null, fn ($q, $unit) => $q->where('unit', $unit))
            ->when($validated['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->latest('updated_at');

        $page = $query->paginate($validated['per_page'] ?? 10);

        return response()->json([
            'data' => collect($page->items())->map(fn ($par) => $this->detail($par)),
            'meta' => $this->paginationMeta($page),
            'units' => PropertyAcknowledgementReceipt::query()
                ->whereNotNull('unit')->where('unit', '<>', '')->distinct()->orderBy('unit')->pluck('unit'),
        ]);
    }

    public function show(PropertyAcknowledgementReceipt $par): JsonResponse
    {
        $par->load(['personnel', 'previousPar:id,par_number']);
        $this->log('par_viewed', $par, ['par_number' => $par->par_number]);

        return response()->json(['data' => $this->detail($par)]);
    }

    public function update(UpdateParRequest $request, PropertyAcknowledgementReceipt $par): JsonResponse
    {
        if ($par->status === 'Replaced') {
            return response()->json(['message' => 'A replaced PAR is immutable. Update its active replacement instead.'], 422);
        }

        $data = $request->validated();
        $personnelData = collect($data)->only(['personnel_rank','personnel_first_name','personnel_middle_name','personnel_last_name','personnel_afp_serial_number'])
            ->mapWithKeys(fn ($value, $key) => [str_replace('personnel_', '', $key) => $value])->all();
        $data = collect($data)->except(array_map(fn ($key) => 'personnel_'.$key, array_keys($personnelData)))->all();
        if (empty($data['issued_by']) && ($data['issued_by_personnel_id'] ?? null)) {
            $data['issued_by'] = \App\Models\Personnel::findOrFail($data['issued_by_personnel_id'])->full_name;
        }
        if (empty($data['approved_by']) && ($data['approved_by_personnel_id'] ?? null)) {
            $data['approved_by'] = \App\Models\Personnel::findOrFail($data['approved_by_personnel_id'])->full_name;
        }
        $before = $par->only(array_keys($data));
        DB::transaction(function () use ($par, $data, $personnelData) {
            if ($personnelData) $par->personnel->update($personnelData);
            $par->update($data + ['updated_by' => $this->userId()]);
        });
        $this->log('par_updated', $par, ['before' => $before, 'after' => $par->fresh()->only(array_keys($before))]);

        return response()->json(['message' => 'PAR updated successfully.', 'data' => $this->detail($par->fresh('personnel'))]);
    }

    public function replace(
        ReplaceParRequest $request,
        PropertyAcknowledgementReceipt $par,
        ParNumberGenerator $numbers
    ): JsonResponse {
        if ($par->status === 'Replaced') {
            return response()->json(['message' => 'This PAR has already been replaced.'], 422);
        }

        $replacement = DB::transaction(function () use ($request, $par, $numbers) {
            $locked = PropertyAcknowledgementReceipt::lockForUpdate()->findOrFail($par->id);
            if ($locked->status === 'Replaced') {
                abort(422, 'This PAR has already been replaced.');
            }

            $data = $request->safe()->except('replacement_reason');
            $replacement = PropertyAcknowledgementReceipt::create($data + [
                'par_number' => $numbers->next(),
                'personnel_id' => $locked->personnel_id,
                'previous_par_id' => $locked->id,
                'status' => $data['status'] === 'Inactive' ? 'Inactive' : 'Active',
                'replacement_reason' => $request->validated('replacement_reason'),
                'created_by' => $this->userId(),
                'updated_by' => $this->userId(),
            ]);

            $locked->update([
                'status' => 'Replaced',
                'replaced_at' => now(),
                'updated_by' => $this->userId(),
            ]);

            return $replacement;
        });

        $this->log('par_replaced', $replacement, [
            'previous_par' => $par->par_number,
            'new_par' => $replacement->par_number,
            'reason' => $replacement->replacement_reason,
        ]);

        return response()->json([
            'message' => "PAR replaced with {$replacement->par_number}.",
            'data' => $this->detail($replacement->load('personnel')),
        ], 201);
    }

    public function document(PropertyAcknowledgementReceipt $par)
    {
        $par->load(['personnel', 'previousPar:id,par_number']);
        $this->log('par_document_viewed', $par, ['par_number' => $par->par_number]);

        return view('par.document', compact('par'));
    }

    public function pdf(PropertyAcknowledgementReceipt $par)
    {
        $par->load(['personnel', 'previousPar:id,par_number']);
        $this->log('par_pdf_generated', $par, ['par_number' => $par->par_number]);

        return Pdf::loadView('par.pdf', compact('par'))
            ->setPaper('a4')
            ->download("{$par->par_number}.pdf");
    }

    private function summary(PropertyAcknowledgementReceipt $par): array
    {
        return [
            'id' => $par->id,
            'par_number' => $par->par_number,
            'personnel' => $par->personnel->full_name,
            'afp_serial_number' => $par->personnel->afp_serial_number,
            'unit' => $par->unit,
            'firearm' => $par->firearm,
            'status' => $par->status,
            'updated_at' => $par->updated_at?->toIso8601String(),
        ];
    }

    private function detail(PropertyAcknowledgementReceipt $par): array
    {
        $personnel = $par->personnel;
        return array_merge($this->summary($par), [
            'personnel_id' => $personnel->id,
            'rank' => $personnel->rank,
            'date_of_birth' => $personnel->date_of_birth,
            'firearm_serial_number' => $par->firearm_serial_number,
            'firearm_quantity' => $par->firearm_quantity,
            'firearm_unit_cost' => $par->firearm_unit_cost,
            'ammunition_quantity' => $par->ammunition_quantity,
            'ammunition_unit_cost' => $par->ammunition_unit_cost,
            'equipment_items' => $par->equipment_items ?? [],
            'issued_date' => $par->issued_date?->format('Y-m-d'),
            'valid_until' => $par->valid_until?->format('Y-m-d'),
            'issued_by' => $par->issued_by,
            'approved_by' => $par->approved_by,
            'issued_by_personnel_id' => $par->issued_by_personnel_id,
            'approved_by_personnel_id' => $par->approved_by_personnel_id,
            'receiver_signature' => $par->receiver_signature,
            'issued_by_signature' => $par->issued_by_signature,
            'approved_by_signature' => $par->approved_by_signature,
            'personnel_rank' => $personnel->rank,
            'personnel_first_name' => $personnel->first_name,
            'personnel_middle_name' => $personnel->middle_name,
            'personnel_last_name' => $personnel->last_name,
            'personnel_afp_serial_number' => $personnel->afp_serial_number,
            'remarks' => $par->remarks,
            'replacement_reason' => $par->replacement_reason,
            'previous_par_number' => $par->previousPar?->par_number,
            'document_url' => route('staff.par.document', $par),
            'pdf_url' => route('staff.par.pdf', $par),
        ]);
    }

    private function metricDetail(PropertyAcknowledgementReceipt $par, string $status): array
    {
        return [
            'date' => ($par->replaced_at ?? $par->issued_date ?? $par->updated_at)?->toIso8601String(),
            'personnel' => $par->personnel?->full_name ?? 'Unknown personnel',
            'reference' => $par->par_number,
            'status' => $status,
        ];
    }

    private function userId(): ?int
    {
        $user = session('user');
        return is_object($user) ? ($user->id ?? null) : ($user['id'] ?? null);
    }

    private function parWaitingPersonnel($query): void
    {
        $query->active()
            ->where('approved_status', 'new')
            ->whereDoesntHave('propertyAcknowledgementReceipts', fn ($par) => $par->where('status', 'Active'));
    }

    private function paginationMeta($page): array
    {
        return ['current_page' => $page->currentPage(), 'last_page' => $page->lastPage(),
            'per_page' => $page->perPage(), 'total' => $page->total(),
            'from' => $page->firstItem(), 'to' => $page->lastItem()];
    }

    private function log(string $action, PropertyAcknowledgementReceipt $par, array $details): void
    {
        $user = session('user');
        DB::table('audit_logs')->insert([
            'user_id' => $this->userId(),
            'user_name' => is_object($user) ? ($user->name ?? 'System') : ($user['name'] ?? 'System'),
            'user_role' => is_object($user) ? ($user->role ?? 'staff') : ($user['role'] ?? 'staff'),
            'action' => $action,
            'model_type' => PropertyAcknowledgementReceipt::class,
            'model_id' => $par->id,
            'subject' => $par->par_number,
            'target' => $par->par_number,
            'description' => json_encode($details),
            'ip_address' => request()->ip(),
            'created_at' => now(),
        ]);
    }
}

