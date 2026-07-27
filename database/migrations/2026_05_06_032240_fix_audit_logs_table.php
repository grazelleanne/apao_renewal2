<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class FixAuditLogsTable extends Migration
{
    public function up()
    {
        // If table doesn't exist, create it fresh
        if (!Schema::hasTable('audit_logs')) {
            Schema::create('audit_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('user_name')->nullable();
                $table->string('user_role')->nullable();
                $table->string('action')->nullable();
                $table->string('model_type')->nullable();
                $table->string('model_id')->nullable();
                $table->string('subject')->nullable();
                $table->text('old_values')->nullable();
                $table->text('new_values')->nullable();
                $table->text('description')->nullable();
                $table->string('ip_address')->nullable();
                $table->timestamps();
            });
        } else {
            // Table exists but missing columns — add them
            Schema::table('audit_logs', function (Blueprint $table) {
                if (!Schema::hasColumn('audit_logs', 'user_id')) {
                    $table->unsignedBigInteger('user_id')->nullable()->first();
                }
                if (!Schema::hasColumn('audit_logs', 'user_name')) {
                    $table->string('user_name')->nullable();
                }
                if (!Schema::hasColumn('audit_logs', 'user_role')) {
                    $table->string('user_role')->nullable();
                }
                if (!Schema::hasColumn('audit_logs', 'action')) {
                    $table->string('action')->nullable();
                }
                if (!Schema::hasColumn('audit_logs', 'model_type')) {
                    $table->string('model_type')->nullable();
                }
                if (!Schema::hasColumn('audit_logs', 'model_id')) {
                    $table->string('model_id')->nullable();
                }
                if (!Schema::hasColumn('audit_logs', 'subject')) {
                    $table->string('subject')->nullable();
                }
                if (!Schema::hasColumn('audit_logs', 'old_values')) {
                    $table->text('old_values')->nullable();
                }
                if (!Schema::hasColumn('audit_logs', 'new_values')) {
                    $table->text('new_values')->nullable();
                }
                if (!Schema::hasColumn('audit_logs', 'description')) {
                    $table->text('description')->nullable();
                }
                if (!Schema::hasColumn('audit_logs', 'ip_address')) {
                    $table->string('ip_address')->nullable();
                }
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('audit_logs');
    }
}