<?php

namespace App\Http\Controllers;

use App\Http\Requests\CloseTicketRequest;
use App\Http\Requests\StoreTicketRequest;
use App\Http\Requests\UpdateTicketRequest;
use App\Models\Ticket;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TicketController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(Ticket::STATUSES)],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', ...($request->filled('date_from') ? ['after_or_equal:date_from'] : [])],
        ]);
        $query = Ticket::query()->select([
            'id', 'ticket_number', 'user_name', 'requested_at', 'request_type',
            'complaint_type', 'channel', 'method', 'account_campaign_id', 'diagnosis_issue',
            'evidence_reference', 'resolution_update', 'status', 'resolved_at', 'priority', 'handling_level',
            'original_ticket_number', 'created_by', 'import_source_key', 'created_at', 'updated_at',
        ]);
        if ($search = trim($filters['q'] ?? '')) {
            $query->where(function ($query) use ($search) {
                foreach (['ticket_number', 'user_name', 'account_campaign_id', 'diagnosis_issue'] as $column) {
                    $query->orWhere($column, 'like', '%'.$search.'%');
                }
            });
        }
        if ($status = $filters['status'] ?? null) {
            if ($status === 'Open') {
                $query->where(function ($query) {
                    $query->where('status', 'Open')->orWhereNull('status')->orWhereRaw("TRIM(status) = ''");
                });
            } else {
                $query->where('status', $status);
            }
        }

        if (! empty($filters['date_from'])) {
            $query->where('requested_at', '>=', $filters['date_from'].' 00:00:00');
        }
        if (! empty($filters['date_to'])) {
            $query->where('requested_at', '<', Carbon::parse($filters['date_to'])->addDay()->format('Y-m-d').' 00:00:00');
        }

        return view('ticketing.index', [
            'tickets' => $query->orderByDesc('requested_at')->orderByDesc('id')->paginate(25)->withQueryString(),
            'filters' => $filters,
        ]);
    }

    public function create(): View
    {
        return view('ticketing.create', [
            'picUsersByRole' => User::query()->select(['id', 'name', 'email', 'role'])
                ->orderBy('role')->orderBy('name')->orderBy('id')->get()
                ->groupBy(fn (User $user) => $user->role ?: 'Tanpa role'),
        ]);
    }

    public function store(StoreTicketRequest $request): RedirectResponse
    {
        $attributes = $request->validated();
        $pic = User::findOrFail($attributes['pic_user_id']);
        unset($attributes['pic_user_id']);
        $attributes['user_name'] = $pic->name;
        $ticket = Ticket::submit($attributes + ['created_by' => $request->user()->id]);

        return redirect()->route('ticketing.create')->with('success', 'Tiket '.$ticket->ticket_number.' berhasil disimpan.');
    }

    public function close(CloseTicketRequest $request, Ticket $ticket): RedirectResponse
    {
        $ticket->closeWithResolution($request->validated('resolution_update'), $request->validated('resolved_at'));

        return redirect()->route('ticketing.index')->with('success', 'Tiket '.$ticket->ticket_number.' berhasil ditutup.');
    }

    public function edit(Ticket $ticket): View
    {
        $data = $this->create()->getData();

        return view('ticketing.create', $data + ['ticket' => $ticket]);
    }

    public function update(UpdateTicketRequest $request, Ticket $ticket): RedirectResponse
    {
        $attributes = $request->validated();
        $version = $attributes['version'];
        $picId = $attributes['pic_user_id'] ?? null;
        unset($attributes['version'], $attributes['pic_user_id']);
        if ($picId) {
            $attributes['user_name'] = User::findOrFail($picId)->name;
        }
        $attributes['resolved_at'] = $attributes['resolved_at'] ?? null;
        DB::transaction(function () use ($ticket, $version, $attributes) {
            $current = Ticket::whereKey($ticket->id)->lockForUpdate()->firstOrFail();
            $this->checkVersion($current, $version);
            $current->update($attributes);
        }, 3);

        return redirect()->route('ticketing.index')->with('success', 'Tiket '.$ticket->ticket_number.' berhasil diperbarui.');
    }

    public function destroy(Request $request, Ticket $ticket): RedirectResponse
    {
        $data = $request->validate(['version' => ['required', 'string', 'size:64']]);
        DB::transaction(function () use ($ticket, $data) {
            $current = Ticket::whereKey($ticket->id)->lockForUpdate()->firstOrFail();
            $this->checkVersion($current, $data['version']);
            $current->delete();
        }, 3);

        return redirect()->route('ticketing.index')->with('success', 'Tiket '.$ticket->ticket_number.' berhasil dihapus.');
    }

    private function checkVersion(Ticket $ticket, string $version): void
    {
        if (! hash_equals($ticket->versionToken(), $version)) {
            throw ValidationException::withMessages(['ticket' => 'Tiket telah berubah. Muat ulang halaman sebelum melanjutkan.']);
        }
    }
}
