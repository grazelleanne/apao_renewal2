<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('renewal_transactions', 'source_history_id')) {
            Schema::table('renewal_transactions', function (Blueprint $table) {
                $table->unsignedBigInteger('source_history_id')->nullable()->after('id');
                $table->unique('source_history_id', 'renewal_transactions_history_unique');
            });
        }

        $historyRows = DB::table('renewal_history')
            ->where('action', 'renewed')
            ->whereNotNull('date_of_validity')
            ->orderBy('id')
            ->get();

        foreach ($historyRows as $history) {
            $personnel = DB::table('personnel')
                ->where('item_number', $history->item_number)
                ->first();

            if (!$personnel) {
                continue;
            }

            $receipt = DB::table('property_acknowledgement_receipts')
                ->where('personnel_id', $personnel->id)
                ->whereDate('valid_until', $history->date_of_validity)
                ->orderByDesc('issued_date')
                ->orderByDesc('id')
                ->first();

            $processorId = $history->inspected_by
                ? DB::table('users')->where('name', $history->inspected_by)->value('id')
                : null;

            DB::table('renewal_transactions')->updateOrInsert(
                ['source_history_id' => $history->id],
                [
                    'personnel_id' => $personnel->id,
                    'item_number' => $history->item_number,
                    'par_number' => $receipt->par_number ?? null,
                    'renewal_date' => date('Y-m-d', strtotime($history->created_at)),
                    'new_validity_date' => $history->date_of_validity,
                    'old_validity_date' => $history->previous_validity,
                    'status' => 'approved',
                    'processed_by' => $history->inspected_by,
                    'processed_by_user_id' => $processorId,
                    'remarks' => $history->remarks,
                    'created_at' => $history->created_at,
                    'updated_at' => $history->updated_at,
                ]
            );
        }
    }

    public function down(): void
    {
        if (!Schema::hasColumn('renewal_transactions', 'source_history_id')) {
            return;
        }

        DB::table('renewal_transactions')->whereNotNull('source_history_id')->delete();

        Schema::table('renewal_transactions', function (Blueprint $table) {
            $table->dropUnique('renewal_transactions_history_unique');
            $table->dropColumn('source_history_id');
        });
    }
};
