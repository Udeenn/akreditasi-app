<?php

namespace App\Repositories;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class VisitRepository
{
    /**
     * Mengambil data union dari visitorhistory dan visitorcorner.
     */
    public function getVisitDataUnionByDateRange(Carbon $start, Carbon $end, string $sqlDateFormat, ?string $selectedLokasi = null): \Illuminate\Support\Collection
    {
        $qHistory = DB::connection('mysql')->table('visitorhistory')
            ->select(DB::raw("DATE_FORMAT(visittime, '$sqlDateFormat') as periode"), 'cardnumber', DB::raw('COUNT(*) as cnt'))
            ->whereBetween('visittime', [$start, $end])
            ->groupBy('periode', 'cardnumber');

        $qCorner = DB::connection('mysql')->table('visitorcorner')
            ->select(DB::raw("DATE_FORMAT(visittime, '$sqlDateFormat') as periode"), 'cardnumber', DB::raw('COUNT(*) as cnt'))
            ->whereBetween('visittime', [$start, $end])
            ->groupBy('periode', 'cardnumber');

        if ($selectedLokasi) {
            if ($selectedLokasi === 'pusat') {
                $qHistory->where(fn($q) => $q->where('location', 'pusat')->orWhereNull('location'));
                $qCorner->where(fn($q) => $q->where('notes', 'pusat')->orWhere('notes', '')->orWhereNull('notes'));
            } else {
                $qHistory->where('location', $selectedLokasi);
                $qCorner->where('notes', $selectedLokasi);
            }
        }

        return $qHistory->unionAll($qCorner)->get();
    }

    /**
     * Membangun base query untuk union visitorhistory dan visitorcorner tanpa dieksekusi (mengembalikan Builder).
     * Digunakan oleh Service untuk menambahkan select kustom, groupBy, atau klausa lainnya.
     */
    public function getRawVisitsQuery(Carbon $start, Carbon $end, ?string $selectedLokasi = null): \Illuminate\Database\Query\Builder
    {
        $qHistory = DB::connection('mysql')->table('visitorhistory')
            ->select('visittime', 'cardnumber', 'id')
            ->whereBetween('visittime', [$start, $end]);

        $qCorner = DB::connection('mysql')->table('visitorcorner')
            ->select('visittime', 'cardnumber', 'id')
            ->whereBetween('visittime', [$start, $end]);

        if ($selectedLokasi) {
            if ($selectedLokasi === 'pusat') {
                $qHistory->where(fn($q) => $q->where('location', 'pusat')->orWhereNull('location'));
                $qCorner->where(fn($q) => $q->where('notes', 'pusat')->orWhere('notes', '')->orWhereNull('notes'));
            } else {
                $qHistory->where('location', $selectedLokasi);
                $qCorner->where('notes', $selectedLokasi);
            }
        }

        return $qHistory->unionAll($qCorner);
    }
}
