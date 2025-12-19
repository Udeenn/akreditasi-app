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
        $allProdiListObj = M_Auv::where('category', 'PRODI')->get();
        $prodiToFacultyMap = $this->getProdiToFacultyMap($allProdiListObj);
        $listFakultas = collect($prodiToFacultyMap)->unique()->sort()->values()->all();

        $tanggalAwal = Carbon::now()->startOfMonth()->toDateString();
        $tanggalAkhir = Carbon::now()->toDateString();
        $tahunAwal = Carbon::now()->year;
        $tahunAkhir = Carbon::now()->year;

        // 2. REQUEST AJAX YAJRA (Tabel)
        if ($request->ajax()) {
            return $this->getDataTables($request);
        }

        $hasFilter = $request->hasAny(['filter_type', 'fakultas', 'tanggal_awal', 'tahun_awal']);
        $filterType = $request->input('filter_type', 'daily'); // Default filter type

        $chartData = [];
        $totalKeseluruhanKunjungan = 0;
        $displayPeriod = '';

        // 4. LOAD CHART DATA (Hanya jika ada filter)
        if ($hasFilter) {
            // Hitung data chart
            $chartDataRaw = $this->buildQuery($request, true);
            $chartData = $chartDataRaw['chart'];
            $totalKeseluruhanKunjungan = $chartDataRaw['total'];

            // Setup Display Period
            if ($filterType === 'yearly') {
                $thAwal = $request->input('tahun_awal', Carbon::now()->year);
                $thAkhir = $request->input('tahun_akhir', Carbon::now()->year);
                $displayPeriod = "Tahun " . $thAwal . ($thAwal != $thAkhir ? " s.d. " . $thAkhir : "");
            } else {
                $tglAwal = $request->input('tanggal_awal', Carbon::now()->startOfMonth()->toDateString());
                $tglAkhir = $request->input('tanggal_akhir', Carbon::now()->toDateString());
                $displayPeriod = "Periode " . Carbon::parse($tglAwal)->locale('id')->isoFormat('D MMMM Y') . " s.d. " . Carbon::parse($tglAkhir)->locale('id')->isoFormat('D MMMM Y');
            }
        }

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
            'displayPeriod'
        ));
    }

    private function buildQuery($request, $forChart = false)
    {
        $filterType     = $request->input('filter_type', 'daily');
        $fakultasFilter = $request->input('fakultas');
        $searchKeyword  = $request->input('search_manual');

        // ---------------------------------------------------------------------
        // STEP 1: SETUP TANGGAL
        // ---------------------------------------------------------------------
        if ($filterType === 'yearly') {
            $thAwal = $request->input('tahun_awal', \Carbon\Carbon::now()->year);
            $thAkhir = $request->input('tahun_akhir', \Carbon\Carbon::now()->year);
            if ($thAwal > $thAkhir) $thAwal = $thAkhir;
            $start = \Carbon\Carbon::createFromDate($thAwal, 1, 1)->startOfDay();
            $end = \Carbon\Carbon::createFromDate($thAkhir, 12, 31)->endOfDay();
            $dateFormatPHP = 'Y-m-01';
        } else {
            $start = \Carbon\Carbon::parse($request->input('tanggal_awal', \Carbon\Carbon::now()->startOfMonth()->toDateString()))->startOfDay();
            $end = \Carbon\Carbon::parse($request->input('tanggal_akhir', \Carbon\Carbon::now()->toDateString()))->endOfDay();
            $dateFormatPHP = 'Y-m-d';
        }

        // ---------------------------------------------------------------------
        // STEP 2: AMBIL DATA KUNJUNGAN (DB SATELIT / MYSQL)
        // ---------------------------------------------------------------------
        $qHistory = DB::connection('mysql')->table('visitorhistory')
            ->select('visittime', 'cardnumber')
            ->whereBetween('visittime', [$start, $end]);

        $qCorner = DB::connection('mysql')->table('visitorcorner')
            ->select('visittime', 'cardnumber')
            ->whereBetween('visittime', [$start, $end]);

        $rawVisits = $qHistory->unionAll($qCorner)->get();

        // ---------------------------------------------------------------------
        // STEP 3: AMBIL DATA BORROWER (DB KOHA / MYSQL2)
        // ---------------------------------------------------------------------
        $cardNumbers = $rawVisits->pluck('cardnumber')->unique()->values()->all();

        $borrowers = DB::connection('mysql2')->table('borrowers')
            ->select('cardnumber', 'categorycode')
            ->whereIn('cardnumber', $cardNumbers)
            ->get()
            ->pluck('categorycode', 'cardnumber');

        // ---------------------------------------------------------------------
        // STEP 4: MAPPING DATA & LOGIKA IDENTIFIKASI
        // ---------------------------------------------------------------------
        $processedData = $rawVisits->map(function ($visit) use ($borrowers, $dateFormatPHP) {
            $cardnumber = $visit->cardnumber;
            $visittime  = $visit->visittime;
            $categorycode = $borrowers[$cardnumber] ?? null;

            // Logika Identifikasi
            $kodeIdentifikasi = substr($cardnumber, 0, 4); // Default

            if ($categorycode && str_starts_with($categorycode, 'TC')) {
                $kodeIdentifikasi = 'DOSEN';
            } elseif ($categorycode && (str_starts_with($categorycode, 'STAF') || $categorycode === 'LIBRARIAN')) {
                $kodeIdentifikasi = 'TENDIK';
            } elseif (str_starts_with($cardnumber, 'KSPMBKM')) {
                $kodeIdentifikasi = 'KSPMBKM';
            } elseif (str_starts_with($cardnumber, 'KSPBIPA')) {
                $kodeIdentifikasi = 'KSPBIPA';
            } elseif (in_array(substr($cardnumber, 0, 2), ['XA', 'XC', 'LB'])) {
                $kodeIdentifikasi = substr($cardnumber, 0, 2);
            } elseif (substr($cardnumber, 0, 3) === 'KSP') {
                $kodeIdentifikasi = 'KSP';
            }

            return [
                'tanggal_kunjungan' => \Carbon\Carbon::parse($visittime)->format($dateFormatPHP),
                'kode_identifikasi' => strtoupper(trim($kodeIdentifikasi)),
                'cardnumber' => $cardnumber,
            ];
        });

        // ---------------------------------------------------------------------
        // STEP 5: FILTERING & FORMATTING NAMA (BAGIAN UTAMA YANG DIBENAHI)
        // ---------------------------------------------------------------------

        // A. Ambil Data Referensi Prodi dari DB
        // Pastikan namespace model M_Auv sesuai aplikasi Anda
        $allProdiListObj = \App\Models\M_Auv::where('category', 'PRODI')->get();

        // B. Ambil Mapping Fakultas (Kode => 'SINGKATAN - Nama Panjang')
        $facultyMap = $this->getProdiToFacultyMap($allProdiListObj);

        // C. Ambil Nama Prodi Asli (Kode => Nama Prodi)
        // 'lib' biasanya nama kolom deskripsi di Koha. Jika kosong, coba ganti 'description'
        $prodiNameMap = $allProdiListObj->pluck('lib', 'authorised_value')->toArray();

        // D. RAKIT LABEL FINAL: "SINGKATAN / Nama Prodi"
        $fullProdiList = [];

        // D. RAKIT LABEL FINAL: "SINGKATAN / Nama Prodi"
        $fullProdiList = [];

        foreach ($prodiNameMap as $code => $name) {
            $facultyString = $facultyMap[$code] ?? '';

            // 1. Ambil Singkatan Fakultas (misal: "FHIP")
            $parts = explode(' - ', $facultyString);
            $acronym = isset($parts[0]) ? trim($parts[0]) : '';

            $cleanName = $name; // Default

            // Cek apakah nama prodi diawali dengan singkatan fakultas?
            if (!empty($acronym) && str_starts_with(strtoupper($name), $acronym)) {
                // Hapus singkatan dari nama (misal "FHIP" dibuang)
                $tempName = substr($name, strlen($acronym));
                // Hapus sisa karakter pemisah seperti " / ", "-", atau spasi di depan
                $cleanName = ltrim($tempName, "/- ");
            }

            // 3. Gabungkan Ulang dengan Format Rapi
            if (!empty($acronym) && $acronym !== 'Lainnya') {
                $fullProdiList[$code] = $acronym . ' / ' . $cleanName;
            } else {
                $fullProdiList[$code] = $name;
            }
        }

        // E. Tambahkan Kode Manual (Agar tidak muncul "Prodi Tidak Dikenal")
        $fullProdiList['DOSEN']   = 'Dosen & Pengajar';
        $fullProdiList['TENDIK']  = 'Tenaga Kependidikan';
        $fullProdiList['KSP']     = 'Kartu Sekali Kunjung';
        $fullProdiList['KSPMBKM'] = 'MBKM';
        $fullProdiList['KSPBIPA'] = 'BIPA';
        $fullProdiList['XA']      = 'Almuni';
        $fullProdiList['LB']      = 'Anggota Luar Biasa';

        // F. Filter Berdasarkan Fakultas (Dropdown)
        if ($fakultasFilter && $fakultasFilter !== 'semua') {
            $processedData = $processedData->filter(function ($item) use ($fakultasFilter, $facultyMap) {
                // Cek fakultas dari kode item
                $fakultasItem = $facultyMap[$item['kode_identifikasi']] ?? null;
                return $fakultasItem === $fakultasFilter;
            });
        }

        // G. Filter Search Manual (Khusus Chart)
        if ($forChart && $searchKeyword) {
            $keyword = strtoupper(trim($searchKeyword));
            $processedData = $processedData->filter(function ($item) use ($keyword) {
                return str_contains($item['kode_identifikasi'], $keyword);
            });
        }

        // ---------------------------------------------------------------------
        // STEP 6: RETURN DATA
        // ---------------------------------------------------------------------

        if ($forChart) {
            $grouped = $processedData->groupBy('tanggal_kunjungan');
            $chartData = $grouped->map(function ($group, $key) use ($filterType) {
                $label = $key;
                if ($filterType === 'yearly') {
                    $label = \Carbon\Carbon::parse($key)->locale('id')->isoFormat('MMMM Y');
                }
                return [
                    'label' => $label,
                    'total_kunjungan' => $group->count()
                ];
            })->values();

            return [
                'chart' => $chartData,
                'total' => $processedData->count()
            ];
        }

        // Untuk DataTables
        $tableData = $processedData->groupBy(function ($item) {
            return $item['tanggal_kunjungan'] . '|' . $item['kode_identifikasi'];
        })->map(function ($group) {
            $first = $group->first();
            return [
                'tanggal_kunjungan' => $first['tanggal_kunjungan'],
                'kode_identifikasi' => $first['kode_identifikasi'],
                'jumlah_kunjungan_harian' => $group->count()
            ];
        })->values();

        return [
            'data'          => $tableData,
            'filterType'    => $filterType,
            'fullProdiList' => $fullProdiList
        ];
    }

    private function getProdiToFacultyMap($listprodi)
    {
        $map = [];
        $facultyMapping = $this->facultyMapping ?? [];

        foreach ($listprodi as $prodi) {
            $prodiCode = $prodi->authorised_value;

            $firstLetter = substr($prodiCode, 0, 1);
            $firstTwoLetters = substr($prodiCode, 0, 2);
            $firstThreeLetters = substr($prodiCode, 0, 3);

            if (in_array($firstThreeLetters, ['J53', 'J52'])) {
                $map[$prodiCode] = 'FKG - Fakultas Kedokteran Gigi';
            } else if ($firstTwoLetters === 'J5') {
                $map[$prodiCode] = 'FK - Fakultas Kedokteran';
            } else if ($firstLetter === 'J') {
                $map[$prodiCode] = 'FIK - Fakultas Ilmu Kesehatan';
            } else if (isset($facultyMapping[$firstLetter])) {
                $map[$prodiCode] = $facultyMapping[$firstLetter];
            } else {
                $map[$prodiCode] = 'Lainnya';
            }

            // Override Spesifik
            if (in_array($prodiCode, ['A510', 'A610', 'KIP/PSKGJ PAUD', 'Q100', 'S400', 'Q200', 'Q300', 'S200'])) {
                $map[$prodiCode] = 'FKIP - Fakultas Keguruan dan Ilmu Pendidikan';
            }
            if (in_array($prodiCode, ['W100', 'P100'])) {
                $map[$prodiCode] = 'FEB - Fakultas Ekonomi dan Bisnis';
            }
            if (in_array($prodiCode, ['U200', 'U100', 'S100'])) {
                $map[$prodiCode] = 'FT - Fakultas Teknik';
            }
            if (in_array($prodiCode, ['S300', 'T100'])) {
                $map[$prodiCode] = 'FPsi - Fakultas Psikologi';
            }
            if (in_array($prodiCode, ['I000', 'O100', 'O300', 'O200', 'O000'])) {
                $map[$prodiCode] = 'FAI - Fakultas Agama Islam';
            }
            if (in_array($prodiCode, ['R100', 'R200'])) {
                $map[$prodiCode] = 'FHIP - Fakultas Hukum dan Ilmu Politik';
            }
            if (in_array($prodiCode, ['V100'])) {
                $map[$prodiCode] = 'FF - Fakultas Farmasi';
            }
        }

        // Tambahan Manual untuk Filter
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
        // A. Ambil Data Referensi
        $allProdiListObj = M_Auv::where('category', 'PRODI')
            ->onlyProdiTampil()
            ->get();

        $facultyMap = $this->getProdiToFacultyMap($allProdiListObj);

        $prodiNameMap = $allProdiListObj->pluck('lib', 'authorised_value')->toArray();

        $listProdi = [];

        foreach ($prodiNameMap as $code => $name) {
            $facultyString = $facultyMap[$code] ?? '';

            // Ambil Singkatan (misal "FT")
            $parts = explode(' - ', $facultyString);
            $acronym = isset($parts[0]) ? trim($parts[0]) : '';

            // BERSIHKAN NAMA: Jika nama prodi diawali singkatan fakultas, hapus depannya
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

        // E. Tambahkan Kode Manual (Wajib agar tidak error/kosong)
        $listProdi['DOSEN']   = 'Dosen';
        $listProdi['TENDIK']  = 'Tenaga Kependidikan';
        $listProdi['KSP']     = 'Kartu Sekali Kunjung';
        $listProdi['KSPMBKM'] = 'MBKM';
        $listProdi['KSPBIPA'] = 'BIPA';
        $listProdi['XA']      = 'Alumni';
        $listProdi['LB']      = 'Anggota Luar Biasa';

        $filterType      = $request->input('filter_type', 'daily');
        $kodeProdiFilter = $request->input('prodi');
        $perPage         = $request->input('per_page', 12);
        $hasFilter       = $request->hasAny(['filter_type', 'prodi', 'tanggal_awal', 'tahun_awal']);

        $tanggalAwal  = $request->input('tanggal_awal', Carbon::now()->startOfMonth()->toDateString());
        $tanggalAkhir = $request->input('tanggal_akhir', Carbon::now()->toDateString());
        $tahunAwal    = $request->input('tahun_awal', Carbon::now()->year);
        $tahunAkhir   = $request->input('tahun_akhir', Carbon::now()->year);
        $displayPeriod = '';

        // Setup Rentang Waktu
        if ($filterType === 'yearly') {
            if ($tahunAwal > $tahunAkhir) $tahunAwal = $tahunAkhir;
            $start = Carbon::createFromDate($tahunAwal, 1, 1)->startOfDay();
            $end   = Carbon::createFromDate($tahunAkhir, 12, 31)->endOfDay();
            $dateFormatPHP = 'Y-m-01'; // Grouping bulanan
            $displayPeriod = "Tahun " . $tahunAwal . ($tahunAwal != $tahunAkhir ? " s.d. " . $tahunAkhir : "");
        } else {
            $start = Carbon::parse($tanggalAwal)->startOfDay();
            $end   = Carbon::parse($tanggalAkhir)->endOfDay();
            $dateFormatPHP = 'Y-m-d'; // Grouping harian
            $displayPeriod = "Periode " . $start->locale('id')->isoFormat('D MMMM Y') . " s.d. " . $end->locale('id')->isoFormat('D MMMM Y');
        }

        // A. Ambil Data Kunjungan (DB Satelit)
        $qHistory = DB::connection('mysql')->table('visitorhistory')
            ->select('visittime', 'cardnumber')
            ->whereBetween('visittime', [$start, $end]);

        $qCorner = DB::connection('mysql')->table('visitorcorner')
            ->select('visittime', 'cardnumber')
            ->whereBetween('visittime', [$start, $end]);

        // Eksekusi Query Visitor
        $rawVisits = $qHistory->unionAll($qCorner)->get();

        $cardNumbers = $rawVisits->pluck('cardnumber')->unique()->values()->all();

        // Query ke DB Koha menggunakan whereIn (Bukan JOIN SQL)
        $borrowers = DB::connection('mysql2')->table('borrowers')
            ->select('cardnumber', 'categorycode')
            ->whereIn('cardnumber', $cardNumbers)
            ->get()
            ->pluck('categorycode', 'cardnumber'); // Array [cardnumber => categorycode]

        $processedData = $rawVisits->map(function ($visit) use ($borrowers, $dateFormatPHP, $listProdi) {
            $cardnumber = $visit->cardnumber;
            $categorycode = $borrowers[$cardnumber] ?? null;

            // Logika Identifikasi (Sama dengan buildQuery)
            $kodeIdentifikasi = substr($cardnumber, 0, 4); // Default

            if ($categorycode && str_starts_with($categorycode, 'TC')) {
                $kodeIdentifikasi = 'DOSEN';
            } elseif ($categorycode && (str_starts_with($categorycode, 'STAF') || $categorycode === 'LIBRARIAN')) {
                $kodeIdentifikasi = 'TENDIK';
            } elseif (str_starts_with($cardnumber, 'KSPMBKM')) {
                $kodeIdentifikasi = 'KSPMBKM';
            } elseif (str_starts_with($cardnumber, 'KSPBIPA')) {
                $kodeIdentifikasi = 'KSPBIPA';
            } elseif (in_array(substr($cardnumber, 0, 2), ['XA', 'XC', 'LB'])) {
                $kodeIdentifikasi = substr($cardnumber, 0, 2);
            } elseif (substr($cardnumber, 0, 3) === 'KSP') {
                $kodeIdentifikasi = 'KSP';
            }

            $code = strtoupper(trim($kodeIdentifikasi));

            return [
                'tanggal_kunjungan' => Carbon::parse($visit->visittime)->format($dateFormatPHP),
                'kode_identifikasi' => $code,
                // Langsung pasang nama prodi disini biar mudah difilter/ditampilkan
                'nama_prodi'        => $listProdi[$code] ?? 'Prodi Tidak Dikenal',
                'kode_prodi'        => $code
            ];
        });

        // =====================================================================
        // 5. FILTERING (COLLECTION BASED)
        // =====================================================================

        // Filter Dropdown Prodi
        if (!empty($kodeProdiFilter) && strtolower($kodeProdiFilter) !== 'semua') {
            $processedData = $processedData->filter(function ($item) use ($kodeProdiFilter) {
                // Bisa filter by kode atau nama jika perlu. Disini by Kode.
                return $item['kode_identifikasi'] === $kodeProdiFilter;
            });
        }

        // =====================================================================
        // 6. GROUPING & COUNTING
        // =====================================================================

        // Grouping agar unik per Tanggal & Prodi
        $groupedData = $processedData->groupBy(function ($item) {
            return $item['tanggal_kunjungan'] . '|' . $item['kode_identifikasi'];
        })->map(function ($group) {
            $first = $group->first();
            // Ubah array jadi Object agar kompatibel dengan View blade ($item->property)
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
        ]); // Collection urut

        $totalKeseluruhanKunjungan = $processedData->count(); // Total mentah sebelum digroup

        // =====================================================================
        // 7. CHART DATA & PAGINATION MANUAL
        // =====================================================================

        // A. Siapkan Chart Data (Dari grouped data)
        $chartGrouped = $processedData->groupBy('tanggal_kunjungan');
        $chartData = $chartGrouped->map(function ($group, $key) use ($filterType) {
            $label = $key;
            if ($filterType === 'yearly') {
                try {
                    $label = Carbon::parse($key)->locale('id')->isoFormat('MMMM Y');
                } catch (\Exception $e) {
                }
            }
            return (object) [
                'label' => $label,
                'total_kunjungan' => $group->count()
            ];
        })->values();

        // B. Manual Pagination (Karena data sekarang berupa Collection, bukan Query Builder)
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $currentItems = $groupedData->slice(($currentPage - 1) * $perPage, $perPage)->values();

        $paginatedData = new LengthAwarePaginator(
            $currentItems,
            $groupedData->count(),
            $perPage,
            $currentPage,
            ['path' => LengthAwarePaginator::resolveCurrentPath(), 'query' => $request->query()]
        );

        // =====================================================================
        // 8. RETURN VIEW
        // =====================================================================

        return view('pages.kunjungan.prodiTable', [
            'data'          => $paginatedData, // Variable ini dipakai di foreach view
            'listProdi'     => $listProdi,
            'tanggalAwal'   => $tanggalAwal,
            'tanggalAkhir'  => $tanggalAkhir,
            'filterType'    => $filterType,
            'tahunAwal'     => $tahunAwal,
            'tahunAkhir'    => $tahunAkhir,
            'perPage'       => $perPage,
            'displayPeriod' => $displayPeriod,
            'chartData'     => $chartData,
            'totalKeseluruhanKunjungan' => $totalKeseluruhanKunjungan,
            'hasFilter'     => $hasFilter
        ]);
    }

    public function getDetailPengunjung(Request $request)
    {
        $tanggal = $request->query('tanggal'); // YYYY-MM-DD
        $bulanTahun = $request->query('bulan'); // YYYY-MM
        $kodeIdentifikasiReq = $request->query('kode_identifikasi'); // Parameter Filter
        $isExport = $request->query('export');

        if ((!$tanggal && !$bulanTahun) || !$kodeIdentifikasiReq) {
            return response()->json(['error' => 'Parameter tidak lengkap.'], 400);
        }

        // =====================================================================
        // 1. SIAPKAN FILTER TANGGAL
        // =====================================================================
        if ($bulanTahun) {
            // Filter Bulanan
            // Kita gunakan raw string untuk whereBetween agar index mysql terpakai optimal
            $start = Carbon::createFromFormat('Y-m', $bulanTahun)->startOfMonth()->toDateTimeString();
            $end   = Carbon::createFromFormat('Y-m', $bulanTahun)->endOfMonth()->toDateTimeString();
        } else {
            // Filter Harian
            $start = Carbon::parse($tanggal)->startOfDay()->toDateTimeString();
            $end   = Carbon::parse($tanggal)->endOfDay()->toDateTimeString();
        }

        // =====================================================================
        // 2. AMBIL DATA KUNJUNGAN (DB SATELIT)
        // =====================================================================
        $qHistory = DB::connection('mysql')->table('visitorhistory')
            ->select('cardnumber', 'visittime')
            ->whereBetween('visittime', [$start, $end]);

        $qCorner = DB::connection('mysql')->table('visitorcorner')
            ->select('cardnumber', 'visittime')
            ->whereBetween('visittime', [$start, $end]);

        // Eksekusi query visitor (Raw Data)
        $rawVisits = $qHistory->unionAll($qCorner)->get();

        if ($rawVisits->isEmpty()) {
            return response()->json([]);
        }

        // =====================================================================
        // 3. AMBIL DATA BORROWER (DB KOHA - TANPA JOIN)
        // =====================================================================
        $cardNumbers = $rawVisits->pluck('cardnumber')->unique()->values()->all();

        $borrowers = DB::connection('mysql2')->table('borrowers')
            ->select('cardnumber', 'surname', 'firstname', 'categorycode')
            ->whereIn('cardnumber', $cardNumbers)
            ->get()
            ->keyBy('cardnumber'); // Key by cardnumber agar mudah dipanggil

        // =====================================================================
        // 4. MAPPING & FILTERING (LOGIC PHP)
        // =====================================================================
        $targetKode = strtoupper(trim($kodeIdentifikasiReq));

        // Proses setiap kunjungan: Tentukan Kodenya, lalu Filter
        $filteredVisits = $rawVisits->map(function ($visit) use ($borrowers) {
            $card = $visit->cardnumber;
            $borrower = $borrowers[$card] ?? null;
            $catCode = $borrower ? $borrower->categorycode : null;

            // Nama Lengkap
            $nama = 'Tanpa Nama';
            if ($borrower) {
                $nama = trim(($borrower->firstname ?? '') . ' ' . ($borrower->surname ?? ''));
            }

            // --- LOGIKA IDENTIFIKASI (Sama dengan Controller Utama) ---
            $kode = substr($card, 0, 4); // Default

            if ($catCode && str_starts_with($catCode, 'TC')) {
                $kode = 'DOSEN';
            } elseif ($catCode && (str_starts_with($catCode, 'STAF') || $catCode === 'LIBRARIAN')) {
                $kode = 'TENDIK';
            } elseif (str_starts_with($card, 'KSPMBKM')) {
                $kode = 'KSPMBKM';
            } elseif (str_starts_with($card, 'KSPBIPA')) {
                $kode = 'KSPBIPA';
            } elseif (in_array(substr($card, 0, 2), ['XA', 'XC', 'LB'])) {
                $kode = substr($card, 0, 2);
            } elseif (substr($card, 0, 3) === 'KSP') {
                $kode = 'KSP';
            }

            return [
                'cardnumber' => $card,
                'nama'       => $nama,
                'kode_ident' => strtoupper($kode)
            ];
        })->filter(function ($item) use ($targetKode) {
            // HANYA AMBIL YANG SESUAI REQUEST PARAMETER
            return $item['kode_ident'] === $targetKode;
        });

        // =====================================================================
        // 5. GROUPING & COUNTING (TOTAL KUNJUNGAN PER ORANG)
        // =====================================================================

        // Group by cardnumber untuk menghitung berapa kali orang tersebut masuk
        $finalData = $filteredVisits->groupBy('cardnumber')->map(function ($group) {
            $first = $group->first();
            return [
                'cardnumber'  => $first['cardnumber'],
                'nama'        => $first['nama'],
                'visit_count' => $group->count()
            ];
        })->sortByDesc('visit_count')->values(); // Urutkan terbanyak & reset keys

        // =====================================================================
        // 6. RESPONSE (EXPORT / PAGINATION)
        // =====================================================================

        if ($isExport) {
            return response()->json($finalData);
        }

        // Manual Pagination untuk Collection
        $perPage = $request->input('per_page', 10);
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $currentItems = $finalData->slice(($currentPage - 1) * $perPage, $perPage)->values();

        $paginated = new LengthAwarePaginator(
            $currentItems,
            $finalData->count(),
            $perPage,
            $currentPage,
            ['path' => LengthAwarePaginator::resolveCurrentPath(), 'query' => $request->query()]
        );

        return response()->json($paginated);
    }

    public function getProdiExportData(Request $request)
    {
        ini_set('memory_limit', '1024M');
        set_time_limit(300);

        $filterType = $request->input('filter_type', 'daily');
        $kodeProdiFilter = $request->input('prodi');

        if ($filterType === 'yearly') {
            $tahunAwal = $request->input('tahun_awal', Carbon::now()->year);
            $tahunAkhir = $request->input('tahun_akhir', Carbon::now()->year);
            if ($tahunAwal > $tahunAkhir) $tahunAwal = $tahunAkhir;

            $start = Carbon::createFromDate($tahunAwal, 1, 1)->startOfDay();
            $end = Carbon::createFromDate($tahunAkhir, 12, 31)->endOfDay();

            $periodeDisplay = "Tahun " . $tahunAwal . ($tahunAwal != $tahunAkhir ? " s/d " . $tahunAkhir : "");
            $sqlDateFormat = "%Y-%m-01"; // Format SQL
        } else {
            $start = Carbon::parse($request->input('tanggal_awal', Carbon::now()->startOfMonth()->toDateString()))->startOfDay();
            $end = Carbon::parse($request->input('tanggal_akhir', Carbon::now()->toDateString()))->endOfDay();

            $periodeDisplay = "Periode " . $start->locale('id')->isoFormat('D MMMM Y') . " s.d. " . $end->locale('id')->isoFormat('D MMMM Y');
            $sqlDateFormat = "%Y-%m-%d"; // Format SQL
        }

        $queryStr = "
        DATE_FORMAT(visittime, '$sqlDateFormat') as tgl_kunjungan,
        cardnumber,
        COUNT(*) as total_hits
    ";

        // A. Ambil Rekap History
        $historyData = DB::connection('mysql')->table('visitorhistory')
            ->selectRaw($queryStr)
            ->whereBetween('visittime', [$start, $end])
            ->groupByRaw("DATE_FORMAT(visittime, '$sqlDateFormat'), cardnumber")
            ->get();

        // B. Ambil Rekap Corner
        $cornerData = DB::connection('mysql')->table('visitorcorner')
            ->selectRaw($queryStr)
            ->whereBetween('visittime', [$start, $end])
            ->groupByRaw("DATE_FORMAT(visittime, '$sqlDateFormat'), cardnumber")
            ->get();

        // C. Gabung data di PHP (Merge Collection)
        // Ini jauh lebih cepat daripada Union SQL beda database
        $mergedData = $historyData->merge($cornerData);

        // Jika kosong langsung return file kosong/pesan
        if ($mergedData->isEmpty()) {
            return response()->stream(function () {}, 200, ['Content-Type' => 'text/csv']);
        }

        $uniqueCards = $mergedData->pluck('cardnumber')->unique()->values()->all();

        $borrowers = DB::connection('mysql2')->table('borrowers')
            ->select('cardnumber', 'categorycode')
            ->whereIn('cardnumber', $uniqueCards)
            ->pluck('categorycode', 'cardnumber')
            ->toArray();

        $finalReport = [];

        foreach ($mergedData as $row) {
            $card = $row->cardnumber;
            $tgl  = $row->tgl_kunjungan;
            $hits = $row->total_hits;

            $catCode = $borrowers[$card] ?? null;

            $kodeIdentifikasi = substr($card, 0, 4);

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

            // Kita jumlahkan akumulasi dari SQL
            $finalReport[$key]['jumlah'] += $hits;
        }

        // -------------------------------------------------------------------------
        // 5. FINISHING & FILTERING
        // -------------------------------------------------------------------------
        $dataCollection = collect(array_values($finalReport));

        // Filter Prodi
        if ($kodeProdiFilter && strtolower($kodeProdiFilter) !== 'semua') {
            $filterTarget = strtoupper($kodeProdiFilter);
            $dataCollection = $dataCollection->filter(function ($item) use ($filterTarget) {
                return $item['kode'] === $filterTarget;
            });
        }

        // Sorting
        $dataCollection = $dataCollection->sortBy([
            ['tanggal', 'asc'],
            ['kode', 'asc']
        ]);

        $grandTotal = $dataCollection->sum('jumlah');

        $allProdiListObj = M_Auv::where('category', 'PRODI')->get();
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
        // Tambahan Manual
        $fullProdiList['DOSEN']   = 'Dosen & Pengajar';
        $fullProdiList['TENDIK']  = 'Tenaga Kependidikan';
        $fullProdiList['KSP']     = 'Kartu Sekali Kunjung';
        $fullProdiList['KSPMBKM'] = 'MBKM';
        $fullProdiList['KSPBIPA'] = 'BIPA';
        $fullProdiList['XA']      = 'Alumni';
        $fullProdiList['LB']      = 'Anggota Luar Biasa';

        $namaProdiFilter = $fullProdiList[strtoupper($kodeProdiFilter)] ?? 'Seluruh Kategori';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="laporan_kunjungan.csv"',
        ];

        $callback = function () use ($dataCollection, $filterType, $namaProdiFilter, $periodeDisplay, $grandTotal, $fullProdiList) {
            if (ob_get_level()) {
                ob_end_clean();
            }
            $file = fopen('php://output', 'w');
            fputs($file, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM

            fputcsv($file, ["Laporan Statistik Kunjungan: " . $namaProdiFilter], ';');
            fputcsv($file, ["Periode: " . $periodeDisplay], ';');
            fputcsv($file, [''], ';');

            $headers = ['Tanggal / Bulan', 'Kode Identifikasi', 'Nama Prodi/Kategori', 'Jumlah Kunjungan'];
            fputcsv($file, $headers, ';');

            foreach ($dataCollection as $row) {
                $tanggal = ($filterType === 'yearly') ?
                    Carbon::parse($row['tanggal'])->locale('id')->isoFormat('MMMM Y') :
                    Carbon::parse($row['tanggal'])->locale('id')->isoFormat('dddd, D MMMM Y');

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
                ->select('borrowernumber', 'cardnumber', 'firstname', 'surname', 'email', 'phone')
                ->where(DB::raw('TRIM(LOWER(cardnumber))'), $cardnumber)
                ->first();

            if ($fullBorrowerDetails) {
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
                ORDER BY tahun_bulan ASC
            ";

                $semuaKunjungan = DB::connection('mysql')->select($queryString, $bindings);

                $dataKunjungan = collect($semuaKunjungan);
                $totalKunjunganSum = $dataKunjungan->sum('jumlah_kunjungan');

                $perPage = 12;
                $currentPage = \Illuminate\Pagination\Paginator::resolveCurrentPage('page');
                $currentItems = array_slice($dataKunjungan->all(), $perPage * ($currentPage - 1), $perPage);
                $dataKunjungan = new \Illuminate\Pagination\LengthAwarePaginator($currentItems, $dataKunjungan->count(), $perPage, $currentPage, ['path' => \Illuminate\Pagination\Paginator::resolveCurrentPath()]);
                $dataKunjungan->appends(request()->query());

                if ($dataKunjungan->isEmpty()) {
                    $pesan = 'Tidak ada data kunjungan ditemukan untuk Nomor Kartu Anggota: ' . $fullBorrowerDetails->cardnumber . ' (' . $fullBorrowerDetails->firstname . ' ' . $fullBorrowerDetails->surname . ').';
                } else {
                    $pesan = null;
                }
            } else {
                $pesan = 'Detail peminjam tidak ditemukan di database utama untuk Nomor Kartu Anggota: ' . $cardnumber . '.';
            }
        }

        return view('pages.kunjungan.cekKehadiran', compact('dataKunjungan', 'fullBorrowerDetails', 'pesan', 'cardnumber', 'tahun', 'totalKunjunganSum'));
    }

    public function getLokasiDetail(Request $request)
    {
        $cardnumber = $request->input('cardnumber');
        $tahunBulan = $request->input('tahun_bulan');
        $perPage = 10; // Jumlah item per halaman

        if (!$cardnumber || !$tahunBulan) {
            return response()->json(['error' => 'Parameter tidak lengkap.'], 400);
        }
        try {
            $lokasiMapping = [
                'sni' => 'SNI Corner',
                'bi' => 'Bank Indonesia Corner',
                'mc' => 'Muhammadiyah Corner',
                'pusat' => 'Perpustakaan Pusat',
                'pasca' => 'Perpustakaan Pascasarjana',
                'fk' => 'Perpustakaan Kedokteran',
                'ref' => 'Referensi Perpustakaan Pusat',
            ];

            $cardnumber = trim(strtolower($cardnumber));

            $queryString = "
            SELECT combined.visittime AS visit_date, combined.visit_location
            FROM (
                (SELECT visittime, IFNULL(location, 'pusat') as visit_location, cardnumber FROM visitorhistory)
                UNION ALL
                (SELECT visittime, notes as visit_location, cardnumber FROM visitorcorner)
            ) AS combined
            WHERE TRIM(LOWER(combined.cardnumber)) = ? AND EXTRACT(YEAR_MONTH FROM combined.visittime) = ?
            ORDER BY combined.visittime ASC
        ";

            $bindings = [$cardnumber, $tahunBulan];

            $semuaData = DB::connection('mysql')->select($queryString, $bindings);

            $lokasiData = new \Illuminate\Pagination\LengthAwarePaginator(
                array_slice($semuaData, $perPage * (\Illuminate\Pagination\Paginator::resolveCurrentPage() - 1), $perPage),
                count($semuaData),
                $perPage,
                \Illuminate\Pagination\Paginator::resolveCurrentPage(),
                ['path' => \Illuminate\Pagination\Paginator::resolveCurrentPath()]
            );

            $lokasiData->getCollection()->transform(function ($item) use ($lokasiMapping) {
                $normalizedLokasi = strtolower(trim($item->visit_location));
                $item->visit_location = $lokasiMapping[$normalizedLokasi] ?? $item->visit_location;
                return $item;
            });

            $bulanTahunFormatted = Carbon::createFromFormat('Ym', $tahunBulan)->format('F Y');

            return response()->json([
                'lokasi' => $lokasiData->items(),
                'pagination_data' => $lokasiData->toArray(),
                'bulan_tahun_formatted' => $bulanTahunFormatted,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Gagal mengambil data lokasi.', 'message' => $e->getMessage()], 500);
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

        $fullBorrowerDetails = DB::connection('mysql')->table('borrowers')
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
            // 'ref' => 'Referensi Perpustakaan Pusat',
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
