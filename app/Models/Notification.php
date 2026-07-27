<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Notification extends Model
{
    use HasFactory;

    // Explicitly set table name to avoid any conflict
    // with Laravel's built-in notifications table
    protected $table = 'personnel_notifications';

    protected $fillable = [
        'user_id',   // staff user who registered the personnel
        'title',     // e.g. "License Renewed — Cruz, Juan"
        'message',   // e.g. "Admin approved renewal. Email sent to..."
        'type',      // 'renewed' | 'expired' | 'within_renewal'
        'read',      // false = unread (shows badge on bell)
    ];

    protected $casts = [
        'read'       => 'boolean',
        'created_at' => 'datetime',
    ];

    // ── Relationship: belongs to the staff user ────────────────────────────────
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // ── Scope: only unread ─────────────────────────────────────────────────────
    public function scopeUnread($query)
    {
        return $query->where('read', false);
    }

    // ── Scope: for a specific user ─────────────────────────────────────────────
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
}