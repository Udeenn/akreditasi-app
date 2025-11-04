<?php

namespace App\Http\Controllers;

use App\Models\M_vishistory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Pagination\Paginator;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VisitHistory extends Controller
{
    public function kunjunganProdiTable(Request $request)
    {
        $listProdiFromDb = DB::connection('mysql2')->table('authorised_values')
            ->select('authorised_value', 'lib')
            ->where('category', 'PRODI')
            ->whereRaw('CHAR_LENGTH(lib) >= 13')
            ->orderBy('authorised_value', 'asc')
            ->get()
            ->map(function ($prodi) {
                $cleanedLib = $prodi->lib;
                if (str_starts_with($cleanedLib, 'FAI/ ')) {
                    $cleanedLib = substr($cleanedLib, 5);
                }
                $prodi->lib = trim($cleanedLib);
                return $prodi;
            })
            ->pluck('lib', 'authorised_value');

        $staticValues = [
            'DOSEN' => 'Dosen',
            'TENDIK' => 'Tenaga Kependidikan',
            'XA' => 'Alumni',
            'KSP' => 'Sekali Kunjung',
            'LB' => 'Anggota Luar Biasa',
            'KSPMBKM' => 'Magang MBKM',
            'KSPBIPA' => 'Bahasa Indonesia dan Penutur Asing'
        ];

        $listProdi = $staticValues + $listProdiFromDb->all();

        $filterType = $request->input('filter_type', 'daily');
        $kodeProdiFilter = $request->input('prodi');
        $perPage = $request->input('per_page', 10);
        $hasFilter = $request->hasAny(['filter_type', 'prodi', 'tanggal_awal', 'tahun_awal']);

        $data = collect([]);
        $chartData = collect([]);
        $totalKeseluruhanKunjungan = 0;
        $tanggalAwal = null;
        $tanggalAkhir = null;
        $tahunAwal = null;
        $tahunAkhir = null;
        $displayPeriod = '';

        if ($hasFilter) {
            if ($filterType === 'yearly') {
                $tahunAwal = $request->input('tahun_awal', Carbon::now()->year);
                $tahunAkhir = $request->input('tahun_akhir', Carbon::now()->year);
                if ($tahunAwal > $tahunAkhir) $tahunAwal = $tahunAkhir;
                $tanggalAwal = Carbon::createFromDate($tahunAwal, 1, 1)->format('Y-m-d');
                $tanggalAkhir = Carbon::createFromDate($tahunAkhir, 12, 31)->format('Y-m-d');
                $displayPeriod = "Tahun " . $tahunAwal . ($tahunAwal != $tahunAkhir ? " s.d. " . $tahunAkhir : "");
            } else { // 'daily'
                $tanggalAwal = $request->input('tanggal_awal', Carbon::now()->startOfMonth()->toDateString());
                $tanggalAkhir = $request->input('tanggal_akhir', Carbon::now()->toDateString());
                $displayPeriod = "Periode " . Carbon::parse($tanggalAwal)->locale('id')->isoFormat('D MMMM Y') . " s.d. " . Carbon::parse($tanggalAkhir)->locale('id')->isoFormat('D MMMM Y');
            }

            $applyProdiFilter = function ($query, $filterCode) {
                if (empty($filterCode) || strtolower($filterCode) === 'semua') {
                    return;
                }
                switch (strtoupper($filterCode)) {
                    case 'DOSEN':
                        $query->where('b.categorycode', 'like', 'TC%');
                        break;
                    case 'TENDIK':
                        $query->where('b.categorycode', 'like', 'STAF%');
                        break;
                    case 'XA':
                    case 'XC':
                    case 'LB':
                        $query->whereRaw('SUBSTR(vh.cardnumber, 1, 2) = ?', [$filterCode]);
                        break;
                    case 'KSP':
                        $query->whereRaw('SUBSTR(vh.cardnumber, 1, 3) = ?', [$filterCode]);
                        break;
                    case 'KSPMBKM':
                    case 'KSPBIPA':
                        $query->whereRaw('SUBSTR(vh.cardnumber, 1, 7) = ?', [$filterCode]);
                        break;
                    default:
                        $query->whereRaw('SUBSTR(vh.cardnumber, 1, 4) = ?', [$filterCode]);
                        break;
                }
            };

            // Kueri untuk total keseluruhan
            $totalKeseluruhanQuery = M_vishistory::query()->from('koha.visitorhistory as vh')
                ->leftJoin('borrowers as b', 'vh.cardnumber', '=', 'b.cardnumber')
                ->whereBetween('vh.visittime', [$tanggalAwal . ' 00:00:00', $tanggalAkhir . ' 23:59:59']);

            $applyProdiFilter($totalKeseluruhanQuery, $kodeProdiFilter);
            $totalKeseluruhanKunjungan = $totalKeseluruhanQuery->count();

            $baseQuery = M_vishistory::query()->from('koha.visitorhistory as vh')
                ->leftJoin('borrowers as b', 'vh.cardnumber', '=', 'b.cardnumber')
                ->selectRaw('
                ' . ($filterType === 'yearly' ? 'DATE_FORMAT(vh.visittime, "%Y-%m")' : 'DATE(vh.visittime)') . ' as tanggal_kunjungan,
                CASE
                    -- PERUBAHAN 2: Logika identifikasi Dosen dan Tendik
                    WHEN b.categorycode LIKE "TC%" THEN "DOSEN"
                    WHEN b.categorycode LIKE "STAF%" THEN "TENDIK"
                    WHEN SUBSTR(vh.cardnumber, 1, 7) = "KSPMBKM" THEN "KSPMBKM"
                    WHEN SUBSTR(vh.cardnumber, 1, 7) = "KSPBIPA" THEN "KSPBIPA"
                    WHEN SUBSTR(vh.cardnumber, 1, 2) = "XA" THEN "XA"
                    WHEN SUBSTR(vh.cardnumber, 1, 2) = "XC" THEN "XC"
                    WHEN SUBSTR(vh.cardnumber, 1, 3) = "KSP" THEN "KSP"
                    WHEN SUBSTR(vh.cardnumber, 1, 2) = "LB" THEN "LB"
                    -- Logika `LENGTH <= 6` dihapus karena sudah digantikan
                    ELSE SUBSTR(vh.cardnumber, 1, 4)
                END as kode_identifikasi,
                COUNT(vh.id) as jumlah_kunjungan_harian
            ')
                ->whereBetween('vh.visittime', [$tanggalAwal . ' 00:00:00', $tanggalAkhir . ' 23:59:59']);

            $applyProdiFilter($baseQuery, $kodeProdiFilter);

            $baseQuery->groupBy('tanggal_kunjungan', 'kode_identifikasi')
                ->orderBy('tanggal_kunjungan', 'asc')
                ->orderBy('kode_identifikasi', 'asc');

            $data = $baseQuery->paginate($perPage);

            $prodiMapping = $staticValues + $listProdiFromDb->all();
            $data->getCollection()->transform(function ($item) use ($prodiMapping) {
                $item->nama_prodi = $prodiMapping[strtoupper($item->kode_identifikasi)] ?? 'Prodi Tidak Dikenal';
                $item->kode_prodi = $item->kode_identifikasi;
                return $item;
            });
            $data->appends($request->all());

            $chartData = (clone $baseQuery)->selectRaw('
            ' . ($filterType === 'yearly' ? 'DATE_FORMAT(vh.visittime, "%Y-%m")' : 'DATE(vh.visittime)') . ' as label,
            COUNT(vh.id) as total_kunjungan
        ')->reorder()->groupBy('label')->orderBy('label', 'asc')->get();
        }

        return view('pages.kunjungan.prodiTable', compact('data', 'listProdi', 'tanggalAwal', 'tanggalAkhir', 'filterType', 'tahunAwal', 'tahunAkhir', 'perPage', 'displayPeriod', 'chartData', 'totalKeseluruhanKunjungan', 'hasFilter'));
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

        $query = M_vishistory::select(
            'visitorhistory.cardnumber',
            'borrowers.surname as nama',
            DB::raw('COUNT(visitorhistory.id) as visit_count')
        )
            ->leftJoin('borrowers', 'visitorhistory.cardnumber', '=', 'borrowers.cardnumber');

        if ($bulanTahun) {
            $query->where(DB::raw('DATE_FORMAT(visitorhistory.visittime, "%Y-%m")'), $bulanTahun);
        } else {
            $startOfDay = Carbon::parse($tanggal)->startOfDay()->toDateTimeString();
            $endOfDay = Carbon::parse($tanggal)->endOfDay()->toDateTimeString();
            $query->whereBetween('visitorhistory.visittime', [$startOfDay, $endOfDay]);
        }

        switch (strtoupper($kodeIdentifikasi)) {
            // untuk DOSEN
            case 'DOSEN':
                $query->where('borrowers.categorycode', 'like', 'TC%');
                break;
            // untuk TENDIK
            case 'TENDIK':
                $query->where('borrowers.categorycode', 'like', 'STAF%');
                break;

            case 'XA':
            case 'XC':
            case 'LB':
                $query->whereRaw('SUBSTR(visitorhistory.cardnumber, 1, 2) = ?', [$kodeIdentifikasi]);
                break;
            case 'KSP':
                $query->whereRaw('SUBSTR(visitorhistory.cardnumber, 1, 3) = ?', [$kodeIdentifikasi]);
                break;
            case 'KSPMBKM':
            case 'KSPBIPA':
                $query->whereRaw('SUBSTR(visitorhistory.cardnumber, 1, 7) = ?', [$kodeIdentifikasi]);
                break;
            default: // Ini untuk prodi mahasiswa
                $query->whereRaw('SUBSTR(visitorhistory.cardnumber, 1, 4) = ?', [$kodeIdentifikasi]);
                break;
        }

        $query->groupBy('visitorhistory.cardnumber', 'borrowers.surname')
            ->orderBy('visit_count', 'desc');

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
        if ($filterType === 'yearly') {
            $tahunAwal = $request->input('tahun_awal', Carbon::now()->year);
            $tahunAkhir = $request->input('tahun_akhir', Carbon::now()->year);
            if ($tahunAwal > $tahunAkhir) $tahunAwal = $tahunAkhir;
            $tanggalAwal = Carbon::createFromDate($tahunAwal, 1, 1)->format('Y-m-d');
            $tanggalAkhir = Carbon::createFromDate($tahunAkhir, 12, 31)->format('Y-m-d');
            $periodeDisplay = "Tahun " . $tahunAwal . ($tahunAwal != $tahunAkhir ? " s/d " . $tahunAkhir : "");
        } else { // 'daily'
            $tanggalAwal = $request->input('tanggal_awal', Carbon::now()->startOfMonth()->toDateString());
            $tanggalAkhir = $request->input('tanggal_akhir', Carbon::now()->toDateString());
            $periodeDisplay = "Periode " . Carbon::parse($tanggalAwal)->locale('id')->isoFormat('D MMMM Y') . " s.d. " . Carbon::parse($tanggalAkhir)->locale('id')->isoFormat('D MMMM Y');
        }

        $kodeProdiFilter = $request->input('prodi');

        // Bangun kueri tanpa paginasi
        $baseQuery = DB::connection('mysql2')->table('visitorhistory as vh')
            ->leftJoin('borrowers as b', 'vh.cardnumber', '=', 'b.cardnumber')
            ->selectRaw('
            ' . ($filterType === 'yearly' ? 'DATE_FORMAT(vh.visittime, "%Y-%m")' : 'DATE(vh.visittime)') . ' as tanggal_kunjungan,
            CASE
                WHEN b.categorycode LIKE "TC%" THEN "DOSEN"
                WHEN b.categorycode LIKE "STAF%" THEN "TENDIK"
                WHEN SUBSTR(vh.cardnumber, 1, 7) = "KSPMBKM" THEN "KSPMBKM"
                WHEN SUBSTR(vh.cardnumber, 1, 7) = "KSPBIPA" THEN "KSPBIPA"
                WHEN SUBSTR(vh.cardnumber, 1, 2) = "XA" THEN "XA"
                WHEN SUBSTR(vh.cardnumber, 1, 2) = "XC" THEN "XC"
                WHEN SUBSTR(vh.cardnumber, 1, 3) = "KSP" THEN "KSP"
                WHEN SUBSTR(vh.cardnumber, 1, 2) = "LB" THEN "LB"
                ELSE SUBSTR(vh.cardnumber, 1, 4)
            END as kode_identifikasi,
            COUNT(vh.id) as jumlah_kunjungan_harian
        ')
            ->whereBetween('vh.visittime', [$tanggalAwal . ' 00:00:00', $tanggalAkhir . ' 23:59:59']);

        if ($kodeProdiFilter && strtolower($kodeProdiFilter) !== 'semua') {
            switch (strtoupper($kodeProdiFilter)) {
                case 'DOSEN':
                    $baseQuery->where('b.categorycode', 'like', 'TC%');
                    break;
                case 'TENDIK':
                    $baseQuery->where('b.categorycode', 'like', 'STAF%');
                    break;
                case 'XA':
                case 'XC':
                case 'LB':
                    $baseQuery->whereRaw('SUBSTR(vh.cardnumber, 1, 2) = ?', [$kodeProdiFilter]);
                    break;
                case 'KSP':
                    $baseQuery->whereRaw('SUBSTR(vh.cardnumber, 1, 3) = ?', [$kodeProdiFilter]);
                    break;
                case 'KSPMBKM':
                case 'KSPBIPA':
                    $baseQuery->whereRaw('SUBSTR(vh.cardnumber, 1, 7) = ?', [$kodeProdiFilter]);
                    break;
                default:
                    $baseQuery->whereRaw('SUBSTR(vh.cardnumber, 1, 4) = ?', [$kodeProdiFilter]);
                    break;
            }
        }

        $data = $baseQuery->groupBy('tanggal_kunjungan', 'kode_identifikasi')
            ->orderBy('tanggal_kunjungan', 'asc')
            ->orderBy('kode_identifikasi', 'asc')
            ->get();

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
            })
            ->pluck('lib', 'authorised_value');

        $staticValues = [
            'DOSEN' => 'Dosen',
            'TENDIK' => 'Tenaga Kependidikan',
            'XA' => 'Alumni',
            'XC' => 'Dosen Tidak Tetap',
            'KSP' => 'Sekali Kunjung',
            'LB' => 'Anggota Luar Biasa',
            'KSPMBKM' => 'Magang MBKM',
            'KSPBIPA' => 'Bahasa Indonesia dan Penutur Asing (BIPA)'
        ];

        $prodiMapping = $staticValues + $listProdiFromDb->all();
        $namaProdiFilter = $prodiMapping[strtoupper($kodeProdiFilter)] ?? 'Seluruh Kategori';

        $data->transform(function ($item) use ($prodiMapping) {
            $item->nama_prodi = $prodiMapping[strtoupper($item->kode_identifikasi)] ?? 'Prodi Tidak Dikenal';
            $item->kode_prodi = $item->kode_identifikasi;
            return $item;
        });

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="laporan_kunjungan.csv"',
        ];

        $callback = function () use ($data, $filterType, $namaProdiFilter, $periodeDisplay) {
            $file = fopen('php://output', 'w');
            fputs($file, $bom = chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($file, ["Laporan Statistik Kunjungan: " . $namaProdiFilter], ';');
            fputcsv($file, ["Periode: " . $periodeDisplay], ';');
            fputcsv($file, [''], ';');

            $headers = ['Tanggal / Bulan', 'Kode Identifikasi', 'Nama Prodi/Kategori', 'Jumlah Kunjungan'];
            fputcsv($file, $headers, ';');

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

                // Manual pagination
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

        return view('pages.kunjungan.cekKehadiran', compact('dataKunjungan', 'fullBorrowerDetails', 'pesan', 'cardnumber', 'tahun'));
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

        $borrowerInfo = M_vishistory::where('cardnumber', $cardnumber)->first();
        if (!$borrowerInfo) {
            return response()->json(['error' => 'Nomor Kartu Anggota (Cardnumber) tidak ditemukan dalam histori kunjungan.'], 404);
        }

        $query = M_vishistory::on('mysql2')
            ->selectRaw('
            EXTRACT(YEAR_MONTH FROM visittime) as tahun_bulan,
            COUNT(id) as jumlah_kunjungan
        ')
            ->where('cardnumber', $cardnumber)
            ->when($tahun, function ($query, $tahun) {
                return $query->whereYear('visittime', $tahun);
            });

        $dataKunjungan = $query
            ->groupBy(DB::raw('EXTRACT(YEAR_MONTH FROM visittime)'))
            ->orderBy(DB::raw('EXTRACT(YEAR_MONTH FROM visittime)'), 'asc')
            ->get();

        $fullBorrowerDetails = DB::connection('mysql2')->table('borrowers')
            ->select('cardnumber', 'firstname', 'surname')
            ->where('cardnumber', $cardnumber)
            ->first();

        $exportData = $dataKunjungan->map(function ($row) {
            return [
                'bulan_tahun' => \Carbon\Carbon::createFromFormat('Ym', $row->tahun_bulan)->format('M Y'),
                'jumlah_kunjungan' => $row->jumlah_kunjungan,
            ];
        });

        return response()->json([
            'data' => $exportData,
            'borrower_name' => $fullBorrowerDetails ? $fullBorrowerDetails->firstname . ' ' . $fullBorrowerDetails->surname : 'Unknown',
            'cardnumber' => $cardnumber,
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
        // --- 1. Ambil Semua Filter ---
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

        // --- 2. Siapkan Nama File Dinamis ---
        $lokasiText = 'Semua_Lokasi';
        if ($selectedLokasi) {
            $lokasiText = preg_replace('/[^A-Za-z0-9\-]/', '_', $selectedLokasi);
        }

        // --- 3. Bangun Query Secara Dinamis (Sama Seperti di Controller Utama) ---
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

        // --- 4. Definisikan Callback untuk Men-generate CSV ---
        $callback = function () use ($historyQuery, $cornerQuery, $filterType, $startYear, $endYear, $startDate, $endDate, $selectedLokasi) {

            $file = fopen('php://output', 'w');
            fputs($file, $bom = (chr(0xEF) . chr(0xBB) . chr(0xBF)));

            // Tulis header informasi
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
