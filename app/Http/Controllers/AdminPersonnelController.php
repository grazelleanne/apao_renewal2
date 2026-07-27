<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Personnel;

class AdminPersonnelController extends Controller
{
    public function update(Request $request, $id)
    {
        $request->validate([
            'rank'               => 'sometimes|string|max:20',
            'lastName'           => 'sometimes|string|max:100',
            'firstName'          => 'sometimes|string|max:100',
            'middleName'         => 'sometimes|string|max:100',
            'afpSerialNumber'    => 'sometimes|string|max:50',
            'afosMos'            => 'sometimes|string|max:50',
            'branch'             => 'sometimes|string|max:50',
            'unit'               => 'sometimes|string|max:100',
            'dateOfValidity'     => 'sometimes|date',
            'dateOfBirth'        => 'sometimes|date',
            'pistolNomenclature' => 'sometimes|string|max:100',
            'pistolSerialNumber' => 'sometimes|string|max:50',
            'qtyAmmo'            => 'sometimes|integer|min:0',
            'approvedStatus'     => 'sometimes|in:pending,renewed,within,expired',
        ]);

        $personnel = Personnel::where('itemNumber', $id)->firstOrFail();
        $personnel->update($request->only([
            'rank', 'lastName', 'firstName', 'middleName',
            'afpSerialNumber', 'afosMos', 'branch', 'unit',
            'dateOfValidity', 'dateOfBirth',
            'pistolNomenclature', 'pistolSerialNumber', 'qtyAmmo',
            'approvedStatus',
        ]));

        return response()->json(['success' => true, 'data' => $personnel]);
    }
}