<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$rows = Illuminate\Support\Facades\DB::table('mpcc_branch_targets')->select('branch','month','year')->distinct()->orderBy('branch')->get();
foreach ($rows as $row) { echo $row->branch, ' | ', $row->month, ' | ', $row->year, PHP_EOL; }
