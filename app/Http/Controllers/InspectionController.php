<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Personnel;
use App\Models\Inspection;
use Illuminate\Support\Facades\DB;

class InspectionController extends Controller
{
    // ── List view ──
    public function index()
    {
        $user = auth()->user();
        return view('admin.inspection', compact('user'));
    }

    // ── Stat cards + table data ──
    public function getData()
    {
        $personnel = Personnel::all();

        $data = $personnel->map(function ($p) {
            $inspection = Inspection::where('item_number', $p->itemNumber)->latest()->first();
            return [
                'itemNumber'       => $p->itemNumber,
                'rank'             => $p->rank ?? '',
                'lastName'         => $p->lastName ?? '',
                'firstName'        => $p->firstName ?? '',
                'middleName'       => $p->middleName ?? '',
                'afpSerialNumber'  => $p->afpSerialNumber ?? '',
                'pistolType'       => $p->pistolType ?? '',
                'unit'             => $p->unit ?? '',
                'dateRegistered'   => $p->created_at?->format('M d, Y'),
                'inspectionStatus' => $inspection?->status ?? 'pending',
            ];
        });

        return response()->json([
            'success'  => true,
            'data'     => $data,
            'pending'  => $data->where('inspectionStatus', 'pending')->count(),
            'under'    => $data->where('inspectionStatus', 'under')->count(),
            'approved' => $data->where('inspectionStatus', 'approved')->count(),
        ]);
    }

    // ── Single record detail for checklist ──
    public function detail($itemNumber)
    {
        $personnel = Personnel::where('itemNumber', $itemNumber)->first();
        if (!$personnel) {
            return response()->json(['success' => false, 'error' => 'Not found.']);
        }

        $inspection = Inspection::where('item_number', $itemNumber)->latest()->first();

        // Pull signatory defaults from ics_settings (single row)
        $ics = DB::table('ics_settings')->latest()->first();

        return response()->json([
            'success'    => true,
            'personnel'  => $personnel,
            'inspection' => $inspection,
            'ics'        => $ics,
        ]);
    }

    // ── Save / update inspection ──
    public function save(Request $request)
    {
        try {
            $data = $request->all();

            Inspection::updateOrCreate(
                ['item_number' => $data['itemNumber']],
                [
                    'status'                             => $data['status']                            ?? 'under',
                    'remarks'                            => $data['remarks']                           ?? '',
                    'inspected_by_name'                  => $data['inspectedByName']                   ?? '',
                    'inspected_by_rank'                  => $data['inspectedByRank']                   ?? '',
                    'inspected_by_position'              => $data['inspectedByPosition']               ?? '',
                    'witnessed_by_name'                  => $data['witnessedByName']                   ?? '',
                    'witnessed_by_rank'                  => $data['witnessedByRank']                   ?? '',
                    'witnessed_by_position'              => $data['witnessedByPosition']               ?? '',
                    'approved_by_name'                   => $data['approvedByName']                    ?? '',
                    'approved_by_rank'                   => $data['approvedByRank']                    ?? '',
                    'approved_by_position'               => $data['approvedByPosition']                ?? '',
                    'noted_by_name'                      => $data['notedByName']                       ?? '',
                    'noted_by_rank'                      => $data['notedByRank']                       ?? '',
                    'noted_by_position'                  => $data['notedByPosition']                   ?? '',
                    'barrel'                             => $data['barrel']                            ?? 'serviceable',
                    'slide'                              => $data['slide']                             ?? 'serviceable',
                    'recoil_spring_assembly'             => $data['recoil_spring_assembly']            ?? 'serviceable',
                    'firing_pin'                         => $data['firing_pin']                        ?? 'serviceable',
                    'spacer_sleeve'                      => $data['spacer_sleeve']                     ?? 'serviceable',
                    'firing_pin_spring'                  => $data['firing_pin_spring']                 ?? 'serviceable',
                    'spring_cups'                        => $data['spring_cups']                       ?? 'serviceable',
                    'firing_pin_safety'                  => $data['firing_pin_safety']                 ?? 'serviceable',
                    'firing_pin_safety_spring'           => $data['firing_pin_safety_spring']          ?? 'serviceable',
                    'extractor'                          => $data['extractor']                         ?? 'serviceable',
                    'extractor_depressor_plunger'        => $data['extractor_depressor_plunger']       ?? 'serviceable',
                    'extractor_depressor_plunger_spring' => $data['extractor_depressor_plunger_spring']?? 'serviceable',
                    'trigger_loaded_bearing'             => $data['trigger_loaded_bearing']            ?? 'serviceable',
                    'rear_sight'                         => $data['rear_sight']                        ?? 'serviceable',
                    'front_sight'                        => $data['front_sight']                       ?? 'serviceable',
                ]
            );

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    // ── Notify staff for ICS renewal ──
    public function notifyStaff(Request $request)
    {
        try {
            $itemNumber = $request->input('itemNumber');
            $message    = $request->input('message');

            $personnel = Personnel::where('itemNumber', $itemNumber)->first();
            if (!$personnel) {
                return response()->json(['success' => false, 'error' => 'Personnel not found.']);
            }

            // Store in personnel_notifications table
            DB::table('personnel_notifications')->insert([
                'item_number' => $itemNumber,
                'message'     => $message,
                'type'        => 'ics_renewal',
                'status'      => 'unread',
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    // ── Print report ──
    public function print($itemNumber)
    {
        $personnel  = Personnel::where('itemNumber', $itemNumber)->firstOrFail();
        $inspection = Inspection::where('item_number', $itemNumber)->latest()->first();
        $ics        = DB::table('ics_settings')->latest()->first();

        return view('admin.inspection-print', compact('personnel', 'inspection', 'ics'));
    }
}