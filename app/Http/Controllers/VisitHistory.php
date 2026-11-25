<?php

namespace App\Http\Controllers;

use App\Models\M_vishistory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Dompdf\Dompdf;
use Dompdf\Options;
use App\Models\M_Auv;
use Illuminate\Pagination\Paginator;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Cache;
use App\Services\VisitStatisticsService;
use App\Services\DateFilterService;
use App\Services\ProdiService;

class VisitHistory extends Controller
{
    use \App\Traits\CsvExportable;

    public function __construct(
        private VisitStatisticsService $visitService,
        private DateFilterService $dateFilterService,
        private ProdiService $prodiService,
        private \App\Services\BorrowerService $borrowerService
    ) {}

    public function kunjunganFakultasTable(Request $request)
    {

        try {
            $allProdiListObj = M_Auv::getCachedProdiList();
            $prodiToFacultyMap = $this->prodiService->getProdiToFacultyMap($allProdiListObj);
            
            $listFakultas = collect($prodiToFacultyMap)
                ->unique()
                ->filter(fn($v) => !in_array($v, [
                    'Dosen', 'Tenaga Kependidikan',
                    'Alumni', 'Anggota Luar Biasa', 'Kartu Sekali Kunjung'
                ]))
                ->sort()->values()->all();

            $filters = $this->dateFilterService->parseFilter($request);
            $filterType = $filters['filterType'];
            $hasFilter  = $filters['hasFilter'];
            $selectedFakultas = $request->input('fakultas', 'semua');
            $selectedLokasi   = $request->input('lokasi');

            $tanggalAwal = $filters['tanggalAwal'];
            $tanggalAkhir = $filters['tanggalAkhir'];
            $tahunAwal = $filters['tahunAwal'];
            $tahunAkhir = $filters['tahunAkhir'];

            $result = $this->visitService->getKunjunganFakultas(
                $filterType, $tanggalAwal, $tanggalAkhir, $tahunAwal, $tahunAkhir, $selectedFakultas, $selectedLokasi
            );

            $tableData = collect($result['tableData'])->map(fn($item) => (object) $item);
            $chartData = $result['chartData'];
            $rankingFakultas = $result['rankingFakultas'] ?? [];
            $rankingProdi = $result['rankingProdi'] ?? [];
            $totalKeseluruhanKunjungan = $result['total'];

            $displayPeriod = $this->dateFilterService->getDisplayPeriod($filterType, $tanggalAwal, $tanggalAkhir, $tahunAwal, $tahunAkhir);

            $lokasiMapping = [
                'sni' => 'SNI Corner', 'bi' => 'Bank Indonesia Corner', 'mc' => 'Muhammadiyah Corner',
                'pusat' => 'Perpustakaan Pusat', 'pasca' => 'Perpustakaan Pascasarjana',
                'fk' => 'Perpustakaan Kedokteran', 'ref' => 'Referensi Perpustakaan Pusat',
            ];

            return view('pages.kunjungan.fakultasTable', compact(
                'listFakultas', 'tanggalAwal', 'tanggalAkhir', 'tahunAwal', 'tahunAkhir',
                'chartData', 'rankingFakultas', 'rankingProdi', 'totalKeseluruhanKunjungan', 'hasFilter', 'filterType',
                'displayPeriod', 'lokasiMapping', 'tableData'
            ));

        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Kunjungan Fakultas Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(), 'filters' => $request->all(),
            ]);
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    // =====================================================================
    // exportCsvFakultas — Lightweight export using same service data
    // =====================================================================
    public function exportCsvFakultas(Request $request)
    {
        $filters = $this->dateFilterService->parseFilter($request);
        $filterType = $filters['filterType'];
        $selectedFakultas = $request->input('fakultas', 'semua');
        $selectedLokasi = $request->input('lokasi');
        $tanggalAwal = $filters['tanggalAwal'];
        $tanggalAkhir = $filters['tanggalAkhir'];
        $tahunAwal = $filters['tahunAwal'];
        $tahunAkhir = $filters['tahunAkhir'];

        $result = $this->visitService->getKunjunganFakultas(
            $filterType, $tanggalAwal, $tanggalAkhir, $tahunAwal, $tahunAkhir, $selectedFakultas, $selectedLokasi
        );

        $tableData = $result['tableData'];
        $totalKunjungan = $result['total'];

        // Build CSV
        $judulLaporan = "LAPORAN KUNJUNGAN PER FAKULTAS";
        $subJudul = "";
        $filePeriod = "";

        try {
            if ($filterType === 'yearly') {
                $subJudul = "PERIODE TAHUNAN: $tahunAwal s.d. $tahunAkhir";
                $filePeriod = "tahun_{$tahunAwal}_{$tahunAkhir}";
            } else {
                $tglA = Carbon::parse($tanggalAwal)->locale('id')->isoFormat('D MMMM Y');
                $tglAkh = Carbon::parse($tanggalAkhir)->locale('id')->isoFormat('D MMMM Y');
                $subJudul = "PERIODE HARIAN: $tglA s.d. $tglAkh";
                $filePeriod = $tanggalAwal . "_sd_" . $tanggalAkhir;
            }
        } catch (\Throwable $e) { $filePeriod = date('Ymd'); }

        $namaFileFakultas = "";
        if ($selectedFakultas && $selectedFakultas !== 'semua') {
            $subJudul .= " (FAKULTAS: " . strtoupper($selectedFakultas) . ")";
            $namaFileFakultas = "_" . preg_replace('/[^A-Za-z0-9]/', '_', $selectedFakultas);
        }

        $fileName = 'laporan_kunjungan_fakultas' . $namaFileFakultas . '_' . $filePeriod . '.csv';

        $csvHeaders = ['No', 'Periode', 'Kode Prodi', 'Nama Prodi', 'Jumlah Kunjungan'];
        $titles = [$judulLaporan ?? 'Laporan Kunjungan Fakultas', $subJudul];
        $footerRow = ['', '', '', 'TOTAL KUNJUNGAN', $totalKunjungan];

        return $this->streamCsvExport($tableData, $fileName, $csvHeaders, function($row, &$index) use ($filterType) {
            $rowArray = (array) $row;
            $periodeDisplay = $rowArray['periode'] ?? '-';
            try {
                if ($filterType === 'yearly') {
                    $periodeDisplay = Carbon::createFromFormat('Y-m', $periodeDisplay)->locale('id')->isoFormat('MMMM Y');
                } else {
                    $periodeDisplay = Carbon::parse($periodeDisplay)->locale('id')->isoFormat('dddd, D MMMM Y');
                }
            } catch (\Throwable $e) {}

            $results = [];
            if (!empty($rowArray['prodi_details'])) {
                foreach ($rowArray['prodi_details'] as $detail) {
                    $detArray = (array) $detail;
                    $kodeProdi = $detArray['kode'] ?? '';
                    $namaProdiStr = $detArray['prodi'] ?? '';
                    $namaOnly = str_replace($kodeProdi . ' - ', '', $namaProdiStr);
                    $jml = $detArray['jumlah'] ?? 0;
                    
                    $results[] = [
                        $index++, 
                        $periodeDisplay, 
                        $kodeProdi,
                        $namaOnly,
                        $jml
                    ];
                }
            }
            return $results;
        }, $titles, $footerRow);
    }

    public function kunjunganTanggalTable(Request $request)
    {
        $hasFilter = $request->has('filter_type') || $request->has('tanggal_awal') || $request->has('tanggal_akhir') || $request->has('tahun_awal') || $request->has('tahun_akhir');
        $filterType = $request->input('filter_type', 'daily');
        $tanggalAwal = null;
        $tanggalAkhir = null;
        $tahunAwal = null;
        $tahunAkhir = null;
        $perPage = $request->input('per_page', 10);
        $data = collect();
        $chartData = collect();
        $totalKeseluruhanKunjungan = 0;

        if (!in_array($perPage, [10, 100, 1000])) {
            $perPage = 10;
        }

        if ($hasFilter) {
            $baseQuery = M_vishistory::query();

            if ($filterType === 'yearly') {
                $tahunAwal = (int) $request->input('tahun_awal', Carbon::now()->year);
                $tahunAkhir = (int) $request->input('tahun_akhir', Carbon::now()->year);

                if ($tahunAwal > $tahunAkhir) {
                    return redirect()->back()->withInput($request->all())->with('error', 'Tahun awal tidak boleh lebih besar dari tahun akhir.');
                }

                $tanggalAwal = Carbon::createFromDate($tahunAwal, 1, 1)->format('Y-m-d');
                $tanggalAkhir = Carbon::createFromDate($tahunAkhir, 12, 31)->format('Y-m-d');

                $baseQuery->select(
                    DB::raw('DATE_FORMAT(visittime, "%Y-%m-01") as tanggal_kunjungan'),
                    DB::raw('COUNT(id) as jumlah_kunjungan_harian')
                )
                    ->groupBy(DB::raw('DATE_FORMAT(visittime, "%Y-%m-01")'))
                    ->orderBy(DB::raw('DATE_FORMAT(visittime, "%Y-%m-01")'), 'asc');
            } else { // filterType === 'daily'
                $tanggalAwal = $request->input('tanggal_awal', Carbon::now()->startOfMonth()->format('Y-m-d'));
                $tanggalAkhir = $request->input('tanggal_akhir', Carbon::now()->endOfMonth()->format('Y-m-d'));
                if (Carbon::parse($tanggalAwal)->greaterThan(Carbon::parse($tanggalAkhir))) {
                    return redirect()->back()->withInput($request->all())->with('error', 'Tanggal Awal tidak boleh lebih besar dari Tanggal Akhir.');
                }
                $baseQuery->selectRaw('
                    DATE(visittime) as tanggal_kunjungan,
                    COUNT(id) as jumlah_kunjungan_harian
                ')
                    ->groupBy(DB::raw('DATE(visittime)'))
                    ->orderBy(DB::raw('DATE(visittime)'), 'asc');
            }

            $baseQuery->whereBetween('visittime', [$tanggalAwal . ' 00:00:00', $tanggalAkhir . ' 23:59:59']);

            $cacheKey = 'kunj_tgl_v2_' . md5($filterType . $tanggalAwal . $tanggalAkhir);
            $chartData = Cache::remember($cacheKey, 3600, function() use ($baseQuery) {
                return $baseQuery->get();
            });

            $totalKeseluruhanKunjungan = $chartData->sum('jumlah_kunjungan_harian');

            // Paginate dari memory collection untuk hindari 2 query tambahan
            $currentPage = Paginator::resolveCurrentPage('page');
            $currentItems = $chartData->slice(($currentPage - 1) * $perPage, $perPage)->values()->all();
            $data = new LengthAwarePaginator($currentItems, $chartData->count(), $perPage, $currentPage, [
                'path' => Paginator::resolveCurrentPath()
            ]);
            $data->appends($request->all());
        }

        return view('pages.kunjungan.tanggalTable', compact('data', 'totalKeseluruhanKunjungan', 'filterType', 'tanggalAwal', 'tanggalAkhir', 'tahunAwal', 'tahunAkhir', 'chartData', 'perPage', 'hasFilter'));
    }


    public function kunjunganProdiTable(Request $request)
    {

        $filterType      = $request->input('filter_type', 'daily');
        $kodeProdiFilter = $request->input('prodi');
        $perPage         = $request->input('per_page', 12);
        $hasFilter       = $request->hasAny(['filter_type', 'prodi', 'tanggal_awal', 'tahun_awal']);

        $tanggalAwal  = $request->input('tanggal_awal', \Carbon\Carbon::now()->startOfMonth()->toDateString());
        $tanggalAkhir = $request->input('tanggal_akhir', \Carbon\Carbon::now()->toDateString());
        $tahunAwal    = $request->input('tahun_awal', \Carbon\Carbon::now()->year);
        $tahunAkhir   = $request->input('tahun_akhir', \Carbon\Carbon::now()->year);

        $result = $this->visitService->getKunjunganProdi(
            $filterType, $tanggalAwal, $tanggalAkhir, $tahunAwal, $tahunAkhir, $kodeProdiFilter
        );

        $tableDataArray = $result['tableData'];
        $totalKeseluruhanKunjungan = $result['totalKeseluruhanKunjungan'];

        // Formatting for View
        $displayPeriod = $this->dateFilterService->getDisplayPeriod($filterType, $tanggalAwal, $tanggalAkhir, $tahunAwal, $tahunAkhir);

        // Chart Data (extracted from tableData)
        $chartData = collect($tableDataArray)->map(function($item) use ($filterType) {
            $label = $item['tanggal'];
            if ($filterType === 'yearly') {
                try {
                    $label = \Carbon\Carbon::parse($item['tanggal'])->locale('id')->isoFormat('MMMM Y');
                } catch (\Exception $e) {}
            }
            return (object) [
                'raw_date' => $item['tanggal'],
                'label' => $label,
                'total_kunjungan' => $item['total_kunjungan']
            ];
        });

        $jumlahPeriode = $chartData->count();
        $rerataKunjungan = ($jumlahPeriode > 0) ? ($totalKeseluruhanKunjungan / $jumlahPeriode) : 0;

        // Pagination
        $flattenedTableData = collect();
        foreach ($tableDataArray as $row) {
            foreach ($row['prodi_details'] as $detail) {
                $flattenedTableData->push((object)[
                    'tanggal_kunjungan' => $row['tanggal'],
                    'kode_identifikasi' => $detail['kode'],
                    'nama_prodi' => $detail['prodi'],
                    'kode_prodi' => $detail['kode'],
                    'jumlah_kunjungan_harian' => $detail['jumlah']
                ]);
            }
        }

        $currentPage = \Illuminate\Pagination\LengthAwarePaginator::resolveCurrentPage();
        $currentItems = $flattenedTableData->slice(($currentPage - 1) * $perPage, $perPage)->values();

        $paginatedData = new \Illuminate\Pagination\LengthAwarePaginator(
            $currentItems,
            $flattenedTableData->count(),
            $perPage,
            $currentPage,
            ['path' => \Illuminate\Pagination\LengthAwarePaginator::resolveCurrentPath(), 'query' => $request->query()]
        );

        $listProdi = $this->prodiService->getFullProdiList();

        return view('pages.kunjungan.prodiTable', [
            'data'           => $paginatedData,
            'listProdi'      => $listProdi,
            'tanggalAwal'    => $tanggalAwal,
            'tanggalAkhir'   => $tanggalAkhir,
            'filterType'     => $filterType,
            'tahunAwal'      => $tahunAwal,
            'tahunAkhir'     => $tahunAkhir,
            'perPage'        => $perPage,
            'displayPeriod'  => $displayPeriod,
            'chartData'      => $chartData,
            'totalKeseluruhanKunjungan' => $totalKeseluruhanKunjungan,
            'rerataKunjungan' => $rerataKunjungan,
            'rankingProdi'   => $result['rankingProdi'] ?? [],
            'rankingMahasiswa' => $result['rankingMahasiswa'] ?? [],
            'topProdi'       => [],
            'hasFilter'      => $hasFilter
        ]);
    }

    public function getDetailPengunjung(Request $request)
    {
        // 1. Parameter Validation
        $tanggal = $request->query('tanggal');
        $bulanTahun = $request->query('bulan');
        $targetKode = strtoupper(trim($request->query('kode_identifikasi')));
        $isExport = $request->query('export');

        if ((!$tanggal && !$bulanTahun) || !$targetKode) {
            return response()->json(['error' => 'Parameter tidak lengkap.', 'data' => []], 400);
        }

        // 2. Date Range Setup
        if ($bulanTahun) {
            $start = \Carbon\Carbon::createFromFormat('Y-m', $bulanTahun)->startOfMonth()->toDateTimeString();
            $end   = \Carbon\Carbon::createFromFormat('Y-m', $bulanTahun)->endOfMonth()->toDateTimeString();
        } else {
            $start = \Carbon\Carbon::parse($tanggal)->startOfDay()->toDateTimeString();
            $end   = \Carbon\Carbon::parse($tanggal)->endOfDay()->toDateTimeString();
        }

        $cacheKey = 'detail_pengunjung_' . md5($start . $end . $targetKode);

        // 3. Main Data Fetching (Use identical logic to kunjunganProdiTable for consistency)
        $paginatedData = Cache::remember($cacheKey, 3600, function() use ($start, $end, $targetKode) {
            
            // A. Raw Visits
            $qHistory = DB::connection('mysql')->table('visitorhistory')
                ->select('cardnumber', 'visittime')
                ->whereBetween('visittime', [$start, $end]);

            $qCorner = DB::connection('mysql')->table('visitorcorner')
                ->select('cardnumber', 'visittime')
                ->whereBetween('visittime', [$start, $end]);

            $rawVisits = $qHistory->unionAll($qCorner)->get();

            if ($rawVisits->isEmpty()) {
                return collect([]);
            }

            // B. Borrowers Lookup (Robust Case Handling)
            $cardNumbers = $rawVisits->pluck('cardnumber')
                ->map(fn($id) => strtoupper(trim($id)))
                ->unique()
                ->values()
                ->all();

            $borrowers = collect();
            if (!empty($cardNumbers)) {
                foreach (array_chunk($cardNumbers, 1000) as $chunk) {
                    $chunkBorrowers = DB::connection('mysql2')->table('borrowers')
                        ->select('cardnumber', 'surname', 'firstname', 'categorycode')
                        ->whereIn('cardnumber', $chunk)
                        ->get()
                        ->mapWithKeys(function ($item) {
                            return [strtoupper(trim($item->cardnumber)) => $item];
                        });
                    $borrowers = $borrowers->merge($chunkBorrowers);
                }
            }

            // C. Identification & Filtering
            $filteredVisits = $rawVisits->map(function ($visit) use ($borrowers) {
                $cardnumber = strtoupper(trim($visit->cardnumber));
                $borrower = $borrowers[$cardnumber] ?? null;
                $catCode  = $borrower ? strtoupper($borrower->categorycode) : null;

                $nama = 'Tanpa Nama';
                if ($borrower) {
                    $nama = trim(($borrower->firstname ?? '') . ' ' . ($borrower->surname ?? ''));
                    if (empty($nama)) $nama = 'Tanpa Nama';
                }

                $kodeIdentifikasi = $this->prodiService->identifyProdiCode($cardnumber, $catCode ?? '');

                return [
                    'cardnumber'  => strtoupper(trim($visit->cardnumber)),
                    'nama'        => $nama,
                    'kode_ident'  => strtoupper($kodeIdentifikasi)
                ];
            })->filter(function ($item) use ($targetKode) {
                return $item['kode_ident'] === $targetKode;
            });

            // D. Grouping & Counting
            return $filteredVisits->groupBy('cardnumber')->map(function ($group) {
                $first = $group->first();
                return [
                    'cardnumber'  => $first['cardnumber'],
                    'nama'        => $first['nama'],
                    'visit_count' => $group->count()
                ];
            })->sortByDesc('visit_count')->values();
        });

        if ($isExport === 'csv') {
            // Get Prodi Name
            $prodiName = $targetKode;
            // Use M_Auv table (authorised_values in mysql2)
            $dbProdi = DB::connection('mysql2')->table('authorised_values')
                ->where('category', 'PRODI')
                ->where('authorised_value', $targetKode)
                ->value('lib');
            
            if ($dbProdi) {
                $prodiName = $dbProdi;
            } else {
                $manuals = [
                    'DOSEN'   => 'Dosen & Pengajar',
                    'TENDIK'  => 'Tenaga Kependidikan',
                    'KSPMBKM' => 'MBKM',
                    'KSPBIPA' => 'BIPA',
                ];
                if (isset($manuals[$targetKode])) {
                    $prodiName = $manuals[$targetKode];
                }
            }
            $cleanProdiName = preg_replace('/[^A-Za-z0-9]/', '_', $prodiName);
            $tipe = $bulanTahun ? 'Bulanan' : 'Harian';
            $filename = "Laporan_Detail_Kunjungan_{$cleanProdiName}_{$tipe}_" . ($bulanTahun ?? $tanggal) . ".csv";

            $periode = $bulanTahun ? \Carbon\Carbon::createFromFormat('Y-m', $bulanTahun)->isoFormat('MMMM Y') : \Carbon\Carbon::parse($tanggal)->isoFormat('DD MMMM Y');
            
            $titles = [
                "Laporan Kunjungan: $prodiName",
                "Periode: $periode"
            ];
            
            $csvHeaders = ['No', 'ID Anggota', 'Nama Pengunjung', 'Jumlah Kunjungan'];

            return $this->streamCsvExport($paginatedData, $filename, $csvHeaders, function($row, &$index) {
                return [
                    $index++,
                    $row['cardnumber'],
                    $row['nama'],
                    $row['visit_count']
                ];
            }, $titles);
        }

        // 4. Pagination
        $perPage = $request->input('per_page', 10);
        $currentPage = \Illuminate\Pagination\LengthAwarePaginator::resolveCurrentPage();
        $currentItems = $paginatedData->slice(($currentPage - 1) * $perPage, $perPage)->values();

        $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
            $currentItems,
            $paginatedData->count(),
            $perPage,
            $currentPage,
            ['path' => \Illuminate\Pagination\LengthAwarePaginator::resolveCurrentPath(), 'query' => $request->query()]
        );

        return response()->json($paginator);
    }


    public function getProdiExportData(Request $request)
    {
        $filterType      = $request->input('filter_type', 'daily');
        $kodeProdiFilter = $request->input('prodi');
        $tanggalAwal     = $request->input('tanggal_awal', Carbon::now()->startOfMonth()->toDateString());
        $tanggalAkhir    = $request->input('tanggal_akhir', Carbon::now()->toDateString());
        $tahunAwal       = (int) $request->input('tahun_awal', Carbon::now()->year);
        $tahunAkhir      = (int) $request->input('tahun_akhir', Carbon::now()->year);

        if ($tahunAwal > $tahunAkhir) $tahunAwal = $tahunAkhir;

        // Gunakan service yang sama persis dengan tampilan web agar total 100% konsisten
        $result = $this->visitService->getKunjunganProdi(
            $filterType, $tanggalAwal, $tanggalAkhir, $tahunAwal, $tahunAkhir, $kodeProdiFilter
        );

        $tableDataArray = $result['tableData'];
        $totalKeseluruhanKunjungan = $result['totalKeseluruhanKunjungan'];

        $flattenedData = collect();
        foreach ($tableDataArray as $row) {
            foreach ($row['prodi_details'] as $detail) {
                $flattenedData->push((object)[
                    'tanggal' => $row['tanggal'],
                    'kode'    => $detail['kode'],
                    'nama'    => $detail['prodi'],
                    'jumlah'  => $detail['jumlah'],
                ]);
            }
        }

        $fullProdiList = $this->prodiService->getFullProdiList();
        $namaProdiFilter = $fullProdiList[strtoupper($kodeProdiFilter ?? '')] ?? ($kodeProdiFilter ?: 'Seluruh Kategori');
        $safeProdiName = preg_replace('/[^A-Za-z0-9]/', '_', $namaProdiFilter);

        if ($filterType === 'yearly') {
            $periodeDisplay = "Tahun {$tahunAwal}" . ($tahunAwal != $tahunAkhir ? " s/d {$tahunAkhir}" : "");
            $periodeSuffix = "Tahunan_{$tahunAwal}_sd_{$tahunAkhir}";
        } else {
            $start = Carbon::parse($tanggalAwal);
            $end   = Carbon::parse($tanggalAkhir);
            $periodeDisplay = "Periode " . $start->locale('id')->isoFormat('D MMMM Y') . " s.d. " . $end->locale('id')->isoFormat('D MMMM Y');
            $periodeSuffix = "Harian_{$tanggalAwal}_sd_{$tanggalAkhir}";
        }

        $fileName = "Laporan_Kunjungan_Prodi_{$safeProdiName}_{$periodeSuffix}.csv";
        $titles = [
            "Laporan Statistik Kunjungan: " . $namaProdiFilter,
            "Periode: " . $periodeDisplay
        ];
        $csvHeaders = ['Tanggal / Bulan', 'Kode Identifikasi', 'Nama Prodi/Kategori', 'Jumlah Kunjungan'];
        $footerRow  = ['', '', 'TOTAL', $totalKeseluruhanKunjungan];

        return $this->streamCsvExport($flattenedData, $fileName, $csvHeaders, function($row, &$index) use ($filterType) {
            $tanggal = ($filterType === 'yearly') ?
                Carbon::parse($row->tanggal)->locale('id')->isoFormat('MMMM Y') :
                Carbon::parse($row->tanggal)->locale('id')->isoFormat('dddd, D MMMM Y');

            return [
                $tanggal,
                $row->kode,
                $row->nama,
                $row->jumlah
            ];
        }, $titles, $footerRow);
    }

    public function getDetailPengunjungHarian(Request $request)
    {
        $tanggalKunjungan = $request->input('tanggal');
        $bulanTahun = $request->input('bulan');
        $kodeIdentifikasi = $request->input('kode_identifikasi');
        $page = $request->input('page', 1);

        if ($bulanTahun) {
            $dateCarbon = \Carbon\Carbon::createFromFormat('Y-m', $bulanTahun);
            $startDate = $dateCarbon->startOfMonth()->toDateTimeString();
            $endDate = $dateCarbon->endOfMonth()->toDateTimeString();
            $displayTanggal = $dateCarbon->translatedFormat('F Y');
        } elseif ($tanggalKunjungan) {
            $dateCarbon = \Carbon\Carbon::parse($tanggalKunjungan);
            $startDate = $dateCarbon->startOfDay()->toDateTimeString();
            $endDate = $dateCarbon->endOfDay()->toDateTimeString();
            $displayTanggal = $dateCarbon->translatedFormat('d F Y');
        } else {
            return response()->json(['error' => 'Parameter tanggal/bulan tidak ditemukan.'], 400);
        }

        ['totalVisitors' => $totalVisitors, 'visitors' => $allVisitors] = $this->buildDailyVisitorBreakdown($startDate, $endDate);

        $perPage = 5;
        $currentPage = max(1, (int) $page);
        $pagedItems = $allVisitors->slice(($currentPage - 1) * $perPage, $perPage)->values();
        $visitors = new LengthAwarePaginator(
            $pagedItems,
            $allVisitors->count(),
            $perPage,
            $currentPage,
            ['path' => Paginator::resolveCurrentPath()]
        );

        return response()->json([
            'data' => $visitors->items(),
            'total' => number_format($totalVisitors, 0, ',', '.'),
            'modal_display_date' => $displayTanggal,
            'current_page' => $visitors->currentPage(),
            'last_page' => $visitors->lastPage(),
            'from' => $visitors->firstItem(),
            'per_page' => $visitors->perPage(),
        ]);
    }

    public function getDetailPengunjungHarianExport(Request $request)
    {
        $tanggal = $request->input('tanggal');
        $filterType = $request->input('filter_type', 'daily');

        if (!$tanggal) {
            return response()->json(['error' => 'Tanggal tidak ditemukan.'], 400);
        }

        $dateCarbon = \Carbon\Carbon::parse($tanggal);

        if ($filterType === 'yearly') {
            $startDate = $dateCarbon->startOfMonth()->toDateTimeString();
            $endDate = $dateCarbon->endOfMonth()->toDateTimeString();
        } else { // 'daily'
            $startDate = $dateCarbon->startOfDay()->toDateTimeString();
            $endDate = $dateCarbon->endOfDay()->toDateTimeString();
        }

        ['visitors' => $visitors] = $this->buildDailyVisitorBreakdown($startDate, $endDate);

        return response()->json(['data' => $visitors->values()]);
    }

    private function buildDailyVisitorBreakdown(string $startDate, string $endDate): array
    {
        $qHistory = DB::connection('mysql')->table('visitorhistory')
            ->selectRaw('UPPER(TRIM(cardnumber)) as cardnumber, COUNT(*) as visit_count')
            ->whereBetween('visittime', [$startDate, $endDate])
            ->groupByRaw('UPPER(TRIM(cardnumber))');

        $qCorner = DB::connection('mysql')->table('visitorcorner')
            ->selectRaw('UPPER(TRIM(cardnumber)) as cardnumber, COUNT(*) as visit_count')
            ->whereBetween('visittime', [$startDate, $endDate])
            ->groupByRaw('UPPER(TRIM(cardnumber))');

        $aggregated = [];
        $totalVisitors = 0;

        foreach ($qHistory->unionAll($qCorner)->get() as $row) {
            $card = (string) ($row->cardnumber ?? '');
            $cnt = (int) ($row->visit_count ?? 0);
            $totalVisitors += $cnt;
            if ($card === '') {
                continue;
            }
            $aggregated[$card] = ($aggregated[$card] ?? 0) + $cnt;
        }

        $borrowerInfo = !empty($aggregated)
            ? app(\App\Repositories\BorrowerRepository::class)->getBorrowerInfoByCardnumbers(collect(array_keys($aggregated)))
            : [];

        $visitors = collect($aggregated)->map(function ($count, $card) use ($borrowerInfo) {
            $info = $borrowerInfo[$card] ?? null;
            $nama = $info ? trim(($info->firstname ?? '') . ' ' . ($info->surname ?? '')) : '';
            return [
                'cardnumber' => $card,
                'nama' => $nama !== '' ? $nama : 'Nama tidak ditemukan',
                'visit_count' => $count,
            ];
        })->sortBy('nama', SORT_NATURAL | SORT_FLAG_CASE)->values();

        return compact('totalVisitors', 'visitors');
    }

    public function getKunjunganHarianExportData(Request $request)
    {
        $filterType = $request->input('filter_type', 'daily');

        if ($filterType === 'yearly') {
            $tahunAwal = (int) $request->input('tahun_awal', Carbon::now()->year);
            $tahunAkhir = (int) $request->input('tahun_akhir', Carbon::now()->year);

            if ($tahunAwal > $tahunAkhir) {
                return response()->json(['error' => 'Tahun awal tidak boleh lebih besar dari tahun akhir.'], 400);
            }

            $tanggalAwal = Carbon::createFromDate($tahunAwal, 1, 1)->format('Y-m-d');
            $tanggalAkhir = Carbon::createFromDate($tahunAkhir, 12, 31)->format('Y-m-d');
        } else {
            $tanggalAwal = $request->input('tanggal_awal', Carbon::now()->startOfMonth()->format('Y-m-d'));
            $tanggalAkhir = $request->input('tanggal_akhir', Carbon::now()->endOfMonth()->format('Y-m-d'));

            if (Carbon::parse($tanggalAwal)->greaterThan(Carbon::parse($tanggalAkhir))) {
                return response()->json(['error' => 'Tanggal Awal tidak boleh lebih besar dari Tanggal Akhir.'], 400);
            }
        }

        $cacheKey = 'kunj_tgl_v2_' . md5($filterType . $tanggalAwal . $tanggalAkhir);
        $exportData = Cache::remember($cacheKey, 3600, function () use ($filterType, $tanggalAwal, $tanggalAkhir) {
            $baseQuery = M_vishistory::query()
                ->whereBetween('visittime', [$tanggalAwal . ' 00:00:00', $tanggalAkhir . ' 23:59:59']);

            if ($filterType === 'yearly') {
                $baseQuery->select(
                    DB::raw('DATE_FORMAT(visittime, "%Y-%m-01") as tanggal_kunjungan'),
                    DB::raw('COUNT(id) as jumlah_kunjungan_harian')
                )
                    ->groupBy(DB::raw('DATE_FORMAT(visittime, "%Y-%m-01")'))
                    ->orderBy(DB::raw('DATE_FORMAT(visittime, "%Y-%m-01")'), 'asc');
            } else {
                $baseQuery->selectRaw('
                    DATE(visittime) as tanggal_kunjungan,
                    COUNT(id) as jumlah_kunjungan_harian
                ')
                    ->groupBy(DB::raw('DATE(visittime)'))
                    ->orderBy(DB::raw('DATE(visittime)'), 'asc');
            }

            return $baseQuery->get();
        });

        return response()->json(['data' => $exportData]);
    }

    /**
     * Helper untuk mengambil data kunjungan per cardnumber (single source of truth).
     */
    private function getKehadiranAnggota(string $cardnumber, ?int $tahun = null): array
    {
        $cleanCard = trim($cardnumber);
        if ($cleanCard === '') {
            return [null, collect()];
        }

        $fullBorrowerDetails = DB::connection('mysql2')->table('borrowers')
            ->select('borrowernumber', 'cardnumber', 'firstname', 'surname', 'email', 'phone', 'categorycode')
            ->where('cardnumber', $cleanCard)
            ->orWhere('cardnumber', strtoupper($cleanCard))
            ->orWhere('cardnumber', strtolower($cleanCard))
            ->first();

        if (!$fullBorrowerDetails) {
            return [null, collect()];
        }

        $validCardnumber = $fullBorrowerDetails->cardnumber;

        $queryHistory = DB::connection('mysql')->table('visitorhistory')
            ->select(DB::raw("EXTRACT(YEAR_MONTH FROM visittime) as tahun_bulan"), DB::raw("COUNT(*) as total"))
            ->where('cardnumber', $validCardnumber);

        $queryCorner = DB::connection('mysql')->table('visitorcorner')
            ->select(DB::raw("EXTRACT(YEAR_MONTH FROM visittime) as tahun_bulan"), DB::raw("COUNT(*) as total"))
            ->where('cardnumber', $validCardnumber);

        if ($tahun) {
            $queryHistory->whereBetween('visittime', ["{$tahun}-01-01 00:00:00", "{$tahun}-12-31 23:59:59"]);
            $queryCorner->whereBetween('visittime', ["{$tahun}-01-01 00:00:00", "{$tahun}-12-31 23:59:59"]);
        }

        $queryHistory->groupBy('tahun_bulan');
        $queryCorner->groupBy('tahun_bulan');

        $unionQuery = $queryHistory->unionAll($queryCorner);

        $semuaKunjungan = DB::connection('mysql')
            ->table(DB::raw("({$unionQuery->toSql()}) as combined"))
            ->mergeBindings($unionQuery)
            ->select('tahun_bulan', DB::raw('SUM(total) as jumlah_kunjungan'))
            ->groupBy('tahun_bulan')
            ->orderBy('tahun_bulan', 'ASC')
            ->get();

        return [$fullBorrowerDetails, collect($semuaKunjungan)];
    }

    public function cekKehadiran(Request $request)
    {
        $cardnumber = $request->input('cardnumber');
        $tahun = $request->input('tahun') ? (int) $request->input('tahun') : null;

        $fullBorrowerDetails = null;
        $pesan = 'Silakan masukkan Nomor Kartu Anggota (Cardnumber) untuk melihat laporan kunjungan.';
        $dataKunjungan = collect();
        $totalKunjunganSum = 0;

        if ($cardnumber) {
            [$fullBorrowerDetails, $allKunjungan] = $this->getKehadiranAnggota($cardnumber, $tahun);

            if ($fullBorrowerDetails) {
                $totalKunjunganSum = $allKunjungan->sum('jumlah_kunjungan');

                $perPage = 12;
                $currentPage = Paginator::resolveCurrentPage('page');
                $currentItems = $allKunjungan->slice(($currentPage - 1) * $perPage, $perPage)->all();
                $dataKunjungan = new LengthAwarePaginator($currentItems, $allKunjungan->count(), $perPage, $currentPage, [
                    'path' => Paginator::resolveCurrentPath()
                ]);
                $dataKunjungan->appends(request()->query());

                if ($allKunjungan->isEmpty()) {
                    $pesan = 'Tidak ada data kunjungan ditemukan untuk: ' . $fullBorrowerDetails->firstname;
                } else {
                    $pesan = null;
                }
            } else {
                $pesan = 'Data anggota tidak ditemukan.';
            }
        }

        return view('pages.kunjungan.cekKehadiran', compact('dataKunjungan', 'fullBorrowerDetails', 'pesan', 'cardnumber', 'tahun', 'totalKunjunganSum'));
    }

    public function getLokasiDetail(Request $request)
    {
        $cardnumber = $request->input('cardnumber');
        $tahunBulan = $request->input('tahun_bulan');
        $perPage = 10;

        if (!$cardnumber || !$tahunBulan) {
            return response()->json(['error' => 'Parameter tidak lengkap.'], 400);
        }

        try {
            $cardnumber = trim(strtolower($cardnumber));

            // 1. Pecah string "202412" menjadi Tahun dan Bulan agar ramah Index
            $tahun = substr($tahunBulan, 0, 4);
            $bulan = substr($tahunBulan, 4, 2);

            // 1. Hitung awal & akhir bulan agar B-Tree index visittime terpakai
            $startMonth = \Carbon\Carbon::createFromDate((int)$tahun, (int)$bulan, 1)->startOfMonth()->toDateTimeString();
            $endMonth   = \Carbon\Carbon::createFromDate((int)$tahun, (int)$bulan, 1)->endOfMonth()->toDateTimeString();

            $lokasiMapping = [
                'sni' => 'SNI Corner',
                'bi' => 'Bank Indonesia Corner',
                'mc' => 'Muhammadiyah Corner',
                'pusat' => 'Perpustakaan Pusat',
                'pasca' => 'Perpustakaan Pascasarjana',
                'fk' => 'Perpustakaan Kedokteran',
                'ref' => 'Referensi Perpustakaan Pusat',
            ];

            // 2. Query History (Gunakan whereBetween sargable)
            $queryHistory = DB::connection('mysql')->table('visitorhistory')
                ->select('visittime as visit_date', DB::raw("IFNULL(location, 'pusat') as visit_location"))
                ->where('cardnumber', $cardnumber)
                ->whereBetween('visittime', [$startMonth, $endMonth]);

            // 3. Query Corner (Gunakan whereBetween sargable, guard kolom notes)
            $cornerLocExpr = \App\Repositories\VisitRepository::hasCornerNotesColumn()
                ? DB::raw("COALESCE(NULLIF(notes, ''), 'pusat') as visit_location")
                : DB::raw("'pusat' as visit_location");

            $queryCorner = DB::connection('mysql')->table('visitorcorner')
                ->select('visittime as visit_date', $cornerLocExpr)
                ->where('cardnumber', $cardnumber)
                ->whereBetween('visittime', [$startMonth, $endMonth]);

            $unionQuery = $queryHistory->unionAll($queryCorner);

            $dataLokasi = DB::connection('mysql')
                ->table(DB::raw("({$unionQuery->toSql()}) as combined"))
                ->mergeBindings($unionQuery)
                ->orderBy('visit_date', 'ASC')
                ->paginate($perPage);

            $dataLokasi->getCollection()->transform(function ($item) use ($lokasiMapping) {
                $rawLoc = strtolower(trim($item->visit_location));
                $item->visit_location = $lokasiMapping[$rawLoc] ?? $item->visit_location;
                return $item;
            });

            try {
                $bulanTahunFormatted = \Carbon\Carbon::createFromFormat('Ym', (string)$tahunBulan)->format('F Y');
            } catch (\Exception $e) {
                $bulanTahunFormatted = $tahunBulan;
            }

            return response()->json([
                'lokasi' => $dataLokasi->items(),
                'pagination_data' => $dataLokasi->toArray(),
                'bulan_tahun_formatted' => $bulanTahunFormatted,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Gagal mengambil data lokasi.',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getKehadiranExportData(Request $request)
    {
        $cardnumber = $request->input('cardnumber');
        $tahun = $request->input('tahun') ? (int) $request->input('tahun') : null;

        if (!$cardnumber) {
            return response()->json(['error' => 'Nomor Kartu Anggota (Cardnumber) diperlukan.'], 400);
        }

        [$fullBorrowerDetails, $dataKunjungan] = $this->getKehadiranAnggota($cardnumber, $tahun);

        if (!$fullBorrowerDetails) {
            return response()->json(['error' => 'Detail peminjam tidak ditemukan.'], 404);
        }

        return response()->json([
            'data' => $dataKunjungan,
            'cardnumber' => $fullBorrowerDetails->cardnumber,
            'borrower_name' => trim(($fullBorrowerDetails->firstname ?? '') . ' ' . ($fullBorrowerDetails->surname ?? '')),
        ]);
    }

    public function exportPdf(Request $request)
    {
        $cardnumber = trim((string) $request->input('cardnumber', ''));
        $tahun      = $request->input('tahun') ? (int) $request->input('tahun') : null;

        // ==== Validasi ====
        if ($cardnumber === '') {
            return back()->with('error', 'Nomor Kartu Anggota wajib diisi.');
        }

        [$fullBorrowerDetails, $dataKunjungan] = $this->getKehadiranAnggota($cardnumber, $tahun);

        if (!$fullBorrowerDetails) {
            return back()->with('error', 'Data anggota tidak ditemukan.');
        }

        if ($dataKunjungan->isEmpty()) {
            return back()->with('error', 'Tidak ada data kunjungan yang ditemukan untuk diekspor.');
        }

        $options = new Options();
        $options->setIsRemoteEnabled(true);
        $options->setChroot(public_path());
        $options->setFontCache(storage_path('app/dompdf_font_cache'));
        $options->setTempDir(storage_path('app/dompdf_tmp'));

        $dompdf = new Dompdf($options);

        $barcodeBase64 = null;
        $librarianBarcodeBase64 = null;
        $currentUser = auth()->check() ? auth()->user() : null;

        try {
            // Generate barcode image via bwipjs public API
            if (!empty($fullBorrowerDetails->cardnumber)) {
                $barcodeUrl = 'https://bwipjs-api.metafloor.com/?bcid=code128&text=' . urlencode($fullBorrowerDetails->cardnumber);
                $barcodeData = @file_get_contents($barcodeUrl);
                if ($barcodeData) {
                    $barcodeBase64 = 'data:image/png;base64,' . base64_encode($barcodeData);
                }
            }

            if ($currentUser && !empty($currentUser->username)) {
                $librarianBarcodeUrl = 'https://bwipjs-api.metafloor.com/?bcid=code128&text=' . urlencode($currentUser->username);
                $librarianBarcodeData = @file_get_contents($librarianBarcodeUrl);
                if ($librarianBarcodeData) {
                    $librarianBarcodeBase64 = 'data:image/png;base64,' . base64_encode($librarianBarcodeData);
                }
            }
        } catch (\Exception $e) {
            // Silently fallback if API fails
        }

        $location = 'Surakarta';
        $printedDate = \Carbon\Carbon::now()->locale('id')->isoFormat('D MMMM YYYY');
        $periodeText = $tahun ? "Tahun {$tahun}" : "Semua Periode";

        $html = view('pages.kunjungan.laporan_kehadiran_pdf', [
            'fullBorrowerDetails'    => $fullBorrowerDetails,
            'dataKunjungan'          => $dataKunjungan,
            'barcodeBase64'          => $barcodeBase64,
            'librarianBarcodeBase64' => $librarianBarcodeBase64,
            'location'               => $location,
            'printedDate'            => $printedDate,
            'periodeText'            => $periodeText,
            'librarian'              => $currentUser,
        ])->render();

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();


        $pdfContent = $dompdf->output();

        $cardSafe = preg_replace('/[^\w\-\.]+/', '_', (string) ($fullBorrowerDetails->cardnumber ?? 'anggota'));
        $cardSafe = $cardSafe !== '' ? $cardSafe : 'anggota';
        $fileName = "laporan_kehadiran_{$cardSafe}.pdf";

        if (ob_get_length()) {
            @ob_end_clean();
        }

        return response($pdfContent)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', "inline; filename=\"{$fileName}\"");
    }


    private function getKunjunganGabunganData($filterType, $startYear, $endYear, $startDate, $endDate, $selectedLokasi): array
    {
        $lokasiMapping = [
            'sni' => 'SNI Corner',
            'bi' => 'Bank Indonesia Corner',
            'mc' => 'Muhammadiyah Corner',
            'pusat' => 'Perpustakaan Pusat',
            'pasca' => 'Perpustakaan Pascasarjana',
            'fk' => 'Perpustakaan Kedokteran',
            'ref' => 'Referensi Perpustakaan Pusat',
            'Manual Komputer' => 'Akses Mandiri',
        ];

        $groupByFormat = $filterType == 'yearly' ? 'LEFT(visittime, 7)' : 'DATE(visittime)';
        $timeStart = ($filterType == 'yearly') ? "{$startYear}-01-01 00:00:00" : "{$startDate} 00:00:00";
        $timeEnd   = ($filterType == 'yearly') ? "{$endYear}-12-31 23:59:59" : "{$endDate} 23:59:59";

        $cacheKey = 'kunj_gabungan_' . md5(json_encode([
            $filterType, $startYear, $endYear, $startDate, $endDate, $selectedLokasi
        ]));

        return Cache::remember($cacheKey, 3600, function () use ($timeStart, $timeEnd, $selectedLokasi, $lokasiMapping, $groupByFormat) {
            $applyFilter = function ($q, $locCol) use ($timeStart, $timeEnd, $selectedLokasi, $lokasiMapping) {
                $q->whereBetween('visittime', [$timeStart, $timeEnd]);
                if ($selectedLokasi) {
                    $dbLoc = array_search($selectedLokasi, $lokasiMapping) ?: $selectedLokasi;
                    if ($dbLoc === 'pusat') {
                        $q->where(fn($sub) => $sub->where($locCol, 'pusat')->orWhereNull($locCol)->orWhere($locCol, ''));
                    } else {
                        $q->where($locCol, $dbLoc);
                    }
                }
                return $q;
            };

            $hasNotes = \App\Repositories\VisitRepository::hasCornerNotesColumn();
            $applyCornerFilter = function ($q) use ($dbLoc, $hasNotes) {
                if ($dbLoc !== 'all') {
                    if ($dbLoc === 'pusat') {
                        if ($hasNotes) {
                            $q->where(fn($sub) => $sub->where('notes', 'pusat')->orWhereNull('notes')->orWhere('notes', ''));
                        }
                    } else {
                        if ($hasNotes) {
                            $q->where('notes', $dbLoc);
                        } else {
                            $q->whereRaw('1 = 0');
                        }
                    }
                }
                return $q;
            };

            $hQuery = DB::connection('mysql')->table('visitorhistory')->select(DB::raw("{$groupByFormat} as periode"), DB::raw("COUNT(*) as jumlah"));
            $cQuery = DB::connection('mysql')->table('visitorcorner')->select(DB::raw("{$groupByFormat} as periode"), DB::raw("COUNT(*) as jumlah"));

            $allResults = $applyFilter($hQuery, 'location')->groupBy('periode')->get()
                ->merge($applyCornerFilter($cQuery)->groupBy('periode')->get());

            $allSummarizedResults = $allResults->groupBy('periode')->map(function ($item, $key) {
                return (object)['periode' => $key, 'jumlah' => $item->sum('jumlah')];
            })->sortBy('periode')->values();

            $total = $allSummarizedResults->sum('jumlah');
            $count = $allSummarizedResults->count();

            // Query Top Lokasi
            $hLocQuery = DB::connection('mysql')->table('visitorhistory')->select(DB::raw("IFNULL(location, 'pusat') as lokasi"), DB::raw("COUNT(*) as jumlah"));
            $cornerLocExpr = $hasNotes
                ? DB::raw("COALESCE(NULLIF(notes, ''), 'pusat') as lokasi")
                : DB::raw("'pusat' as lokasi");
            $cLocQuery = DB::connection('mysql')->table('visitorcorner')->select($cornerLocExpr, DB::raw("COUNT(*) as jumlah"));

            $allLokasiResults = $applyFilter($hLocQuery, 'location')->groupBy('lokasi')->get()
                ->merge($applyCornerFilter($cLocQuery)->groupBy('lokasi')->get());

            $topLokasi = $allLokasiResults
                ->whereNotIn('lokasi', ['sni', 'bi', 'mc', 'ref'])
                ->groupBy('lokasi')->map(fn($item) => $item->sum('jumlah'))->sortDesc()->take(3);

            return [
                'allSummarizedResults' => $allSummarizedResults,
                'totalKunjungan'       => $total,
                'rerataKunjungan'      => $count > 0 ? ($total / $count) : 0,
                'maxKunjungan'         => $allSummarizedResults->max('jumlah') ?? 0,
                'chartData'            => $allSummarizedResults->pluck('jumlah', 'periode'),
                'topLokasi'            => $topLokasi,
                'lokasiMapping'        => $lokasiMapping,
            ];
        });
    }

    public function laporanKunjunganGabungan(Request $request)
    {
        if ($request->query('export') === 'csv') {
            return $this->exportKunjunganCsv($request);
        }

        $filterType = $request->input('filter_type', 'yearly');
        $startYear = $request->input('start_year', Carbon::now()->format('Y'));
        $endYear = $request->input('end_year', Carbon::now()->format('Y'));
        $startDate = $request->input('start_date', Carbon::now()->subDays(14)->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->format('Y-m-d'));
        $selectedLokasi = $request->input('lokasi');

        $dataHasil = collect();
        $chartData = collect();
        $totalKunjungan = 0;
        $rerataKunjungan = 0;
        $maxKunjungan = 0;
        $topLokasi = collect();

        $lokasiMapping = [
            'sni' => 'SNI Corner',
            'bi' => 'Bank Indonesia Corner',
            'mc' => 'Muhammadiyah Corner',
            'pusat' => 'Perpustakaan Pusat',
            'pasca' => 'Perpustakaan Pascasarjana',
            'fk' => 'Perpustakaan Kedokteran',
            'ref' => 'Referensi Perpustakaan Pusat',
            'Manual Komputer' => 'Akses Mandiri',
        ];

        $lokasiOptions = collect(array_keys($lokasiMapping));

        if ($request->has('filter_type')) {
            $cached = $this->getKunjunganGabunganData($filterType, $startYear, $endYear, $startDate, $endDate, $selectedLokasi);

            $dataHasil       = $cached['allSummarizedResults'];
            $totalKunjungan  = $cached['totalKunjungan'];
            $rerataKunjungan = $cached['rerataKunjungan'];
            $maxKunjungan    = $cached['maxKunjungan'];
            $chartData       = $cached['chartData'];
            $topLokasi       = $cached['topLokasi'];
        }

        return view('pages.kunjungan.kunjungan_gabungan', compact('filterType', 'rerataKunjungan', 'startYear', 'endYear', 'startDate', 'endDate', 'selectedLokasi', 'dataHasil', 'lokasiMapping', 'lokasiOptions', 'chartData', 'totalKunjungan', 'maxKunjungan', 'topLokasi'));
    }

    public function exportPdfKunjunganGabungan(Request $request)
    {
        $filterType = $request->input('filter_type', 'yearly');
        $startYear = $request->input('start_year', \Carbon\Carbon::now()->format('Y'));
        $endYear = $request->input('end_year', \Carbon\Carbon::now()->format('Y'));
        $startDate = $request->input('start_date', \Carbon\Carbon::now()->subDays(14)->format('Y-m-d'));
        $endDate = $request->input('end_date', \Carbon\Carbon::now()->format('Y-m-d'));
        $selectedLokasi = $request->input('lokasi');
        $chartImage = $request->input('chart_image_base64');

        $cached = $this->getKunjunganGabunganData($filterType, $startYear, $endYear, $startDate, $endDate, $selectedLokasi);
        $lokasiMapping = $cached['lokasiMapping'];
        $dataToExport  = $cached['allSummarizedResults'];
        $totalKunjungan = $cached['totalKunjungan'];
        $rerataKunjungan = $cached['rerataKunjungan'];

        $lokasiText = 'Semua_Lokasi';
        if ($selectedLokasi) {
            $lokasiText = preg_replace('/[^A-Za-z0-9\-]/', '_', array_search($selectedLokasi, $lokasiMapping) ?: $selectedLokasi);
        }

        $filename = ($filterType == 'yearly')
            ? "Laporan_Rekap_Kunjungan_{$lokasiText}_Tahunan_{$startYear}_sd_{$endYear}.pdf"
            : "Laporan_Rekap_Kunjungan_{$lokasiText}_Harian_{$startDate}_sd_{$endDate}.pdf";

        if ($filterType == 'yearly') {
            $periodeText = "Periode Tahunan: {$startYear} - {$endYear}";
        } else {
            $start = \Carbon\Carbon::parse($startDate)->locale('id')->isoFormat('D MMMM YYYY');
            $end = \Carbon\Carbon::parse($endDate)->locale('id')->isoFormat('D MMMM YYYY');
            $periodeText = "Periode Harian: {$start} - {$end}";
        }

        $displayLokasi = $selectedLokasi ? ($lokasiMapping[$selectedLokasi] ?? $selectedLokasi) : 'Semua Lokasi';

        $logoPath = public_path('img/ums.png');
        $logoBase64 = '';
        if (file_exists($logoPath)) {
            $logoData = file_get_contents($logoPath);
            $logoBase64 = 'data:image/png;base64,' . base64_encode($logoData);
        }

        $librarianBarcodeBase64 = null;
        $currentUser = auth()->check() ? auth()->user() : null;
        if ($currentUser && !empty($currentUser->username)) {
            try {
                $librarianBarcodeUrl = 'https://bwipjs-api.metafloor.com/?bcid=code128&text=' . urlencode($currentUser->username);
                $librarianBarcodeData = @file_get_contents($librarianBarcodeUrl);
                if ($librarianBarcodeData) {
                    $librarianBarcodeBase64 = 'data:image/png;base64,' . base64_encode($librarianBarcodeData);
                }
            } catch (\Exception $e) {
                // Silently fallback if API fails
            }
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pages.kunjungan.pdf.kunjunganGabungan', compact(
            'dataToExport', 'filterType', 'periodeText', 'displayLokasi',
            'totalKunjungan', 'rerataKunjungan', 'chartImage', 'logoBase64',
            'currentUser', 'librarianBarcodeBase64'
        ))->setPaper('a4', 'landscape');

        return $pdf->download($filename);
    }

    private function exportKunjunganCsv(Request $request): StreamedResponse
    {
        $filterType = $request->input('filter_type', 'yearly');
        $startYear = $request->input('start_year', Carbon::now()->format('Y'));
        $endYear = $request->input('end_year', Carbon::now()->format('Y'));
        $startDate = $request->input('start_date', Carbon::now()->subDays(14)->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->format('Y-m-d'));
        $selectedLokasi = $request->input('lokasi');

        $cached = $this->getKunjunganGabunganData($filterType, $startYear, $endYear, $startDate, $endDate, $selectedLokasi);
        $lokasiMapping = $cached['lokasiMapping'];
        $dataToExport  = $cached['allSummarizedResults'];

        $lokasiText = 'Semua_Lokasi';
        if ($selectedLokasi) {
            $lokasiText = preg_replace('/[^A-Za-z0-9\-]/', '_', array_search($selectedLokasi, $lokasiMapping) ?: $selectedLokasi);
        }

        $fileName = ($filterType == 'yearly')
            ? "Laporan_Rekap_Kunjungan_{$lokasiText}_Tahunan_{$startYear}_sd_{$endYear}.csv"
            : "Laporan_Rekap_Kunjungan_{$lokasiText}_Harian_{$startDate}_sd_{$endDate}.csv";

        $titles = [
            'Laporan Rekapitulasi Kunjungan Perpustakaan'
        ];

        if ($filterType == 'yearly') {
            $titles[] = "Periode Tahunan: {$startYear} - {$endYear}";
            $headerPeriode = 'Bulan';
        } else {
            $start = Carbon::parse($startDate)->locale('id')->isoFormat('D MMMM YYYY');
            $end = Carbon::parse($endDate)->locale('id')->isoFormat('D MMMM YYYY');
            $titles[] = "Periode Harian: {$start} - {$end}";
            $headerPeriode = 'Tanggal';
        }

        $displayLokasi = $selectedLokasi ? ($lokasiMapping[$selectedLokasi] ?? $selectedLokasi) : 'Semua Lokasi';
        $titles[] = "Lokasi: " . $displayLokasi;

        $csvHeaders = [$headerPeriode, 'Jumlah Kunjungan'];
        $footerRow = ['Total Keseluruhan', $cached['totalKunjungan']];

        return $this->streamCsvExport($dataToExport, $fileName, $csvHeaders, function($row, &$index) use ($filterType) {
            $formattedPeriode = $filterType == 'yearly'
                ? Carbon::parse($row->periode)->locale('id')->isoFormat('MMMM YYYY')
                : Carbon::parse($row->periode)->locale('id')->isoFormat('dddd, D MMMM YYYY');
            
            return [
                $formattedPeriode, 
                $row->jumlah
            ];
        }, $titles, $footerRow);
    }

}
