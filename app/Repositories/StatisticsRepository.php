<?php

namespace App\Repositories;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class StatisticsRepository
{
    /**
     * Mengambil agregat data peminjaman per periode, tipe, dan borrowernumber dari tabel statistics (Koha).
     */
    public function getBorrowingStatisticsByDateRange(Carbon $start, Carbon $end, string $sqlDateFormat): \Illuminate\Support\LazyCollection
    {
        return DB::connection('mysql2')->table('statistics as s')
            ->whereIn('s.type', ['issue', 'renew', 'return'])
            ->whereBetween('s.datetime', [$start, $end])
            ->select(
                DB::raw("DATE_FORMAT(s.datetime, '$sqlDateFormat') as periode"),
                's.type',
                's.borrowernumber',
                DB::raw('COUNT(*) as cnt')
            )
            ->groupBy('periode', 's.type', 's.borrowernumber')
            ->cursor();
    }
}
