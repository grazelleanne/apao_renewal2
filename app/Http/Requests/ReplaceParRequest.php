<?php

namespace App\Http\Requests;

class ReplaceParRequest extends UpdateParRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'replacement_reason' => ['required', 'string', 'min:5', 'max:3000'],
        ]);
    }
}
