<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Services\TicketReportExcelExporter;
use App\Services\TicketReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TicketReportController extends Controller
{
    public function index(Request $request, TicketReportService $reports): View
    {
        [$years, $filters] = $this->context($request);

        return view('ticketing.report.index', [
            'years' => $years,
            'filters' => $filters,
            'report' => $reports->build($filters['year'], $filters['month'], $filters['date_from'] ?? null, $filters['date_to'] ?? null),
        ]);
    }

    public function export(Request $request, string $reportType, TicketReportService $reports, TicketReportExcelExporter $exporter): StreamedResponse
    {
        $selection = $request->validate(['pivot' => ['nullable', 'integer', 'between:0,3']]);
        $pivot = isset($selection['pivot']) ? (int) $selection['pivot'] : null;
        abort_if($pivot !== null && ($reportType === 'all' || $pivot >= ($reportType === 'summary' ? 4 : 3)), 404);
        [, $filters] = $this->context($request);
        $report = $reports->build($filters['year'], $filters['month'], $filters['date_from'] ?? null, $filters['date_to'] ?? null);
        $book = $exporter->build($report, $filters, $reportType, $pivot);
        $filename = sprintf('ticketing-%s%s-%d-%02d.xlsx', $reportType, $pivot === null ? '' : '-'.($pivot + 1), $filters['year'], $filters['month']);

        return response()->streamDownload(function () use ($book) {
            try {
                (new Xlsx($book))->save('php://output');
            } finally {
                $book->disconnectWorksheets();
            }
        }, $filename, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'Cache-Control' => 'private, no-store']);
    }

    private function context(Request $request): array
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

        return [$years->push($year)->unique()->sortDesc()->values(), array_replace($filters, ['year' => $year, 'month' => $month])];
    }
}
