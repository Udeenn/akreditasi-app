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

class VisitHistory extends Controller
{
    private $facultyMapping = [
        'A' => 'FKIP - Fakultas Keguruan dan Ilmu Pendidikan',
        'B' => 'FEB - Fakultas Ekonomi dan Bisnis',
        'C' => 'FHIP - Fakultas Hukum dan Ilmu Politik',
        'D' => 'FT - Fakultas Teknik',
        'E' => 'FG - Fakultas Geografi',
        'F' => 'FPsi - Fakultas Psikologi',
        'G' => 'FAI - Fakultas Agama Islam',
        'H' => 'FAI - Fakultas Agama Islam',
        'K' => 'FF - Fakultas Farmasi',
        'L' => 'FKI - Fakultas Komunikasi dan Informatika',
    ];


    public function kunjunganFakultasTable(Request $request)
    {
        ini_set('memory_limit', '512M');

        // 1. SETUP DATA STATIC
        $allProdiListObj = \App\Models\M_Auv::where('category', 'PRODI')->get();
        $prodiToFacultyMap = $this->getProdiToFacultyMap($allProdiListObj);
        // $listFakultas = collect($prodiToFacultyMap)->unique()->sort()->values()->all();

        $listFakultas = collect($prodiToFacultyMap)
            ->unique()
            ->filter(function ($value) {
                // Masukkan nama-nama kategori yang ingin DIHAPUS dari dropdown
                $blacklist = [
                    'Lainnya',
                    'Dosen',
                    'Dosen & Pengajar',
                    'Tendik',
                    'Tenaga Kependidikan',
                    'Alumni',
                    'Alumni Universitas',
                    'Alumni UMS',
                    'Anggota Luar Biasa',
                    'Tamu VIP / Dinas',
                    'Kartu Sekali Kunjung'
                ];
                return !in_array($value, $blacklist);
            })
            ->sort()
            ->values()
            ->all();

        // Default Tanggal (Harian): Ambil dari input, kalau kosong baru mundur 30 hari
        $defaultTglAwal = \Carbon\Carbon::now()->subDays(30)->toDateString();
        $tanggalAwal    = $request->input('tanggal_awal', $defaultTglAwal);
        $tanggalAkhir   = $request->input('tanggal_akhir', \Carbon\Carbon::now()->toDateString());

        // Default Tahun (Tahunan): Ambil dari input, kalau kosong baru set 2020
        $tahunAwal  = $request->input('tahun_awal', 2020);
        $tahunAkhir = $request->input('tahun_akhir', \Carbon\Carbon::now()->year);

        // -----------------------------------------------------------

        // 2. REQUEST AJAX YAJRA (Tabel)
        if ($request->ajax()) {
            return $this->getDataTables($request);
        }

        $hasFilter = $request->hasAny(['filter_type', 'fakultas', 'tanggal_awal', 'tahun_awal']);
        $filterType = $request->input('filter_type', 'daily');

        $chartData = [];
        $totalKeseluruhanKunjungan = 0;
        $displayPeriod = '';

        if ($hasFilter) {
            $chartDataRaw = $this->buildQuery($request, true);
            $chartData = $chartDataRaw['chart'];
            $totalKeseluruhanKunjungan = $chartDataRaw['total'];

            // Setup Display Period Text
            if ($filterType === 'yearly') {
                $displayPeriod = "Tahun " . $tahunAwal . ($tahunAwal != $tahunAkhir ? " s.d. " . $tahunAkhir : "");
            } else {
                $displayPeriod = "Periode " . \Carbon\Carbon::parse($tanggalAwal)->locale('id')->isoFormat('D MMMM Y') .
                    " s.d. " . \Carbon\Carbon::parse($tanggalAkhir)->locale('id')->isoFormat('D MMMM Y');
            }
        }

    $lokasiMapping = [
        'sni' => 'SNI Corner',
        'bi' => 'Bank Indonesia Corner',
        'mc' => 'Muhammadiyah Corner',
        'pusat' => 'Perpustakaan Pusat',
        'pasca' => 'Perpustakaan Pascasarjana',
        'fk' => 'Perpustakaan Kedokteran',
        'ref' => 'Referensi Perpustakaan Pusat',
    ];

    return view('pages.kunjungan.fakultasTable', compact(
        'listFakultas',
        'tanggalAwal',
        'tanggalAkhir',
        'tahunAwal',
        'tahunAkhir',
        'chartData',
        'totalKeseluruhanKunjungan',
        'hasFilter',
        'filterType',
        'displayPeriod',
        'lokasiMapping'
    ));
}


    private function buildQuery($request, $forChart = false)
    {
        try {
            DB::connection('mysql')->statement("SET SESSION sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''));");
        } catch (\Exception $e) {
        }

        $filterType     = $request->input('filter_type', 'daily');
        $fakultasFilter = $request->input('fakultas');
        $searchKeyword  = $request->input('search_manual');
        $selectedLokasi = $request->input('lokasi');

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
            ->leftJoin('db_data.borrowers as b', 'v.cardnumber', '=', 'b.cardnumber') // Asumsi sesama dbmysql / bisa join
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

        // Jika beda server fisik (jarang di dev local user), query ini akan fail. 
        // Solusi fallback: Tetap fetch raw tapi select column specific, lalu map di PHP (sedikit lebih lambat dr SQL pure tapi faster than original code).
        // Saya asumsikan user pakai Localhost XAMPP jadi bisa cross-db join.
        // PERLU DISESUAIKAN NAMA DB NYA. Di config: 'mysql2' => database='koha'
        // Di query saya pakai `koha.borrowers`. Perlu cek nama DB asli dari env.
        // Config bilang: env('DB_SECOND_DATABASE', 'koha'). Saya akan pakai DB::raw untk safety join.
        
        // REVISI STRATEGI: Karena saya tidak tahu pasti nama DB nya (bisa berubah di env),
        // Join cross-database agak berisiko jika production beda server.
        // TAPI code lama melakukan query massive "SELECT ... WHERE IN (...)" ke mysql2.
        // Code lama: fetch all visits -> extract cardnumbers -> query borrowers whereIn -> map.
        // Itu sebenernya sudah "best effort" untuk cross-db. Masalahnya ada di `processedData` loop array map massive.
        // JIKA jumlah visit besar (misal 100rb), loop PHP mati.
        
        // STRATEGI HYBRID (SAFE & FAST):
        // 1. Fetch data visitor (Grouped by date, cardnumber) -> Reduce row count drasticly (karena 1 orang bisa berkali2 sehari visitor corner)
        // 2. Fetch borrower data for those cardnumbers (WhereIn).
        // 3. Map di PHP.
        // Ini mirip kode lama TAPI kita GROUP BY di SQL LEVEL DULU sebelum fetch ke PHP.
        
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
        // Hasil: [tgl_group, cardnumber, cnt]
        // Jumlah row = jumlah unik pengunjung per hari per tanggal. Jauh lebih sedikit dari total raw row.

        // Collect Cardnumbers
        $cardNumbers = $unionResults->pluck('cardnumber')->unique()->values();
        
        // Fetch Borrowers (Sekali query)
        $borrowers = collect();
        if ($cardNumbers->isNotEmpty()) {
             // Chunk if too many 
             $borrowers = DB::connection('mysql2')->table('borrowers')
                ->select('cardnumber', 'categorycode')
                ->whereIn('cardnumber', $cardNumbers)
                ->get()
                ->mapWithKeys(function ($item) {
                     return [strtoupper(trim($item->cardnumber)) => $item->categorycode];
                });
        }

        // Processing di PHP (Sekarang loop nya hanya per unique visitor/day, bukan per visit log)
        $processedData = $unionResults->map(function($row) use ($borrowers, $start) { // $start dipake buat fallback date format kalau perlu
             $cardNumber = strtoupper(trim($row->cardnumber));
             $catCode = $borrowers[$cardNumber] ?? null;
             $cat = strtoupper($catCode ?? '');
             
             // Identifikasi Logic (Copied)
             $kode = null;
             if ($cat) {
                if (str_starts_with($cat, 'TC')) $kode = 'DOSEN';
                elseif (str_starts_with($cat, 'STAF') || str_contains($cat, 'LIB') || $cat === 'LIBRARIAN') $kode = 'TENDIK';
                elseif (str_starts_with($cat, 'DOSEN')) $kode = 'DOSEN';
             }
             if (!$kode) {
                if (str_starts_with($cardNumber, 'KSPMBKM')) $kode = 'KSPMBKM';
                elseif (str_starts_with($cardNumber, 'KSPBIPA')) $kode = 'KSPBIPA';
                elseif (str_starts_with($cardNumber, 'VIP')) $kode = 'DOSEN';
                elseif (in_array(substr($cardNumber, 0, 2), ['XA', 'XC', 'LB'])) $kode = substr($cardNumber, 0, 2);
                elseif (substr($cardNumber, 0, 3) === 'KSP') $kode = 'KSP';
                elseif (strlen($cardNumber) <= 9 && !preg_match('/^[A-Z]\d{3}/', $cardNumber)) $kode = 'TENDIK';
                else $kode = substr($cardNumber, 0, 4);
             }
             $kode = strtoupper(trim($kode));
             
             return [
                 'tanggal_kunjungan' => $row->tgl_group,
                 'kode_identifikasi' => $kode,
                 'count' => (int)$row->cnt,
                 'cardnumber' => $cardNumber // Keep for debugging if needed
             ];
        });

        // --- STEP 5: MAPPING NAMA PRODI ---
        $allProdiListObj = \App\Models\M_Auv::where('category', 'PRODI')->get();
        $facultyMap = $this->getProdiToFacultyMap($allProdiListObj);
        
        // Filter Fakultasi logic (moved here from Query for flexibility)
        if ($fakultasFilter && $fakultasFilter !== 'semua') {
            $processedData = $processedData->filter(function ($item) use ($fakultasFilter, $facultyMap) {
                // Kita perlu map kode ke fakultas
                // Gunakan helper mapCodeToFaculty
                $fakultasItem = $facultyMap[$item['kode_identifikasi']] ??
                    $this->mapCodeToFaculty($item['kode_identifikasi'], $this->facultyMapping);
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
                    'label' => $label,
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
        $prodiNameMap = $allProdiListObj->pluck('lib', 'authorised_value')->toArray();
        $fullProdiList = [];
        foreach ($prodiNameMap as $code => $name) {
            $facultyString = $facultyMap[$code] ?? '';
            $parts = explode(' - ', $facultyString);
            $acronym = isset($parts[0]) ? trim($parts[0]) : '';
            $cleanName = $name;
            if (!empty($acronym) && str_starts_with(strtoupper($name), $acronym)) {
                $cleanName = ltrim(substr($name, strlen($acronym)), "/- ");
            }
            $fullProdiList[$code] = $cleanName;
        }
        // Manual names injections... (copy existing manual names logic here or helper) can be done in calling function too but okay.
        $manualNames = [
            'G000' => 'Kesehatan Masyarakat', 'G100' => 'Ilmu Gizi', 'I000' => 'Pendidikan Agama Islam',
            'O100' => 'Hukum Ekonomi Syariah', 'O200' => 'Pendidikan Bahasa Arab', 'O300' => 'Ilmu Al-Quran dan Tafsir',
            'O000' => 'Studi Islam', 'DOSEN' => 'Dosen & Pengajar', 'TENDIK' => 'Tenaga Kependidikan',
            'KSP' => 'Kartu Sekali Kunjung', 'KSPMBKM' => 'MBKM', 'KSPBIPA' => 'BIPA',
            'XA' => 'Alumni Universitas', 'LB' => 'Anggota Luar Biasa', 'XC' => 'Exchange'
        ];
        foreach ($manualNames as $c => $n) $fullProdiList[$c] = $n;

        return [
            'data'          => $tableData,
            'filterType'    => $filterType,
            'fullProdiList' => $fullProdiList
        ];
    }



    // Helper Terpusat: Semua logika deteksi ada di sini
    private function mapCodeToFaculty($prodiCode, $facultyMapping)
    {
        // 1. Bersihkan Input
        $prodiCode = strtoupper(trim($prodiCode));

        $firstLetter = substr($prodiCode, 0, 1);
        $firstTwoLetters = substr($prodiCode, 0, 2);
        $firstThreeLetters = substr($prodiCode, 0, 3);

        // 2. CEK KODE SPESIFIK (Gabungan dari kode lama & baru)
        // FKIP
        if (in_array($prodiCode, ['A510', 'A610', 'KIP/PSKGJ PAUD', 'Q100', 'S400', 'Q200', 'Q300', 'S200'])) {
            return 'FKIP - Fakultas Keguruan dan Ilmu Pendidikan';
        }

        // FEB
        if (in_array($prodiCode, ['W100', 'P100'])) {
            return 'FEB - Fakultas Ekonomi dan Bisnis';
        }

        // FT (Teknik) - Gabungan U, S, dan D
        if (in_array($prodiCode, ['U200', 'U100', 'S100', 'D100', 'D200', 'D400'])) {
            return 'FT - Fakultas Teknik';
        }

        // FPsi (Psikologi) - Gabungan S, T, dan F
        if (in_array($prodiCode, ['S300', 'T100', 'F100'])) {
            return 'FPsi - Fakultas Psikologi';
        }

        // FAI (Agama Islam) - Kode spesifik
        if (in_array($prodiCode, ['I000', 'O100', 'O300', 'O200', 'O000'])) {
            return 'FAI - Fakultas Agama Islam';
        }

        // FHIP (Hukum) - Gabungan R dan C
        if (in_array($prodiCode, ['R100', 'R200', 'C100'])) {
            return 'FHIP - Fakultas Hukum dan Ilmu Politik';
        }

        // FF (Farmasi) - Gabungan V dan K
        if (in_array($prodiCode, ['V100', 'K100'])) {
            return 'FF - Fakultas Farmasi';
        }

        // 3. CEK BERDASARKAN PREFIX (HURUF DEPAN)

        // FIK / FK / FKG
        if (in_array($firstThreeLetters, ['J53', 'J52'])) return 'FKG - Fakultas Kedokteran Gigi';
        if ($firstTwoLetters === 'J5') return 'FK - Fakultas Kedokteran';
        // G biasanya Gizi/Kesmas (FIK), J adalah kode standar FIK
        if ($firstLetter === 'J' || $firstLetter === 'G') return 'FIK - Fakultas Ilmu Kesehatan';

        // FAI (Prefix Umum)
        if (in_array($firstLetter, ['I', 'O', 'H'])) return 'FAI - Fakultas Agama Islam';

        // 4. MAPPING STANDAR DARI ARRAY PROPERTY
        if (isset($facultyMapping[$firstLetter])) {
            return $facultyMapping[$firstLetter];
        }

        return 'Lainnya';
    }

    public function exportCsvFakultas(Request $request)
    {
        // 1. Ambil Data (Gunakan logic yang sama dengan tabel)
        // Kita set parameter ke-2 (forChart) jadi false, karena kita butuh data detail tabel
        $buildResult = $this->buildQuery($request, false);

        $dataCollection = $buildResult['data'];          // Data kunjungan
        $fullProdiList  = $buildResult['fullProdiList']; // List Nama Prodi
        $filterType     = $buildResult['filterType'];    // 'daily' atau 'yearly'

        // 2. Buat Judul Laporan Dinamis
        $judulLaporan = "LAPORAN KUNJUNGAN PER FAKULTAS";
        $subJudul     = "";

        try {
            if ($filterType === 'yearly') {
                $thAwal  = $request->input('tahun_awal') ?? 2020;
                $thAkhir = $request->input('tahun_akhir') ?? date('Y');
                $subJudul = "PERIODE TAHUNAN: $thAwal s.d. $thAkhir";
            } else {
                $rawTglAwal = $request->input('tanggal_awal') ?? date('Y-m-d', strtotime('-30 days'));
                $rawTglAkhir = $request->input('tanggal_akhir') ?? date('Y-m-d');

                $tglAwal  = \Carbon\Carbon::parse($rawTglAwal)->locale('id')->isoFormat('D MMMM Y');
                $tglAkhir = \Carbon\Carbon::parse($rawTglAkhir)->locale('id')->isoFormat('D MMMM Y');
                $subJudul = "PERIODE HARIAN: $tglAwal s.d. $tglAkhir";
            }
        } catch (\Throwable $e) {
            $subJudul = "PERIODE: -";
        }

        // Tambahkan info Fakultas jika difilter
        $fakultas = $request->input('fakultas');
        if ($fakultas && $fakultas !== 'semua') {
            $subJudul .= " (FAKULTAS: " . strtoupper($fakultas) . ")";
        }

        $fileName = 'laporan_kunjungan_fakultas_' . date('Ymd_His') . '.csv';

        // 3. Callback Stream CSV
        $callback = function () use ($dataCollection, $fullProdiList, $filterType, $judulLaporan, $subJudul) {
            $file = fopen('php://output', 'w');

            // BOM untuk Excel (Agar karakter aneh/utf8 terbaca benar)
            fwrite($file, "\xEF\xBB\xBF");

            // --- HEADER JUDUL ---
            fputcsv($file, [$judulLaporan], ';');
            fputcsv($file, [$subJudul], ';');
            fputcsv($file, [], ';'); // Baris kosong jeda

            // --- HEADER KOLOM ---
            fputcsv($file, ['No', 'Tanggal / Periode', 'Kode', 'Nama Prodi / Kategori', 'Jumlah Kunjungan'], ';');

            // --- ISI DATA ---
            $no = 1;
            foreach ($dataCollection as $row) {
                // Format Tanggal sesuai filter
                $tglRaw = $row['tanggal_kunjungan'];
                $tglDisplay = $tglRaw;

                try {
                    if ($tglRaw && $tglRaw !== '-' && strlen($tglRaw) > 4) {
                        if ($filterType === 'yearly') {
                            $tglDisplay = \Carbon\Carbon::parse($tglRaw)->locale('id')->isoFormat('MMMM Y');
                        } else {
                            $tglDisplay = \Carbon\Carbon::parse($tglRaw)->locale('id')->isoFormat('dddd, D MMMM Y');
                        }
                    }
                } catch (\Throwable $e) {
                    $tglDisplay = $tglRaw;
                }

                // Ambil Nama Prodi dari Mapping
                $kode = $row['kode_identifikasi'];
                $namaProdi = $fullProdiList[$kode] ?? 'Prodi Tidak Dikenal';

                fputcsv($file, [
                    $no++,
                    $tglDisplay,
                    $kode,
                    $namaProdi,
                    $row['jumlah_kunjungan_harian']
                ], ';');
            }

            fclose($file);
        };

        return response()->stream($callback, 200, [
            'Content-Type'        => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
    }

    // Function Utama: Sekarang jadi bersih dan hanya looping
    private function getProdiToFacultyMap($listprodi)
    {
        $map = [];
        $facultyMapping = $this->facultyMapping ?? [];

        foreach ($listprodi as $prodi) {
            $prodiCode = $prodi->authorised_value;
            // Panggil helper yang sudah lengkap logikanya
            $map[$prodiCode] = $this->mapCodeToFaculty($prodiCode, $facultyMapping);
        }

        // Tambahan Manual yang tidak ada di database Prodi
        $manualCodes = ['DOSEN', 'TENDIK'];
        foreach ($manualCodes as $code) {
            $map[$code] = $this->mapCodeToFaculty($code, $facultyMapping);
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



    public function kunjunganProdiTable(Request $request)
    {
        ini_set('memory_limit', '512M');

        // A. Ambil Data Referensi Prodi - OPTIMASI: Gunakan cached list
        $allProdiListObj = \App\Models\M_Auv::getCachedProdiList();

        $facultyMap = $this->getProdiToFacultyMap($allProdiListObj);
        $prodiNameMap = $allProdiListObj->pluck('lib', 'authorised_value')->toArray();

        $listProdi = [];

        foreach ($prodiNameMap as $code => $name) {
            $facultyString = $facultyMap[$code] ?? '';
            $parts = explode(' - ', $facultyString);
            $acronym = isset($parts[0]) ? trim($parts[0]) : '';

            $cleanName = $name;
            if (!empty($acronym) && str_starts_with(strtoupper($name), $acronym)) {
                $tempName = substr($name, strlen($acronym));
                $cleanName = ltrim($tempName, "/- ");
            }

            if (!empty($acronym) && $acronym !== 'Lainnya') {
                $listProdi[$code] = $acronym . ' / ' . $cleanName;
            } else {
                $listProdi[$code] = $name;
            }
        }

        // E. Tambahkan Kode Manual (Update Lengkap)
        $listProdi['DOSEN']   = 'Dosen & Pengajar';
        $listProdi['TENDIK']  = 'Tenaga Kependidikan';
        $listProdi['KSP']     = 'Kartu Sekali Kunjung';
        $listProdi['KSPMBKM'] = 'MBKM';
        $listProdi['KSPBIPA'] = 'BIPA';
        $listProdi['XA']      = 'Alumni Universitas';
        $listProdi['LB']      = 'Anggota Luar Biasa';
        $listProdi['XC']      = 'Anggota Luar Biasa (Exchange)';
        // $listProdi['VIP']  = 'Tamu VIP'; // Tidak perlu jika VIP digabung ke DOSEN

        // Setup Filter & Tanggal
        $filterType      = $request->input('filter_type', 'daily');
        $kodeProdiFilter = $request->input('prodi');
        $perPage         = $request->input('per_page', 12);
        $hasFilter       = $request->hasAny(['filter_type', 'prodi', 'tanggal_awal', 'tahun_awal']);

        $tanggalAwal  = $request->input('tanggal_awal', \Carbon\Carbon::now()->startOfMonth()->toDateString());
        $tanggalAkhir = $request->input('tanggal_akhir', \Carbon\Carbon::now()->toDateString());
        $tahunAwal    = $request->input('tahun_awal', \Carbon\Carbon::now()->year);
        $tahunAkhir   = $request->input('tahun_akhir', \Carbon\Carbon::now()->year);
        $displayPeriod = '';

        if ($filterType === 'yearly') {
            if ($tahunAwal > $tahunAkhir) $tahunAwal = $tahunAkhir;
            $start = \Carbon\Carbon::createFromDate($tahunAwal, 1, 1)->startOfDay();
            $end   = \Carbon\Carbon::createFromDate($tahunAkhir, 12, 31)->endOfDay();
            $dateFormatPHP = 'Y-m-01';
            $displayPeriod = "Tahun " . $tahunAwal . ($tahunAwal != $tahunAkhir ? " s.d. " . $tahunAkhir : "");
        } else {
            $start = \Carbon\Carbon::parse($tanggalAwal)->startOfDay();
            $end   = \Carbon\Carbon::parse($tanggalAkhir)->endOfDay();
            $dateFormatPHP = 'Y-m-d';
            $displayPeriod = "Periode " . $start->locale('id')->isoFormat('D MMMM Y') . " s.d. " . $end->locale('id')->isoFormat('D MMMM Y');
        }

        // B. Ambil Data Kunjungan (Raw Query)
        $qHistory = DB::connection('mysql')->table('visitorhistory')
            ->select('visittime', 'cardnumber')
            ->whereBetween('visittime', [$start, $end]);

        $qCorner = DB::connection('mysql')->table('visitorcorner')
            ->select('visittime', 'cardnumber')
            ->whereBetween('visittime', [$start, $end]);

        $rawVisits = $qHistory->unionAll($qCorner)->get();

        // C. Ambil Data Borrower (FIX: CASE SENSITIVITY)
        // 1. Ambil ID unik, trim, dan UPPERCASE
        $cardNumbers = $rawVisits->pluck('cardnumber')
            ->map(fn($id) => strtoupper(trim($id)))
            ->unique()
            ->values()
            ->all();

        // 2. Query ke DB Koha
        $borrowers = collect();
        if (!empty($cardNumbers)) {
            $borrowers = DB::connection('mysql2')->table('borrowers')
                ->select('cardnumber', 'categorycode')
                ->whereIn('cardnumber', $cardNumbers)
                ->get()
                // 3. Map Key Array jadi UPPERCASE
                ->mapWithKeys(function ($item) {
                    return [strtoupper(trim($item->cardnumber)) => $item->categorycode];
                });
        }

        // D. Mapping & Identifikasi
        $processedData = $rawVisits->map(function ($visit) use ($borrowers, $dateFormatPHP, $listProdi) {
            // FIX: Paksa Uppercase saat lookup
            $cardnumber = strtoupper(trim($visit->cardnumber));

            $categorycode = $borrowers[$cardnumber] ?? null;
            $cat = strtoupper($categorycode ?? '');

            // --- LOGIKA IDENTIFIKASI (SINKRON DENGAN BUILDQUERY) ---
            $kodeIdentifikasi = null;

            // 1. Cek Database
            if ($cat) {
                if (str_starts_with($cat, 'TC')) $kodeIdentifikasi = 'DOSEN';
                elseif (str_starts_with($cat, 'STAF') || str_contains($cat, 'LIB') || $cat === 'LIBRARIAN') $kodeIdentifikasi = 'TENDIK';
                elseif (str_starts_with($cat, 'DOSEN')) $kodeIdentifikasi = 'DOSEN';
            }

            // 2. Cek Pattern Manual
            if (!$kodeIdentifikasi) {
                if (str_starts_with($cardnumber, 'KSPMBKM')) $kodeIdentifikasi = 'KSPMBKM';
                elseif (str_starts_with($cardnumber, 'KSPBIPA')) $kodeIdentifikasi = 'KSPBIPA';

                // VIP -> DOSEN
                elseif (str_starts_with($cardnumber, 'VIP')) $kodeIdentifikasi = 'DOSEN';

                // XA, XC, LB -> Alumni/LB
                elseif (in_array(substr($cardnumber, 0, 2), ['XA', 'XC', 'LB'])) {
                    $kodeIdentifikasi = substr($cardnumber, 0, 2);
                } elseif (substr($cardnumber, 0, 3) === 'KSP') $kodeIdentifikasi = 'KSP';

                // Tendik (ID Pendek)
                elseif (strlen($cardnumber) <= 9 && !preg_match('/^[A-Z]\d{3}/', $cardnumber)) {
                    $kodeIdentifikasi = 'TENDIK';
                } else {
                    $kodeIdentifikasi = substr($cardnumber, 0, 4);
                }
            }

            $code = strtoupper(trim($kodeIdentifikasi));

            return [
                'tanggal_kunjungan' => \Carbon\Carbon::parse($visit->visittime)->format($dateFormatPHP),
                'kode_identifikasi' => $code,
                'nama_prodi'        => $listProdi[$code] ?? 'Prodi Tidak Dikenal',
                'kode_prodi'        => $code
            ];
        });

        // 5. FILTERING (Dropdown)
        if (!empty($kodeProdiFilter) && strtolower($kodeProdiFilter) !== 'semua') {
            $processedData = $processedData->filter(function ($item) use ($kodeProdiFilter) {
                return $item['kode_identifikasi'] === $kodeProdiFilter;
            });
        }

        // 6. GROUPING & COUNTING
        $groupedData = $processedData->groupBy(function ($item) {
            return $item['tanggal_kunjungan'] . '|' . $item['kode_identifikasi'];
        })->map(function ($group) {
            $first = $group->first();
            return (object) [
                'tanggal_kunjungan' => $first['tanggal_kunjungan'],
                'kode_identifikasi' => $first['kode_identifikasi'],
                'nama_prodi'        => $first['nama_prodi'],
                'kode_prodi'        => $first['kode_prodi'],
                'jumlah_kunjungan_harian' => $group->count()
            ];
        })->sortBy([
            ['tanggal_kunjungan', 'asc'],
            ['kode_identifikasi', 'asc']
        ]);

        $totalKeseluruhanKunjungan = $processedData->count();

        // 7. CHART DATA
        $chartGrouped = $processedData->groupBy('tanggal_kunjungan')->sortKeys();
        $chartData = $chartGrouped->map(function ($group, $key) use ($filterType) {

            $label = $key;
            return (object) [
                'label' => $label,
                'total_kunjungan' => $group->count()
            ];
        })->values();

        // 8. PAGINATION MANUAL
        $currentPage = \Illuminate\Pagination\LengthAwarePaginator::resolveCurrentPage();
        $currentItems = $groupedData->slice(($currentPage - 1) * $perPage, $perPage)->values();

        $paginatedData = new \Illuminate\Pagination\LengthAwarePaginator(
            $currentItems,
            $groupedData->count(),
            $perPage,
            $currentPage,
            ['path' => \Illuminate\Pagination\LengthAwarePaginator::resolveCurrentPath(), 'query' => $request->query()]
        );

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
            'hasFilter'      => $hasFilter
        ]);
    }

    public function getDetailPengunjung(Request $request)
    {
        // PENTING: Gunakan variabel yang konsisten
        $tanggal = $request->query('tanggal');
        $bulanTahun = $request->query('bulan');
        // Definisi variabel targetKode di sini agar bisa dipakai di filter bawah
        $targetKode = strtoupper(trim($request->query('kode_identifikasi')));
        $isExport = $request->query('export');

        if ((!$tanggal && !$bulanTahun) || !$targetKode) {
            return response()->json(['error' => 'Parameter tidak lengkap.'], 400);
        }
        if ($bulanTahun) {
            $start = \Carbon\Carbon::createFromFormat('Y-m', $bulanTahun)->startOfMonth()->toDateTimeString();
            $end   = \Carbon\Carbon::createFromFormat('Y-m', $bulanTahun)->endOfMonth()->toDateTimeString();
        } else {
            $start = \Carbon\Carbon::parse($tanggal)->startOfDay()->toDateTimeString();
            $end   = \Carbon\Carbon::parse($tanggal)->endOfDay()->toDateTimeString();
        }

        $qHistory = DB::connection('mysql')->table('visitorhistory')
            ->select('cardnumber', 'visittime')
            ->whereBetween('visittime', [$start, $end]);

        $qCorner = DB::connection('mysql')->table('visitorcorner')
            ->select('cardnumber', 'visittime')
            ->whereBetween('visittime', [$start, $end]);

        $rawVisits = $qHistory->unionAll($qCorner)->get();

        if ($rawVisits->isEmpty()) {
            return response()->json([]);
        }

        $rawCardNumbers = $rawVisits->pluck('cardnumber')->unique()->values()->all();

        $borrowers = collect();
        if (!empty($rawCardNumbers)) {
            $borrowers = DB::connection('mysql2')->table('borrowers')
                ->select('cardnumber', 'surname', 'firstname', 'categorycode')
                ->whereIn('cardnumber', $rawCardNumbers) // Gunakan ID asli
                ->get()
                // DI SINI KITA NORMALISASI: Paksa Key Array jadi UPPERCASE
                ->mapWithKeys(function ($item) {
                    return [strtoupper(trim($item->cardnumber)) => $item];
                });
        }


        $filteredVisits = $rawVisits->map(function ($visit) use ($borrowers) {
            // Normalisasi ID dari Log Kunjungan ke Uppercase
            $card = strtoupper(trim($visit->cardnumber));

            // Lookup ke Array Borrowers (yang kuncinya sudah Uppercase)
            $borrower = $borrowers[$card] ?? null;
            $catCode  = $borrower ? strtoupper($borrower->categorycode) : null;

            // Nama Lengkap
            $nama = 'Tanpa Nama';
            if ($borrower) {
                $nama = trim(($borrower->firstname ?? '') . ' ' . ($borrower->surname ?? ''));
                if (empty($nama)) $nama = 'Tanpa Nama';
            }

            $kode = null;

            if ($catCode) {
                if (str_starts_with($catCode, 'TC')) $kode = 'DOSEN';
                elseif (str_starts_with($catCode, 'STAF') || str_contains($catCode, 'LIB') || $catCode === 'LIBRARIAN') $kode = 'TENDIK';
                elseif (str_starts_with($catCode, 'DOSEN')) $kode = 'DOSEN';
            }

            // 2. Cek Pattern Manual
            if (!$kode) {
                if (str_starts_with($card, 'KSPMBKM')) $kode = 'KSPMBKM';
                elseif (str_starts_with($card, 'KSPBIPA')) $kode = 'KSPBIPA';
                elseif (str_starts_with($card, 'VIP')) $kode = 'DOSEN';
                elseif (in_array(substr($card, 0, 2), ['XA', 'XC', 'LB'])) {
                    $kode = substr($card, 0, 2);
                } elseif (substr($card, 0, 3) === 'KSP') $kode = 'KSP';
                elseif (strlen($card) <= 9 && !preg_match('/^[A-Z]\d{3}/', $card)) {
                    $kode = 'TENDIK';
                } else {
                    $kode = substr($card, 0, 4);
                }
            }

            return [
                'cardnumber' => $visit->cardnumber,
                'nama'       => $nama,
                'kode_ident' => strtoupper($kode)
            ];
        })->filter(function ($item) use ($targetKode) {
            // DISINI TIDAK AKAN ERROR LAGI KARENA $targetKode SUDAH DIDEFINISIKAN
            return $item['kode_ident'] === $targetKode;
        });

        $finalData = $filteredVisits->groupBy('cardnumber')->map(function ($group) {
            $first = $group->first();
            return [
                'cardnumber'  => $first['cardnumber'],
                'nama'        => $first['nama'],
                'visit_count' => $group->count()
            ];
        })->sortByDesc('visit_count')->values();

        if ($isExport) {
            return response()->json($finalData);
        }

        // Pagination Manual
        $perPage = $request->input('per_page', 10);
        $currentPage = \Illuminate\Pagination\LengthAwarePaginator::resolveCurrentPage();
        $currentItems = $finalData->slice(($currentPage - 1) * $perPage, $perPage)->values();

        $paginated = new \Illuminate\Pagination\LengthAwarePaginator(
            $currentItems,
            $finalData->count(),
            $perPage,
            $currentPage,
            ['path' => \Illuminate\Pagination\LengthAwarePaginator::resolveCurrentPath(), 'query' => $request->query()]
        );

        return response()->json($paginated);
    }


    // public function getProdiExportData(Request $request)
    // {
    //     ini_set('memory_limit', '1024M');
    //     set_time_limit(300);

    //     $filterType = $request->input('filter_type', 'daily');
    //     $kodeProdiFilter = $request->input('prodi');

    //     if ($filterType === 'yearly') {
    //         $tahunAwal = $request->input('tahun_awal', Carbon::now()->year);
    //         $tahunAkhir = $request->input('tahun_akhir', Carbon::now()->year);
    //         if ($tahunAwal > $tahunAkhir) $tahunAwal = $tahunAkhir;

    //         $start = Carbon::createFromDate($tahunAwal, 1, 1)->startOfDay();
    //         $end = Carbon::createFromDate($tahunAkhir, 12, 31)->endOfDay();

    //         $periodeDisplay = "Tahun " . $tahunAwal . ($tahunAwal != $tahunAkhir ? " s/d " . $tahunAkhir : "");
    //         $sqlDateFormat = "%Y-%m-01"; // Format SQL
    //     } else {
    //         $start = Carbon::parse($request->input('tanggal_awal', Carbon::now()->startOfMonth()->toDateString()))->startOfDay();
    //         $end = Carbon::parse($request->input('tanggal_akhir', Carbon::now()->toDateString()))->endOfDay();

    //         $periodeDisplay = "Periode " . $start->locale('id')->isoFormat('D MMMM Y') . " s.d. " . $end->locale('id')->isoFormat('D MMMM Y');
    //         $sqlDateFormat = "%Y-%m-%d"; // Format SQL
    //     }

    //     $queryStr = "
    //     DATE_FORMAT(visittime, '$sqlDateFormat') as tgl_kunjungan,
    //     cardnumber,
    //     COUNT(*) as total_hits
    // ";

    //     $historyData = DB::connection('mysql')->table('visitorhistory')
    //         ->selectRaw($queryStr)
    //         ->whereBetween('visittime', [$start, $end])
    //         ->groupByRaw("DATE_FORMAT(visittime, '$sqlDateFormat'), cardnumber")
    //         ->get();


    //     $cornerData = DB::connection('mysql')->table('visitorcorner')
    //         ->selectRaw($queryStr)
    //         ->whereBetween('visittime', [$start, $end])
    //         ->groupByRaw("DATE_FORMAT(visittime, '$sqlDateFormat'), cardnumber")
    //         ->get();


    //     $mergedData = $historyData->merge($cornerData);

    //     if ($mergedData->isEmpty()) {
    //         return response()->stream(function () {}, 200, ['Content-Type' => 'text/csv']);
    //     }

    //     $uniqueCards = $mergedData->pluck('cardnumber')->unique()->values()->all();

    //     $borrowers = DB::connection('mysql2')->table('borrowers')
    //         ->select('cardnumber', 'categorycode')
    //         ->whereIn('cardnumber', $uniqueCards)
    //         ->pluck('categorycode', 'cardnumber')
    //         ->toArray();

    //     $finalReport = [];

    //     foreach ($mergedData as $row) {
    //         $card = $row->cardnumber;
    //         $tgl  = $row->tgl_kunjungan;
    //         $hits = $row->total_hits;

    //         $catCode = $borrowers[$card] ?? null;

    //         $kodeIdentifikasi = substr($card, 0, 4);

    //         if ($catCode && str_starts_with($catCode, 'TC')) {
    //             $kodeIdentifikasi = 'DOSEN';
    //         } elseif ($catCode && (str_starts_with($catCode, 'STAF') || $catCode === 'LIBRARIAN')) {
    //             $kodeIdentifikasi = 'TENDIK';
    //         } elseif (str_starts_with($card, 'KSPMBKM')) {
    //             $kodeIdentifikasi = 'KSPMBKM';
    //         } elseif (str_starts_with($card, 'KSPBIPA')) {
    //             $kodeIdentifikasi = 'KSPBIPA';
    //         } elseif (in_array(substr($card, 0, 2), ['XA', 'XC', 'LB'])) {
    //             $kodeIdentifikasi = substr($card, 0, 2);
    //         } elseif (substr($card, 0, 3) === 'KSP') {
    //             $kodeIdentifikasi = 'KSP';
    //         }

    //         $key = $tgl . '|' . strtoupper(trim($kodeIdentifikasi));

    //         if (!isset($finalReport[$key])) {
    //             $finalReport[$key] = [
    //                 'tanggal' => $tgl,
    //                 'kode' => strtoupper(trim($kodeIdentifikasi)),
    //                 'jumlah' => 0
    //             ];
    //         }

    //         // Kita jumlahkan akumulasi dari SQL
    //         $finalReport[$key]['jumlah'] += $hits;
    //     }

    //     $dataCollection = collect(array_values($finalReport));

    //     // Filter Prodi
    //     if ($kodeProdiFilter && strtolower($kodeProdiFilter) !== 'semua') {
    //         $filterTarget = strtoupper($kodeProdiFilter);
    //         $dataCollection = $dataCollection->filter(function ($item) use ($filterTarget) {
    //             return $item['kode'] === $filterTarget;
    //         });
    //     }

    //     // Sorting
    //     $dataCollection = $dataCollection->sortBy([
    //         ['tanggal', 'asc'],
    //         ['kode', 'asc']
    //     ]);

    //     $grandTotal = $dataCollection->sum('jumlah');

    //     $allProdiListObj = M_Auv::where('category', 'PRODI')->get();
    //     $facultyMap = $this->getProdiToFacultyMap($allProdiListObj);
    //     $prodiNameMap = $allProdiListObj->pluck('lib', 'authorised_value')->toArray();

    //     $fullProdiList = [];
    //     foreach ($prodiNameMap as $code => $name) {
    //         $facultyString = $facultyMap[$code] ?? '';
    //         $parts = explode(' - ', $facultyString);
    //         $acronym = isset($parts[0]) ? trim($parts[0]) : '';

    //         $cleanName = $name;
    //         if (!empty($acronym) && str_starts_with(strtoupper($name), $acronym)) {
    //             $tempName = substr($name, strlen($acronym));
    //             $cleanName = ltrim($tempName, "/- ");
    //         }

    //         if (!empty($acronym) && $acronym !== 'Lainnya') {
    //             $fullProdiList[$code] = $acronym . ' / ' . $cleanName;
    //         } else {
    //             $fullProdiList[$code] = $name;
    //         }
    //     }
    //     // Tambahan Manual
    //     $fullProdiList['DOSEN']   = 'Dosen & Pengajar';
    //     $fullProdiList['TENDIK']  = 'Tenaga Kependidikan';
    //     $fullProdiList['KSP']     = 'Kartu Sekali Kunjung';
    //     $fullProdiList['KSPMBKM'] = 'MBKM';
    //     $fullProdiList['KSPBIPA'] = 'BIPA';
    //     $fullProdiList['XA']      = 'Alumni';
    //     $fullProdiList['LB']      = 'Anggota Luar Biasa';

    //     $namaProdiFilter = $fullProdiList[strtoupper($kodeProdiFilter)] ?? 'Seluruh Kategori';

    //     $headers = [
    //         'Content-Type' => 'text/csv; charset=UTF-8',
    //         'Content-Disposition' => 'attachment; filename="laporan_kunjungan.csv"',
    //     ];

    //     $callback = function () use ($dataCollection, $filterType, $namaProdiFilter, $periodeDisplay, $grandTotal, $fullProdiList) {
    //         if (ob_get_level()) {
    //             ob_end_clean();
    //         }
    //         $file = fopen('php://output', 'w');
    //         fputs($file, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM

    //         fputcsv($file, ["Laporan Statistik Kunjungan: " . $namaProdiFilter], ';');
    //         fputcsv($file, ["Periode: " . $periodeDisplay], ';');
    //         fputcsv($file, [''], ';');

    //         $headers = ['Tanggal / Bulan', 'Kode Identifikasi', 'Nama Prodi/Kategori', 'Jumlah Kunjungan'];
    //         fputcsv($file, $headers, ';');

    //         foreach ($dataCollection as $row) {
    //             $tanggal = ($filterType === 'yearly') ?
    //                 Carbon::parse($row['tanggal'])->locale('id')->isoFormat('MMMM Y') :
    //                 Carbon::parse($row['tanggal'])->locale('id')->isoFormat('dddd, D MMMM Y');

    //             $namaProdi = $fullProdiList[$row['kode']] ?? 'Prodi Tidak Dikenal';

    //             fputcsv($file, [
    //                 $tanggal,
    //                 $row['kode'],
    //                 $namaProdi,
    //                 $row['jumlah']
    //             ], ';');
    //         }

    //         fputcsv($file, ['', '', 'TOTAL', $grandTotal], ';');
    //         fclose($file);
    //     };

    //     return response()->stream($callback, 200, $headers);
    // }

    public function getProdiExportData(Request $request)
    {
        ini_set('memory_limit', '1024M');
        set_time_limit(300);

        $filterType = $request->input('filter_type', 'daily');
        $kodeProdiFilter = $request->input('prodi');

        // Setup Tanggal & SQL Format
        if ($filterType === 'yearly') {
            $tahunAwal = $request->input('tahun_awal', \Carbon\Carbon::now()->year);
            $tahunAkhir = $request->input('tahun_akhir', \Carbon\Carbon::now()->year);
            if ($tahunAwal > $tahunAkhir) $tahunAwal = $tahunAkhir;

            $start = \Carbon\Carbon::createFromDate($tahunAwal, 1, 1)->startOfDay();
            $end = \Carbon\Carbon::createFromDate($tahunAkhir, 12, 31)->endOfDay();

            $periodeDisplay = "Tahun " . $tahunAwal . ($tahunAwal != $tahunAkhir ? " s/d " . $tahunAkhir : "");
            $sqlDateFormat = "%Y-%m-01";
        } else {
            $start = \Carbon\Carbon::parse($request->input('tanggal_awal', \Carbon\Carbon::now()->startOfMonth()->toDateString()))->startOfDay();
            $end = \Carbon\Carbon::parse($request->input('tanggal_akhir', \Carbon\Carbon::now()->toDateString()))->endOfDay();

            $periodeDisplay = "Periode " . $start->locale('id')->isoFormat('D MMMM Y') . " s.d. " . $end->locale('id')->isoFormat('D MMMM Y');
            $sqlDateFormat = "%Y-%m-%d";
        }

        // Query Utama
        $queryStr = "
            DATE_FORMAT(visittime, '$sqlDateFormat') as tgl_kunjungan,
            cardnumber,
            COUNT(*) as total_hits
        ";

        $historyData = DB::connection('mysql')->table('visitorhistory')
            ->selectRaw($queryStr)
            ->whereBetween('visittime', [$start, $end])
            ->groupByRaw("DATE_FORMAT(visittime, '$sqlDateFormat'), cardnumber")
            ->get();

        $cornerData = DB::connection('mysql')->table('visitorcorner')
            ->selectRaw($queryStr)
            ->whereBetween('visittime', [$start, $end])
            ->groupByRaw("DATE_FORMAT(visittime, '$sqlDateFormat'), cardnumber")
            ->get();

        $mergedData = $historyData->merge($cornerData);

        if ($mergedData->isEmpty()) {
            return response()->stream(function () {}, 200, ['Content-Type' => 'text/csv']);
        }

        // Ambil Data Prodi (Borrowers)
        $uniqueCards = $mergedData->pluck('cardnumber')->unique()->values()->all();
        $borrowers = DB::connection('mysql2')->table('borrowers')
            ->select('cardnumber', 'categorycode')
            ->whereIn('cardnumber', $uniqueCards)
            ->pluck('categorycode', 'cardnumber')
            ->toArray();

        // Proses Data
        $finalReport = [];
        foreach ($mergedData as $row) {
            $card = $row->cardnumber;
            $tgl  = $row->tgl_kunjungan;
            $hits = $row->total_hits;

            $catCode = $borrowers[$card] ?? null;
            $kodeIdentifikasi = substr($card, 0, 4);

            // Logika Identifikasi yang sama dengan function tabel
            if ($catCode && str_starts_with($catCode, 'TC')) {
                $kodeIdentifikasi = 'DOSEN';
            } elseif ($catCode && (str_starts_with($catCode, 'STAF') || $catCode === 'LIBRARIAN')) {
                $kodeIdentifikasi = 'TENDIK';
            } elseif (str_starts_with($card, 'KSPMBKM')) {
                $kodeIdentifikasi = 'KSPMBKM';
            } elseif (str_starts_with($card, 'KSPBIPA')) {
                $kodeIdentifikasi = 'KSPBIPA';
            } elseif (in_array(substr($card, 0, 2), ['XA', 'XC', 'LB'])) {
                $kodeIdentifikasi = substr($card, 0, 2);
            } elseif (substr($card, 0, 3) === 'KSP') {
                $kodeIdentifikasi = 'KSP';
            }

            $key = $tgl . '|' . strtoupper(trim($kodeIdentifikasi));

            if (!isset($finalReport[$key])) {
                $finalReport[$key] = [
                    'tanggal' => $tgl,
                    'kode' => strtoupper(trim($kodeIdentifikasi)),
                    'jumlah' => 0
                ];
            }
            $finalReport[$key]['jumlah'] += $hits;
        }

        $dataCollection = collect(array_values($finalReport));

        // Filter Prodi di level Collection
        if ($kodeProdiFilter && strtolower($kodeProdiFilter) !== 'semua') {
            $filterTarget = strtoupper($kodeProdiFilter);
            $dataCollection = $dataCollection->filter(function ($item) use ($filterTarget) {
                return $item['kode'] === $filterTarget;
            });
        }

        $dataCollection = $dataCollection->sortBy([
            ['tanggal', 'asc'],
            ['kode', 'asc']
        ]);

        $grandTotal = $dataCollection->sum('jumlah');

        // Build Full Prodi List untuk Label
        $allProdiListObj = \App\Models\M_Auv::where('category', 'PRODI')->get();
        $facultyMap = $this->getProdiToFacultyMap($allProdiListObj);
        $prodiNameMap = $allProdiListObj->pluck('lib', 'authorised_value')->toArray();

        $fullProdiList = [];
        foreach ($prodiNameMap as $code => $name) {
            $facultyString = $facultyMap[$code] ?? '';
            $parts = explode(' - ', $facultyString);
            $acronym = isset($parts[0]) ? trim($parts[0]) : '';

            $cleanName = $name;
            if (!empty($acronym) && str_starts_with(strtoupper($name), $acronym)) {
                $tempName = substr($name, strlen($acronym));
                $cleanName = ltrim($tempName, "/- ");
            }

            if (!empty($acronym) && $acronym !== 'Lainnya') {
                $fullProdiList[$code] = $acronym . ' / ' . $cleanName;
            } else {
                $fullProdiList[$code] = $name;
            }
        }

        $fullProdiList['DOSEN']   = 'Dosen & Pengajar';
        $fullProdiList['TENDIK']  = 'Tenaga Kependidikan';
        $fullProdiList['KSP']     = 'Kartu Sekali Kunjung';
        $fullProdiList['KSPMBKM'] = 'MBKM';
        $fullProdiList['KSPBIPA'] = 'BIPA';
        $fullProdiList['XA']      = 'Alumni';
        $fullProdiList['LB']      = 'Anggota Luar Biasa';

        // --- PEMBUATAN NAMA FILE DINAMIS ---
        $namaProdiFilter = $fullProdiList[strtoupper($kodeProdiFilter)] ?? 'Seluruh Kategori';

        // 1. Bersihkan Nama Prodi (Hanya huruf angka dan underscore)
        $cleanProdiName = preg_replace('/[^A-Za-z0-9]+/', '_', $namaProdiFilter);
        $cleanProdiName = trim($cleanProdiName, '_');

        // 2. Buat String Periode
        $filenamePeriod = '';
        if ($filterType === 'yearly') {
            $filenamePeriod = 'Tahun_' . $tahunAwal;
            if ($tahunAwal != $tahunAkhir) $filenamePeriod .= '-' . $tahunAkhir;
        } else {
            // Gunakan format YYYY-MM-DD
            $filenamePeriod = \Carbon\Carbon::parse($start)->format('Y-m-d') . '_sd_' . \Carbon\Carbon::parse($end)->format('Y-m-d');
        }

        // 3. Nama File Akhir
        $csvFilename = "Laporan_Kunjungan_{$cleanProdiName}_{$filenamePeriod}.csv";

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$csvFilename}\"",
        ];

        // --- GENERATE CSV STREAM ---
        $callback = function () use ($dataCollection, $filterType, $namaProdiFilter, $periodeDisplay, $grandTotal, $fullProdiList) {
            if (ob_get_level()) ob_end_clean();
            $file = fopen('php://output', 'w');
            fputs($file, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM UTF-8

            // Judul Internal
            fputcsv($file, ["Laporan Statistik Kunjungan: " . $namaProdiFilter], ';');
            fputcsv($file, ["Periode: " . $periodeDisplay], ';');
            fputcsv($file, [''], ';');

            // Header Tabel
            $headers = ['Tanggal / Bulan', 'Kode Identifikasi', 'Nama Prodi/Kategori', 'Jumlah Kunjungan'];
            fputcsv($file, $headers, ';');

            foreach ($dataCollection as $row) {
                $tanggal = ($filterType === 'yearly') ?
                    \Carbon\Carbon::parse($row['tanggal'])->locale('id')->isoFormat('MMMM Y') :
                    \Carbon\Carbon::parse($row['tanggal'])->locale('id')->isoFormat('dddd, D MMMM Y');

                $namaProdi = $fullProdiList[$row['kode']] ?? 'Prodi Tidak Dikenal';

                fputcsv($file, [
                    $tanggal,
                    $row['kode'],
                    $namaProdi,
                    $row['jumlah']
                ], ';');
            }

            fputcsv($file, ['', '', 'TOTAL', $grandTotal], ';');
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
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

            $data = (clone $baseQuery)->paginate($perPage);
            $chartData = (clone $baseQuery)->get();
            $totalKeseluruhanKunjungan = $chartData->sum('jumlah_kunjungan_harian');
            $data->appends($request->all());
        }

        return view('pages.kunjungan.tanggalTable', compact('data', 'totalKeseluruhanKunjungan', 'filterType', 'tanggalAwal', 'tanggalAkhir', 'tahunAwal', 'tahunAkhir', 'chartData', 'perPage', 'hasFilter'));
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
            'visitorhistory.cardnumber',
            'koha.borrowers.surname',
            DB::raw('COUNT(visitorhistory.id) as visit_count')
        )
            ->join('koha.borrowers', 'visitorhistory.cardnumber', '=', 'koha.borrowers.cardnumber')
            ->whereBetween('visittime', [$startDate, $endDate])
            ->groupBy('visitorhistory.cardnumber', 'koha.borrowers.surname')
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
            'visitorhistory.cardnumber',
            'koha.borrowers.surname',
            DB::raw('COUNT(visitorhistory.id) as visit_count')
        )
            ->join('koha.borrowers', 'visitorhistory.cardnumber', '=', 'koha.borrowers.cardnumber')
            ->whereBetween('visittime', [$startDate, $endDate])
            ->groupBy('visitorhistory.cardnumber', 'koha.borrowers.surname')
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
            $cardnumber = trim(strtolower($cardnumber));

            $fullBorrowerDetails = DB::connection('mysql2')->table('koha.borrowers')
                ->select('borrowernumber', 'cardnumber', 'firstname', 'surname', 'email', 'phone', 'categorycode')
                ->where('cardnumber', $cardnumber)
                ->orWhere(DB::raw('TRIM(LOWER(cardnumber))'), $cardnumber)
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
                    $queryHistory->whereYear('visittime', $tahun);
                    $queryCorner->whereYear('visittime', $tahun);
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

            $lokasiMapping = [
                'sni' => 'SNI Corner',
                'bi' => 'Bank Indonesia Corner',
                'mc' => 'Muhammadiyah Corner',
                'pusat' => 'Perpustakaan Pusat',
                'pasca' => 'Perpustakaan Pascasarjana',
                'fk' => 'Perpustakaan Kedokteran',
                'ref' => 'Referensi Perpustakaan Pusat',
            ];

            // 2. Query History (Gunakan whereYear & whereMonth)
            $queryHistory = DB::connection('mysql')->table('visitorhistory')
                ->select('visittime as visit_date', DB::raw("IFNULL(location, 'pusat') as visit_location"))
                ->where('cardnumber', $cardnumber)
                ->whereYear('visittime', $tahun) // OPTIMASI: Pakai Index
                ->whereMonth('visittime', $bulan); // OPTIMASI: Pakai Index

            // 3. Query Corner (Gunakan whereYear & whereMonth)
            $queryCorner = DB::connection('mysql')->table('visitorcorner')
                ->select('visittime as visit_date', 'notes as visit_location')
                ->where('cardnumber', $cardnumber)
                ->whereYear('visittime', $tahun) // OPTIMASI: Pakai Index
                ->whereMonth('visittime', $bulan); // OPTIMASI: Pakai Index

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

        $html = view('pages.kunjungan.laporan_kehadiran_pdf', [
            'fullBorrowerDetails' => $fullBorrowerDetails,
            'dataKunjungan'       => $dataKunjungan,
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

        $historyLokasi = DB::connection('mysql')->table('visitorhistory')->select(DB::raw("IFNULL(location, 'pusat') as lokasi_kunjungan"))->distinct();
        $cornerLokasi = DB::connection('mysql')->table('visitorcorner')->select(DB::raw("COALESCE(NULLIF(notes, ''), 'pusat') as lokasi_kunjungan"))->distinct();
        $lokasiOptions = $historyLokasi->get()->merge($cornerLokasi->get())->pluck('lokasi_kunjungan')->unique()->sort()->values();

        if ($request->has('filter_type')) {
            $groupByFormat = $filterType == 'yearly' ? 'LEFT(visittime, 7)' : 'DATE(visittime)';

            $historyQuery = DB::connection('mysql')->table('visitorhistory')
                ->select(DB::raw("{$groupByFormat} as periode"), DB::raw("COUNT(*) as jumlah"))
                ->groupBy('periode');

            $cornerQuery = DB::connection('mysql')->table('visitorcorner')
                ->select(DB::raw("{$groupByFormat} as periode"), DB::raw("COUNT(*) as jumlah"))
                ->groupBy('periode');

            if ($filterType == 'yearly') {
                $historyQuery->whereBetween(DB::raw('YEAR(visittime)'), [$startYear, $endYear]);
                $cornerQuery->whereBetween(DB::raw('YEAR(visittime)'), [$startYear, $endYear]);
            } else { // date_range
                $historyQuery->whereBetween('visittime', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
                $cornerQuery->whereBetween('visittime', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
            }

            if ($selectedLokasi) {
                $dbLokasi = array_search($selectedLokasi, $lokasiMapping) ?: $selectedLokasi;
                $historyQuery->where(DB::raw("IFNULL(location, 'pusat')"), $dbLokasi);
                $cornerQuery->where(DB::raw("COALESCE(NULLIF(notes, ''), 'pusat')"), $dbLokasi);
            }

            $allResults = $historyQuery->get()->merge($cornerQuery->get());

            $allSummarizedResults = $allResults->groupBy('periode')->map(function ($item, $key) {
                return (object)['periode' => $key, 'jumlah' => $item->sum('jumlah')];
            })->sortBy('periode')->values();

            $totalKunjungan = $allSummarizedResults->sum('jumlah');
            $jumlahPeriode = $allSummarizedResults->count();
            $rerataKunjungan = ($jumlahPeriode > 0) ? ($totalKunjungan / $jumlahPeriode) : 0;

            $maxKunjungan = $allSummarizedResults->max('jumlah') ?? 0;
            $chartData = $allSummarizedResults->pluck('jumlah', 'periode');

            $currentPage = Paginator::resolveCurrentPage('page');
            $perPage = 12; // Jumlah item per halaman sesuai permintaan

            $currentPageItems = $allSummarizedResults->slice(($currentPage - 1) * $perPage, $perPage)->all();

            // Buat instance Paginator secara manual
            $dataHasil = new LengthAwarePaginator(
                $currentPageItems,
                count($allSummarizedResults),
                $perPage,
                $currentPage,
                ['path' => Paginator::resolveCurrentPath()]
            );

            $dataHasil->appends($request->all());

            // Query Top Lokasi yang Dinamis
            $historyLokasiQuery = DB::connection('mysql')->table('visitorhistory')
                ->select(DB::raw("IFNULL(location, 'pusat') as lokasi"), DB::raw("COUNT(*) as jumlah"))
                ->groupBy('lokasi');

            $cornerLokasiQuery = DB::connection('mysql')->table('visitorcorner')
                ->select(DB::raw("COALESCE(NULLIF(notes, ''), 'pusat') as lokasi"), DB::raw("COUNT(*) as jumlah"))
                ->groupBy('lokasi');

            // Terapkan filter waktu yang SESUAI dengan filter utama
            if ($filterType == 'yearly') {
                $historyLokasiQuery->whereBetween(DB::raw('YEAR(visittime)'), [$startYear, $endYear]);
                $cornerLokasiQuery->whereBetween(DB::raw('YEAR(visittime)'), [$startYear, $endYear]);
            } else { // date_range
                $historyLokasiQuery->whereBetween('visittime', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
                $cornerLokasiQuery->whereBetween('visittime', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
            }

            $allLokasiResults = $historyLokasiQuery->get()->merge($cornerLokasiQuery->get());
            $topLokasi = $allLokasiResults
                ->whereNotIn('lokasi', ['sni', 'bi', 'mc', 'ref'])
                ->groupBy('lokasi')->map(fn($item, $key) => $item->sum('jumlah'))->sortDesc()->take(3);
        }

        // Kirim variabel 'maxKunjungan' bukan 'maxKunjunganBulanan'
        return view('pages.kunjungan.kunjungan_gabungan', compact('filterType', 'rerataKunjungan', 'startYear', 'endYear', 'startDate', 'endDate', 'selectedLokasi', 'dataHasil', 'lokasiMapping', 'lokasiOptions', 'chartData', 'totalKunjungan', 'maxKunjungan', 'topLokasi'));
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

        if ($filterType == 'yearly') {
            $fileName = "Rekap_Bulanan_{$lokasiText}_{$startYear}-{$endYear}.csv";
            $historyQuery->whereBetween(DB::raw('YEAR(visittime)'), [$startYear, $endYear]);
            $cornerQuery->whereBetween(DB::raw('YEAR(visittime)'), [$startYear, $endYear]);
        } else { // date_range
            $fileName = "Rekap_Harian_{$lokasiText}_{$startDate}_sd_{$endDate}.csv";
            $historyQuery->whereBetween('visittime', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
            $cornerQuery->whereBetween('visittime', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        }

        if ($selectedLokasi) {
            $dbLokasi = array_search($selectedLokasi, $lokasiMapping) ?: $selectedLokasi;
            $historyQuery->where(DB::raw("IFNULL(location, 'pusat')"), $dbLokasi);
            $cornerQuery->where(DB::raw("COALESCE(NULLIF(notes, ''), 'pusat')"), $dbLokasi);
        }

        $callback = function () use ($historyQuery, $cornerQuery, $filterType, $startYear, $endYear, $startDate, $endDate, $selectedLokasi, $lokasiMapping) {

            $file = fopen('php://output', 'w');

            // Menambahkan Byte Order Mark (BOM) untuk memastikan Excel membaca UTF-8 dengan benar
            fputs($file, $bom = (chr(0xEF) . chr(0xBB) . chr(0xBF)));

            // --- Bagian Header Laporan ---

            // Judul Laporan
            fputcsv($file, ['Laporan Rekapitulasi Kunjungan Perpustakaan'], ';');

            // Periode
            if ($filterType == 'yearly') {
                fputcsv($file, ["Periode Tahunan:", "{$startYear} - {$endYear}"], ';');
                $headerPeriode = 'Bulan';
            } else { // date_range
                // MENGGUNAKAN locale('id') untuk format bahasa Indonesia
                $start = Carbon::parse($startDate)->locale('id')->isoFormat('D MMMM YYYY');
                $end = Carbon::parse($endDate)->locale('id')->isoFormat('D MMMM YYYY');
                fputcsv($file, ["Periode Harian:", "{$start} - {$end}"], ';');
                $headerPeriode = 'Tanggal';
            }

            // Lokasi
            $displayLokasi = $selectedLokasi ? ($lokasiMapping[$selectedLokasi] ?? $selectedLokasi) : 'Semua Lokasi';
            fputcsv($file, ["Lokasi:", $displayLokasi], ';');

            fputcsv($file, [], ';'); // Baris kosong

            // --- Eksekusi Query dan Proses Data ---

            $allResults = $historyQuery->get()->merge($cornerQuery->get());
            $dataToExport = $allResults->groupBy('periode')->map(fn($item, $key) => (object)['periode' => $key, 'jumlah' => $item->sum('jumlah')])->sortBy('periode')->values();

            // Tulis header tabel
            fputcsv($file, [$headerPeriode, 'Jumlah Kunjungan'], ';');

            // --- Tulis Baris Data ---

            foreach ($dataToExport as $row) {
                // MENGGUNAKAN locale('id') untuk format bahasa Indonesia
                $formattedPeriode = $filterType == 'yearly'
                    ? Carbon::parse($row->periode)->locale('id')->isoFormat('MMMM YYYY')
                    : Carbon::parse($row->periode)->locale('id')->isoFormat('dddd, D MMMM YYYY');
                fputcsv($file, [$formattedPeriode, $row->jumlah], ';');
            }

            // --- Total ---

            fputcsv($file, [], ';'); // Baris kosong
            fputcsv($file, ['Total Keseluruhan', $dataToExport->sum('jumlah')], ';');

            fclose($file);
        };

        $headers = [
            "Content-type"        => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename=\"$fileName\"",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        return response()->stream($callback, 200, $headers);
    }
}
