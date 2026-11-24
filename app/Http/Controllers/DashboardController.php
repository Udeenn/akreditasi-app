<?php

namespace App\Http\Controllers;

use App\Models\M_biblio;
use App\Models\M_eprodi;
use App\Models\M_items;
use App\Models\M_viscorner;
use App\Models\M_vishistory;
use App\Models\M_Auv;
use App\Helpers\FacultyHelper;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Services\ProdiService;
use App\Services\BorrowerService;

class DashboardController extends Controller
{
    public function __construct(
        private ProdiService $prodiService,
        private BorrowerService $borrowerService
    ) {}

    private function safeCacheCount(string $cacheKey, callable $filter, string $selectExpr): int
    {
        try {
            return (int) Cache::remember($cacheKey, 3600, function () use ($filter, $selectExpr) {
                $q = M_items::on('mysql2')
                    ->select(DB::raw("{$selectExpr} AS total"))
                    ->where('items.damaged', 0)
                    ->where('items.itemlost', 0)
                    ->where('items.withdrawn', 0);
                $filter($q);
                return $q->value('total') ?? 0;
            });
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("Dashboard count error [{$cacheKey}]: " . $e->getMessage());
            return 0;
        }
    }

    public function totalStatistik()
    {
        try {
            $kunjunganHarian = Cache::remember('dashboard_kunjungan_harian_' . Carbon::today()->toDateString(), 300, function() {
                return M_vishistory::whereDate('visittime', Carbon::today())->count();
            });
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Dashboard kunjungan harian error: ' . $e->getMessage());
            $kunjunganHarian = 0;
        }

        $totalJudulBuku = $this->safeCacheCount(
            'dashboard_total_judul_buku',
            fn($q) => $q->where(DB::raw('LEFT(items.itype, 2)'), 'BK'),
            'COUNT(DISTINCT items.biblionumber)'
        );
        $totalEksemplar = $this->safeCacheCount(
            'dashboard_total_eksemplar',
            fn($q) => $q->where(DB::raw('LEFT(items.itype, 2)'), 'BK'),
            'COUNT(items.biblionumber)'
        );
        $totalEbooks = $this->safeCacheCount(
            'dashboard_total_ebooks',
            fn($q) => $q->where(DB::raw('LEFT(items.itype, 2)'), 'EB'),
            'COUNT(items.biblionumber)'
        );
        $totalJurnal = $this->safeCacheCount(
            'dashboard_total_jurnal',
            fn($q) => $q->whereIn('items.itype', ['EJ', 'JRA', 'JR', 'JRT']),
            'COUNT(items.biblionumber)'
        );

        $formatTotalJudulBuku = number_format($totalJudulBuku, 0, ',', '.');
        $formatTotalEksemplar = number_format($totalEksemplar, 0, ',', '.');
        $formatTotalEbooks = number_format($totalEbooks, 0, ',', '.');
        $formatTotalJurnal = number_format($totalJurnal, 0, ',', '.');

        // === KUNJUNGAN PER FAKULTAS TAHUN INI ===
        $currentYear = Carbon::now()->year;
        try {
        $kunjunganFakultas = Cache::remember("dashboard_kunjungan_fakultas_{$currentYear}", 3600, function () use ($currentYear) {
            $start = Carbon::createFromDate($currentYear, 1, 1)->startOfDay();
            $end = Carbon::createFromDate($currentYear, 12, 31)->endOfDay();

            // Query visitorhistory + visitorcorner
            $qHistory = DB::connection('mysql')->table('visitorhistory')
                ->select('cardnumber', DB::raw('COUNT(*) as cnt'))
                ->whereBetween('visittime', [$start, $end])
                ->groupBy('cardnumber');

            $qCorner = DB::connection('mysql')->table('visitorcorner')
                ->select('cardnumber', DB::raw('COUNT(*) as cnt'))
                ->whereBetween('visittime', [$start, $end])
                ->groupBy('cardnumber');

            $rawData = $qHistory->unionAll($qCorner)->get();

            // Fetch borrower info with PRODI attribute using BorrowerService
            $cardNumbers = $rawData->pluck('cardnumber');
            $borrowerInfo = $this->borrowerService->getBorrowerInfoByCardnumbers($cardNumbers);

            // Map to faculty
            $fakultasCount = [];
            foreach ($rawData as $row) {
                $cn = strtoupper(trim($row->cardnumber));
                $info = $borrowerInfo[$cn] ?? null;
                $cat = $info->categorycode ?? '';
                $prodi = $info->prodi_code ?? '';

                $kode = $this->prodiService->identifyProdiCode($cn, $cat, $prodi);
                $fakultas = FacultyHelper::mapCodeToFaculty($kode);

                // Exclude non-faculty entries
                $blacklist = ['Lainnya', 'Dosen', 'Dosen & Pengajar', 'Tendik', 'Tenaga Kependidikan'];
                if (in_array($fakultas, $blacklist)) continue;

                if (!isset($fakultasCount[$fakultas])) $fakultasCount[$fakultas] = 0;
                $fakultasCount[$fakultas] += (int) $row->cnt;
            }

            arsort($fakultasCount);
            return $fakultasCount;
        });
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Dashboard kunjungan fakultas error: ' . $e->getMessage());
            $kunjunganFakultas = [];
        }

        // === PEMINJAMAN PER FAKULTAS TAHUN INI ===
        try {
        $peminjamanFakultas = Cache::remember("dashboard_peminjaman_fakultas_{$currentYear}", 3600, function () use ($currentYear) {
            $start = Carbon::createFromDate($currentYear, 1, 1)->startOfDay();
            $end = Carbon::createFromDate($currentYear, 12, 31)->endOfDay();

            $rawData = DB::connection('mysql2')->table('statistics as s')
                ->leftJoin('borrowers as b', 'b.borrowernumber', '=', 's.borrowernumber')
                ->leftJoin('borrower_attributes as ba', function ($join) {
                    $join->on('ba.borrowernumber', '=', 'b.borrowernumber')
                        ->where('ba.code', '=', 'PRODI');
                })
                ->whereIn('s.type', ['issue', 'renew'])
                ->whereBetween('s.datetime', [$start, $end])
                ->select('b.cardnumber', 'b.categorycode', 'ba.attribute as prodi_code', DB::raw('COUNT(*) as cnt'))
                ->groupBy('b.cardnumber', 'b.categorycode', 'ba.attribute')
                ->get();

            $fakultasCount = [];
            foreach ($rawData as $row) {
                $catCode = $row->categorycode ?? '';
                $cardnumber = $row->cardnumber ?? '';
                $prodiCode = $row->prodi_code ?? '';

                $kode = $this->prodiService->identifyProdiCode($cardnumber, $catCode, $prodiCode);
                $fakultas = FacultyHelper::mapCodeToFaculty($kode);

                $blacklist = ['Lainnya', 'Dosen', 'Dosen & Pengajar', 'Tendik', 'Tenaga Kependidikan'];
                if (in_array($fakultas, $blacklist)) continue;

                if (!isset($fakultasCount[$fakultas])) $fakultasCount[$fakultas] = 0;
                $fakultasCount[$fakultas] += (int) $row->cnt;
            }

            arsort($fakultasCount);
            return $fakultasCount;
        });
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Dashboard peminjaman fakultas error: ' . $e->getMessage());
            $peminjamanFakultas = [];
        }

        return view('dashboard', compact(
            'totalJurnal', 'kunjunganHarian',
            'formatTotalEksemplar', 'formatTotalJudulBuku', 'formatTotalEbooks', 'formatTotalJurnal',
            'kunjunganFakultas', 'peminjamanFakultas'
        ));
    }
}
