<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$total = Illuminate\Support\Facades\DB::table('mpcc_targets')->count();
echo 'total=' . $total . "\n";
$rows = Illuminate\Support\Facades\DB::table('mpcc_targets')->orderBy('id')->get();
foreach ($rows as $row) {
    echo $row->id . ' | user=' . $row->user_id . ' | y=' . $row->year . ' | m=' . $row->month . ' | branch=' . $row->branch . ' | target=' . $row->target_amount . "\n";
}
