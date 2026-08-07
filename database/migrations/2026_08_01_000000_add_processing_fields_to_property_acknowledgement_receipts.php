<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('property_acknowledgement_receipts', function (Blueprint $table) {
            if (!Schema::hasColumn('property_acknowledgement_receipts', 'issued_by_personnel_id')) $table->unsignedBigInteger('issued_by_personnel_id')->nullable()->after('issued_by');
            if (!Schema::hasColumn('property_acknowledgement_receipts', 'approved_by_personnel_id')) $table->unsignedBigInteger('approved_by_personnel_id')->nullable()->after('approved_by');
            $table->longText('receiver_signature')->nullable()->after('remarks');
            $table->longText('issued_by_signature')->nullable()->after('receiver_signature');
            $table->longText('approved_by_signature')->nullable()->after('issued_by_signature');
        });
        Schema::table('property_acknowledgement_receipts', function (Blueprint $table) {
            $table->foreign('issued_by_personnel_id', 'par_issued_person_fk')->references('id')->on('personnel')->nullOnDelete();
            $table->foreign('approved_by_personnel_id', 'par_approved_person_fk')->references('id')->on('personnel')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('property_acknowledgement_receipts', function (Blueprint $table) {
            $table->dropForeign('par_issued_person_fk');
            $table->dropForeign('par_approved_person_fk');
            $table->dropColumn(['issued_by_personnel_id', 'approved_by_personnel_id', 'receiver_signature', 'issued_by_signature', 'approved_by_signature']);
        });
    }
};
