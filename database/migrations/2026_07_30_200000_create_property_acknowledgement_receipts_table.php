<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('property_acknowledgement_receipts', function (Blueprint $table) {
            $table->id();
            $table->string('par_number')->unique();
            $table->foreignId('personnel_id')->constrained('personnel')->restrictOnDelete();
            $table->foreignId('previous_par_id')->nullable()
                ->constrained('property_acknowledgement_receipts')->nullOnDelete();
            $table->string('unit')->nullable();
            $table->string('firearm');
            $table->string('firearm_serial_number')->nullable();
            $table->unsignedInteger('firearm_quantity')->default(1);
            $table->decimal('firearm_unit_cost', 12, 2)->default(0);
            $table->unsignedInteger('ammunition_quantity')->default(0);
            $table->decimal('ammunition_unit_cost', 12, 2)->default(0);
            $table->string('status')->default('Active')->index();
            $table->date('issued_date');
            $table->date('valid_until')->nullable();
            $table->string('issued_by')->nullable();
            $table->string('approved_by')->nullable();
            $table->text('remarks')->nullable();
            $table->text('replacement_reason')->nullable();
            $table->timestamp('replaced_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['personnel_id', 'status']);
        });

        $year = now()->format('Y');
        $personnel = DB::table('personnel')->orderBy('id')->get();
        foreach ($personnel as $person) {
            $legacyNumber = trim((string) ($person->par_number ?? ''));
            $number = $legacyNumber !== ''
                ? $legacyNumber
                : sprintf('PAR-%s-%06d', $year, (int) $person->id);

            if (DB::table('property_acknowledgement_receipts')->where('par_number', $number)->exists()) {
                $sequence = (int) $person->id;
                do {
                    $number = sprintf('PAR-%s-%06d', $year, $sequence++);
                } while (DB::table('property_acknowledgement_receipts')->where('par_number', $number)->exists());
            }

            DB::table('property_acknowledgement_receipts')->insert([
                'par_number' => $number,
                'personnel_id' => $person->id,
                'unit' => $person->unit ?? null,
                'firearm' => $person->pistol_nomenclature ?: 'Unspecified firearm',
                'firearm_serial_number' => $person->pistol_serial_number ?? null,
                'ammunition_quantity' => (int) ($person->qty_ammo ?? 0),
                'status' => 'Active',
                'issued_date' => $person->last_renewed_at ?? now()->toDateString(),
                'valid_until' => $person->date_of_validity ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('property_acknowledgement_receipts');
    }
};
