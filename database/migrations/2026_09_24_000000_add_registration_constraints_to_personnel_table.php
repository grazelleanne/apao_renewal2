<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('personnel', 'contact_number')) {
            Schema::table('personnel', function (Blueprint $table) {
                $table->string('contact_number', 20)->nullable()->after('email');
            });
        }

        if (!Schema::hasColumn('personnel', 'issued_by')) {
            Schema::table('personnel', function (Blueprint $table) {
                $table->string('issued_by')->nullable()->after('contact_number');
            });
        }

        // Match the normalization used by registration before enforcing indexes.
        DB::table('personnel')->whereNotNull('email')->update([
            'email' => DB::raw("NULLIF(LOWER(TRIM(email)), '')"),
        ]);
        DB::table('personnel')->whereNotNull('afp_serial_number')->update([
            'afp_serial_number' => DB::raw("NULLIF(UPPER(TRIM(afp_serial_number)), '')"),
        ]);
        DB::table('personnel')->whereNotNull('pistol_serial_number')->update([
            'pistol_serial_number' => DB::raw("NULLIF(UPPER(TRIM(pistol_serial_number)), '')"),
        ]);

        $this->addUniqueIndexWhenHistoricalDataIsClean('email', 'personnel_email_unique');
        $this->addUniqueIndexWhenHistoricalDataIsClean('afp_serial_number', 'personnel_afp_serial_unique');
        $this->addUniqueIndexWhenHistoricalDataIsClean('pistol_serial_number', 'personnel_pistol_serial_unique');
    }

    public function down(): void
    {
        foreach ([
            'personnel_email_unique',
            'personnel_afp_serial_unique',
            'personnel_pistol_serial_unique',
        ] as $index) {
            if (Schema::hasIndex('personnel', $index)) {
                Schema::table('personnel', function (Blueprint $table) use ($index) {
                    $table->dropUnique($index);
                });
            }
        }

        $columns = array_values(array_filter(
            ['contact_number', 'issued_by'],
            fn (string $column) => Schema::hasColumn('personnel', $column)
        ));

        if ($columns !== []) {
            Schema::table('personnel', function (Blueprint $table) use ($columns) {
                $table->dropColumn($columns);
            });
        }
    }

    private function addUniqueIndexWhenHistoricalDataIsClean(string $column, string $index): void
    {
        if (Schema::hasIndex('personnel', $index)) {
            return;
        }

        $hasDuplicates = DB::table('personnel')
            ->whereNotNull($column)
            ->select($column)
            ->groupBy($column)
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if ($hasDuplicates) {
            return;
        }

        Schema::table('personnel', function (Blueprint $table) use ($column, $index) {
            $table->unique($column, $index);
        });
    }
};
