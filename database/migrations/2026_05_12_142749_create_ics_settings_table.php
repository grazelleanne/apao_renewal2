<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('ics_settings')) {
            Schema::create('ics_settings', function (Blueprint $table) {
                $table->id();
                $table->string('office_name')->nullable();           // e.g. "PROPERTY ACCOUNTABILITY OFFICE, GS"
                $table->string('agency_name')->nullable();           // e.g. "ARMY PROPERTY ACCOUNTABILITY OFFICE"
                $table->string('unit_address')->nullable();          // e.g. "Camp Edilberto Evangelista, Patag, CDO"
                $table->string('unit_code')->nullable();             // e.g. "10FPAO"
                $table->string('chief_officer_name')->nullable();    // Signing officer full name
                $table->string('chief_officer_position')->nullable();// e.g. "Acting Chief, 10FPAO, APAO, PA"
                $table->string('issued_by_name')->nullable();        // Issued-by officer name
                $table->string('issued_by_position')->nullable();    // e.g. "Chief, PAOGS, APAO, PA"
                $table->string('pistol_unit_cost')->nullable();      // e.g. "16450"
                $table->string('ammo_unit_cost')->nullable();        // e.g. "15.07"
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ics_settings');
    }
};