<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personnel', function (Blueprint $table) {
            if (!Schema::hasColumn('personnel', 'pistol_type')) {
                $table->string('pistol_type')->nullable()->after('pistol_serial_number');
            }
        });

        // Auto-fill pistol_type from pistol_nomenclature if it exists
        DB::table('personnel')->whereNull('pistol_type')->update([
            'pistol_type' => DB::raw('pistol_nomenclature'),
        ]);
    }

    public function down(): void
    {
        Schema::table('personnel', function (Blueprint $table) {
            $table->dropColumn('pistol_type');
        });
    }
};