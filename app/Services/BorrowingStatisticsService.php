<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class BorrowingStatisticsService
{
    public function __construct(
        private ProdiService $prodiService,
        private BorrowerService $borrowerService,
        private \App\Repositories\StatisticsRepository $statisticsRepository
    ) {}

    /**
     * Logic for peminjamanFakultasTable
     */
    public function getPeminjamanFakultas(string $filterType, string $startDate, string $endDate, int $startYear, int $endYear, string $selectedFakultas): array
    {
        $cacheKey = 'peminjaman_fakultas_v5_' . md5(json_encode([
            'filterType' => $filterType,
            'start' => (in_array($filterType, ['yearly', 'monthly'])) ? "$startYear-01-01" : $startDate,
            'end' => (in_array($filterType, ['yearly', 'monthly'])) ? "$endYear-12-31" : $endDate,
            'selectedFakultas' => $selectedFakultas,
        ]));

        // Data historis (bukan hari ini) bisa di-cache lebih lama
        $endBoundary = in_array($filterType, ['yearly', 'monthly'])
            ? Carbon::createFromDate($endYear, 12, 31)
            : Carbon::parse($endDate);
        $ttl = $endBoundary->isToday() || $endBoundary->isFuture() ? 1800 : 86400;

        return Cache::remember($cacheKey, $ttl, function () use ($filterType, $startDate, $endDate, $startYear, $endYear, $selectedFakultas) {
            
            if (in_array($filterType, ['yearly', 'monthly'])) {
                $start = Carbon::createFromDate($startYear, 1, 1)->startOfDay();
                $end = Carbon::createFromDate($endYear, 12, 31)->endOfDay();
                $sqlDateFormat = '%Y-%m';
            } else {
                $start = Carbon::parse($startDate)->startOfDay();
                $end = Carbon::parse($endDate)->endOfDay();
                $sqlDateFormat = '%Y-%m-%d';
            }

            $rawData = $this->statisticsRepository->getBorrowingStatisticsByDateRange($start, $end, $sqlDateFormat);

            $prodiListMap = \App\Models\M_Auv::getCachedProdiList()->pluck('lib', 'authorised_value')->toArray();
            $aggregatedData = [];
            $allBorrowers = [];
            $facultyTotals = [];
            $prodiTotals = [];

            foreach ($rawData->chunk(5000) as $chunk) {
                $borrowerNumbers = collect($chunk)->pluck('borrowernumber')->unique()->values();
                $borrowerInfo = app(\App\Repositories\BorrowerRepository::class)->getBorrowerInfoByBorrowerNumbers($borrowerNumbers);

                foreach ($chunk as $row) {
                    $info = $borrowerInfo[$row->borrowernumber] ?? null;
                    $catCode = $info->categorycode ?? '';
                    $cardnumber = $info->cardnumber ?? '';
                    $prodiCode = $info->prodi_code ?? '';
                    
                    $kode = $this->prodiService->identifyProdiCode($cardnumber, $catCode, $prodiCode);
                    $fakultas = \App\Helpers\FacultyHelper::mapCodeToFaculty($kode);

                    if ($selectedFakultas && $selectedFakultas !== 'semua' && $fakultas !== $selectedFakultas) {
                        continue;
                    }

                    $cnt = (int) ($row->cnt ?? 1);
                    if (!in_array($fakultas, ['Dosen', 'Tenaga Kependidikan', 'Alumni', 'Anggota Luar Biasa', 'Kartu Sekali Kunjung', 'Lainnya'])) {
                        $facultyTotals[$fakultas] = ($facultyTotals[$fakultas] ?? 0) + $cnt;
                    }
                    $namaProdi = $prodiListMap[$kode] ?? $kode;
                    $prodiDisplay = $kode . ' - ' . $namaProdi;
                    $prodiTotals[$prodiDisplay] = ($prodiTotals[$prodiDisplay] ?? 0) + $cnt;
                    $periode = $row->periode;

                    if (!isset($aggregatedData[$periode])) $aggregatedData[$periode] = [];
                    if (!isset($aggregatedData[$periode][$prodiDisplay])) {
                        $aggregatedData[$periode][$prodiDisplay] = ['issue' => 0, 'renew' => 0, 'return' => 0, 'borrowers' => []];
                    }

                    $aggregatedData[$periode][$prodiDisplay][$row->type] += $cnt;
                    if (in_array($row->type, ['issue', 'renew'])) {
                        $aggregatedData[$periode][$prodiDisplay]['borrowers'][$row->borrowernumber] = true;
                        $allBorrowers[$row->borrowernumber] = true;
                    }
                }
            }

            $summary = $this->buildPeriodTableData($aggregatedData);
            $tableData = $summary['tableData'];
            $totalIssues = $summary['totalIssues'];
            $totalRenews = $summary['totalRenews'];
            $totalReturns = $summary['totalReturns'];
            $totalCirculation = $totalIssues + $totalRenews + $totalReturns;
            $totalBorrowers = count($allBorrowers);

            // Build chartData for the chart
            $chartData = $tableData->map(function ($row) use ($filterType) {
                $label = (in_array($filterType, ['yearly', 'monthly']))
                    ? \Carbon\Carbon::createFromFormat('Y-m', $row['periode'])->format('M Y')
                    : \Carbon\Carbon::parse($row['periode'])->format('d M Y');
                return [
                    'label'        => $label,
                    'issue'        => $row['jumlah_issue'],
                    'renew'        => $row['jumlah_renew'],
                    'pengembalian' => $row['jumlah_buku_kembali'],
                    'sirkulasi'    => $row['total_sirkulasi'],
                ];
            })->values()->toArray();

            arsort($facultyTotals);
            $rankingFakultas = [];
            foreach ($facultyTotals as $name => $total) {
                $rankingFakultas[] = ['name' => $name, 'total' => $total];
            }

            arsort($prodiTotals);
            $rankingProdi = [];
            foreach ($prodiTotals as $name => $total) {
                $rankingProdi[] = ['name' => $name, 'total' => $total];
            }

            return [
                'totalIssues'     => $totalIssues,
                'totalRenews'     => $totalRenews,
                'totalReturns'    => $totalReturns,
                'totalCirculation'=> $totalCirculation,
                'totalBorrowers'  => $totalBorrowers,
                'tableData'       => $tableData->toArray(),
                'chartData'       => $chartData,
                'rankingFakultas' => $rankingFakultas,
                'rankingProdi'    => $rankingProdi,
            ];
        });
    }

    /**
     * Logic for peminjamanProdiChart / Table
     */
    public function getPeminjamanProdi(string $filterType, string $startDate, string $endDate, int $startYear, int $endYear, ?string $selectedProdi): array
    {
        if ($selectedProdi !== null && strtoupper(trim($selectedProdi)) === 'STAFF') {
            $selectedProdi = 'TENDIK';
        }

        $cacheKey = 'peminjaman_prodi_v6_' . md5(json_encode([
            'filterType' => $filterType,
            'start' => (in_array($filterType, ['yearly', 'monthly'])) ? "$startYear-01-01" : $startDate,
            'end' => (in_array($filterType, ['yearly', 'monthly'])) ? "$endYear-12-31" : $endDate,
            'selectedProdi' => $selectedProdi,
        ]));

        // Data historis (bukan hari ini) bisa di-cache lebih lama
        $endBoundary = in_array($filterType, ['yearly', 'monthly'])
            ? Carbon::createFromDate($endYear, 12, 31)
            : Carbon::parse($endDate);
        $ttl = $endBoundary->isToday() || $endBoundary->isFuture() ? 1800 : 86400;

        return Cache::remember($cacheKey, $ttl, function () use ($filterType, $startDate, $endDate, $startYear, $endYear, $selectedProdi) {
            
            if (in_array($filterType, ['yearly', 'monthly'])) {
                $start = Carbon::createFromDate($startYear, 1, 1)->startOfDay();
                $end = Carbon::createFromDate($endYear, 12, 31)->endOfDay();
                $sqlDateFormat = '%Y-%m-01';
            } else {
                $start = Carbon::parse($startDate)->startOfDay();
                $end = Carbon::parse($endDate)->endOfDay();
                $sqlDateFormat = '%Y-%m-%d';
            }

            $rawData = $this->statisticsRepository->getBorrowingStatisticsByDateRange($start, $end, $sqlDateFormat);
            
            $prodiListMap = \App\Models\M_Auv::getCachedProdiList()->pluck('lib', 'authorised_value')->toArray();
            $aggregatedData = [];
            $allBorrowers = [];
            $prodiTotals = [];
            $studentStats = [];
            $nonStudents = ['DOSEN', 'TENDIK', 'KSP', 'KSPMBKM', 'KSPBIPA', 'XA', 'LB', 'XC', 'UMUM'];

            foreach ($rawData->chunk(5000) as $chunk) {
                $borrowerNumbers = collect($chunk)->pluck('borrowernumber')->unique()->values();
                $borrowerInfo = app(\App\Repositories\BorrowerRepository::class)->getBorrowerInfoByBorrowerNumbers($borrowerNumbers);
                
                foreach ($chunk as $row) {
                    $info = $borrowerInfo[$row->borrowernumber] ?? null;
                    $catCode = $info->categorycode ?? '';
                    $cardnumber = $info->cardnumber ?? '';
                    $prodiCode = $info->prodi_code ?? '';
                    
                    $kode = $this->prodiService->identifyProdiCode($cardnumber, $catCode, $prodiCode);

                    if ($selectedProdi && $selectedProdi !== 'semua') {
                        if ($selectedProdi === 'ALL_MAHASISWA') {
                            if (in_array($kode, $nonStudents)) {
                                continue;
                            }
                        } elseif ($kode !== $selectedProdi) {
                            continue;
                        }
                    }

                    $cnt = (int) ($row->cnt ?? 1);
                    $namaProdi = $prodiListMap[$kode] ?? $kode;
                    $prodiDisplay = $kode . ' - ' . $namaProdi;
                    $prodiTotals[$prodiDisplay] = ($prodiTotals[$prodiDisplay] ?? 0) + $cnt;

                    if (!in_array($kode, $nonStudents) && $cardnumber !== '') {
                        $bId = $row->borrowernumber;
                        if (!isset($studentStats[$bId])) {
                            $nama = trim(($info->firstname ?? '') . ' ' . ($info->surname ?? ''));
                            $studentStats[$bId] = [
                                'cardnumber' => strtoupper(trim($cardnumber)),
                                'nama'       => $nama !== '' ? $nama : 'Tanpa Nama',
                                'prodi'      => $kode,
                                'prodi_name' => $namaProdi,
                                'issue'      => 0,
                                'renew'      => 0,
                                'return'     => 0,
                                'total'      => 0,
                            ];
                        }
                        $studentStats[$bId][$row->type] += $cnt;
                        $studentStats[$bId]['total'] += $cnt;
                    }

                    $tgl = $row->periode;

                    if (!isset($aggregatedData[$tgl])) $aggregatedData[$tgl] = [];
                    if (!isset($aggregatedData[$tgl][$prodiDisplay])) {
                        $aggregatedData[$tgl][$prodiDisplay] = ['issue' => 0, 'renew' => 0, 'return' => 0, 'borrowers' => []];
                    }

                    $aggregatedData[$tgl][$prodiDisplay][$row->type] += $cnt;
                    if (in_array($row->type, ['issue', 'renew'])) {
                        $aggregatedData[$tgl][$prodiDisplay]['borrowers'][$row->borrowernumber] = true;
                        $allBorrowers[$row->borrowernumber] = true;
                    }
                }
            }

            $summary = $this->buildPeriodTableData($aggregatedData);
            $tableData = $summary['tableData'];
            $totalIssues = $summary['totalIssues'];
            $totalRenews = $summary['totalRenews'];
            $totalReturns = $summary['totalReturns'];
            $totalCirculation = $totalIssues + $totalRenews + $totalReturns;
            $totalBorrowers = count($allBorrowers);

            arsort($prodiTotals);
            $rankingProdi = [];
            foreach ($prodiTotals as $name => $total) {
                $rankingProdi[] = ['name' => $name, 'total' => $total];
            }

            usort($studentStats, fn($a, $b) => $b['total'] <=> $a['total']);
            $rankingMahasiswa = array_slice($studentStats, 0, 200);

            return [
                'totalIssues' => $totalIssues,
                'totalRenews' => $totalRenews,
                'totalReturns' => $totalReturns,
                'totalCirculation' => $totalCirculation,
                'totalBorrowers' => $totalBorrowers,
                'tableData' => $tableData->toArray(),
                'rankingProdi' => $rankingProdi,
                'rankingMahasiswa' => $rankingMahasiswa,
            ];
        });
    }

    private function buildPeriodTableData(array $aggregatedData): array
    {
        $totalIssues = 0;
        $totalRenews = 0;
        $totalReturns = 0;
        $tableData = [];

        foreach ($aggregatedData as $periode => $prodis) {
            $prodiDetails = [];
            $periodeIssues = 0;
            $periodeRenews = 0;
            $periodeReturns = 0;
            $periodeBorrowers = [];

            foreach ($prodis as $prodiName => $counts) {
                $issue = $counts['issue'] ?? 0;
                $renew = $counts['renew'] ?? 0;
                $return = $counts['return'] ?? 0;

                $prodiDetails[] = [
                    'prodi' => $prodiName,
                    'jumlah_issue' => $issue,
                    'jumlah_renew' => $renew,
                    'jumlah_buku_kembali' => $return,
                    'total_sirkulasi' => $issue + $renew + $return,
                    'jumlah_peminjam_unik' => count($counts['borrowers']),
                ];

                $periodeIssues += $issue;
                $periodeRenews += $renew;
                $periodeReturns += $return;
                foreach ($counts['borrowers'] as $b => $t) {
                    $periodeBorrowers[$b] = true;
                }

                $totalIssues += $issue;
                $totalRenews += $renew;
                $totalReturns += $return;
            }

            usort($prodiDetails, fn($a, $b) => $b['total_sirkulasi'] <=> $a['total_sirkulasi']);

            $tableData[] = [
                'periode' => $periode,
                'jumlah_issue' => $periodeIssues,
                'jumlah_renew' => $periodeRenews,
                'jumlah_buku_kembali' => $periodeReturns,
                'total_sirkulasi' => $periodeIssues + $periodeRenews + $periodeReturns,
                'jumlah_peminjam_unik' => count($periodeBorrowers),
                'prodi_details' => collect($prodiDetails),
            ];
        }

        usort($tableData, fn($a, $b) => $a['periode'] <=> $b['periode']);

        return [
            'tableData' => collect($tableData),
            'totalIssues' => $totalIssues,
            'totalRenews' => $totalRenews,
            'totalReturns' => $totalReturns,
        ];
    }

}
