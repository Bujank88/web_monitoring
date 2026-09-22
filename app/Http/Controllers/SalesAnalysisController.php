<?php

namespace App\Http\Controllers;

use App\Services\SalesAnalysisService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;

class SalesAnalysisController extends Controller
{
    public function index()
    {
        return view('admin.sales-analysis', ['channels' => SalesAnalysisService::CHANNELS]);
    }

    public function script()
    {
        // cPanel may serve a separate document root from the project's public folder.
        return response()->file(base_path('public/js/sales-analysis.js'), [
            'Content-Type' => 'application/javascript; charset=UTF-8',
            'Cache-Control' => 'private, max-age=300',
            'X-Content-Type-Options' => 'nosniff',
        ])->setPrivate();
    }

    public function data(Request $request, string $chart, SalesAnalysisService $service)
    {
        abort_unless(in_array($chart, ['trend', 'accounts-trend', 'retention', 'channels'], true), 404);
        $validated = $request->validate([
            'month' => ['required', 'date_format:Y-m', 'before_or_equal:'.now()->format('Y-m')],
            'channel' => ['sometimes', Rule::in(array_merge(['all'], array_keys(SalesAnalysisService::CHANNELS)))],
        ]);
        $channel = in_array($chart, ['trend', 'accounts-trend'], true) ? ($validated['channel'] ?? 'all') : 'all';
        $period = $service->period($validated['month']);
        $version = $chart === 'retention' ? 'v2' : 'v1';
        $key = "sales-analysis:{$version}:{$chart}:{$period['month']}:{$period['end']->format('Y-m-d')}:{$channel}";

        // Serve the last result while refreshing stale data after the response.
        $payload = Cache::flexible($key, [300, 3600], function () use ($service, $chart, $period, $channel) {
            $data = match ($chart) {
                'channels' => $service->channels($period),
                'retention' => $service->retention($period),
                default => $service->trend($period, $channel, $chart === 'accounts-trend'),
            };

            return $data + ['period' => [
                'current' => $period['current_label'], 'previous' => $period['previous_label'],
            ], 'updated_at' => now()->format('d M Y H:i')];
        }, ['seconds' => 120]);

        return response()->json($payload);
    }
}
