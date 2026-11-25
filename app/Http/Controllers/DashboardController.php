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
        private BorrowerService $borrowerService,
        private \App\Repositories\BorrowerRepository $borrowerRepository
    ) {}

    private function getCachedItemTotals(): array
    {
        try {
            return Cache::remember('dashboard_item_totals_v1', 3600, function () {
                $row = M_items::on('mysql2')
                    ->selectRaw("
                        COUNT(DISTINCT CASE WHEN items.itype LIKE 'BK%' THEN items.biblionumber END) AS total_judul_buku,
                        COUNT(CASE WHEN items.itype LIKE 'BK%' THEN items.biblionumber END) AS total_eksemplar,
                        COUNT(CASE WHEN items.itype LIKE 'EB%' THEN items.biblionumber END) AS total_ebooks,
                        COUNT(CASE WHEN items.itype IN ('EJ', 'JRA', 'JR', 'JRT') THEN items.biblionumber END) AS total_jurnal
                    ")
                    ->where('items.damaged', 0)
                    ->where('items.itemlost', 0)
                    ->where('items.withdrawn', 0)
                    ->where(function ($q) {
                        $q->where('items.itype', 'LIKE', 'BK%')
                          ->orWhere('items.itype', 'LIKE', 'EB%')
                          ->orWhereIn('items.itype', ['EJ', 'JRA', 'JR', 'JRT']);
                    })
                    ->first();

                return [
                    'totalJudulBuku' => (int) ($row->total_judul_buku ?? 0),
                    'totalEksemplar' => (int) ($row->total_eksemplar ?? 0),
                    'totalEbooks'    => (int) ($row->total_ebooks ?? 0),
                    'totalJurnal'    => (int) ($row->total_jurnal ?? 0),
                ];
            });
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Dashboard item totals error: ' . $e->getMessage());
            return [
                'totalJudulBuku' => 0,
                'totalEksemplar' => 0,
                'totalEbooks'    => 0,
                'totalJurnal'    => 0,
            ];
        }
    }

    public function totalStatistik()
    {
        try {
            $kunjunganHarian = Cache::remember('dashboard_kunjungan_harian_' . Carbon::today()->toDateString(), 300, function() {
                return M_vishistory::whereBetween('visittime', [
                    Carbon::today()->startOfDay(),
                    Carbon::today()->endOfDay(),
                ])->count();
            });
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Dashboard kunjungan harian error: ' . $e->getMessage());
            $kunjunganHarian = 0;
        }

        $itemTotals = $this->getCachedItemTotals();
        $totalJudulBuku = $itemTotals['totalJudulBuku'];
        $totalEksemplar = $itemTotals['totalEksemplar'];
        $totalEbooks    = $itemTotals['totalEbooks'];
        $totalJurnal    = $itemTotals['totalJurnal'];

        $formatTotalJudulBuku = number_format($totalJudulBuku, 0, ',', '.');
        $formatTotalEksemplar = number_format($totalEksemplar, 0, ',', '.');
        $formatTotalEbooks = number_format($totalEbooks, 0, ',', '.');
        $formatTotalJurnal = number_format($totalJurnal, 0, ',', '.');

        // === KUNJUNGAN PER FAKULTAS TAHUN INI ===
        $currentYear = Carbon::now()->year;
        try {
        $kunjunganFakultas = Cache::remember("dashboard_kunjungan_fakultas_v2_{$currentYear}", 3600, function () use ($currentYear) {
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
        $peminjamanFakultas = Cache::remember("dashboard_peminjaman_fakultas_v2_{$currentYear}", 3600, function () use ($currentYear) {
            $start = Carbon::createFromDate($currentYear, 1, 1)->startOfDay();
            $end = Carbon::createFromDate($currentYear, 12, 31)->endOfDay();

            // Agregasi per borrowernumber terlebih dahulu agar tidak men-join borrower_attributes saat scan statistics
            $rawData = DB::connection('mysql2')->table('statistics as s')
                ->whereIn('s.type', ['issue', 'renew'])
                ->whereBetween('s.datetime', [$start, $end])
                ->select('s.borrowernumber', DB::raw('COUNT(*) as cnt'))
                ->groupBy('s.borrowernumber')
                ->get();

            $fakultasCount = [];
            $blacklist = ['Lainnya', 'Dosen', 'Dosen & Pengajar', 'Tendik', 'Tenaga Kependidikan'];

            foreach ($rawData->chunk(5000) as $chunk) {
                $borrowerNumbers = $chunk->pluck('borrowernumber')->filter()->unique()->values();
                $borrowerInfo = $this->borrowerRepository->getBorrowerInfoByBorrowerNumbers($borrowerNumbers);

                foreach ($chunk as $row) {
                    $info = $borrowerInfo[$row->borrowernumber] ?? null;
                    $catCode = $info->categorycode ?? '';
                    $cardnumber = $info->cardnumber ?? '';
                    $prodiCode = $info->prodi_code ?? '';

                    $kode = $this->prodiService->identifyProdiCode($cardnumber, $catCode, $prodiCode);
                    $fakultas = FacultyHelper::mapCodeToFaculty($kode);

                    if (in_array($fakultas, $blacklist)) continue;

                    $fakultasCount[$fakultas] = ($fakultasCount[$fakultas] ?? 0) + (int) $row->cnt;
                }
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
