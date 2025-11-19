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

    private function getProdiToFacultyMap($listprodi)
    {
        $map = [];
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
            } else if (isset($this->facultyMapping[$firstLetter])) {
                $map[$prodiCode] = $this->facultyMapping[$firstLetter];
            } else {
                $map[$prodiCode] = 'Lainnya';
            }

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

        // Tambahan Manual untuk Static Values
        $map['DOSEN']   = 'Dosen & Tendik';
        $map['TENDIK']  = 'Dosen & Tendik';

        return $map;
    }

    public function kunjunganFakultasTable(Request $request)
    {
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

        // 3. DEFINISI VARIABEL UNTUK VIEW
        // <--- PERBAIKAN 1: Definisi $hasFilter
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

        // <--- PERBAIKAN 2: Tambahkan variabel ke compact()
        return view('pages.kunjungan.fakultasTable', compact(
            'listFakultas',
            'tanggalAwal',
            'tanggalAkhir',
            'tahunAwal',
            'tahunAkhir',
            'chartData',
            'totalKeseluruhanKunjungan',
            'hasFilter',      // <--- Wajib ada
            'filterType',     // <--- Wajib ada untuk form
            'displayPeriod'   // <--- Wajib ada untuk judul
        ));
    }


    private function buildQuery($request, $forChart = false)
    {
        // Ambil Filter
        $filterType     = $request->input('filter_type', 'daily');
        $fakultasFilter = $request->input('fakultas');
        // Search ditangani otomatis oleh Yajra nanti untuk tabel,
        // tapi untuk Chart kita butuh parameter search manual jika ada
        $searchKeyword  = $request->input('search_manual');

        // Setup Tanggal
        if ($filterType === 'yearly') {
            $thAwal = $request->input('tahun_awal', Carbon::now()->year);
            $thAkhir = $request->input('tahun_akhir', Carbon::now()->year);
            if ($thAwal > $thAkhir) $thAwal = $thAkhir;
            $start = Carbon::createFromDate($thAwal, 1, 1)->format('Y-m-d');
            $end = Carbon::createFromDate($thAkhir, 12, 31)->format('Y-m-d');
            $sqlDateFormat = "DATE_FORMAT(all_visits.visittime, '%Y-%m-01')";
        } else {
            $start = $request->input('tanggal_awal', Carbon::now()->startOfMonth()->toDateString());
            $end = $request->input('tanggal_akhir', Carbon::now()->toDateString());
            $sqlDateFormat = "DATE(all_visits.visittime)";
        }

        // Setup Mapping Prodi
        $listNamaProdi = M_Auv::where('category', 'PRODI')->get()
            ->mapWithKeys(function ($item) {
                $lib = $item->lib;
                if (str_starts_with($lib, 'FAI/ ')) $lib = substr($lib, 5);
                return [strtoupper(trim($item->authorised_value)) => trim($lib)];
            })->toArray();
        $staticValues = [
            'DOSEN' => 'Dosen',
            'TENDIK' => 'Tenaga Kependidikan',
            'XA' => 'Alumni',
            'KSP' => 'Sekali Kunjung',
            'LB' => 'Anggota Luar Biasa',
            'KSPMBKM' => 'Magang MBKM',
            'KSPBIPA' => 'BIPA'
        ];
        $fullProdiList = $staticValues + $listNamaProdi;

        // Logika Filter Fakultas
        $codesFromFaculty = [];
        $isFacultyFilterActive = ($fakultasFilter && $fakultasFilter !== 'semua');
        if ($isFacultyFilterActive) {
            $allProdiListObj = M_Auv::where('category', 'PRODI')->get();
            $prodiToFacultyMap = $this->getProdiToFacultyMap($allProdiListObj);
            foreach ($prodiToFacultyMap as $code => $facultyName) {
                if ($facultyName === $fakultasFilter) $codesFromFaculty[] = strtoupper(trim($code));
            }
        }

        // Logika Search Manual (Khusus untuk Chart)
        $codesFromSearch = [];
        if ($forChart && $searchKeyword) {
            $keyword = strtolower(trim($searchKeyword));
            foreach ($fullProdiList as $code => $name) {
                if (str_contains(strtolower($code), $keyword) || str_contains(strtolower($name), $keyword)) {
                    $codesFromSearch[] = strtoupper(trim($code));
                }
            }
        }

        // BUILD QUERY UNION
        $strStart = $start . ' 00:00:00';
        $strEnd   = $end . ' 23:59:59';

        $qHistory = DB::connection('mysql2')->table('visitorhistory')
            ->select('visittime', 'cardnumber')
            ->whereRaw("visittime >= '$strStart' AND visittime <= '$strEnd'");
        $qCorner = DB::connection('mysql2')->table('visitorcorner')
            ->select('visittime', 'cardnumber')
            ->whereRaw("visittime >= '$strStart' AND visittime <= '$strEnd'");

        $query = DB::connection('mysql2')->query()
            ->fromSub($qHistory->unionAll($qCorner), 'all_visits')
            ->leftJoin('borrowers as b', 'all_visits.cardnumber', '=', 'b.cardnumber')
            ->selectRaw("
            $sqlDateFormat as tanggal_kunjungan,
            CASE
                WHEN b.categorycode LIKE 'TC%' THEN 'DOSEN'
                WHEN b.categorycode LIKE 'STAF%' THEN 'TENDIK'
                WHEN SUBSTR(all_visits.cardnumber, 1, 7) = 'KSPMBKM' THEN 'KSPMBKM'
                WHEN SUBSTR(all_visits.cardnumber, 1, 7) = 'KSPBIPA' THEN 'KSPBIPA'
                WHEN SUBSTR(all_visits.cardnumber, 1, 2) IN ('XA', 'XC', 'LB') THEN SUBSTR(all_visits.cardnumber, 1, 2)
                WHEN SUBSTR(all_visits.cardnumber, 1, 3) = 'KSP' THEN 'KSP'
                ELSE SUBSTR(all_visits.cardnumber, 1, 4)
            END as kode_identifikasi,
            COUNT(*) as jumlah_kunjungan_harian
        ");

        // Apply Filter Fakultas (HAVING)
        if ($isFacultyFilterActive) {
            if (!empty($codesFromFaculty)) {
                $query->havingRaw("UPPER(TRIM(kode_identifikasi)) IN ('" . implode("','", $codesFromFaculty) . "')");
            } else {
                $query->havingRaw("1 = 0");
            }
        }

        // Apply Filter Search (Khusus Chart)
        if ($forChart && $searchKeyword) {
            if (!empty($codesFromSearch)) {
                $query->havingRaw("UPPER(TRIM(kode_identifikasi)) IN ('" . implode("','", $codesFromSearch) . "')");
            } else {
                $query->havingRaw("1 = 0");
            }
        }

        // Grouping
        $query->groupBy('tanggal_kunjungan', 'kode_identifikasi');

        // Jika untuk Chart/Total
        if ($forChart) {
            $rawData = $query->orderBy('tanggal_kunjungan', 'asc')->get();

            $chartData = $rawData->groupBy('tanggal_kunjungan')->map(function ($group, $key) use ($filterType) {
                $label = $key;
                if ($filterType === 'yearly') {
                    try {
                        $label = Carbon::parse($key)->locale('id')->isoFormat('MMMM Y');
                    } catch (\Exception $e) {
                    }
                }
                return [
                    'label' => $label,
                    'total_kunjungan' => $group->sum('jumlah_kunjungan_harian')
                ];
            })->values();

            return [
                'chart' => $chartData,
                'total' => $rawData->sum('jumlah_kunjungan_harian')
            ];
        }


        return [
            'query' => $query,
            'fullProdiList' => $fullProdiList,
            'filterType' => $filterType
        ];
    }

    private function getDataTables($request)
    {
        $build = $this->buildQuery($request, false);
        $query = $build['query'];
        $fullProdiList = $build['fullProdiList'];
        $filterType = $build['filterType'];

        // 1. Wrap Query Utama
        $wrappedQuery = DB::connection('mysql2')->query()->fromSub($query, 'sub_query');
        $queryForTotal = clone $wrappedQuery;

        // Ambil keyword pencarian dari request Yajra
        $keyword = $request->input('search.value');

        if (!empty($keyword)) {
            $keyword = strtolower($keyword);
            $matchedCodes = [];

            // Cari kode prodi yang cocok
            foreach ($fullProdiList as $code => $name) {
                if (str_contains(strtolower($name), $keyword) || str_contains(strtolower($code), $keyword)) {
                    $matchedCodes[] = $code;
                }
            }

            if (!empty($matchedCodes)) {
                $queryForTotal->whereIn('kode_identifikasi', $matchedCodes);
            } else {
                $queryForTotal->where(function ($q) use ($keyword) {
                    $q->whereRaw("1 = 0"); // Default fail untuk prodi
                    $q->orWhere('tanggal_kunjungan', 'like', "%{$keyword}%");
                });
            }
        }

        // Hitung jumlahnya
        $filteredTotal = $queryForTotal->sum('jumlah_kunjungan_harian');

        return DataTables::of($wrappedQuery)
            ->addIndexColumn()
            ->editColumn('tanggal_kunjungan', function ($row) use ($filterType) {
                if ($filterType === 'yearly') {
                    try {
                        return Carbon::parse($row->tanggal_kunjungan)->locale('id')->isoFormat('MMMM Y');
                    } catch (\Exception $e) {
                        return $row->tanggal_kunjungan;
                    }
                }
                try {
                    return Carbon::parse($row->tanggal_kunjungan)->locale('id')->isoFormat('dddd, D MMMM Y');
                } catch (\Exception $e) {
                    return $row->tanggal_kunjungan;
                }
            })
            ->addColumn('nama_prodi', function ($row) use ($fullProdiList) {
                $code = strtoupper(trim($row->kode_identifikasi));
                $name = $fullProdiList[$code] ?? 'Prodi Tidak Dikenal';
                return '<div class="d-flex flex-column">
                        <span class="fw-bold text-primary">' . $name . '</span>
                        <small class="text-muted">' . $code . '</small>
                    </div>';
            })
            // Logic Search untuk Tabel
            ->filterColumn('nama_prodi', function ($query, $keyword) use ($fullProdiList) {
                $matchedCodes = [];
                $keyword = strtolower($keyword);
                foreach ($fullProdiList as $code => $name) {
                    if (str_contains(strtolower($name), $keyword) || str_contains(strtolower($code), $keyword)) {
                        $matchedCodes[] = $code;
                    }
                }
                if (!empty($matchedCodes)) {
                    $query->whereIn('kode_identifikasi', $matchedCodes);
                } else {
                    $query->whereRaw("1 = 0");
                }
            })
            ->editColumn('jumlah_kunjungan_harian', function ($row) {
                return '<span class="">' . number_format($row->jumlah_kunjungan_harian, 0, ',', '.') . '</span>';
            })
            ->rawColumns(['nama_prodi', 'jumlah_kunjungan_harian'])

            ->with([
                'recordsTotalFiltered' => number_format($filteredTotal, 0, ',', '.')
            ])
            ->make(true);
    }

    public function kunjunganProdiTable(Request $request)
    {
        // 1. SETUP DATA AWAL
        $listProdiFromDb = M_Auv::where('category', 'PRODI')
            ->whereRaw('CHAR_LENGTH(lib) >= 13')
            ->onlyProdiTampil()
            ->orderBy('authorised_value', 'asc')
            ->pluck('lib', 'authorised_value')
            ->toArray();

        $staticValues = [
            'DOSEN' => 'Dosen',
            'TENDIK' => 'Tenaga Kependidikan',
        ];
        $listProdi = $staticValues + $listProdiFromDb;

        // 2. SETUP FILTER
        $filterType      = $request->input('filter_type', 'daily');
        $kodeProdiFilter = $request->input('prodi');
        $perPage         = $request->input('per_page', 10);
        $hasFilter       = $request->hasAny(['filter_type', 'prodi', 'tanggal_awal', 'tahun_awal']);

        $data = collect([]);
        $chartData = collect([]);
        $totalKeseluruhanKunjungan = 0;
        $displayPeriod = '';

        $tanggalAwal = $request->input('tanggal_awal', Carbon::now()->startOfMonth()->toDateString());
        $tanggalAkhir = $request->input('tanggal_akhir', Carbon::now()->toDateString());
        $tahunAwal = $request->input('tahun_awal', Carbon::now()->year);
        $tahunAkhir = $request->input('tahun_akhir', Carbon::now()->year);

        if ($hasFilter) {
            if ($filterType === 'yearly') {
                if ($tahunAwal > $tahunAkhir) $tahunAwal = $tahunAkhir;
                $tanggalAwal = Carbon::createFromDate($tahunAwal, 1, 1)->format('Y-m-d');
                $tanggalAkhir = Carbon::createFromDate($tahunAkhir, 12, 31)->format('Y-m-d');
                $displayPeriod = "Tahun " . $tahunAwal . ($tahunAwal != $tahunAkhir ? " s.d. " . $tahunAkhir : "");
                // Format Standard SQL (YYYY-MM-01) agar View tidak error saat parsing
                $sqlDateFormat = "DATE_FORMAT(all_visits.waktu_kunjungan, '%Y-%m-01')";
            } else {
                $displayPeriod = "Periode " . Carbon::parse($tanggalAwal)->locale('id')->isoFormat('D MMMM Y') . " s.d. " . Carbon::parse($tanggalAkhir)->locale('id')->isoFormat('D MMMM Y');
                $sqlDateFormat = "DATE(all_visits.waktu_kunjungan)";
            }

            // 3. QUERY SQL UNION (Data Mentah)
            $strStart = $tanggalAwal . ' 00:00:00';
            $strEnd   = $tanggalAkhir . ' 23:59:59';

            $qHistory = DB::connection('mysql2')->table('visitorhistory')
                ->select('visittime as waktu_kunjungan', 'cardnumber')
                ->whereRaw("visittime >= '$strStart' AND visittime <= '$strEnd'");

            $qCorner = DB::connection('mysql2')->table('visitorcorner')
                ->select('visittime as waktu_kunjungan', 'cardnumber')
                ->whereRaw("visittime >= '$strStart' AND visittime <= '$strEnd'");

            // Wrapper Query
            $query = DB::connection('mysql2')->query()
                ->fromSub($qHistory->unionAll($qCorner), 'all_visits')
                ->leftJoin('borrowers as b', 'all_visits.cardnumber', '=', 'b.cardnumber')
                ->selectRaw("
                $sqlDateFormat as tanggal_kunjungan,
                CASE
                    WHEN b.categorycode LIKE 'TC%' THEN 'DOSEN'
                    WHEN b.categorycode LIKE 'STAF%' THEN 'TENDIK'
                    WHEN SUBSTR(all_visits.cardnumber, 1, 7) = 'KSPMBKM' THEN 'KSPMBKM'
                    WHEN SUBSTR(all_visits.cardnumber, 1, 7) = 'KSPBIPA' THEN 'KSPBIPA'
                    WHEN SUBSTR(all_visits.cardnumber, 1, 2) IN ('XA', 'XC', 'LB') THEN SUBSTR(all_visits.cardnumber, 1, 2)
                    WHEN SUBSTR(all_visits.cardnumber, 1, 3) = 'KSP' THEN 'KSP'
                    ELSE SUBSTR(all_visits.cardnumber, 1, 4)
                END as kode_identifikasi,
                COUNT(*) as jumlah_kunjungan_harian
            ");

            // Filter Prodi
            if (!empty($kodeProdiFilter) && strtolower($kodeProdiFilter) !== 'semua') {
                $fc = strtoupper($kodeProdiFilter);
                switch ($fc) {
                    case 'DOSEN':
                        $query->where('b.categorycode', 'like', 'TC%');
                        break;
                    case 'TENDIK':
                        $query->where('b.categorycode', 'like', 'STAF%');
                        break;
                    case 'XA':
                    case 'XC':
                    case 'LB':
                        $query->whereRaw("SUBSTR(all_visits.cardnumber, 1, 2) = ?", [$fc]);
                        break;
                    case 'KSP':
                        $query->whereRaw("SUBSTR(all_visits.cardnumber, 1, 3) = ?", [$fc]);
                        break;
                    case 'KSPMBKM':
                    case 'KSPBIPA':
                        $query->whereRaw("SUBSTR(all_visits.cardnumber, 1, 7) = ?", [$fc]);
                        break;
                    default:
                        $query->whereRaw("SUBSTR(all_visits.cardnumber, 1, 4) = ?", [$fc]);
                        break;
                }
            }

            // Grouping (Menyatukan duplikat bulan)
            $query->groupBy('tanggal_kunjungan', 'kode_identifikasi')
                ->orderBy('tanggal_kunjungan', 'asc')
                ->orderBy('kode_identifikasi', 'asc');

            $totalKeseluruhanKunjungan = (clone $query)->get()->sum('jumlah_kunjungan_harian');
            $data = $query->paginate($perPage);

            $data->getCollection()->transform(function ($item) use ($listProdi) {
                $item->nama_prodi = $listProdi[strtoupper($item->kode_identifikasi)] ?? 'Prodi Tidak Dikenal';
                $item->kode_prodi = $item->kode_identifikasi;

                // KITA HAPUS LOGIKA PENGUBAHAN TANGGAL DISINI
                // Biarkan View yang menangani formatting tampilannya.

                return $item;
            });

            $data->appends($request->all());

            // Chart Data (Chart butuh label teks, jadi disini BOLEH diformat)
            $chartData = DB::connection('mysql2')->query()->fromSub($query, 'chart_base')
                ->select('tanggal_kunjungan as label')
                ->selectRaw('SUM(jumlah_kunjungan_harian) as total_kunjungan')
                ->groupBy('label')
                ->orderBy('label', 'asc')
                ->get();

            // Format Label Chart (Hanya untuk Grafik)
            if ($filterType === 'yearly') {
                $chartData->transform(function ($item) {
                    try {
                        $item->label = Carbon::parse($item->label)->locale('id')->isoFormat('MMMM Y');
                    } catch (\Exception $e) {
                    }
                    return $item;
                });
            }
        }

        return view('pages.kunjungan.prodiTable', compact(
            'data',
            'listProdi',
            'tanggalAwal',
            'tanggalAkhir',
            'filterType',
            'tahunAwal',
            'tahunAkhir',
            'perPage',
            'displayPeriod',
            'chartData',
            'totalKeseluruhanKunjungan',
            'hasFilter'
        ));
    }

    public function getDetailPengunjung(Request $request)
    {
        $tanggal = $request->query('tanggal'); // YYYY-MM-DD
        $bulanTahun = $request->query('bulan'); // YYYY-MM
        $kodeIdentifikasi = $request->query('kode_identifikasi');
        $isExport = $request->query('export');

        if ((!$tanggal && !$bulanTahun) || !$kodeIdentifikasi) {
            return response()->json(['error' => 'Parameter tidak lengkap.'], 400);
        }

        // 1. SIAPKAN FILTER TANGGAL (Raw String)
        $dateWhereClause = "";
        if ($bulanTahun) {
            // Filter Bulanan
            $dateWhereClause = "DATE_FORMAT(visittime, '%Y-%m') = '$bulanTahun'";
        } else {
            // Filter Harian
            $startOfDay = Carbon::parse($tanggal)->startOfDay()->toDateTimeString();
            $endOfDay   = Carbon::parse($tanggal)->endOfDay()->toDateTimeString();
            $dateWhereClause = "visittime BETWEEN '$startOfDay' AND '$endOfDay'";
        }

        // 2. BUILD QUERY UNION
        // Query A: History
        $qHistory = DB::connection('mysql2')->table('visitorhistory')
            ->select('cardnumber', 'visittime')
            ->whereRaw($dateWhereClause);

        // Query B: Corner
        $qCorner = DB::connection('mysql2')->table('visitorcorner')
            ->select('cardnumber', 'visittime')
            ->whereRaw($dateWhereClause);

        // 3. WRAPPER QUERY (Gabungan + Join Borrowers)
        $query = DB::connection('mysql2')->query()
            ->fromSub($qHistory->unionAll($qCorner), 'all_visits')
            ->leftJoin('borrowers', 'all_visits.cardnumber', '=', 'borrowers.cardnumber')
            ->select(
                'all_visits.cardnumber',
                'borrowers.surname as nama',
                DB::raw('COUNT(*) as visit_count')
            );

        // 4. FILTER KODE IDENTIFIKASI
        if ($kodeIdentifikasi) {
            $kd = strtoupper($kodeIdentifikasi);
            switch ($kd) {
                case 'DOSEN':
                    $query->where('borrowers.categorycode', 'like', 'TC%');
                    break;
                case 'TENDIK':
                    $query->where('borrowers.categorycode', 'like', 'STAF%');
                    break;
                case 'XA':
                case 'XC':
                case 'LB':
                    // Gunakan all_visits.cardnumber agar tidak ambigu
                    $query->whereRaw("SUBSTR(all_visits.cardnumber, 1, 2) = ?", [$kd]);
                    break;
                case 'KSP':
                    $query->whereRaw("SUBSTR(all_visits.cardnumber, 1, 3) = ?", [$kd]);
                    break;
                case 'KSPMBKM':
                case 'KSPBIPA':
                    $query->whereRaw("SUBSTR(all_visits.cardnumber, 1, 7) = ?", [$kd]);
                    break;
                default:
                    $query->whereRaw("SUBSTR(all_visits.cardnumber, 1, 4) = ?", [$kd]);
                    break;
            }
        }

        // 5. GROUPING & ORDERING
        $query->groupBy('all_visits.cardnumber', 'borrowers.surname')
            ->orderBy('visit_count', 'desc');

        // 6. EKSEKUSI
        if ($isExport) {
            $detailPengunjung = $query->get();
        } else {
            $perPage = $request->input('per_page', 10);
            $detailPengunjung = $query->paginate($perPage);
        }

        return response()->json($detailPengunjung);
    }

    public function getProdiExportData(Request $request)
    {
        $filterType = $request->input('filter_type', 'daily');

        // 1. SETUP TANGGAL (Sama seperti sebelumnya)
        if ($filterType === 'yearly') {
            $tahunAwal = $request->input('tahun_awal', Carbon::now()->year);
            $tahunAkhir = $request->input('tahun_akhir', Carbon::now()->year);
            if ($tahunAwal > $tahunAkhir) $tahunAwal = $tahunAkhir;

            $tanggalAwal = Carbon::createFromDate($tahunAwal, 1, 1)->format('Y-m-d');
            $tanggalAkhir = Carbon::createFromDate($tahunAkhir, 12, 31)->format('Y-m-d');
            $periodeDisplay = "Tahun " . $tahunAwal . ($tahunAwal != $tahunAkhir ? " s/d " . $tahunAkhir : "");
            $sqlDateFormat = "DATE_FORMAT(all_visits.visittime, '%Y-%m-01')";
        } else {
            $tanggalAwal = $request->input('tanggal_awal', Carbon::now()->startOfMonth()->toDateString());
            $tanggalAkhir = $request->input('tanggal_akhir', Carbon::now()->toDateString());
            $periodeDisplay = "Periode " . Carbon::parse($tanggalAwal)->locale('id')->isoFormat('D MMMM Y') . " s.d. " . Carbon::parse($tanggalAkhir)->locale('id')->isoFormat('D MMMM Y');
            $sqlDateFormat = "DATE(all_visits.visittime)";
        }

        $kodeProdiFilter = $request->input('prodi');

        // 2. BUILD QUERY UNION (Sama seperti sebelumnya)
        $strStart = $tanggalAwal . ' 00:00:00';
        $strEnd   = $tanggalAkhir . ' 23:59:59';

        $qHistory = DB::connection('mysql2')->table('visitorhistory')
            ->select('cardnumber', 'visittime')
            ->whereRaw("visittime >= '$strStart' AND visittime <= '$strEnd'");

        $qCorner = DB::connection('mysql2')->table('visitorcorner')
            ->select('cardnumber', 'visittime')
            ->whereRaw("visittime >= '$strStart' AND visittime <= '$strEnd'");

        // 3. WRAPPER QUERY (Sama seperti sebelumnya)
        $baseQuery = DB::connection('mysql2')->query()
            ->fromSub($qHistory->unionAll($qCorner), 'all_visits')
            ->leftJoin('borrowers as b', 'all_visits.cardnumber', '=', 'b.cardnumber')
            ->selectRaw("
            $sqlDateFormat as tanggal_kunjungan,
            CASE
                WHEN b.categorycode LIKE 'TC%' THEN 'DOSEN'
                WHEN b.categorycode LIKE 'STAF%' THEN 'TENDIK'
                WHEN SUBSTR(all_visits.cardnumber, 1, 7) = 'KSPMBKM' THEN 'KSPMBKM'
                WHEN SUBSTR(all_visits.cardnumber, 1, 7) = 'KSPBIPA' THEN 'KSPBIPA'
                WHEN SUBSTR(all_visits.cardnumber, 1, 2) IN ('XA', 'XC', 'LB') THEN SUBSTR(all_visits.cardnumber, 1, 2)
                WHEN SUBSTR(all_visits.cardnumber, 1, 3) = 'KSP' THEN 'KSP'
                ELSE SUBSTR(all_visits.cardnumber, 1, 4)
            END as kode_identifikasi,
            COUNT(*) as jumlah_kunjungan_harian
        ");

        // 4. FILTER PRODI (Sama seperti sebelumnya)
        if ($kodeProdiFilter && strtolower($kodeProdiFilter) !== 'semua') {
            $fc = strtoupper($kodeProdiFilter);
            switch ($fc) {
                case 'DOSEN':
                    $baseQuery->where('b.categorycode', 'like', 'TC%');
                    break;
                case 'TENDIK':
                    $baseQuery->where('b.categorycode', 'like', 'STAF%');
                    break;
                case 'XA':
                case 'XC':
                case 'LB':
                    $baseQuery->whereRaw("SUBSTR(all_visits.cardnumber, 1, 2) = ?", [$fc]);
                    break;
                case 'KSP':
                    $baseQuery->whereRaw("SUBSTR(all_visits.cardnumber, 1, 3) = ?", [$fc]);
                    break;
                case 'KSPMBKM':
                case 'KSPBIPA':
                    $baseQuery->whereRaw("SUBSTR(all_visits.cardnumber, 1, 7) = ?", [$fc]);
                    break;
                default:
                    $baseQuery->whereRaw("SUBSTR(all_visits.cardnumber, 1, 4) = ?", [$fc]);
                    break;
            }
        }

        // 5. GROUPING & EXECUTION
        $data = $baseQuery->groupBy('tanggal_kunjungan', 'kode_identifikasi')
            ->orderBy('tanggal_kunjungan', 'asc')
            ->orderBy('kode_identifikasi', 'asc')
            ->get();

        // --- HITUNG TOTAL KUNJUNGAN (PENAMBAHAN DISINI) ---
        $grandTotal = $data->sum('jumlah_kunjungan_harian');

        // 6. SETUP DATA CSV
        $listProdiFromDb = DB::connection('mysql2')->table('authorised_values')
            ->select('authorised_value', 'lib')
            ->where('category', 'PRODI')
            ->whereRaw('CHAR_LENGTH(lib) >= 13')
            ->orderBy('lib', 'asc')
            ->get()
            ->map(function ($prodi) {
                $cleanedLib = $prodi->lib;
                if (str_starts_with($cleanedLib, 'FAI/ ')) {
                    $cleanedLib = substr($cleanedLib, 5);
                }
                $prodi->lib = trim($cleanedLib);
                return $prodi;
            })->pluck('lib', 'authorised_value');

        $staticValues = [
            'DOSEN' => 'Dosen',
            'TENDIK' => 'Tenaga Kependidikan',
            'XA' => 'Alumni',
            'XC' => 'Dosen Tidak Tetap',
            'KSP' => 'Sekali Kunjung',
            'LB' => 'Anggota Luar Biasa',
            'KSPMBKM' => 'Magang MBKM',
            'KSPBIPA' => 'BIPA'
        ];

        $prodiMapping = $staticValues + $listProdiFromDb->all();
        $namaProdiFilter = $prodiMapping[strtoupper($kodeProdiFilter)] ?? 'Seluruh Kategori';

        // Transform data untuk CSV
        $data->transform(function ($item) use ($prodiMapping) {
            $item->nama_prodi = $prodiMapping[strtoupper($item->kode_identifikasi)] ?? 'Prodi Tidak Dikenal';
            $item->kode_prodi = $item->kode_identifikasi;
            return $item;
        });

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="laporan_kunjungan.csv"',
        ];

        // --- CALLBACK STREAMING (MODIFIKASI DISINI) ---
        // Tambahkan $grandTotal ke 'use (...)'
        $callback = function () use ($data, $filterType, $namaProdiFilter, $periodeDisplay, $grandTotal) {
            $file = fopen('php://output', 'w');
            fputs($file, $bom = chr(0xEF) . chr(0xBB) . chr(0xBF)); // Tambah BOM UTF-8

            // Header Laporan
            fputcsv($file, ["Laporan Statistik Kunjungan: " . $namaProdiFilter], ';');
            fputcsv($file, ["Periode: " . $periodeDisplay], ';');
            fputcsv($file, [''], ';'); // Spasi

            // Header Tabel
            $headers = ['Tanggal / Bulan', 'Kode Identifikasi', 'Nama Prodi/Kategori', 'Jumlah Kunjungan'];
            fputcsv($file, $headers, ';');

            // Isi Data
            foreach ($data as $row) {
                $tanggal = ($filterType === 'yearly') ?
                    Carbon::parse($row->tanggal_kunjungan)->locale('id')->isoFormat('MMMM Y') :
                    Carbon::parse($row->tanggal_kunjungan)->locale('id')->isoFormat('dddd, D MMMM Y');

                fputcsv($file, [
                    $tanggal,
                    $row->kode_prodi,
                    $row->nama_prodi,
                    $row->jumlah_kunjungan_harian
                ], ';');
            }

            fputcsv($file, [
                '',         // Kolom 1 Kosong
                '',         // Kolom 2 Kosong
                'TOTAL',    // Label TOTAL
                $grandTotal // Nilai Total
            ], ';');

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
            'borrowers.surname',
            DB::raw('COUNT(visitorhistory.id) as visit_count')
        )
            ->join('borrowers', 'visitorhistory.cardnumber', '=', 'borrowers.cardnumber')
            ->whereBetween('visittime', [$startDate, $endDate])
            ->groupBy('visitorhistory.cardnumber', 'borrowers.surname')
            ->orderBy('borrowers.surname', 'asc')
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
            'borrowers.surname',
            DB::raw('COUNT(visitorhistory.id) as visit_count')
        )
            ->join('borrowers', 'visitorhistory.cardnumber', '=', 'borrowers.cardnumber')
            ->whereBetween('visittime', [$startDate, $endDate])
            ->groupBy('visitorhistory.cardnumber', 'borrowers.surname')
            ->orderBy('borrowers.surname', 'asc')
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

            $fullBorrowerDetails = DB::connection('mysql2')->table('borrowers')
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

                $semuaKunjungan = DB::connection('mysql2')->select($queryString, $bindings);

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

            $semuaData = DB::connection('mysql2')->select($queryString, $bindings);

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
        $fullBorrowerDetails = DB::connection('mysql2')->table('borrowers')
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

        $dataKunjunganExport = DB::connection('mysql2')->select($queryString, $bindings);

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
        $dataKunjungan = DB::connection('mysql2')->select($queryString, $bindings);
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
            // 'sni' => 'SNI Corner',
            // 'bi' => 'Bank Indonesia Corner',
            // 'mc' => 'Muhammadiyah Corner',
            'pusat' => 'Perpustakaan Pusat',
            'pasca' => 'Perpustakaan Pascasarjana',
            'fk' => 'Perpustakaan Kedokteran',
            // 'ref' => 'Referensi Perpustakaan Pusat',
        ];

        $historyLokasi = DB::connection('mysql2')->table('visitorhistory')->select(DB::raw("IFNULL(location, 'pusat') as lokasi_kunjungan"))->distinct();
        $cornerLokasi = DB::connection('mysql2')->table('visitorcorner')->select(DB::raw("COALESCE(NULLIF(notes, ''), 'pusat') as lokasi_kunjungan"))->distinct();
        $lokasiOptions = $historyLokasi->get()->merge($cornerLokasi->get())->pluck('lokasi_kunjungan')->unique()->sort()->values();

        if ($request->has('filter_type')) {
            $groupByFormat = $filterType == 'yearly' ? 'LEFT(visittime, 7)' : 'DATE(visittime)';

            $historyQuery = DB::connection('mysql2')->table('visitorhistory')
                ->select(DB::raw("{$groupByFormat} as periode"), DB::raw("COUNT(*) as jumlah"))
                ->groupBy('periode');

            $cornerQuery = DB::connection('mysql2')->table('visitorcorner')
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
            $historyLokasiQuery = DB::connection('mysql2')->table('visitorhistory')
                ->select(DB::raw("IFNULL(location, 'pusat') as lokasi"), DB::raw("COUNT(*) as jumlah"))
                ->groupBy('lokasi');

            $cornerLokasiQuery = DB::connection('mysql2')->table('visitorcorner')
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
            $lokasiText = preg_replace('/[^A-Za-z0-9\-]/', '_', $selectedLokasi);
        }

        $groupByFormat = $filterType == 'yearly' ? 'LEFT(visittime, 7)' : 'DATE(visittime)';

        $historyQuery = DB::connection('mysql2')->table('visitorhistory')
            ->select(DB::raw("{$groupByFormat} as periode"), DB::raw("COUNT(*) as jumlah"))
            ->groupBy('periode');

        $cornerQuery = DB::connection('mysql2')->table('visitorcorner')
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

        $callback = function () use ($historyQuery, $cornerQuery, $filterType, $startYear, $endYear, $startDate, $endDate, $selectedLokasi) {

            $file = fopen('php://output', 'w');
            fputs($file, $bom = (chr(0xEF) . chr(0xBB) . chr(0xBF)));

            fputcsv($file, ['Laporan Rekapitulasi Kunjungan Perpustakaan'], ';');
            if ($filterType == 'yearly') {
                fputcsv($file, ["Periode Tahunan:", "{$startYear} - {$endYear}"], ';');
                $headerPeriode = 'Bulan';
            } else { // date_range
                $start = Carbon::parse($startDate)->isoFormat('D MMMM YYYY');
                $end = Carbon::parse($endDate)->isoFormat('D MMMM YYYY');
                fputcsv($file, ["Periode Harian:", "{$start} - {$end}"], ';');
                $headerPeriode = 'Tanggal';
            }
            fputcsv($file, ["Lokasi:", $selectedLokasi ?: 'Semua Lokasi'], ';');
            fputcsv($file, [], ';');

            // Eksekusi query dan proses data
            $allResults = $historyQuery->get()->merge($cornerQuery->get());
            $dataToExport = $allResults->groupBy('periode')->map(fn($item, $key) => (object)['periode' => $key, 'jumlah' => $item->sum('jumlah')])->sortBy('periode')->values();

            // Tulis header tabel
            fputcsv($file, [$headerPeriode, 'Jumlah Kunjungan'], ';');

            // Tulis baris data
            foreach ($dataToExport as $row) {
                $formattedPeriode = $filterType == 'yearly'
                    ? Carbon::parse($row->periode)->isoFormat('MMMM YYYY')
                    : Carbon::parse($row->periode)->isoFormat('dddd, D MMMM YYYY');
                fputcsv($file, [$formattedPeriode, $row->jumlah], ';');
            }

            // Tulis total di akhir
            fputcsv($file, [], ';');
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
