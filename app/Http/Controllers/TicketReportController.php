<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Services\TicketReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TicketReportController extends Controller
{
    public function index(Request $request, TicketReportService $reports): View
    {
        $filters = $request->validate([
            'year' => ['nullable', 'integer', 'between:2000,2100'],
            'month' => ['nullable', 'integer', 'between:1,12'],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', ...($request->filled('date_from') ? ['after_or_equal:date_from'] : [])],
        ]);
        $years = Ticket::query()->selectRaw('DISTINCT SUBSTR(requested_at, 1, 4) AS year')->orderByDesc('year')->pluck('year')->map(fn ($year) => (int) $year);
        $year = (int) ($filters['year'] ?? $years->first() ?? now()->year);
        $latest = Ticket::where('requested_at', '>=', $year.'-01-01 00:00:00')
            ->where('requested_at', '<', ($year + 1).'-01-01 00:00:00')->max('requested_at');
        $month = (int) ($filters['month'] ?? ($latest ? Carbon::parse($latest)->month : now()->month));

        return view('ticketing.report.index', [
            'years' => $years->push($year)->unique()->sortDesc()->values(),
            'filters' => array_replace($filters, ['year' => $year, 'month' => $month]),
            'report' => $reports->build($year, $month, $filters['date_from'] ?? null, $filters['date_to'] ?? null),
        ]);
    }
}
