<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('renewal_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('personnel_id');          // FK to personnel.id
            $table->integer('item_number');                       // personnel item_number (for easy lookup)
            $table->string('par_number')->nullable();             // e.g. "PAR-2026-0001"
            $table->date('renewal_date');                         // date renewal was processed
            $table->date('new_validity_date');                    // new expiry date after renewal
            $table->date('old_validity_date')->nullable();        // previous expiry date
            $table->enum('status', [
                'submitted',   // documents submitted by personnel
                'processing',  // being processed by staff/admin
                'approved',    // approved by APAO
                'completed',   // PAR signed and released
                'cancelled'    // cancelled
            ])->default('submitted');
            $table->string('processed_by')->nullable();           // name of staff/admin who processed
            $table->unsignedBigInteger('processed_by_user_id')->nullable(); // FK to users.id
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->foreign('personnel_id')
                  ->references('id')
                  ->on('personnel')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('renewal_transactions');
    }
};