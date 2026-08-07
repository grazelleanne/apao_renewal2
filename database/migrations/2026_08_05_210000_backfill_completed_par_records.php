<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $year = now()->format('Y');
        $sequence = 1;

        DB::table('personnel')
            ->where('is_archived', false)
            ->where('approved_status', '<>', 'new')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('property_acknowledgement_receipts')
                    ->whereColumn('property_acknowledgement_receipts.personnel_id', 'personnel.id');
            })
            ->orderBy('id')
            ->chunkById(100, function ($personnel) use ($year, &$sequence) {
                foreach ($personnel as $person) {
                    $legacyNumber = trim((string) ($person->par_number ?? ''));
                    $number = $legacyNumber !== '' ? $legacyNumber : $this->nextParNumber($year, $sequence);

                    if (DB::table('property_acknowledgement_receipts')->where('par_number', $number)->exists()) {
                        $number = $this->nextParNumber($year, $sequence);
                    }

                    DB::table('property_acknowledgement_receipts')->insert([
                        'par_number' => $number,
                        'personnel_id' => $person->id,
                        'unit' => $person->unit ?? null,
                        'firearm' => $person->pistol_nomenclature ?: 'Unspecified firearm',
                        'firearm_serial_number' => $person->pistol_serial_number ?? null,
                        'firearm_quantity' => 1,
                        'firearm_unit_cost' => 35000,
                        'ammunition_quantity' => (int) ($person->qty_ammo ?? 0),
                        'ammunition_unit_cost' => 22,
                        'equipment_items' => json_encode([
                            '4 pcs Back Straps',
                            '4 pcs Magazine (17 rds Cap)',
                            '1 set Cleaning Kit',
                            '1 pc Speed Loader',
                            '1 pc User’s Manual',
                            '1 pc Gun Case',
                            '1 pc Holster w/Hanger',
                            '1 pc Magazine Pouch 3 magazine Capacity',
                        ]),
                        'status' => 'Active',
                        'issued_date' => $person->last_renewed_at ?? $person->date_approved ?? now()->toDateString(),
                        'valid_until' => $person->date_of_validity ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            });
    }

    public function down(): void
    {
        //
    }

    private function nextParNumber(string $year, int &$sequence): string
    {
        do {
            $number = sprintf('PAR-%s-%06d', $year, $sequence++);
        } while (DB::table('property_acknowledgement_receipts')->where('par_number', $number)->exists());

        return $number;
    }
};
