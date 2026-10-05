<div class="card card-outline card-info">
    <div class="card-header">
        <h3 class="card-title">{{ $pivot['title'] }}</h3>
        <a href="{{ route('ticketing.report.export', array_merge($filters, ['reportType' => $reportType, 'pivot' => $pivotIndex])) }}" class="btn btn-sm btn-outline-success float-right"><i class="fas fa-file-excel mr-1"></i> Export Excel</a>
    </div>
    <div class="card-body p-0 table-responsive">
        <table class="table table-bordered table-hover table-sm mb-0">
            <thead class="bg-light"><tr>
                <th style="min-width:240px;">Kategori</th>
                @foreach($pivot['columns'] as $column)<th class="text-right text-nowrap">{{ $column }}</th>@endforeach
                <th class="text-right text-nowrap">Grand Total</th>
            </tr></thead>
            <tbody>
                @foreach($pivot['rows'] as $row)
                <tr class="{{ $row['subtotal'] ? 'font-weight-bold bg-light' : '' }}">
                    <th scope="row" style="padding-left:{{ 12 + $row['depth'] * 20 }}px;">{{ $row['label'] }}</th>
                    @foreach($row['cells'] as $cell)
                    <td class="text-right text-nowrap">{{ $cell === null ? '—' : number_format($cell, $pivot['metric'] === 'count' ? 0 : 2, ',', '.') }}{{ $cell !== null && $pivot['metric'] === 'rate' ? '%' : '' }}</td>
                    @endforeach
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
