<?php

namespace App\Http\Requests;

use App\Models\Ticket;
use Illuminate\Foundation\Http\FormRequest;

class CloseTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && in_array($this->user()->role, Ticket::INPUT_ROLES, true);
    }

    public function rules(): array
    {
        return [
            'resolution_update' => ['required', 'string', 'max:10000'],
            'resolved_at' => ['required', 'date_format:Y-m-d\TH:i,Y-m-d\TH:i:s', 'before_or_equal:now'],
        ];
    }

    public function attributes(): array
    {
        return ['resolution_update' => 'hasil penanganan', 'resolved_at' => 'tanggal dan jam Closed'];
    }
}
