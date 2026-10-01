<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('ics_settings')) {
            return;
        }

        $existing = DB::table('ics_settings')->orderBy('id')->first();

        if ($existing) {
            return;
        }

        $values = [
            'office_name' => 'PROPERTY ACCOUNTABILITY OFFICE, GENERAL SUPPORT (GS)',
            'agency_name' => 'ARMY PROPERTY ACCOUNTABILITY OFFICE',
            'unit_address' => 'Fort Andres Bonifacio, Taguig City',
            'issued_by_name' => 'MS ROSEMARIE O VILBAR',
            'issued_by_position' => 'Chief, PAOGS, APAO, PA',
            'pistol_unit_cost' => '16450.00',
            'ammo_unit_cost' => '15.07',
            'updated_at' => now(),
        ];

        DB::table('ics_settings')->insert($values + [
            'unit_code' => null,
            'chief_officer_name' => null,
            'chief_officer_position' => null,
            'created_at' => now(),
        ]);
    }

    public function down(): void
    {
        if (!Schema::hasTable('ics_settings')) {
            return;
        }

        DB::table('ics_settings')
            ->where('office_name', 'PROPERTY ACCOUNTABILITY OFFICE, GENERAL SUPPORT (GS)')
            ->where('agency_name', 'ARMY PROPERTY ACCOUNTABILITY OFFICE')
            ->where('issued_by_name', 'MS ROSEMARIE O VILBAR')
            ->delete();
    }
};
