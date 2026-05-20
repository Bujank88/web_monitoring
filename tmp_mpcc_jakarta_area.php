<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$rows = Illuminate\Support\Facades\DB::table('users')->select('area','branch')->where('role','MPCC')->where('branch','like','%jakarta%')->orderBy('branch')->get();
foreach ($rows as $row) { echo ($row->area ?? '-'), ' | ', ($row->branch ?? '-'), PHP_EOL; }
