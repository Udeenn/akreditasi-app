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
        $cacheKey = 'peminjaman_fakultas_v3_' . md5(json_encode([
            'filterType' => $filterType,
            'start' => ($filterType == 'yearly') ? "$startYear-01-01" : $startDate,
            'end' => ($filterType == 'yearly') ? "$endYear-12-31" : $endDate,
            'selectedFakultas' => $selectedFakultas,
        ]));

        return Cache::remember($cacheKey, 3600, function () use ($filterType, $startDate, $endDate, $startYear, $endYear, $selectedFakultas) {
            
            if ($filterType == 'daily') {
                $start = Carbon::parse($startDate)->startOfDay();
                $end = Carbon::parse($endDate)->endOfDay();
                $sqlDateFormat = '%Y-%m-%d';
            } else {
                $start = Carbon::createFromDate($startYear, 1, 1)->startOfDay();
                $end = Carbon::createFromDate($endYear, 12, 31)->endOfDay();
                $sqlDateFormat = '%Y-%m';
            }

            $rawData = $this->statisticsRepository->getBorrowingStatisticsByDateRange($start, $end, $sqlDateFormat);

            $borrowerNumbers = $rawData->pluck('borrowernumber')->unique()->values();
            
            // Chunked fetch for borrower category and attributes by borrowernumber
            // Using a new method in BorrowerRepository for fetching by borrowernumber instead of cardnumber
            $borrowerInfo = app(\App\Repositories\BorrowerRepository::class)->getBorrowerInfoByBorrowerNumbers($borrowerNumbers);

            $processedData = $rawData->map(function ($row) use ($borrowerInfo) {
                $info = $borrowerInfo[$row->borrowernumber] ?? null;
                
                $catCode = $info->categorycode ?? '';
                $cardnumber = $info->cardnumber ?? '';
                $prodiCode = $info->prodi_code ?? '';
                
                $kode = $this->prodiService->identifyProdiCode($cardnumber, $catCode, $prodiCode);
                $fakultas = \App\Helpers\FacultyHelper::mapCodeToFaculty($kode);

                return [
                    'periode' => $row->periode,
                    'type' => $row->type,
                    'fakultas' => $fakultas,
                    'prodi_name' => $kode,
                    'borrowernumber' => $row->borrowernumber,
                ];
            });

            if ($selectedFakultas && $selectedFakultas !== 'semua') {
                $processedData = $processedData->filter(fn($item) => $item['fakultas'] === $selectedFakultas);
            }

            $totalIssues = $processedData->where('type', 'issue')->count();
            $totalRenews = $processedData->where('type', 'renew')->count();
            $totalReturns = $processedData->where('type', 'return')->count();
            $totalCirculation = $totalIssues + $totalRenews + $totalReturns;
            $totalBorrowers = $processedData->whereIn('type', ['issue', 'renew'])->pluck('borrowernumber')->unique()->count();

            $tableGrouped = $processedData->groupBy('periode');
            $tableData = $tableGrouped->map(function ($group, $periode) {
                $prodiGrouped = $group->groupBy('prodi_name');
                $prodiDetails = $prodiGrouped->map(function ($pGroup, $pName) {
                    return [
                        'prodi' => $pName,
                        'jumlah_issue' => $pGroup->where('type', 'issue')->count(),
                        'jumlah_renew' => $pGroup->where('type', 'renew')->count(),
                        'jumlah_buku_kembali' => $pGroup->where('type', 'return')->count(),
                        'total_sirkulasi' => $pGroup->count(),
                        'jumlah_peminjam_unik' => $pGroup->whereIn('type', ['issue', 'renew'])->pluck('borrowernumber')->unique()->count(),
                    ];
                })->values()->sortByDesc('total_sirkulasi')->values();

                return [
                    'periode' => $periode,
                    'jumlah_issue' => $group->where('type', 'issue')->count(),
                    'jumlah_renew' => $group->where('type', 'renew')->count(),
                    'jumlah_buku_kembali' => $group->where('type', 'return')->count(),
                    'total_sirkulasi' => $group->count(),
                    'jumlah_peminjam_unik' => $group->whereIn('type', ['issue', 'renew'])->pluck('borrowernumber')->unique()->count(),
                    'prodi_details' => $prodiDetails,
                ];
            })->sortBy('periode')->values();

            return [
                'totalIssues' => $totalIssues,
                'totalRenews' => $totalRenews,
                'totalReturns' => $totalReturns,
                'totalCirculation' => $totalCirculation,
                'totalBorrowers' => $totalBorrowers,
                'tableData' => $tableData->toArray(),
            ];
        });
    }

    /**
     * Logic for peminjamanProdiChart / Table
     */
    public function getPeminjamanProdi(string $filterType, string $startDate, string $endDate, int $startYear, int $endYear, ?string $selectedProdi): array
    {
        $cacheKey = 'peminjaman_prodi_v3_' . md5(json_encode([
            'filterType' => $filterType,
            'start' => ($filterType == 'yearly') ? "$startYear-01-01" : $startDate,
            'end' => ($filterType == 'yearly') ? "$endYear-12-31" : $endDate,
            'selectedProdi' => $selectedProdi,
        ]));

        return Cache::remember($cacheKey, 3600, function () use ($filterType, $startDate, $endDate, $startYear, $endYear, $selectedProdi) {
            
            if ($filterType == 'daily') {
                $start = Carbon::parse($startDate)->startOfDay();
                $end = Carbon::parse($endDate)->endOfDay();
                $sqlDateFormat = '%Y-%m-%d';
            } else {
                $start = Carbon::createFromDate($startYear, 1, 1)->startOfDay();
                $end = Carbon::createFromDate($endYear, 12, 31)->endOfDay();
                $sqlDateFormat = '%Y-%m';
            }

            // Gunakan base query dari StatisticsRepository
            $rawQuery = $this->statisticsRepository->getRawBorrowingStatisticsQuery($start, $end);
            $rawData = collect($rawQuery->get()); // Eksekusi

            $borrowerNumbers = $rawData->pluck('borrowernumber')->unique()->values();
            
            // Ambil info prodi dan kategori
            $borrowerInfo = app(\App\Repositories\BorrowerRepository::class)->getBorrowerInfoByBorrowerNumbers($borrowerNumbers);

            // Filter Raw Data Based on Prodi and Mapping
            $processedData = collect();

            foreach ($rawData as $row) {
                $info = $borrowerInfo[$row->borrowernumber] ?? null;
                $catCode = $info->categorycode ?? '';
                $cardnumber = $info->cardnumber ?? '';
                $prodiCode = $info->prodi_code ?? '';
                
                $kode = $this->prodiService->identifyProdiCode($cardnumber, $catCode, $prodiCode);
                
                // Jika user memilih prodi tertentu, lewati yang tidak sesuai
                if ($selectedProdi && $selectedProdi !== 'semua' && $kode !== $selectedProdi) {
                    continue;
                }

                $tgl = Carbon::parse($row->datetime)->format($sqlDateFormat === '%Y-%m' ? 'Y-m-01' : 'Y-m-d');

                $processedData->push([
                    'periode' => $tgl,
                    'type' => $row->type,
                    'prodi_name' => $kode,
                    'borrowernumber' => $row->borrowernumber,
                ]);
            }

            $totalIssues = $processedData->where('type', 'issue')->count();
            $totalRenews = $processedData->where('type', 'renew')->count();
            $totalReturns = $processedData->where('type', 'return')->count();
            $totalCirculation = $totalIssues + $totalRenews + $totalReturns;
            $totalBorrowers = $processedData->whereIn('type', ['issue', 'renew'])->pluck('borrowernumber')->unique()->count();

            $tableGrouped = $processedData->groupBy('periode');
            $tableData = $tableGrouped->map(function ($group, $periode) {
                $prodiGrouped = $group->groupBy('prodi_name');
                $prodiDetails = $prodiGrouped->map(function ($pGroup, $pName) {
                    return [
                        'prodi' => $pName,
                        'jumlah_issue' => $pGroup->where('type', 'issue')->count(),
                        'jumlah_renew' => $pGroup->where('type', 'renew')->count(),
                        'jumlah_buku_kembali' => $pGroup->where('type', 'return')->count(),
                        'total_sirkulasi' => $pGroup->count(),
                        'jumlah_peminjam_unik' => $pGroup->whereIn('type', ['issue', 'renew'])->pluck('borrowernumber')->unique()->count(),
                    ];
                })->values()->sortByDesc('total_sirkulasi')->values();

                return [
                    'periode' => $periode,
                    'jumlah_issue' => $group->where('type', 'issue')->count(),
                    'jumlah_renew' => $group->where('type', 'renew')->count(),
                    'jumlah_buku_kembali' => $group->where('type', 'return')->count(),
                    'total_sirkulasi' => $group->count(),
                    'jumlah_peminjam_unik' => $group->whereIn('type', ['issue', 'renew'])->pluck('borrowernumber')->unique()->count(),
                    'prodi_details' => $prodiDetails,
                ];
            })->sortBy('periode')->values();

            return [
                'totalIssues' => $totalIssues,
                'totalRenews' => $totalRenews,
                'totalReturns' => $totalReturns,
                'totalCirculation' => $totalCirculation,
                'totalBorrowers' => $totalBorrowers,
                'tableData' => $tableData->toArray(),
            ];
        });
    }

}
