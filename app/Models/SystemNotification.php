<?php
// app/Models/SystemNotification.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemNotification extends Model
{
    protected $fillable = ['user_id', 'type', 'title', 'message', 'is_read'];

    protected $casts = ['is_read' => 'boolean'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function toApiArray(): array
    {
        return [
            'id'        => $this->id,
            'type'      => $this->type,
            'title'     => $this->title,
            'message'   => $this->message,
            'read'      => $this->is_read,
            'createdAt' => $this->created_at->toIso8601String(),
        ];
    }
}