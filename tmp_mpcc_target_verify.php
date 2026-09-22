<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$rows = Illuminate\Support\Facades\DB::table('mpcc_targets as mt')
    ->join('users as u', 'u.id', '=', 'mt.user_id')
    ->where('mt.year', 2026)
    ->where('mt.month', 5)
    ->select('u.name', 'mt.area', 'mt.branch', 'mt.target_amount')
    ->orderBy('u.id')
    ->get();

echo 'rows=' . $rows->count() . "\n";
foreach ($rows->take(13) as $row) {
    echo $row->name . ' | ' . $row->branch . ' | ' . $row->target_amount . "\n";
}
