<?php
// database/migrations/2024_01_01_000002_create_personnel_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personnel', function (Blueprint $table) {
            $table->id();
            $table->integer('item_number')->unique();
            $table->date('date_of_validity')->nullable();
            $table->string('last_name');
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('rank')->nullable();
            $table->string('afp_serial_number')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('pistol_nomenclature')->nullable();
            $table->string('pistol_serial_number')->nullable();
            $table->integer('qty_ammo')->default(0);
            $table->enum('approved_status', ['valid', 'within_renewal', 'expired'])->default('valid');
            $table->boolean('is_archived')->default(false);
            $table->date('archived_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personnel');
    }
};