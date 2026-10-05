<?php

namespace App\Http\Requests;

use App\Models\Ticket;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && in_array($this->user()->role, Ticket::INPUT_ROLES, true);
    }

    public function rules(): array
    {
        return [
            'pic_user_id' => ['required', 'integer', Rule::exists('users', 'id')],
            'requested_at' => ['required', 'date_format:Y-m-d\TH:i,Y-m-d\TH:i:s'],
            'request_type' => ['required', Rule::in(Ticket::REQUEST_TYPES)],
            'complaint_type' => ['required', Rule::in(Ticket::COMPLAINT_TYPES)],
            'channel' => ['nullable', Rule::in(Ticket::CHANNELS)],
            'method' => ['nullable', Rule::in(Ticket::METHODS)],
            'account_campaign_id' => ['required', 'string', 'max:5000'],
            'diagnosis_issue' => ['required', 'string', 'max:10000'],
            'evidence_reference' => ['nullable', 'string', 'max:5000'],
            'resolution_update' => ['nullable', 'required_if:status,Closed', 'string', 'max:10000'],
            'status' => ['required', Rule::in(Ticket::STATUSES)],
            'resolved_at' => ['nullable', 'required_if:status,Closed', 'prohibited_unless:status,Closed', 'date_format:Y-m-d\TH:i,Y-m-d\TH:i:s', 'after_or_equal:requested_at'],
            'priority' => ['required', Rule::in(Ticket::PRIORITIES)],
            'handling_level' => ['nullable', Rule::in(Ticket::HANDLING_LEVELS)],
        ];
    }

    public function attributes(): array
    {
        return [
            'pic_user_id' => 'PIC pengajuan', 'requested_at' => 'waktu pengajuan',
            'account_campaign_id' => 'akun / campaign ID', 'diagnosis_issue' => 'diagnosis isu',
            'resolved_at' => 'waktu penyelesaian', 'resolution_update' => 'hasil penanganan',
        ];
    }
}
