<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateParRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'firearm' => ['required', 'string', 'max:255'],
            'firearm_serial_number' => ['nullable', 'string', 'max:255'],
            'firearm_quantity' => ['required', 'integer', 'min:1', 'max:999'],
            'firearm_unit_cost' => ['required', 'numeric', 'min:0', 'max:999999999.99'],
            'ammunition_quantity' => ['required', 'integer', 'min:0', 'max:999999'],
            'ammunition_unit_cost' => ['required', 'numeric', 'min:0', 'max:999999999.99'],
            'unit' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'in:Active,Inactive'],
            'issued_date' => ['required', 'date'],
            'valid_until' => ['nullable', 'date', 'after_or_equal:issued_date'],
            'issued_by' => ['nullable', 'string', 'max:255'],
            'approved_by' => ['nullable', 'string', 'max:255'],
            'equipment_items' => ['nullable', 'array', 'max:100'],
            'equipment_items.*' => ['string', 'max:255'],
            'issued_by_personnel_id' => ['nullable', 'integer', 'exists:personnel,id'],
            'approved_by_personnel_id' => ['nullable', 'integer', 'exists:personnel,id'],
            'receiver_signature' => ['nullable', 'string', 'max:2000000'],
            'issued_by_signature' => ['nullable', 'string', 'max:2000000'],
            'approved_by_signature' => ['nullable', 'string', 'max:2000000'],
            'personnel_rank' => ['nullable', 'string', 'max:100'],
            'personnel_first_name' => ['nullable', 'string', 'max:255'],
            'personnel_middle_name' => ['nullable', 'string', 'max:255'],
            'personnel_last_name' => ['nullable', 'string', 'max:255'],
            'personnel_afp_serial_number' => ['nullable', 'string', 'max:255'],
            'remarks' => ['nullable', 'string', 'max:3000'],
        ];
    }
}
