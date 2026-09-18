<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$rows = Illuminate\Support\Facades\DB::table('users')->select('branch')->where('role','MPCC')->where('branch','like','%jakarta%')->distinct()->orderBy('branch')->pluck('branch');
foreach ($rows as $row) { echo $row, PHP_EOL; }
