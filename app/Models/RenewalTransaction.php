<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RenewalTransaction extends Model
{
    protected $table = 'renewal_transactions';

    protected $fillable = [
        'personnel_id',
        'item_number',
        'par_number',
        'renewal_date',
        'new_validity_date',
        'old_validity_date',
        'status',
        'processed_by',
        'processed_by_user_id',
        'remarks',
    ];

    protected $casts = [
        'renewal_date'      => 'date',
        'new_validity_date' => 'date',
        'old_validity_date' => 'date',
    ];

    // -------------------------------------------------------
    // Relationships
    // -------------------------------------------------------

    public function personnel()
    {
        return $this->belongsTo(Personnel::class, 'personnel_id');
    }

    // -------------------------------------------------------
    // PAR Number Generator
    // e.g. PAR-2026-0001
    // -------------------------------------------------------

    public static function generateParNumber(): string
    {
        $year  = now()->year;
        $count = self::whereYear('created_at', $year)->count() + 1;
        return sprintf('PAR-%d-%04d', $year, $count);
    }

    // -------------------------------------------------------
    // Status label helper
    // -------------------------------------------------------

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'submitted'  => 'Submitted',
            'processing' => 'Processing',
            'approved'   => 'Approved',
            'completed'  => 'Completed',
            'cancelled'  => 'Cancelled',
            default      => ucfirst($this->status),
        };
    }

    // -------------------------------------------------------
    // Scopes
    // -------------------------------------------------------

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeForPersonnel($query, $personnelId)
    {
        return $query->where('personnel_id', $personnelId);
    }

    public function scopeForYear($query, $year)
    {
        return $query->whereYear('renewal_date', $year);
    }
}