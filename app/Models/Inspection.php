<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Inspection extends Model
{
    protected $table = 'inspections';

    protected $fillable = [
        'personnel_id', 'item_number', 'afp_serial_number', 'pistol_type',
        'date_registered', 'status',
        'barrel', 'slide', 'recoil_spring_assembly', 'firing_pin',
        'spacer_sleeve', 'firing_pin_spring', 'spring_cups',
        'firing_pin_safety', 'firing_pin_safety_spring', 'extractor',
        'extractor_depressor_plunger', 'extractor_depressor_plunger_spring',
        'trigger_loaded_bearing', 'rear_sight', 'front_sight',
        'remarks',
        'inspected_by_name', 'inspected_by_rank', 'inspected_by_position',
        'witnessed_by_name', 'witnessed_by_rank', 'witnessed_by_position',
        'approved_by_name', 'approved_by_rank', 'approved_by_position',
        'noted_by_name', 'noted_by_rank', 'noted_by_position',
        'inspected_by_user_id', 'inspected_at',
    ];

    protected $casts = [
        'date_registered' => 'date',
        'inspected_at'    => 'datetime',
    ];

    public function personnel()
    {
        return $this->belongsTo(Personnel::class, 'personnel_id');
    }

    // Checklist parts list (used in views)
    public static function parts(): array
    {
        return [
            'barrel'                              => 'Barrel',
            'slide'                               => 'Slide',
            'recoil_spring_assembly'              => 'Recoil Spring Assembly',
            'firing_pin'                          => 'Firing Pin',
            'spacer_sleeve'                       => 'Spacer Sleeve',
            'firing_pin_spring'                   => 'Firing Pin Spring',
            'spring_cups'                         => 'Spring Cups',
            'firing_pin_safety'                   => 'Firing Pin Safety',
            'firing_pin_safety_spring'            => 'Firing Pin Safety Spring',
            'extractor'                           => 'Extractor',
            'extractor_depressor_plunger'         => 'Extractor Depressor Plunger',
            'extractor_depressor_plunger_spring'  => 'Extractor Depressor Plunger Spring',
            'trigger_loaded_bearing'              => 'Trigger-Loaded Bearing',
            'rear_sight'                          => 'Rear Sight',
            'front_sight'                         => 'Front Sight',
        ];
    }
}