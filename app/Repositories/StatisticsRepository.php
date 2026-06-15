<?php

namespace App\Repositories;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class StatisticsRepository
{
    /**
     * Mengambil raw data peminjaman dari tabel statistics (Koha).
     */
    public function getBorrowingStatisticsByDateRange(Carbon $start, Carbon $end, string $sqlDateFormat): \Illuminate\Support\Collection
    {
        return DB::connection('mysql2')->table('statistics as s')
            ->whereIn('s.type', ['issue', 'renew', 'return'])
            ->whereBetween('s.datetime', [$start, $end])
            ->select(
                DB::raw("DATE_FORMAT(s.datetime, '$sqlDateFormat') as periode"),
                's.type',
                's.borrowernumber'
            )
            ->get();
    }

    /**
     * Membangun base query untuk tabel statistics tanpa dieksekusi (mengembalikan Builder).
     */
    public function getRawBorrowingStatisticsQuery(Carbon $start, Carbon $end): \Illuminate\Database\Query\Builder
    {
        return DB::connection('mysql2')->table('statistics as s')
            ->whereIn('s.type', ['issue', 'renew', 'return'])
            ->whereBetween('s.datetime', [$start, $end]);
    }
}
