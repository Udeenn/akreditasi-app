<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * DateFilterService — Standarisasi parsing filter tanggal (daily/yearly).
 *
 * Menggantikan pola parsing tanggal yang terduplikasi di setiap method controller:
 *   $filterType = $request->input('filter_type', 'daily');
 *   if ($filterType === 'yearly') { ... } else { ... }
 */
class DateFilterService
{
    /**
     * Parse parameter filter tanggal dari request.
     *
     * @return array{
     *     filterType: string,
     *     start: Carbon,
     *     end: Carbon,
     *     sqlDateFormat: string,
     *     tanggalAwal: string,
     *     tanggalAkhir: string,
     *     tahunAwal: int,
     *     tahunAkhir: int,
     *     hasFilter: bool
     * }
     */
    public function parseFilter(Request $request, array $options = []): array
    {
        $filterType = $request->input('filter_type', $options['defaultFilterType'] ?? 'daily');

        // Deteksi apakah ada filter aktif
        $hasFilter = $request->hasAny([
            'filter_type', 'tanggal_awal', 'tanggal_akhir',
            'tahun_awal', 'tahun_akhir',
            'start_date', 'end_date', 'start_year', 'end_year',
        ]);

        // Support kedua format parameter naming (kunjungan vs peminjaman)
        $tanggalAwal  = $request->input('tanggal_awal', $request->input('start_date', Carbon::now()->subDays(30)->toDateString()));
        $tanggalAkhir = $request->input('tanggal_akhir', $request->input('end_date', Carbon::now()->toDateString()));
        $tahunAwal    = (int) $request->input('tahun_awal', $request->input('start_year', Carbon::now()->year));
        $tahunAkhir   = (int) $request->input('tahun_akhir', $request->input('end_year', Carbon::now()->year));

        if ($filterType === 'yearly' || $filterType === 'monthly') {
            if ($tahunAwal > $tahunAkhir) {
                [$tahunAwal, $tahunAkhir] = [$tahunAkhir, $tahunAwal];
            }

            $start = Carbon::createFromDate($tahunAwal, 1, 1)->startOfDay();
            $end   = Carbon::createFromDate($tahunAkhir, 12, 31)->endOfDay();
            $sqlDateFormat = '%Y-%m';
        } else {
            $start = Carbon::parse($tanggalAwal)->startOfDay();
            $end   = Carbon::parse($tanggalAkhir)->endOfDay();
            $sqlDateFormat = '%Y-%m-%d';

            if ($start->greaterThan($end)) {
                [$start, $end] = [$end, $start];
                [$tanggalAwal, $tanggalAkhir] = [$tanggalAkhir, $tanggalAwal];
            }
        }

        return [
            'filterType'   => $filterType,
            'start'        => $start,
            'end'          => $end,
            'sqlDateFormat' => $sqlDateFormat,
            'tanggalAwal'  => $tanggalAwal,
            'tanggalAkhir' => $tanggalAkhir,
            'tahunAwal'    => $tahunAwal,
            'tahunAkhir'   => $tahunAkhir,
            'hasFilter'    => $hasFilter,
        ];
    }

    /**
     * Generate display period string untuk UI.
     */
    public function getDisplayPeriod(string $filterType, $start, $end, int $tahunAwal = 0, int $tahunAkhir = 0): string
    {
        if ($filterType === 'yearly' || $filterType === 'monthly') {
            return "Tahun {$tahunAwal}" . ($tahunAwal != $tahunAkhir ? " s.d. {$tahunAkhir}" : "");
        }

        $startCarbon = $start instanceof Carbon ? $start : Carbon::parse($start);
        $endCarbon   = $end instanceof Carbon ? $end : Carbon::parse($end);

        return "Periode " .
            $startCarbon->locale('id')->isoFormat('D MMMM Y') .
            " s.d. " .
            $endCarbon->locale('id')->isoFormat('D MMMM Y');
    }
}
