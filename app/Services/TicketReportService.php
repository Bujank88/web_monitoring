<?php

namespace App\Services;

use App\Models\Ticket;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class TicketReportService
{
    public const CATEGORIES = [
        '(Hari yang sama) Cepat', '(Hari yang sama) Sedang', '(Hari yang sama) Lambat',
        'Lebih dari 1 Hari', 'Sedang Berlangsung', 'Periksa Waktu',
    ];

    public function build(int $year, int $month, ?string $from = null, ?string $to = null): array
    {
        $query = Ticket::query()->select(['requested_at', 'resolved_at', 'status', 'channel', 'complaint_type'])
            ->where('requested_at', '>=', sprintf('%04d-01-01 00:00:00', $year))
            ->where('requested_at', '<', sprintf('%04d-01-01 00:00:00', $year + 1));
        if ($from) {
            $query->where('requested_at', '>=', $from.' 00:00:00');
        }
        if ($to) {
            $query->where('requested_at', '<', Carbon::parse($to)->addDay()->format('Y-m-d').' 00:00:00');
        }
        $records = $query->get()->map(function (Ticket $ticket) {
            $minutes = $ticket->status === 'Closed' && $ticket->resolved_at
                ? ($ticket->resolved_at->getTimestamp() - $ticket->requested_at->getTimestamp()) / 60 : null;

            return [
                'month' => $ticket->requested_at->month,
                'week' => min(4, (int) ceil($ticket->requested_at->day / 7)),
                'status' => $ticket->status,
                'channel' => trim((string) $ticket->channel) ?: '(blank)',
                'complaint' => trim((string) $ticket->complaint_type) ?: '(blank)',
                'minutes' => $minutes,
                'category' => $this->category($ticket->status, $minutes),
            ];
        });
        $weekly = $records->where('month', $month)->values();
        $weeklyPeriods = [];
        for ($week = 1; $week <= 4; $week++) {
            $weeklyPeriods[] = ['label' => 'Week '.$week, 'month' => $month, 'week' => $week];
        }
        $monthlyPeriods = [];
        for ($number = 1; $number <= 12; $number++) {
            $label = Carbon::create($year, $number, 1)->locale('id')->translatedFormat('F');
            if ($number === $month) {
                foreach ($weeklyPeriods as $period) {
                    $monthlyPeriods[] = ['label' => $label.' '.$period['label'], 'month' => $number, 'week' => $period['week']];
                }
                $label .= ' Total';
            }
            $monthlyPeriods[] = ['label' => $label, 'month' => $number, 'week' => null];
        }

        return [
            'weekly_total' => $weekly->count(),
            'year_total' => $records->count(),
            'filtered_total' => $weekly->count(),
            'closed' => $weekly->where('status', 'Closed')->count(),
            'ongoing' => $weekly->where('status', '!=', 'Closed')->count(),
            'invalid_times' => $weekly->where('category', 'Periksa Waktu')->count(),
            'resolution_rate' => $this->value($weekly->where('status', 'Closed'), $weekly, 'rate'),
            'average_minutes' => $this->value($weekly, $weekly, 'average'),
            'channel' => [
                $this->pivot('Jumlah tiket per status dan channel', $weekly, $weeklyPeriods, ['status', 'channel']),
                $this->pivot('Rata-rata penyelesaian per channel (menit)', $weekly, $weeklyPeriods, ['channel'], 'average'),
                $this->pivot('Kategori penanganan per channel', $weekly, $weeklyPeriods, ['channel', 'category']),
            ],
            'complaint' => [
                $this->pivot('Jumlah tiket per complaint type dan status', $weekly, $weeklyPeriods, ['complaint', 'status']),
                $this->pivot('Rata-rata penyelesaian per complaint type (menit)', $weekly, $weeklyPeriods, ['complaint'], 'average'),
                $this->pivot('Kategori penanganan per complaint type', $weekly, $weeklyPeriods, ['complaint', 'category']),
            ],
            'summary' => [
                $this->pivot('Jumlah tiket per kategori penanganan', $records, $monthlyPeriods, ['category']),
                $this->pivot('Rata-rata penyelesaian per kategori (menit)', $records, $monthlyPeriods, ['category'], 'average'),
                $this->pivot('Jumlah tiket per status', $records, $monthlyPeriods, ['status']),
                $this->pivot('Persentase status / resolution rate', $records, $monthlyPeriods, ['status'], 'rate'),
            ],
            'trend' => collect(range(1, 12))->map(function ($number) use ($records, $year) {
                $group = $records->where('month', $number);

                return [
                    'label' => Carbon::create($year, $number, 1)->locale('id')->translatedFormat('M'),
                    'total' => $group->count(), 'closed' => $group->where('status', 'Closed')->count(),
                ];
            })->all(),
        ];
    }

    private function category(string $status, ?float $minutes): string
    {
        if ($status !== 'Closed') {
            return 'Sedang Berlangsung';
        }
        if ($minutes === null || $minutes < 0) {
            return 'Periksa Waktu';
        }

        return match (true) {
            $minutes <= 15 => self::CATEGORIES[0],
            $minutes <= 60 => self::CATEGORIES[1],
            $minutes <= 1440 => self::CATEGORIES[2],
            default => 'Lebih dari 1 Hari',
        };
    }

    private function pivot(string $title, Collection $records, array $periods, array $dimensions, string $metric = 'count'): array
    {
        $rows = [];
        $this->groupRows($records, $records, $periods, $dimensions, $metric, $rows);
        $rows[] = $this->row('Grand Total', $records, $records, $periods, $metric, 0, true);

        return ['title' => $title, 'metric' => $metric, 'columns' => array_column($periods, 'label'), 'rows' => $rows];
    }

    private function groupRows(Collection $records, Collection $all, array $periods, array $dimensions, string $metric, array &$rows, int $depth = 0): void
    {
        $dimension = array_shift($dimensions);
        foreach ($records->groupBy($dimension)->sortKeys() as $label => $group) {
            $rows[] = $this->row((string) $label, $group, $all, $periods, $metric, $depth, count($dimensions) > 0);
            if ($dimensions) {
                $this->groupRows($group, $all, $periods, $dimensions, $metric, $rows, $depth + 1);
            }
        }
    }

    private function row(string $label, Collection $group, Collection $all, array $periods, string $metric, int $depth, bool $subtotal): array
    {
        $cells = [];
        foreach ($periods as $period) {
            $subset = fn (Collection $items) => $items->filter(fn ($item) => $item['month'] === $period['month']
                && ($period['week'] === null || $item['week'] === $period['week']));
            $cells[] = $this->value($subset($group), $subset($all), $metric);
        }
        $cells[] = $this->value($group, $all, $metric);

        return ['label' => $label, 'depth' => $depth, 'subtotal' => $subtotal, 'cells' => $cells];
    }

    private function value(Collection $group, Collection $all, string $metric): int|float|null
    {
        return match ($metric) {
            'average' => $group->whereNotNull('minutes')->avg('minutes'),
            'rate' => $all->count() ? $group->count() / $all->count() * 100 : null,
            default => $group->count(),
        };
    }
}
