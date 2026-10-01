<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('personnel', 'contact_number') || Schema::hasIndex('personnel', 'personnel_contact_number_unique')) {
            return;
        }

        DB::table('personnel')
            ->whereNotNull('contact_number')
            ->whereRaw("TRIM(contact_number) = ''")
            ->update(['contact_number' => null]);

        $hasHistoricalDuplicates = DB::table('personnel')
            ->whereNotNull('contact_number')
            ->select('contact_number')
            ->groupBy('contact_number')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if (!$hasHistoricalDuplicates) {
            Schema::table('personnel', function (Blueprint $table) {
                $table->unique('contact_number', 'personnel_contact_number_unique');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasIndex('personnel', 'personnel_contact_number_unique')) {
            Schema::table('personnel', function (Blueprint $table) {
                $table->dropUnique('personnel_contact_number_unique');
            });
        }
    }
};
