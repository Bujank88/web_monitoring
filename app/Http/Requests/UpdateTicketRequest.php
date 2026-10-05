<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class UpdateTicketRequest extends StoreTicketRequest
{
    public function rules(): array
    {
        return array_replace(parent::rules(), [
            // Historical tickets may have no PIC; retain their name unless a user is selected.
            'pic_user_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'version' => ['required', 'string', 'size:64'],
        ]);
    }
}
