<?php

namespace App\Services;

use App\Models\M_vishistory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class VisitStatisticsService
{
    public function __construct(
        private ProdiService $prodiService,
        private BorrowerService $borrowerService,
        private \App\Repositories\VisitRepository $visitRepository
    ) {}

    /**
     * Logic for kunjunganFakultasTable
     */
    public function getKunjunganFakultas(string $filterType, string $startDate, string $endDate, int $startYear, int $endYear, string $selectedFakultas, ?string $selectedLokasi): array
    {
        $allProdiListObj = \App\Models\M_Auv::getCachedProdiList();
        $prodiToFacultyMap = $this->prodiService->getProdiToFacultyMap($allProdiListObj);
        $prodiNameMap = $this->prodiService->getFullProdiList();

        $cacheKey = 'kunjungan_fak_v4_' . md5(json_encode([
            'ft' => $filterType, 'fk' => $selectedFakultas, 'lok' => $selectedLokasi,
            'dt' => ($filterType == 'yearly') ? "$startYear-$endYear" : "$startDate-$endDate",
        ]));

        return Cache::remember($cacheKey, 3600, function () use (
            $filterType, $startDate, $endDate, $startYear, $endYear,
            $selectedLokasi, $selectedFakultas, $prodiToFacultyMap, $prodiNameMap
        ) {
            if ($filterType === 'yearly') {
                $start = Carbon::createFromDate($startYear, 1, 1)->startOfDay();
                $end   = Carbon::createFromDate($endYear, 12, 31)->endOfDay();
                $sqlDateFormat = '%Y-%m';
            } else {
                $start = Carbon::parse($startDate)->startOfDay();
                $end   = Carbon::parse($endDate)->endOfDay();
                $sqlDateFormat = '%Y-%m-%d';
            }

            $rawData = $this->visitRepository->getVisitDataUnionByDateRange($start, $end, $sqlDateFormat, $selectedLokasi);

            $cardNumbers = $rawData->pluck('cardnumber');
            $borrowerInfo = $this->borrowerService->getBorrowerInfoByCardnumbers($cardNumbers);

            $periodeData = [];
            $facultyTotals = [];
            $prodiTotals = [];

            foreach ($rawData as $row) {
                $cn   = strtoupper(trim($row->cardnumber));
                $info = $borrowerInfo[$cn] ?? null;
                $cat  = $info->categorycode ?? '';
                $prodi = $info->prodi_code ?? '';

                $kode = $this->prodiService->identifyProdiCode($cn, $cat, $prodi);
                $fakItem = $prodiToFacultyMap[$kode] ?? \App\Helpers\FacultyHelper::mapCodeToFaculty($kode);

                if ($selectedFakultas && $selectedFakultas !== 'semua') {
                    if ($fakItem !== $selectedFakultas) continue;
                }

                $cnt = (int) $row->cnt;
                if (!in_array($fakItem, ['Dosen', 'Tenaga Kependidikan', 'Alumni', 'Anggota Luar Biasa', 'Kartu Sekali Kunjung'])) {
                    $facultyTotals[$fakItem] = ($facultyTotals[$fakItem] ?? 0) + $cnt;
                }
                $prodiTotals[$kode] = ($prodiTotals[$kode] ?? 0) + $cnt;

                $p = $row->periode;
                if (!isset($periodeData[$p])) $periodeData[$p] = [];
                if (!isset($periodeData[$p][$kode])) $periodeData[$p][$kode] = 0;
                $periodeData[$p][$kode] += $cnt;
            }

            ksort($periodeData);
            $tableData = [];
            $chartData = [];

            foreach ($periodeData as $periode => $prodiCounts) {
                $totalPeriode = array_sum($prodiCounts);
                arsort($prodiCounts);

                $details = [];
                foreach ($prodiCounts as $code => $jumlah) {
                    $details[] = [
                        'prodi' => $code . ' - ' . ($prodiNameMap[$code] ?? 'Tidak Dikenal'),
                        'kode' => $code, 'jumlah' => $jumlah,
                    ];
                }

                $tableData[] = ['periode' => $periode, 'total_kunjungan' => $totalPeriode, 'prodi_details' => $details];

                $chartLabel = $periode;
                try { if ($filterType === 'yearly') $chartLabel = Carbon::createFromFormat('Y-m', $periode)->locale('id')->isoFormat('MMMM Y'); } catch (\Throwable $e) {}
                $chartData[] = ['label' => $chartLabel, 'total_kunjungan' => $totalPeriode];
            }

            arsort($facultyTotals);
            $rankingFakultas = [];
            foreach ($facultyTotals as $name => $total) {
                $rankingFakultas[] = ['name' => $name, 'total' => $total];
            }

            arsort($prodiTotals);
            $rankingProdi = [];
            foreach ($prodiTotals as $code => $total) {
                $rankingProdi[] = [
                    'name' => $code . ' - ' . ($prodiNameMap[$code] ?? 'Tidak Dikenal'),
                    'kode' => $code,
                    'total' => $total
                ];
            }

            return compact('tableData', 'chartData', 'rankingFakultas', 'rankingProdi') + ['total' => collect($tableData)->sum('total_kunjungan')];
        });
    }

    /**
     * Logic for kunjunganProdiTable
     */
    public function getKunjunganProdi(string $filterType, string $startDate, string $endDate, int $startYear, int $endYear, ?string $kodeProdiFilter): array
    {
        $allProdiListObj = \App\Models\M_Auv::getCachedProdiList();
        $prodiNameMap = $this->prodiService->getFullProdiList();

        $cacheKey = 'kunj_prodi_v5_' . md5(json_encode([
            'ft' => $filterType, 'kp' => $kodeProdiFilter,
            'dt' => ($filterType == 'yearly') ? "$startYear-$endYear" : "$startDate-$endDate",
        ]));

        return Cache::remember($cacheKey, 3600, function () use (
            $filterType, $startDate, $endDate, $startYear, $endYear, $kodeProdiFilter, $prodiNameMap
        ) {
            if ($filterType === 'yearly') {
                $start = Carbon::createFromDate($startYear, 1, 1)->startOfDay();
                $end   = Carbon::createFromDate($endYear, 12, 31)->endOfDay();
                $dateFormatPHP = 'Y-m-01';
            } else {
                $start = Carbon::parse($startDate)->startOfDay();
                $end   = Carbon::parse($endDate)->endOfDay();
                $dateFormatPHP = 'Y-m-d';
            }

            // Pengelompokan Data per Tanggal dan Prodi
            $periodeData = [];
            $prodiTotals = [];

            $processVisits = function ($visits) use (&$periodeData, &$prodiTotals, $dateFormatPHP, $kodeProdiFilter) {
                $cardNumbers = collect($visits)->pluck('cardnumber');
                $borrowerInfo = $this->borrowerService->getBorrowerInfoByCardnumbers($cardNumbers);

                foreach ($visits as $row) {
                    $cn   = strtoupper(trim($row->cardnumber));
                    $info = $borrowerInfo[$cn] ?? null;
                    $cat  = $info->categorycode ?? '';
                    $prodi = $info->prodi_code ?? '';

                    $kode = $this->prodiService->identifyProdiCode($cn, $cat, $prodi);

                    // Filter by Prodi jika ada
                    if ($kodeProdiFilter) {
                        if ($kodeProdiFilter === 'ALL_MAHASISWA') {
                            $nonStudents = ['DOSEN', 'TENDIK', 'KSP', 'KSPMBKM', 'KSPBIPA', 'XA', 'LB', 'XC'];
                            if (in_array($kode, $nonStudents)) {
                                continue;
                            }
                        } elseif ($kode !== $kodeProdiFilter) {
                            continue;
                        }
                    }

                    $tgl = \Illuminate\Support\Carbon::parse($row->visittime)->format($dateFormatPHP);

                    if (!isset($periodeData[$tgl])) $periodeData[$tgl] = [];
                    if (!isset($periodeData[$tgl][$kode])) $periodeData[$tgl][$kode] = 0;
                    
                    $periodeData[$tgl][$kode]++;
                    $prodiTotals[$kode] = ($prodiTotals[$kode] ?? 0) + 1;
                }
            };

            \Illuminate\Support\Facades\DB::connection('mysql')->table('visitorhistory')
                ->select('id', 'visittime', 'cardnumber')
                ->whereBetween('visittime', [$start, $end])
                ->chunkById(5000, $processVisits);

            \Illuminate\Support\Facades\DB::connection('mysql')->table('visitorcorner')
                ->select('id', 'visittime', 'cardnumber')
                ->whereBetween('visittime', [$start, $end])
                ->chunkById(5000, $processVisits);

            ksort($periodeData);
            
            $tableData = [];
            $totalKeseluruhanKunjungan = 0;

            foreach ($periodeData as $tanggal => $prodiCounts) {
                $totalHarian = array_sum($prodiCounts);
                $totalKeseluruhanKunjungan += $totalHarian;
                
                arsort($prodiCounts);

                $details = [];
                foreach ($prodiCounts as $code => $jumlah) {
                    $details[] = [
                        'prodi' => $code . ' - ' . ($prodiNameMap[$code] ?? 'Tidak Dikenal'),
                        'kode' => $code,
                        'jumlah' => $jumlah,
                    ];
                }

                $tableData[] = [
                    'tanggal' => $tanggal,
                    'total_kunjungan' => $totalHarian,
                    'prodi_details' => $details,
                ];
            }

            arsort($prodiTotals);
            $rankingProdi = [];
            foreach ($prodiTotals as $code => $total) {
                $rankingProdi[] = [
                    'name' => $code . ' - ' . ($prodiNameMap[$code] ?? 'Tidak Dikenal'),
                    'kode' => $code,
                    'total' => $total
                ];
            }

            return compact('tableData', 'totalKeseluruhanKunjungan', 'rankingProdi');
        });
    }

}
