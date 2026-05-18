<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$rows = Illuminate\Support\Facades\DB::table('mpcc_branch_targets')
    ->where('year', 2026)
    ->where('month', 5)
    ->select('branch', 'target_revenue_branch_billion', 'target_revenue_cluster_billion')
    ->orderBy('branch')
    ->get();
foreach ($rows as $row) {
    echo $row->branch . ' | branch=' . $row->target_revenue_branch_billion . ' | cluster=' . $row->target_revenue_cluster_billion . "\n";
}
