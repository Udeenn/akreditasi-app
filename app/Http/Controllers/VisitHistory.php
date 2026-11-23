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




    private function buildQuery($request, $forChart = false)
    {
        try {
            DB::connection('mysql')->statement("SET SESSION sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''));");
        } catch (\Exception $e) {
        }

        $filterType     = $request->input('filter_type', 'daily'); // Needed for cache key
        $fakultasFilter = $request->input('fakultas');
        $searchKeyword  = $request->input('search_manual');
        $selectedLokasi = $request->input('lokasi');
        $tglA_key = $request->input('tanggal_awal');
        $tglAkh_key = $request->input('tanggal_akhir');
        $thAwal_key = $request->input('tahun_awal');
        $thAkhir_key = $request->input('tahun_akhir');

        // CACHE KEY GENERATION
        $cacheParams = [
            'type' => 'buildQuery',
            'forChart' => $forChart,
            'filterType' => $filterType,
            'fakultas' => $fakultasFilter,
            'search' => $searchKeyword,
            'lokasi' => $selectedLokasi,
            'dates' => ($filterType == 'yearly') ? "$thAwal_key-$thAkhir_key" : "$tglA_key-$tglAkh_key"
        ];
        $cacheKey = 'visit_history:build_query:v2:' . md5(json_encode($cacheParams));

        return Cache::remember($cacheKey, 3600, function() use ($request, $forChart, $filterType, $fakultasFilter, $searchKeyword, $selectedLokasi) {


        $lokasiMapping = [
            'sni' => 'SNI Corner',
            'bi' => 'Bank Indonesia Corner',
            'mc' => 'Muhammadiyah Corner',
            'pusat' => 'Perpustakaan Pusat',
            'pasca' => 'Perpustakaan Pascasarjana',
            'fk' => 'Perpustakaan Kedokteran',
            'ref' => 'Referensi Perpustakaan Pusat',
        ];

        // --- STEP 1: SETUP TANGGAL ---
        if ($filterType === 'yearly') {
            $thAwal = $request->input('tahun_awal', 2020);
            $thAkhir = $request->input('tahun_akhir', \Carbon\Carbon::now()->year);
            if ($thAwal > $thAkhir) $thAwal = $thAkhir;

            $start = \Carbon\Carbon::createFromDate($thAwal, 1, 1)->startOfDay();
            $end = \Carbon\Carbon::createFromDate($thAkhir, 12, 31)->endOfDay();

            $sqlDateFormat = '%Y-%m-01'; // Group per bulan untuk chart tahunan (Nanti di PHP diubah jadi Nama Bulan)
        } else {
            $defaultStart = \Carbon\Carbon::now()->subDays(30)->toDateString();
            $start = \Carbon\Carbon::parse($request->input('tanggal_awal', $defaultStart))->startOfDay();
            $end = \Carbon\Carbon::parse($request->input('tanggal_akhir', \Carbon\Carbon::now()->toDateString()))->endOfDay();

            $sqlDateFormat = '%Y-%m-%d';
        }
        
        $sqlCategoryLogic = "
            CASE
                -- 1. Cek Borrower Category Code (Prioritas Tertinggi)
                WHEN b.categorycode LIKE 'TC%' THEN 'DOSEN'
                WHEN b.categorycode LIKE 'STAF%' OR b.categorycode LIKE '%LIB%' OR b.categorycode = 'LIBRARIAN' THEN 'TENDIK'
                WHEN b.categorycode LIKE 'DOSEN%' THEN 'DOSEN'

                -- 2. Cek Pola Cardnumber (Manual)
                WHEN UPPER(v.cardnumber) LIKE 'KSPMBKM%' THEN 'KSPMBKM'
                WHEN UPPER(v.cardnumber) LIKE 'KSPBIPA%' THEN 'KSPBIPA'
                WHEN UPPER(v.cardnumber) LIKE 'VIP%' THEN 'DOSEN'
                WHEN SUBSTRING(UPPER(v.cardnumber), 1, 2) IN ('XA', 'XC', 'LB') THEN SUBSTRING(UPPER(v.cardnumber), 1, 2)
                WHEN SUBSTRING(UPPER(v.cardnumber), 1, 3) = 'KSP' THEN 'KSP'
                
                -- Tendik (ID Pendek & Bukan Mahasiswa A123...)
                -- Regex di MySQL mungkin lambat/kompleks, kita pakai simplified logic panjang string
                WHEN LENGTH(v.cardnumber) <= 9 AND v.cardnumber NOT REGEXP '^[A-Z][0-9]{3}' THEN 'TENDIK'
                
                -- Default: 4 Kode Awal
                ELSE LEFT(UPPER(v.cardnumber), 4)
            END
        ";

        // Subquery Union (History + Corner)
        $historyQuery = DB::connection('mysql')->table('visitorhistory')
            ->select('visittime', 'cardnumber')
            ->whereBetween('visittime', [$start, $end]);

        $cornerQuery = DB::connection('mysql')->table('visitorcorner')
            ->select('visittime', 'cardnumber')
            ->whereBetween('visittime', [$start, $end]);

        // FILTER LOKASI
        if ($selectedLokasi) {
            $dbLokasi = array_search($selectedLokasi, $lokasiMapping) ?: $selectedLokasi;
            $historyQuery->where(DB::raw("IFNULL(location, 'pusat')"), $dbLokasi);
            $cornerQuery->where(DB::raw("COALESCE(NULLIF(notes, ''), 'pusat')"), $dbLokasi);
        }

        $unionQuery = $historyQuery->unionAll($cornerQuery);

        // Main Query (Aggregate)
        $query = DB::connection('mysql')->query()
            ->fromSub($unionQuery, 'v')
            ->leftJoin('koha.borrowers as b', 'v.cardnumber', '=', 'b.cardnumber') // Asumsi sesama dbmysql / bisa join
            // NOTE: Jika 'borrowers' beda connection fisik, JOIN tidak bisa dilakukan langsung. 
            // Cek config database. mysql=db_data, mysql2=koha. 
            // Jika beda server, kita tidak bisa pakai JOIN SQL biasa.
            // TAPI, di local biasanya satu server beda DB name. Laravel support cross-db join jika user permission oke.
            // Setting `database` => 'koha' di mysql2. Kita coba pakai full identifier.
            ->select(
                DB::raw("DATE_FORMAT(v.visittime, '$sqlDateFormat') as tanggal_kunjungan"),
                DB::raw("$sqlCategoryLogic as kode_identifikasi"),
                DB::raw('COUNT(*) as total'),
                'v.cardnumber' // Debug/Group need
            );

        
        
        $rawSelect = "DATE_FORMAT(visittime, '$sqlDateFormat') as tgl_raw";
        
        // Group di level subquery masing-masing table dulu biar Union lebih enteng
        $qHistory = DB::connection('mysql')->table('visitorhistory')
             ->select(DB::raw("DATE_FORMAT(visittime, '$sqlDateFormat') as tgl_group"), 'cardnumber', DB::raw('COUNT(*) as cnt'))
             ->whereBetween('visittime', [$start, $end])
             ->groupBy('tgl_group', 'cardnumber');

        $qCorner = DB::connection('mysql')->table('visitorcorner')
             ->select(DB::raw("DATE_FORMAT(visittime, '$sqlDateFormat') as tgl_group"), 'cardnumber', DB::raw('COUNT(*) as cnt'))
             ->whereBetween('visittime', [$start, $end])
             ->groupBy('tgl_group', 'cardnumber');

        // Union hasil group
        $unionResults = $qHistory->unionAll($qCorner)->get();
        
        // Collect Cardnumbers
        $cardNumbers = $unionResults->pluck('cardnumber')->unique()->values();
        
        // Fetch Borrowers (Sekali query)
        $borrowers = collect();
        if ($cardNumbers->isNotEmpty()) {
             // Chunk if too many 
             foreach (array_chunk($cardNumbers->all(), 1000) as $chunk) {
                 $chunkBorrowers = DB::connection('mysql2')->table('borrowers')
                    ->select('cardnumber', 'categorycode')
                    ->whereIn('cardnumber', $chunk)
                    ->get()
                    ->mapWithKeys(function ($item) {
                         return [strtoupper(trim($item->cardnumber)) => $item->categorycode];
                    });
                 $borrowers = $borrowers->merge($chunkBorrowers);
             }
        }

        // Processing di PHP (Sekarang loop nya hanya per unique visitor/day, bukan per visit log)
        $processedData = $unionResults->map(function($row) use ($borrowers, $start) { // $start dipake buat fallback date format kalau perlu
             $cardNumber = strtoupper(trim($row->cardnumber));
             $catCode = $borrowers[$cardNumber] ?? null;
             $cat = strtoupper($catCode ?? '');
             
             $kode = strtoupper(trim($this->identifyProdiCode($cardNumber, $cat)));
             
             return [
                 'tanggal_kunjungan' => $row->tgl_group,
                 'kode_identifikasi' => $kode,
                 'count' => (int)$row->cnt,
                 'cardnumber' => $cardNumber // Keep for debugging if needed
             ];
        });

        // --- STEP 5: MAPPING NAMA PRODI ---
        $facultyMap = $this->prodiService->getProdiToFacultyMap();
        
        // Filter Fakultasi logic (moved here from Query for flexibility)
        if ($fakultasFilter && $fakultasFilter !== 'semua') {
            $processedData = $processedData->filter(function ($item) use ($fakultasFilter, $facultyMap) {
                // Kita perlu map kode ke fakultas
                // Gunakan helper mapCodeToFaculty
                $fakultasItem = $facultyMap[$item['kode_identifikasi']] ??
                    \App\Helpers\FacultyHelper::mapCodeToFaculty($item['kode_identifikasi']);
                return $fakultasItem === $fakultasFilter;
            });
        }

        if ($forChart && $searchKeyword) {
            $keyword = strtoupper(trim($searchKeyword));
            $processedData = $processedData->filter(function ($item) use ($keyword) {
                return str_contains($item['kode_identifikasi'], $keyword);
            });
        }

        // --- STEP 6: RETURN ---
        if ($forChart) {
            $grouped = $processedData->groupBy('tanggal_kunjungan');
            $chartData = $grouped->map(function ($group, $key) use ($filterType) {
                $label = $key;
                try {
                    if ($filterType === 'yearly') {
                        $label = \Carbon\Carbon::parse($key)->locale('id')->isoFormat('MMMM Y');
                    }
                } catch (\Throwable $e) {
                }

                return [
                    'label' => mb_convert_encoding($label ?? '', 'UTF-8', 'UTF-8'),
                    'total_kunjungan' => $group->sum('count')
                ];
            })->values();

            return [
                'chart' => $chartData,
                'total' => $processedData->sum('count')
            ];
        }

        // Table Data: Aggregasi lagi (Sum counts per kode_identifikasi per tanggal)
        // Karena logic map diatas bisa menghasilkan kode identifikasi sama dari cardnumber beda.
        $tableData = $processedData->groupBy(function ($item) {
            return $item['tanggal_kunjungan'] . '|' . $item['kode_identifikasi'];
        })->map(function ($group) {
            $first = $group->first();
            return [
                'tanggal_kunjungan' => $first['tanggal_kunjungan'],
                'kode_identifikasi' => $first['kode_identifikasi'],
                'jumlah_kunjungan_harian' => $group->sum('count')
            ];
        })->values();
        
        // Construct fullProdiList for the table view
        $fullProdiList = $this->getFullProdiList();

        return [
            'data'          => $tableData,
            'filterType'    => $filterType,
            'fullProdiList' => $fullProdiList
        ];
        }); // End Cache::remember
    }

    // =====================================================================
    // exportCsvFakultas — Lightweight export using same cached data
    // =====================================================================
    public function exportCsvFakultas(Request $request)
    {

        // Reuse the same cache from kunjunganFakultasTable
        $filterType = $request->input('filter_type', 'daily');
        $selectedFakultas = $request->input('fakultas', 'semua');
        $selectedLokasi = $request->input('lokasi');
        $tanggalAwal = $request->input('tanggal_awal', Carbon::now()->subDays(30)->toDateString());
        $tanggalAkhir = $request->input('tanggal_akhir', Carbon::now()->toDateString());
        $tahunAwal = $request->input('tahun_awal', 2020);
        $tahunAkhir = $request->input('tahun_akhir', Carbon::now()->year);

        $cacheKey = 'kunjungan_fak_v3_' . md5(json_encode([
            'ft' => $filterType, 'fk' => $selectedFakultas, 'lok' => $selectedLokasi,
            'dt' => ($filterType == 'yearly') ? "$tahunAwal-$tahunAkhir" : "$tanggalAwal-$tanggalAkhir",
        ]));

        $cachedResult = Cache::get($cacheKey);

        // If not cached, trigger the main function to populate cache
        if (!$cachedResult) {
            $this->kunjunganFakultasTable($request);
            $cachedResult = Cache::get($cacheKey);
        }

        if (!$cachedResult) {
            return back()->with('error', 'Data tidak tersedia, silakan coba lagi atau buka tabel terlebih dahulu.');
        }

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
        $tableData = $cachedResult['tableData'];

        $csvHeaders = ['No', 'Periode', 'Kode Prodi', 'Nama Prodi', 'Jumlah Kunjungan'];
        $titles = [$judulLaporan ?? 'Laporan Kunjungan Fakultas', $subJudul];

        $totalKunjungan = 0;
        foreach ($tableData as $row) {
            $rowArray = (array) $row;
            if (!empty($rowArray['prodi_details'])) {
                foreach ($rowArray['prodi_details'] as $detail) {
                    $detArray = (array) $detail;
                    $totalKunjungan += ($detArray['jumlah'] ?? 0);
                }
            }
        }

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
    
    // Function Utama: Sekarang jadi bersih dan hanya looping
    private function getProdiToFacultyMap($listprodi)
    {
        $map = [];

        foreach ($listprodi as $prodi) {
            $prodiCode = $prodi->authorised_value;
            // Panggil helper yang sudah terpusat
            $map[$prodiCode] = \App\Helpers\FacultyHelper::mapCodeToFaculty($prodiCode);
        }

        // Tambahan Manual yang tidak ada di database Prodi
        $manualCodes = ['DOSEN', 'TENDIK'];
        foreach ($manualCodes as $code) {
            $map[$code] = \App\Helpers\FacultyHelper::mapCodeToFaculty($code);
        }

        // Override Nama Khusus (Opsional, untuk memastikan tampilan bagus)
        $map['DOSEN']   = 'Dosen';
        $map['TENDIK']  = 'Tendik';

        return $map;
    }

    private function getDataTables($request)
    {
        // 1. Ambil Data
        $build = $this->buildQuery($request, false);

        /** @var \Illuminate\Support\Collection $dataCollection */
        $dataCollection = $build['data'];
        $fullProdiList  = $build['fullProdiList'];
        $filterType     = $build['filterType'];

        // 2. Pre-process Data (Attach Nama Prodi Text)
        $dataCollection->transform(function ($item) use ($fullProdiList) {
            $code = strtoupper(trim($item['kode_identifikasi']));
            $item['nama_prodi_text'] = $fullProdiList[$code] ?? 'Prodi Tidak Dikenal';
            return $item;
        });

        // 3. Yajra DataTables
        return \Yajra\DataTables\Facades\DataTables::of($dataCollection)
            ->addIndexColumn()

            // Render Kolom Nama
            ->addColumn('nama_prodi', function ($row) {
                $name = $row['nama_prodi_text'];
                $code = $row['kode_identifikasi'];

                return '<div class="d-flex flex-column">
                            <span class="fw-bold text-primary">' . e($name) . '</span>
                            <small class="text-muted">' . e($code) . '</small>
                        </div>';
            })

            // Render Tanggal
            ->editColumn('tanggal_kunjungan', function ($row) use ($filterType) {
                $tgl = $row['tanggal_kunjungan'];
                try {
                    if ($filterType === 'yearly') {
                        return \Carbon\Carbon::parse($tgl)->locale('id')->isoFormat('MMMM Y');
                    }
                    return \Carbon\Carbon::parse($tgl)->locale('id')->isoFormat('dddd, D MMMM Y');
                } catch (\Exception $e) {
                    return $tgl;
                }
            })

            // Render Angka
            ->editColumn('jumlah_kunjungan_harian', function ($row) {
                return '<span>' . number_format($row['jumlah_kunjungan_harian'], 0, ',', '.') . '</span>';
            })

            ->rawColumns(['nama_prodi', 'jumlah_kunjungan_harian'])
            ->make(true);
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
            'topProdi'       => [],
            'hasFilter'      => $hasFilter
        ]);
    }

    /**
     * Helper sentral untuk mengidentifikasi kode program studi/kategori pengunjung
     */
    private function identifyProdiCode($cardnumber, $catCode, $prodiAttr = null) {
        $cat = strtoupper(trim($catCode ?? ''));
        $card = strtoupper(trim($cardnumber));
        $prodi = strtoupper(trim($prodiAttr ?? ''));

        if ($cat) {
            if (str_starts_with($cat, 'TC') || str_starts_with($cat, 'DOSEN')) return 'DOSEN';
            if (str_starts_with($cat, 'STAF') || str_contains($cat, 'LIB') || $cat === 'LIBRARIAN') return 'TENDIK';
        }

        if (!empty($prodi)) {
            return $prodi;
        }

        // Manual Patterns
        if (str_starts_with($card, 'KSPMBKM')) return 'KSPMBKM';
        if (str_starts_with($card, 'KSPBIPA')) return 'KSPBIPA';
        if (str_starts_with($card, 'VIP')) return 'DOSEN';
        if (in_array(substr($card, 0, 2), ['XA', 'XC', 'LB'])) return substr($card, 0, 2);
        if (substr($card, 0, 3) === 'KSP') return 'KSP';
        
        // Tendik (ID Pendek)
        if (strlen($card) <= 9 && !preg_match('/^[A-Z]\d{3}/', $card)) return 'TENDIK';
        
        // Default Regex (Fakultas/Prodi)
        if (preg_match('/^([A-Z]\d{3})/', $card, $matches)) return $matches[1];
        
        // Fallback: 4 karakter pertama
        if (strlen($card) >= 4) return substr($card, 0, 4);

        return 'UMUM';
    }

    /**
     * Helper sentral untuk menarik daftar lengkap prodi + kategori manual
     */
    private function getFullProdiList() {
        return $this->prodiService->getFullProdiList();
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

                $kodeIdentifikasi = $this->identifyProdiCode($cardnumber, $catCode);

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

        $totalVisitors = M_vishistory::whereBetween('visittime', [$startDate, $endDate])->count();
        $visitors = M_vishistory::select(
            DB::raw('UPPER(visitorhistory.cardnumber) as cardnumber'),
            'koha.borrowers.surname',
            DB::raw('COUNT(visitorhistory.id) as visit_count')
        )
            ->join('koha.borrowers', 'visitorhistory.cardnumber', '=', 'koha.borrowers.cardnumber')
            ->whereBetween('visittime', [$startDate, $endDate])
            ->groupBy(DB::raw('UPPER(visitorhistory.cardnumber)'), 'koha.borrowers.surname')
            ->orderBy('koha.borrowers.surname', 'asc')
            ->paginate(5, ['*'], 'page', $page);

        $formattedVisitors = $visitors->map(function ($visitor) {
            return [
                'cardnumber' => $visitor->cardnumber,
                'nama' => $visitor->surname ?? 'Nama tidak ditemukan',
                'visit_count' => $visitor->visit_count,
            ];
        });

        return response()->json([
            'data' => $formattedVisitors,
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

        $visitors = M_vishistory::select(
            DB::raw('UPPER(visitorhistory.cardnumber) as cardnumber'),
            'koha.borrowers.surname',
            DB::raw('COUNT(visitorhistory.id) as visit_count')
        )
            ->join('koha.borrowers', 'visitorhistory.cardnumber', '=', 'koha.borrowers.cardnumber')
            ->whereBetween('visittime', [$startDate, $endDate])
            ->groupBy(DB::raw('UPPER(visitorhistory.cardnumber)'), 'koha.borrowers.surname')
            ->orderBy('koha.borrowers.surname', 'asc')
            ->get(); // Gunakan get() bukan paginate()

        $formattedVisitors = $visitors->map(function ($visitor) {
            return [
                'nama' => $visitor->surname ?? 'Nama tidak ditemukan',
                'cardnumber' => $visitor->cardnumber,
                'visit_count' => $visitor->visit_count,
            ];
        });

        return response()->json(['data' => $formattedVisitors]);
    }

    public function getKunjunganHarianExportData(Request $request)
    {
        $filterType = $request->input('filter_type', 'daily');
        $tanggalAwal = null;
        $tanggalAkhir = null;
        $exportData = collect();

        if ($filterType === 'yearly') {
            // --- PERBAIKAN DI SINI ---
            // Ambil input dari form 'tahun_awal' dan 'tahun_akhir'
            $tahunAwal = (int) $request->input('tahun_awal', Carbon::now()->year);
            $tahunAkhir = (int) $request->input('tahun_akhir', Carbon::now()->year);

            if ($tahunAwal > $tahunAkhir) {
                return response()->json(['error' => 'Tahun awal tidak boleh lebih besar dari tahun akhir.'], 400);
            }

            $tanggalAwal = Carbon::createFromDate($tahunAwal, 1, 1)->format('Y-m-d');
            $tanggalAkhir = Carbon::createFromDate($tahunAkhir, 12, 31)->format('Y-m-d');

            $dataFromDb = M_vishistory::select(
                DB::raw('DATE_FORMAT(visittime, "%Y-%m-01") as tanggal_kunjungan'),
                DB::raw('COUNT(id) as jumlah_kunjungan_harian')
            )
                ->where('visittime', '>=', $tanggalAwal . ' 00:00:00')
                ->where('visittime', '<=', $tanggalAkhir . ' 23:59:59')
                ->groupBy(DB::raw('DATE_FORMAT(visittime, "%Y-%m-01")'))
                ->orderBy(DB::raw('DATE_FORMAT(visittime, "%Y-%m-01")'), 'asc')
                ->get();

            $exportData = $dataFromDb->map(function ($item) {
                $item->tanggal_kunjungan = Carbon::parse($item->tanggal_kunjungan)->format('Y-m-d');
                return $item;
            });
        } else {
            $tanggalAwal = $request->input('tanggal_awal');
            $tanggalAkhir = $request->input('tanggal_akhir');

            if (!$tanggalAwal || !$tanggalAkhir) {
                return response()->json(['error' => 'Tanggal awal dan akhir wajib diisi untuk filter harian.'], 400);
            }

            $exportData = M_vishistory::selectRaw('
                DATE(visittime) as tanggal_kunjungan,
                COUNT(id) as jumlah_kunjungan_harian
            ')
                ->where('visittime', '>=', $tanggalAwal . ' 00:00:00')
                ->where('visittime', '<=', $tanggalAkhir . ' 23:59:59')
                ->groupBy(DB::raw('DATE(visittime)'))
                ->orderBy(DB::raw('DATE(visittime)'), 'asc')
                ->get();
        }

        if ($tanggalAwal && $tanggalAkhir && Carbon::parse($tanggalAwal)->greaterThan(Carbon::parse($tanggalAkhir))) {
            return response()->json(['error' => 'Tanggal Awal tidak boleh lebih besar dari Tanggal Akhir.'], 400);
        }

        return response()->json(['data' => $exportData]);
    }



    public function cekKehadiran(Request $request)
    {
        $cardnumber = $request->input('cardnumber');
        $tahun = $request->input('tahun');

        $fullBorrowerDetails = null;
        $pesan = 'Silakan masukkan Nomor Kartu Anggota (Cardnumber) untuk melihat laporan kunjungan.';
        $dataKunjungan = collect();
        $totalKunjunganSum = 0;

        if ($cardnumber) {
            $cleanCard = trim($cardnumber);

            $fullBorrowerDetails = DB::connection('mysql2')->table('borrowers')
                ->select('borrowernumber', 'cardnumber', 'firstname', 'surname', 'email', 'phone', 'categorycode')
                ->where('cardnumber', $cleanCard)
                ->orWhere('cardnumber', strtoupper($cleanCard))
                ->orWhere('cardnumber', strtolower($cleanCard))
                ->first();

            if ($fullBorrowerDetails) {
                // dd($fullBorrowerDetails);
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
                    ->mergeBindings($unionQuery) // Penting untuk binding parameter
                    ->select('tahun_bulan', DB::raw('SUM(total) as jumlah_kunjungan'))
                    ->groupBy('tahun_bulan')
                    ->orderBy('tahun_bulan', 'ASC')
                    ->get();

                $dataKunjungan = collect($semuaKunjungan);
                $totalKunjunganSum = $dataKunjungan->sum('jumlah_kunjungan');

                $perPage = 12;
                $currentPage = Paginator::resolveCurrentPage('page');
                $currentItems = $dataKunjungan->slice(($currentPage - 1) * $perPage, $perPage)->all();
                $dataKunjungan = new LengthAwarePaginator($currentItems, $dataKunjungan->count(), $perPage, $currentPage, [
                    'path' => Paginator::resolveCurrentPath()
                ]);
                $dataKunjungan->appends(request()->query());

                if ($dataKunjungan->isEmpty()) {
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


    // public function getLokasiDetail(Request $request)
    // {
    //     $cardnumber = $request->input('cardnumber');
    //     $tahunBulan = $request->input('tahun_bulan');
    //     $perPage = 10;

    //     if (!$cardnumber || !$tahunBulan) {
    //         return response()->json(['error' => 'Parameter tidak lengkap.'], 400);
    //     }

    //     try {
    //         $cardnumber = trim(strtolower($cardnumber));

    //         $lokasiMapping = [
    //             'sni' => 'SNI Corner',
    //             'bi' => 'Bank Indonesia Corner',
    //             'mc' => 'Muhammadiyah Corner',
    //             'pusat' => 'Perpustakaan Pusat',
    //             'pasca' => 'Perpustakaan Pascasarjana',
    //             'fk' => 'Perpustakaan Kedokteran',
    //             'ref' => 'Referensi Perpustakaan Pusat',
    //         ];

    //         $queryHistory = DB::connection('mysql')->table('visitorhistory')
    //             ->select('visittime as visit_date', DB::raw("IFNULL(location, 'pusat') as visit_location"))
    //             ->where('cardnumber', $cardnumber)
    //             ->whereRaw("EXTRACT(YEAR_MONTH FROM visittime) = ?", [$tahunBulan]);

    //         $queryCorner = DB::connection('mysql')->table('visitorcorner')
    //             ->select('visittime as visit_date', 'notes as visit_location')
    //             ->where('cardnumber', $cardnumber)
    //             ->whereRaw("EXTRACT(YEAR_MONTH FROM visittime) = ?", [$tahunBulan]);

    //         $unionQuery = $queryHistory->unionAll($queryCorner);

    //         $dataLokasi = DB::connection('mysql')
    //             ->table(DB::raw("({$unionQuery->toSql()}) as combined"))
    //             ->mergeBindings($unionQuery)
    //             ->orderBy('visit_date', 'ASC')
    //             ->paginate($perPage);

    //         $dataLokasi->getCollection()->transform(function ($item) use ($lokasiMapping) {
    //             $rawLoc = strtolower(trim($item->visit_location));
    //             // Gunakan null coalescing operator ?? untuk default value
    //             $item->visit_location = $lokasiMapping[$rawLoc] ?? $item->visit_location;

    //             return $item;
    //         });

    //         try {
    //             $bulanTahunFormatted = Carbon::createFromFormat('Ym', (string)$tahunBulan)->format('F Y');
    //         } catch (\Exception $e) {
    //             $bulanTahunFormatted = $tahunBulan;
    //         }

    //         return response()->json([
    //             'lokasi' => $dataLokasi->items(),
    //             'pagination_data' => $dataLokasi->toArray(),
    //             'bulan_tahun_formatted' => $bulanTahunFormatted,
    //         ]);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'error' => 'Gagal mengambil data lokasi.',
    //             'message' => $e->getMessage()
    //         ], 500);
    //     }
    // }

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

            // 3. Query Corner (Gunakan whereBetween sargable)
            $queryCorner = DB::connection('mysql')->table('visitorcorner')
                ->select('visittime as visit_date', 'notes as visit_location')
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
        $tahun = $request->input('tahun');

        if (!$cardnumber) {
            return response()->json(['error' => 'Nomor Kartu Anggota (Cardnumber) diperlukan.'], 400);
        }

        $cardnumber = trim(strtolower($cardnumber));
        $fullBorrowerDetails = DB::connection('mysql2')->table('koha.borrowers')
            ->select('cardnumber', 'firstname', 'surname')
            ->where(DB::raw('TRIM(LOWER(cardnumber))'), $cardnumber)
            ->first();

        if (!$fullBorrowerDetails) {
            return response()->json(['error' => 'Detail peminjam tidak ditemukan.'], 404);
        }

        $tahunFilter = $tahun ? "AND YEAR(combined.visittime) = ?" : "";
        $bindings = [$cardnumber];
        if ($tahun) {
            $bindings[] = $tahun;
        }

        $queryString = "
        SELECT
            EXTRACT(YEAR_MONTH FROM combined.visittime) AS tahun_bulan,
            COUNT(*) AS jumlah_kunjungan
        FROM (
            (SELECT visittime, cardnumber FROM visitorhistory)
            UNION ALL
            (SELECT visittime, cardnumber FROM visitorcorner)
        ) AS combined
        WHERE TRIM(LOWER(combined.cardnumber)) = ? {$tahunFilter}
        GROUP BY tahun_bulan
        ORDER BY tahun_bulan ASC";

        $dataKunjunganExport = DB::connection('mysql')->select($queryString, $bindings);

        return response()->json([
            'data' => $dataKunjunganExport,
            'cardnumber' => $cardnumber,
            'borrower_name' => $fullBorrowerDetails->firstname . ' ' . $fullBorrowerDetails->surname,
        ]);
    }

    public function exportPdf(Request $request)
    {
        $cardnumber = trim((string) $request->input('cardnumber', ''));
        $tahun      = $request->input('tahun');

        // ==== Validasi ====
        if ($cardnumber === '') {
            return back()->with('error', 'Nomor Kartu Anggota wajib diisi.');
        }

        $normalizedCardnumber = trim(strtolower($cardnumber));

        $fullBorrowerDetails = DB::connection('mysql2')->table('borrowers')
            ->select('cardnumber', 'firstname', 'surname', 'email', 'phone')
            ->where(DB::raw('TRIM(LOWER(cardnumber))'), $normalizedCardnumber)
            ->first();

        if (!$fullBorrowerDetails) {
            return back()->with('error', 'Data anggota tidak ditemukan.');
        }
        $tahunFilter = $tahun ? "AND YEAR(combined.visittime) = ?" : "";
        $bindings = [$normalizedCardnumber];
        if ($tahun) {
            $bindings[] = $tahun;
        }

        $queryString = "
        SELECT
            EXTRACT(YEAR_MONTH FROM combined.visittime) AS tahun_bulan,
            COUNT(*) AS jumlah_kunjungan
        FROM (
            (SELECT visittime, cardnumber FROM visitorhistory)
            UNION ALL
            (SELECT visittime, cardnumber FROM visitorcorner)
        ) AS combined
        WHERE TRIM(LOWER(combined.cardnumber)) = ? {$tahunFilter}
        GROUP BY tahun_bulan
        ORDER BY tahun_bulan ASC";
        $dataKunjungan = DB::connection('mysql')->select($queryString, $bindings);
        $dataKunjungan = collect($dataKunjungan);

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
        ];

        $lokasiOptions = collect(array_keys($lokasiMapping));

        if ($request->has('filter_type')) {
            $groupByFormat = $filterType == 'yearly' ? 'LEFT(visittime, 7)' : 'DATE(visittime)';
            $timeStart = ($filterType == 'yearly') ? "{$startYear}-01-01 00:00:00" : "{$startDate} 00:00:00";
            $timeEnd   = ($filterType == 'yearly') ? "{$endYear}-12-31 23:59:59" : "{$endDate} 23:59:59";

            $cacheKey = 'kunj_gabungan_' . md5(json_encode([
                $filterType, $startYear, $endYear, $startDate, $endDate, $selectedLokasi
            ]));

            $cached = Cache::remember($cacheKey, 3600, function () use ($timeStart, $timeEnd, $selectedLokasi, $lokasiMapping, $groupByFormat) {
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

                $hQuery = DB::connection('mysql')->table('visitorhistory')->select(DB::raw("{$groupByFormat} as periode"), DB::raw("COUNT(*) as jumlah"));
                $cQuery = DB::connection('mysql')->table('visitorcorner')->select(DB::raw("{$groupByFormat} as periode"), DB::raw("COUNT(*) as jumlah"));

                $allResults = $applyFilter($hQuery, 'location')->groupBy('periode')->get()
                    ->merge($applyFilter($cQuery, 'notes')->groupBy('periode')->get());

                $allSummarizedResults = $allResults->groupBy('periode')->map(function ($item, $key) {
                    return (object)['periode' => $key, 'jumlah' => $item->sum('jumlah')];
                })->sortBy('periode')->values();

                $total = $allSummarizedResults->sum('jumlah');
                $count = $allSummarizedResults->count();

                // Query Top Lokasi
                $hLocQuery = DB::connection('mysql')->table('visitorhistory')->select(DB::raw("IFNULL(location, 'pusat') as lokasi"), DB::raw("COUNT(*) as jumlah"));
                $cLocQuery = DB::connection('mysql')->table('visitorcorner')->select(DB::raw("COALESCE(NULLIF(notes, ''), 'pusat') as lokasi"), DB::raw("COUNT(*) as jumlah"));

                $allLokasiResults = $applyFilter($hLocQuery, 'location')->groupBy('lokasi')->get()
                    ->merge($applyFilter($cLocQuery, 'notes')->groupBy('lokasi')->get());

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
                ];
            });

            $dataHasil       = $cached['allSummarizedResults'];
            $totalKunjungan  = $cached['totalKunjungan'];
            $rerataKunjungan = $cached['rerataKunjungan'];
            $maxKunjungan    = $cached['maxKunjungan'];
            $chartData       = $cached['chartData'];
            $topLokasi       = $cached['topLokasi'];
        }

        // Kirim variabel 'maxKunjungan' bukan 'maxKunjunganBulanan'
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

        $lokasiMapping = [
            'sni' => 'SNI Corner',
            'bi' => 'Bank Indonesia Corner',
            'mc' => 'Muhammadiyah Corner',
            'pusat' => 'Perpustakaan Pusat',
            'pasca' => 'Perpustakaan Pascasarjana',
            'fk' => 'Perpustakaan Kedokteran',
            'ref' => 'Referensi Perpustakaan Pusat',
            'Manual Komputer' => 'Akses Mandiri'
        ];

        $lokasiText = 'Semua_Lokasi';
        if ($selectedLokasi) {
            $lokasiText = preg_replace('/[^A-Za-z0-9\-]/', '_', array_search($selectedLokasi, $lokasiMapping) ?: $selectedLokasi);
        }

        $groupByFormat = $filterType == 'yearly' ? 'LEFT(visittime, 7)' : 'DATE(visittime)';

        $historyQuery = DB::connection('mysql')->table('visitorhistory')
            ->select(DB::raw("{$groupByFormat} as periode"), DB::raw("COUNT(*) as jumlah"))
            ->groupBy('periode');

        $cornerQuery = DB::connection('mysql')->table('visitorcorner')
            ->select(DB::raw("{$groupByFormat} as periode"), DB::raw("COUNT(*) as jumlah"))
            ->groupBy('periode');

        $timeStart = ($filterType == 'yearly') ? "{$startYear}-01-01 00:00:00" : "{$startDate} 00:00:00";
        $timeEnd   = ($filterType == 'yearly') ? "{$endYear}-12-31 23:59:59" : "{$endDate} 23:59:59";
        $filename  = ($filterType == 'yearly')
            ? "Laporan_Rekap_Kunjungan_{$lokasiText}_Tahunan_{$startYear}_sd_{$endYear}.pdf"
            : "Laporan_Rekap_Kunjungan_{$lokasiText}_Harian_{$startDate}_sd_{$endDate}.pdf";

        $historyQuery->whereBetween('visittime', [$timeStart, $timeEnd]);
        $cornerQuery->whereBetween('visittime', [$timeStart, $timeEnd]);

        if ($selectedLokasi) {
            $dbLokasi = array_search($selectedLokasi, $lokasiMapping) ?: $selectedLokasi;
            if ($dbLokasi === 'pusat') {
                $historyQuery->where(fn($q) => $q->where('location', 'pusat')->orWhereNull('location')->orWhere('location', ''));
                $cornerQuery->where(fn($q) => $q->where('notes', 'pusat')->orWhereNull('notes')->orWhere('notes', ''));
            } else {
                $historyQuery->where('location', $dbLokasi);
                $cornerQuery->where('notes', $dbLokasi);
            }
        }

        $allResults = $historyQuery->get()->merge($cornerQuery->get());
        $dataToExport = $allResults->groupBy('periode')->map(fn($item, $key) => (object)['periode' => $key, 'jumlah' => $item->sum('jumlah')])->sortBy('periode')->values();

        if ($filterType == 'yearly') {
            $periodeText = "Periode Tahunan: {$startYear} - {$endYear}";
        } else {
            $start = \Carbon\Carbon::parse($startDate)->locale('id')->isoFormat('D MMMM YYYY');
            $end = \Carbon\Carbon::parse($endDate)->locale('id')->isoFormat('D MMMM YYYY');
            $periodeText = "Periode Harian: {$start} - {$end}";
        }

        $displayLokasi = $selectedLokasi ? ($lokasiMapping[$selectedLokasi] ?? $selectedLokasi) : 'Semua Lokasi';
        $totalKunjungan = $dataToExport->sum('jumlah');
        $jumlahPeriode = $dataToExport->count();
        $rerataKunjungan = ($jumlahPeriode > 0) ? ($totalKunjungan / $jumlahPeriode) : 0;

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

        $lokasiMapping = [
            'sni' => 'SNI Corner',
            'bi' => 'Bank Indonesia Corner',
            'mc' => 'Muhammadiyah Corner',
            'pusat' => 'Perpustakaan Pusat',
            'pasca' => 'Perpustakaan Pascasarjana',
            'fk' => 'Perpustakaan Kedokteran',
            'ref' => 'Referensi Perpustakaan Pusat',
            'Manual Komputer' => 'Akses Mandiri'
        ];

        $lokasiText = 'Semua_Lokasi';
        if ($selectedLokasi) {
            // Mengganti nama lokasi menjadi format file yang aman
            $lokasiText = preg_replace('/[^A-Za-z0-9\-]/', '_', array_search($selectedLokasi, $lokasiMapping) ?: $selectedLokasi);
        }

        $groupByFormat = $filterType == 'yearly' ? 'LEFT(visittime, 7)' : 'DATE(visittime)';

        // Query untuk visitorhistory
        $historyQuery = DB::connection('mysql')->table('visitorhistory')
            ->select(DB::raw("{$groupByFormat} as periode"), DB::raw("COUNT(*) as jumlah"))
            ->groupBy('periode');

        // Query untuk visitorcorner
        $cornerQuery = DB::connection('mysql')->table('visitorcorner')
            ->select(DB::raw("{$groupByFormat} as periode"), DB::raw("COUNT(*) as jumlah"))
            ->groupBy('periode');

        $timeStart = ($filterType == 'yearly') ? "{$startYear}-01-01 00:00:00" : "{$startDate} 00:00:00";
        $timeEnd   = ($filterType == 'yearly') ? "{$endYear}-12-31 23:59:59" : "{$endDate} 23:59:59";
        $fileName  = ($filterType == 'yearly')
            ? "Laporan_Rekap_Kunjungan_{$lokasiText}_Tahunan_{$startYear}_sd_{$endYear}.csv"
            : "Laporan_Rekap_Kunjungan_{$lokasiText}_Harian_{$startDate}_sd_{$endDate}.csv";

        $historyQuery->whereBetween('visittime', [$timeStart, $timeEnd]);
        $cornerQuery->whereBetween('visittime', [$timeStart, $timeEnd]);

        if ($selectedLokasi) {
            $dbLokasi = array_search($selectedLokasi, $lokasiMapping) ?: $selectedLokasi;
            if ($dbLokasi === 'pusat') {
                $historyQuery->where(fn($q) => $q->where('location', 'pusat')->orWhereNull('location')->orWhere('location', ''));
                $cornerQuery->where(fn($q) => $q->where('notes', 'pusat')->orWhereNull('notes')->orWhere('notes', ''));
            } else {
                $historyQuery->where('location', $dbLokasi);
                $cornerQuery->where('notes', $dbLokasi);
            }
        }

        $allResults = $historyQuery->get()->merge($cornerQuery->get());
        $dataToExport = $allResults->groupBy('periode')->map(fn($item, $key) => (object)['periode' => $key, 'jumlah' => $item->sum('jumlah')])->sortBy('periode')->values();

        // --- Bagian Header Laporan ---
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
        $footerRow = ['Total Keseluruhan', $dataToExport->sum('jumlah')];

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
