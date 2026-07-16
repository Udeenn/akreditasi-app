<?php
require __DIR__.'/../vendor/autoload.php';
\ = require_once __DIR__.'/../bootstrap/app.php';
\ = \->make(Illuminate\Contracts\Http\Kernel::class);
\ = \->handle(
    \ = Illuminate\Http\Request::capture()
);

use Illuminate\Support\Facades\DB;

try {
    \ = DB::connection('mysql2')->table('biblioitems')
        ->where('cn_class', 'like', 'A210%')
        ->limit(5)->get();
    
    \ = DB::connection('mysql2')->table('biblioitems')
        ->where('cn_class', 'like', 'A210%')
        ->count();
        
    \ = DB::connection('mysql2')->table('statistics')
        ->whereBetween('datetime', ['2026-01-01', '2026-12-31'])
        ->count();

    echo json_encode(['count_A210' => \, 'sample' => \, 'stats_2026' => \]);
} catch (\Exception \) {
    echo \->getMessage();
}

