<?php

namespace App\Services;

use App\Models\PropertyAcknowledgementReceipt;

class ParNumberGenerator
{
    public function next(): string
    {
        $year = now()->format('Y');
        $last = PropertyAcknowledgementReceipt::query()
            ->where('par_number', 'like', "PAR-{$year}-%")
            ->lockForUpdate()
            ->orderByDesc('par_number')
            ->value('par_number');

        $sequence = $last ? ((int) substr($last, strrpos($last, '-') + 1)) + 1 : 1;

        do {
            $number = sprintf('PAR-%s-%06d', $year, $sequence++);
        } while (PropertyAcknowledgementReceipt::where('par_number', $number)->exists());

        return $number;
    }
}
