<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

use App\Models\Inspection;

class Personnel extends Model
{
    protected $table = 'personnel';

    protected $fillable = [
        'item_number',
        'date_of_validity',
        'last_name',
        'first_name',
        'middle_name',
        'rank',
        'afp_serial_number',
        'date_of_birth',
        'pistol_nomenclature',
        'pistol_serial_number',
        'qty_ammo',
        'approved_status',
        'is_archived',
        'archived_at',
        'ics_status',
        'date_approved',
    ];

    public static function computeStatus($validityRaw)
    {
        if (empty($validityRaw)) {
            return 'valid';
        }

        try {
            $validity = Carbon::parse($validityRaw);
        } catch (\Exception $e) {
            return 'valid';
        }

        $today = Carbon::today();

        if ($today->gt($validity)) {
            return 'expired';
        }

        $renewalStart = $validity->copy()->subDays(30);

        if ($today->gte($renewalStart)) {
            return 'within_renewal';
        }

        return 'valid';
    }

    public function refreshStatus()
    {
        $newStatus = self::computeStatus($this->date_of_validity);

        if ($this->approved_status !== $newStatus) {
            $this->approved_status = $newStatus;
            $this->save();
        }
    }

    public function getFullNameAttribute()
    {
        $parts = [];

        if (!empty($this->rank)) {
            $parts[] = $this->rank;
        }

        $lastName = '';
        if (!empty($this->last_name)) {
            $lastName = $this->last_name . ',';
        }

        if (!empty($lastName)) {
            $parts[] = $lastName;
        }

        if (!empty($this->first_name)) {
            $parts[] = $this->first_name;
        }

        if (!empty($this->middle_name)) {
            $parts[] = $this->middle_name;
        }

        return implode(' ', $parts);
    }

    public function scopeActive($query)
    {
        return $query->where('is_archived', false);
    }

    public function scopeArchived($query)
    {
        return $query->where('is_archived', true);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('approved_status', $status);
    }

    public function propertyAcknowledgementReceipts(): HasMany
    {
        return $this->hasMany(PropertyAcknowledgementReceipt::class);
    }

    public function activePropertyAcknowledgementReceipt(): HasOne
    {
        return $this->hasOne(PropertyAcknowledgementReceipt::class)
            ->where('status', 'Active')
            ->latestOfMany();
    }

    public function inspections()
    {
        return $this->hasMany(Inspection::class, 'personnel_id');
    }

    private function formatDate($value)
    {
        if (empty($value)) {
            return null;
        }

        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    private function formatDatetime($value)
    {
        if (empty($value)) {
            return null;
        }

        try {
            return Carbon::parse($value)->format('Y-m-d H:i:s');
        } catch (\Exception $e) {
            return null;
        }
    }

    public function toApiArray()
    {
        return [
            'id'                 => $this->id,
            'itemNumber'         => $this->item_number,
            'dateOfValidity'     => $this->formatDate($this->date_of_validity),
            'lastName'           => $this->last_name,
            'firstName'          => $this->first_name,
            'middleName'         => $this->middle_name,
            'rank'               => $this->rank,
            'afpSerialNumber'    => $this->afp_serial_number,
            'dateOfBirth'        => $this->formatDate($this->date_of_birth),
            'pistolNomenclature' => $this->pistol_nomenclature,
            'pistolSerialNumber' => $this->pistol_serial_number,
            'qtyAmmo'            => $this->qty_ammo,
            'approvedStatus'     => $this->approved_status,
            'isArchived'         => (bool) $this->is_archived,
            'archivedAt'         => $this->formatDate($this->archived_at),
            'createdAt'          => $this->formatDatetime($this->created_at),
            'updatedAt'          => $this->formatDatetime($this->updated_at),
            'dateApproved'       => $this->formatDate($this->date_approved),
            'icsStatus'          => $this->ics_status,
        ];
    }
}
