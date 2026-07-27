<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inspections', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('personnel_id');
            $table->integer('item_number');
            $table->string('afp_serial_number')->nullable();
            $table->string('pistol_type')->nullable();           // e.g. Glock 17, Pistol 9mm
            $table->date('date_registered')->nullable();
            $table->enum('status', [
                'pending',      // awaiting inspection
                'under',        // currently being inspected
                'approved',     // approved / renewed
                'needs_repair', // flagged for repair
            ])->default('pending');

            // 15 firearm parts checklist
            // Each part: 1=Serviceable, 2=Repair(N/A), 3=Replace(Missing), 4=Damage
            $table->string('barrel')->nullable();
            $table->string('slide')->nullable();
            $table->string('recoil_spring_assembly')->nullable();
            $table->string('firing_pin')->nullable();
            $table->string('spacer_sleeve')->nullable();
            $table->string('firing_pin_spring')->nullable();
            $table->string('spring_cups')->nullable();
            $table->string('firing_pin_safety')->nullable();
            $table->string('firing_pin_safety_spring')->nullable();
            $table->string('extractor')->nullable();
            $table->string('extractor_depressor_plunger')->nullable();
            $table->string('extractor_depressor_plunger_spring')->nullable();
            $table->string('trigger_loaded_bearing')->nullable();
            $table->string('rear_sight')->nullable();
            $table->string('front_sight')->nullable();

            $table->text('remarks')->nullable();

            // Signatories
            $table->string('inspected_by_name')->nullable();
            $table->string('inspected_by_rank')->nullable();
            $table->string('inspected_by_position')->nullable();
            $table->string('witnessed_by_name')->nullable();
            $table->string('witnessed_by_rank')->nullable();
            $table->string('witnessed_by_position')->nullable();
            $table->string('approved_by_name')->nullable();
            $table->string('approved_by_rank')->nullable();
            $table->string('approved_by_position')->nullable();
            $table->string('noted_by_name')->nullable();
            $table->string('noted_by_rank')->nullable();
            $table->string('noted_by_position')->nullable();

            $table->unsignedBigInteger('inspected_by_user_id')->nullable();
            $table->timestamp('inspected_at')->nullable();
            $table->timestamps();

            $table->foreign('personnel_id')->references('id')->on('personnel')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inspections');
    }
};