<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$deleted = \Illuminate\Support\Facades\DB::table('stock_levels')->where('quantity', 0)->delete();
echo "Selesai. Deleted stock_levels dengan quantity=0: {$deleted} record.\n";
