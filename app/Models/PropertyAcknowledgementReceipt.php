<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PropertyAcknowledgementReceipt extends Model
{
    protected $fillable = [
        'par_number', 'personnel_id', 'previous_par_id', 'unit', 'firearm',
        'firearm_serial_number', 'firearm_quantity', 'firearm_unit_cost',
        'ammunition_quantity', 'ammunition_unit_cost', 'equipment_items', 'status', 'issued_date',
        'valid_until', 'issued_by', 'issued_by_personnel_id', 'approved_by', 'approved_by_personnel_id', 'remarks',
        'receiver_signature', 'issued_by_signature', 'approved_by_signature',
        'replacement_reason', 'replaced_at', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'issued_date' => 'date',
            'valid_until' => 'date',
            'replaced_at' => 'datetime',
            'firearm_unit_cost' => 'decimal:2',
            'ammunition_unit_cost' => 'decimal:2',
            'equipment_items' => 'array',
        ];
    }

    public function personnel(): BelongsTo
    {
        return $this->belongsTo(Personnel::class);
    }

    public function previousPar(): BelongsTo
    {
        return $this->belongsTo(self::class, 'previous_par_id');
    }

    public function replacements(): HasMany
    {
        return $this->hasMany(self::class, 'previous_par_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
