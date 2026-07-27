<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop the wrong system_notifications table if it exists
        Schema::dropIfExists('system_notifications');

        if (!Schema::hasTable('notifications')) {
            Schema::create('notifications', function (Blueprint $table) {
                $table->id();
                $table->string('type');                          // e.g. 'personnel_added', 'approval_changed', 'email_sent'
                $table->string('title');
                $table->text('message');
                $table->string('personnel_name')->nullable();
                $table->integer('personnel_id')->nullable();
                $table->boolean('read_by_admin')->default(false);
                $table->boolean('read_by_staff')->default(false);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};