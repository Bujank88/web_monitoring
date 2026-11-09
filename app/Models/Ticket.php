<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class Ticket extends Model
{
    public const REQUEST_TYPES = ['Complaint', 'Ad hoc Requests'];

    public const COMPLAINT_TYPES = ['Billing / Payment', 'Campaign Creation', 'Login / Authentication', 'Reporting / Campaign Result', 'Web', 'Others'];

    public const CHANNELS = ['SMS', 'RCS', 'MMS', 'WABA', 'Display'];

    public const METHODS = ['Broadcast', 'LBA', 'Targeted', 'Pop Up'];

    public const STATUSES = ['Open', 'In Progress', 'Pending', 'Closed'];

    public const PRIORITIES = ['Low', 'Medium', 'High', 'TOP PRIORITY'];

    public const HANDLING_LEVELS = ['L0', 'L1', 'L2', 'L3'];

    public const INPUT_ROLES = ['Admin'];

    protected $attributes = ['status' => 'Open'];

    public function getStatusAttribute(?string $value): string
    {
        return trim((string) $value) === '' ? 'Open' : $value;
    }

    public function versionToken(): string
    {
        $attributes = $this->getAttributes();
        unset($attributes['import_data']);
        ksort($attributes);

        return hash('sha256', json_encode($attributes, JSON_THROW_ON_ERROR));
    }

    protected $fillable = [
        'ticket_number', 'original_ticket_number', 'user_name', 'requested_at',
        'request_type', 'complaint_type', 'channel', 'method', 'account_campaign_id',
        'diagnosis_issue', 'evidence_reference', 'resolution_update', 'status',
        'resolved_at', 'priority', 'handling_level', 'created_by',
        'import_source_key', 'import_data',
    ];

    protected function casts(): array
    {
        return ['requested_at' => 'datetime', 'resolved_at' => 'datetime', 'import_data' => 'array'];
    }

    public static function submit(array $attributes): self
    {
        return DB::transaction(function () use ($attributes) {
            $sequence = DB::table('ticket_sequences')->where('id', 1)->lockForUpdate()->first();
            $number = $sequence->next_number;
            $attributes['ticket_number'] = 'T'.str_pad((string) $number, 10, '0', STR_PAD_LEFT);
            DB::table('ticket_sequences')->where('id', 1)->update(['next_number' => $number + 1]);

            return static::create($attributes);
        }, 3);
    }

    public function closeWithResolution(string $result, string $closedAt): void
    {
        DB::transaction(function () use ($result, $closedAt) {
            $ticket = static::whereKey($this->getKey())->lockForUpdate()->firstOrFail();
            if ($ticket->status !== 'Open') {
                throw ValidationException::withMessages(['ticket' => 'Hanya tiket berstatus Open yang dapat ditutup. Muat ulang daftar tiket.']);
            }
            $resolvedAt = Carbon::parse($closedAt);
            if ($ticket->requested_at->gt($resolvedAt)) {
                throw ValidationException::withMessages(['resolved_at' => 'Tanggal dan jam Closed tidak boleh lebih awal dari waktu pengajuan.']);
            }
            if ($resolvedAt->gt(now())) {
                throw ValidationException::withMessages(['resolved_at' => 'Tanggal dan jam Closed tidak boleh melewati waktu saat ini.']);
            }
            $previous = trim((string) $ticket->resolution_update);
            $update = 'Penutupan ('.$resolvedAt->format('d/m/Y H:i:s').'): '.trim($result);
            $ticket->update([
                'status' => 'Closed',
                'resolved_at' => $resolvedAt,
                'resolution_update' => $previous === '' ? $update : $previous."\n\n".$update,
            ]);
        }, 3);
    }
}
