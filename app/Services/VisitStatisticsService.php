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

        $cacheKey = 'kunjungan_fak_v5_' . md5(json_encode([
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
                        'prodi' => $code . ' - ' . $this->resolveProdiName($code, $prodiNameMap),
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
                    'name' => $code . ' - ' . $this->resolveProdiName($code, $prodiNameMap),
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

        $cacheKey = 'kunj_prodi_v7_' . md5(json_encode([
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

            // Pengelompokan Data per Tanggal, Prodi, dan Mahasiswa
            $periodeData = [];
            $prodiTotals = [];
            $studentCounts = [];
            $studentMeta = [];
            $nonStudents = ['DOSEN', 'TENDIK', 'KSP', 'KSPMBKM', 'KSPBIPA', 'XA', 'LB', 'XC', 'UMUM'];

            $processVisits = function ($visits) use (&$periodeData, &$prodiTotals, &$studentCounts, &$studentMeta, $nonStudents, $dateFormatPHP, $kodeProdiFilter) {
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

                    if ($cn !== '' && !in_array($kode, $nonStudents)) {
                        $studentCounts[$cn] = ($studentCounts[$cn] ?? 0) + 1;
                        if (!isset($studentMeta[$cn])) {
                            $nama = trim(($info->firstname ?? '') . ' ' . ($info->surname ?? ''));
                            $studentMeta[$cn] = [
                                'cardnumber' => $cn,
                                'nama'       => $nama !== '' ? $nama : 'Tanpa Nama',
                                'prodi'      => $kode,
                            ];
                        }
                    }
                }
            };

            $qHistory = \Illuminate\Support\Facades\DB::connection('mysql')->table('visitorhistory')
                ->select('id', 'visittime', 'cardnumber')
                ->whereBetween('visittime', [$start, $end]);

            $qCorner = \Illuminate\Support\Facades\DB::connection('mysql')->table('visitorcorner')
                ->select('id', 'visittime', 'cardnumber')
                ->whereBetween('visittime', [$start, $end]);

            // Optimasi: jika filter prodi spesifik (seperti D400), query SQL hanya kartu yang relevan
            if ($kodeProdiFilter && !in_array($kodeProdiFilter, ['ALL_MAHASISWA', 'DOSEN', 'TENDIK', 'KSP', 'KSPMBKM', 'KSPBIPA', 'XA', 'LB', 'XC'])) {
                $qHistory->where('cardnumber', 'LIKE', $kodeProdiFilter . '%');
                $qCorner->where('cardnumber', 'LIKE', $kodeProdiFilter . '%');
            }

            $qHistory->chunkById(5000, $processVisits);
            $qCorner->chunkById(5000, $processVisits);

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
                        'prodi' => $code . ' - ' . $this->resolveProdiName($code, $prodiNameMap),
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
                    'name' => $code . ' - ' . $this->resolveProdiName($code, $prodiNameMap),
                    'kode' => $code,
                    'total' => $total
                ];
            }

            arsort($studentCounts);
            $rankingMahasiswa = [];
            foreach (array_slice($studentCounts, 0, 200, true) as $cn => $total) {
                $meta = $studentMeta[$cn];
                $rankingMahasiswa[] = [
                    'cardnumber' => $meta['cardnumber'],
                    'nama'       => $meta['nama'],
                    'prodi'      => $meta['prodi'],
                    'prodi_name' => $this->resolveProdiName($meta['prodi'], $prodiNameMap),
                    'total'      => $total,
                ];
            }

            return compact('tableData', 'totalKeseluruhanKunjungan', 'rankingProdi', 'rankingMahasiswa');
        });
    }

    private function resolveProdiName(string $code, array $prodiNameMap): string
    {
        if (!empty($prodiNameMap[$code])) {
            return $prodiNameMap[$code];
        }
        $fakName = \App\Helpers\FacultyHelper::mapCodeToFaculty($code);
        return $fakName !== 'Lainnya' ? $fakName : 'Tidak Dikenal';
    }

    /**
     * Agregasi kunjungan harian/bulanan (dipakai API kunjungan/harian).
     */
    public function getKunjunganHarian(string $filterType, string $startDate, string $endDate, int $startYear, int $endYear, ?string $selectedLokasi = null): array
    {
        $cacheKey = 'kunj_harian_api_v1_' . md5(json_encode([
            'ft' => $filterType,
            'lok' => $selectedLokasi,
            'dt' => ($filterType === 'yearly') ? "$startYear-$endYear" : "$startDate-$endDate",
        ]));

        return Cache::remember($cacheKey, 3600, function () use ($filterType, $startDate, $endDate, $startYear, $endYear, $selectedLokasi) {
            if ($filterType === 'yearly') {
                $start = Carbon::createFromDate($startYear, 1, 1)->startOfDay();
                $end = Carbon::createFromDate($endYear, 12, 31)->endOfDay();
                $sqlDateFormat = '%Y-%m';
            } else {
                $start = Carbon::parse($startDate)->startOfDay();
                $end = Carbon::parse($endDate)->endOfDay();
                $sqlDateFormat = '%Y-%m-%d';
            }

            $rawData = $this->visitRepository->getVisitDataUnionByDateRange($start, $end, $sqlDateFormat, $selectedLokasi);

            $grouped = [];
            foreach ($rawData as $row) {
                $grouped[$row->periode] = ($grouped[$row->periode] ?? 0) + (int) $row->cnt;
            }
            ksort($grouped);

            $tableData = [];
            $chartData = [];
            foreach ($grouped as $periode => $jumlah) {
                $tableData[] = ['periode' => $periode, 'total_kunjungan' => $jumlah];
                $label = $periode;
                try {
                    if ($filterType === 'yearly') {
                        $label = Carbon::createFromFormat('Y-m', $periode)->locale('id')->isoFormat('MMMM Y');
                    }
                } catch (\Throwable $e) {
                }
                $chartData[] = ['label' => $label, 'total_kunjungan' => $jumlah];
            }

            return [
                'tableData' => $tableData,
                'chartData' => $chartData,
                'total' => array_sum($grouped),
            ];
        });
    }

}
