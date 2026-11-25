<?php

namespace App\Repositories;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class VisitRepository
{
    public static function hasCornerNotesColumn(): bool
    {
        return Cache::remember('schema_visitorcorner_has_notes', 3600, function () {
            try {
                return Schema::connection('mysql')->hasColumn('visitorcorner', 'notes');
            } catch (\Throwable $e) {
                return false;
            }
        });
    }

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

        $this->applyLocationFilter($qHistory, $qCorner, $selectedLokasi);

        return $qHistory->unionAll($qCorner)->get();
    }

    /**
     * Agregasi jumlah kunjungan langsung per periode tanpa memuat cardnumber ke PHP.
     */
    public function getVisitTotalsByDateRange(Carbon $start, Carbon $end, string $sqlDateFormat, ?string $selectedLokasi = null): \Illuminate\Support\Collection
    {
        $qHistory = DB::connection('mysql')->table('visitorhistory')
            ->select(DB::raw("DATE_FORMAT(visittime, '$sqlDateFormat') as periode"), DB::raw('COUNT(*) as cnt'))
            ->whereBetween('visittime', [$start, $end])
            ->groupBy('periode');

        $qCorner = DB::connection('mysql')->table('visitorcorner')
            ->select(DB::raw("DATE_FORMAT(visittime, '$sqlDateFormat') as periode"), DB::raw('COUNT(*) as cnt'))
            ->whereBetween('visittime', [$start, $end])
            ->groupBy('periode');

        $this->applyLocationFilter($qHistory, $qCorner, $selectedLokasi);

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

        $this->applyLocationFilter($qHistory, $qCorner, $selectedLokasi);

        return $qHistory->unionAll($qCorner);
    }

    private function applyLocationFilter($qHistory, $qCorner, ?string $selectedLokasi): void
    {
        if (!$selectedLokasi) {
            return;
        }

        $hasNotes = self::hasCornerNotesColumn();
        if ($selectedLokasi === 'pusat') {
            $qHistory->where(fn($q) => $q->where('location', 'pusat')->orWhereNull('location'));
            if ($hasNotes) {
                $qCorner->where(fn($q) => $q->where('notes', 'pusat')->orWhere('notes', '')->orWhereNull('notes'));
            }
            return;
        }

        $qHistory->where('location', $selectedLokasi);
        if ($hasNotes) {
            $qCorner->where('notes', $selectedLokasi);
        } else {
            $qCorner->whereRaw('1 = 0');
        }
    }
}
