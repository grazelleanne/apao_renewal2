<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('property_acknowledgement_receipts', function (Blueprint $table) {
            $table->json('equipment_items')->nullable()->after('ammunition_unit_cost');
        });
    }

    public function down(): void
    {
        Schema::table('property_acknowledgement_receipts', fn (Blueprint $table) => $table->dropColumn('equipment_items'));
    }
};
