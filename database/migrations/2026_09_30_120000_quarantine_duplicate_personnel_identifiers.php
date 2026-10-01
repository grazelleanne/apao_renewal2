<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const UNIQUE_FIELDS = [
        'email' => 'personnel_email_unique',
        'afp_serial_number' => 'personnel_afp_serial_unique',
        'pistol_serial_number' => 'personnel_pistol_serial_unique',
    ];

    public function up(): void
    {
        if (!Schema::hasTable('personnel_data_conflicts')) {
            Schema::create('personnel_data_conflicts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('personnel_id')->constrained('personnel')->cascadeOnDelete();
                $table->unsignedInteger('item_number')->nullable();
                $table->string('field', 64);
                $table->string('conflicting_value');
                $table->string('resolution_status', 20)->default('pending');
                $table->timestamps();

                $table->unique(['personnel_id', 'field'], 'personnel_conflict_record_unique');
                $table->index(['field', 'resolution_status'], 'personnel_conflict_status_index');
            });
        }

        foreach (self::UNIQUE_FIELDS as $column => $index) {
            $duplicateValues = DB::table('personnel')
                ->whereNotNull($column)
                ->select($column)
                ->groupBy($column)
                ->havingRaw('COUNT(*) > 1')
                ->pluck($column);

            foreach ($duplicateValues as $value) {
                $records = DB::table('personnel')
                    ->where($column, $value)
                    ->get(['id', 'item_number']);

                foreach ($records as $record) {
                    DB::table('personnel_data_conflicts')->updateOrInsert(
                        [
                            'personnel_id' => $record->id,
                            'field' => $column,
                        ],
                        [
                            'item_number' => $record->item_number,
                            'conflicting_value' => $value,
                            'resolution_status' => 'pending',
                            'updated_at' => now(),
                            'created_at' => now(),
                        ]
                    );
                }

                // A duplicated identifier cannot safely be assigned to any one
                // of several different people. Preserve it above, then require
                // an administrator to enter the correct value for each record.
                DB::table('personnel')->where($column, $value)->update([
                    $column => null,
                    'updated_at' => now(),
                ]);
            }

            if (!Schema::hasIndex('personnel', $index)) {
                Schema::table('personnel', function (Blueprint $table) use ($column, $index) {
                    $table->unique($column, $index);
                });
            }
        }
    }

    public function down(): void
    {
        foreach (self::UNIQUE_FIELDS as $index) {
            if (Schema::hasIndex('personnel', $index)) {
                Schema::table('personnel', function (Blueprint $table) use ($index) {
                    $table->dropUnique($index);
                });
            }
        }

        if (!Schema::hasTable('personnel_data_conflicts')) {
            return;
        }

        DB::table('personnel_data_conflicts')
            ->orderBy('id')
            ->each(function ($conflict) {
                if (!array_key_exists($conflict->field, self::UNIQUE_FIELDS)) {
                    return;
                }

                DB::table('personnel')
                    ->where('id', $conflict->personnel_id)
                    ->whereNull($conflict->field)
                    ->update([$conflict->field => $conflict->conflicting_value]);
            });

        Schema::drop('personnel_data_conflicts');
    }
};
