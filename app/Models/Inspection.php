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
        'frame', 'magazine', 'magazine_catch', 'magazine_catch_spring',
        'trigger', 'trigger_spring', 'trigger_bar', 'slide_stop_lever',
        'trigger_pin', 'trigger_mechanism_housing', 'trigger_housing_pin',
        'locking_block', 'locking_block_pin', 'slide_lock', 'slide_lock_spring',
        'connector', 'guide_rod',
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
            'firing_pin'                          => 'Firing Pin / Striker',
            'firing_pin_safety'                   => 'Firing Pin Safety',
            'extractor'                           => 'Extractor',
            'rear_sight'                          => 'Rear Sight',
            'front_sight'                         => 'Front Sight',
            'frame'                               => 'Frame',
            'magazine'                            => 'Magazine',
            'magazine_catch'                      => 'Magazine Catch',
            'magazine_catch_spring'               => 'Magazine Catch Spring',
            'trigger'                             => 'Trigger',
            'trigger_spring'                      => 'Trigger Spring',
            'trigger_bar'                         => 'Trigger Bar',
            'slide_stop_lever'                    => 'Slide Stop Lever',
            'trigger_pin'                         => 'Trigger Pin',
            'trigger_mechanism_housing'           => 'Trigger Housing / Mechanism Housing',
            'trigger_housing_pin'                 => 'Trigger Housing Pin',
            'locking_block'                       => 'Locking Block',
            'locking_block_pin'                   => 'Locking Block Pin',
            'slide_lock'                          => 'Slide Lock',
            'slide_lock_spring'                   => 'Slide Lock Spring',
            'connector'                           => 'Connector',
            'guide_rod'                           => 'Guide Rod',
        ];
    }
}
