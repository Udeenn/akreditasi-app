<?php

namespace App\Http\Controllers;

use App\Models\M_eprodi;
use Illuminate\Support\Facades\Log;
use App\Models\M_items;
use Illuminate\Http\Request;
use App\Helpers\CnClassHelper;
use App\Helpers\CnClassHelperr;
use App\Helpers\QueryHelper;
use App\Models\M_Auv;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class StatistikKoleksi extends Controller
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
                $map[$prodiCode] = 'Unknown';
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
        return $map;
    }

    public function rekapPerFakultas(Request $request)
    {
        $request->validate([
            'fakultas' => 'string|nullable',
            'tahun' => 'numeric|nullable'
        ]);

        $listprodi = M_Auv::where('category', 'PRODI')->whereRaw('CHAR_LENGTH(lib) >= 13')
            ->onlyProdiTampil()
            // ->excludeProdi()
            ->orderBy('lib', 'asc')
            ->get();

        $prodiToFacultyMap = $this->getProdiToFacultyMap($listprodi);
        $prodiCodeToNameMap = $listprodi->pluck('lib', 'authorised_value');
        $faculties = array_unique(array_values($prodiToFacultyMap));
        sort($faculties);
        // $selectedFaculty = $request->input('fakultas', $faculties[0] ?? null);
        $selectedFaculty = $request->input('fakultas');

        $tahunTerakhir = $request->input('tahun', 'all');
        $rekapData = collect();
        $rekapDataPerProdi = [];

        if ($selectedFaculty) {
            $targetProdiCodes = array_keys($prodiToFacultyMap, $selectedFaculty);
            Log::info('Target Prodi Codes for ' . $selectedFaculty . ':', $targetProdiCodes);

            foreach ($targetProdiCodes as $prodiCode) {
                Log::info('Processing counts for Prodi: ' . $prodiCode);
                $namaProdi = $prodiCodeToNameMap->get($prodiCode, 'Prodi Tidak Dikenal');
                try {
                    $cnClasses = CnClassHelperr::getCnClassByProdi($prodiCode);
                    if (is_array($cnClasses) && isset($cnClasses[0]) && is_array($cnClasses[0])) {
                        $cnClasses = $cnClasses[0];
                    }

                    $queryJurnal = M_items::query()
                        ->from('items as i')
                        ->select(
                            'bi.cn_class AS Kelas',
                            DB::raw("CONCAT_WS(' ', b.title, EXTRACTVALUE(bm.metadata, '//datafield[@tag=\"245\"]/subfield[@code=\"b\"]')) AS Judul"),
                            'i.enumchron AS Nomor',
                            'av.lib AS Jenis_Koleksi',
                            'it.description AS Jenis_Item_Tipe',
                            DB::raw('COUNT(DISTINCT i.enumchron) AS Issue'),
                            DB::raw('COUNT(*) AS Eksemplar'),
                        )
                        ->join('biblio as b', 'b.biblionumber', '=', 'i.biblionumber')
                        ->join('biblioitems as bi', 'bi.biblionumber', '=', 'i.biblionumber')
                        ->join('biblio_metadata as bm', 'bm.biblionumber', '=', 'i.biblionumber')
                        ->leftJoin('itemtypes as it', 'it.itemtype', '=', 'i.itype')
                        ->leftJoin('authorised_values as av', function ($join) {
                            $join->on('av.authorised_value', '=', 'i.ccode')
                                ->where('av.category', '=', 'CCODE');
                        })
                        ->where('i.itemlost', 0)
                        ->where('i.withdrawn', 0)
                        ->whereIn('i.itype', ['JR', 'JRA', 'JRT'])
                        ->where('i.barcode', 'not like', 'JE%')

                        ->whereRaw("TRIM(i.enumchron) REGEXP '[0-9]{4}$'");

                    QueryHelper::applyCnClassRules($queryJurnal, $cnClasses);
                    if ($tahunTerakhir !== 'all') {
                        $queryJurnal->whereRaw('RIGHT(i.enumchron, 4) >= ?', [date('Y') - (int)$tahunTerakhir]);
                    }
                    $queryJurnal->groupBy(
                        'Judul',
                        'Kelas',
                        'bi.publishercode',
                        'Jenis_Koleksi',
                        'Jenis_Item_Tipe',
                        'Nomor',
                        'av.lib',
                        'it.description',
                        'i.homebranch'
                    );

                    $rowsJurnal = $queryJurnal->get();
                    $totalJudulJurnal = $rowsJurnal->count();
                    $totalEksemplarJurnal = $rowsJurnal->sum('Eksemplar');

                    if ($totalJudulJurnal > 0 || $totalEksemplarJurnal > 0) {
                        $prodiCounts['Jurnal'] = [
                            'judul' => $totalJudulJurnal,
                            'eksemplar' => $totalEksemplarJurnal,
                        ];
                    }

                    // 1. EJURNAL
                    $queryEjurnal = M_items::query()
                        ->from('items as i')
                        ->select(
                            'bi.cn_class AS Kelas',
                            DB::raw("CONCAT_WS(' ', b.title, EXTRACTVALUE(bm.metadata, '//datafield[@tag=\"245\"]/subfield[@code=\"b\"]')) AS Judul"),
                            'i.enumchron AS Nomor',
                            'av.lib AS Jenis_Koleksi',
                            'it.description AS Jenis_Item_Tipe',
                            DB::raw('COUNT(DISTINCT i.enumchron) AS Issue'),
                            DB::raw('COUNT(*) AS Eksemplar'),
                        )
                        ->join('biblio as b', 'b.biblionumber', '=', 'i.biblionumber')
                        ->join('biblioitems as bi', 'bi.biblionumber', '=', 'i.biblionumber')
                        ->join('biblio_metadata as bm', 'bm.biblionumber', '=', 'i.biblionumber')
                        ->leftJoin('itemtypes as it', 'it.itemtype', '=', 'i.itype')
                        ->leftJoin('authorised_values as av', function ($join) {
                            $join->on('av.authorised_value', '=', 'i.ccode')
                                ->where('av.category', '=', 'CCODE');
                        })
                        ->where('i.itemlost', 0)
                        ->where('i.withdrawn', 0)
                        // ->whereIn('i.itype', ['EJ'])
                        ->whereIn('i.itype', ['JR', 'JRA', 'JRT'])
                        ->where('i.barcode', 'like', 'JE%')
                        ->whereRaw("TRIM(i.enumchron) REGEXP '[0-9]{4}$'");

                    QueryHelper::applyCnClassRules($queryEjurnal, $cnClasses);
                    if ($tahunTerakhir !== 'all') {
                        $queryEjurnal->whereRaw('RIGHT(i.enumchron, 4) >= ?', [date('Y') - (int)$tahunTerakhir]);
                    }
                    $queryEjurnal->groupBy(
                        'Judul',
                        'Kelas',
                        'bi.publishercode',
                        'Jenis_Koleksi',
                        'Jenis_Item_Tipe',
                        'Nomor',
                        'av.lib',
                        'it.description',
                        'i.homebranch'
                    );

                    $rowsEjurnal = $queryEjurnal->get();
                    $totalJudulEjurnal = $rowsEjurnal->count();
                    $totalEksemplarEjurnal = $rowsEjurnal->sum('Eksemplar');

                    if ($totalJudulEjurnal > 0 || $totalEksemplarEjurnal > 0) {
                        $prodiCounts['E-Jurnal'] = [
                            'judul' => $totalJudulEjurnal,
                            'eksemplar' => $totalEksemplarEjurnal,
                        ];
                    }

                    // 2. TEXTBOOK
                    $queryTextbook = M_items::query()
                        ->from('items as i')
                        ->join('biblioitems as bi', 'i.biblionumber', '=', 'bi.biblionumber')
                        ->join('biblio as b', 'i.biblionumber', '=', 'b.biblionumber')
                        ->where('i.itemlost', 0)->where('i.withdrawn', 0)
                        ->whereIn('i.itype', ['BKS', 'BKSA', 'BKSCA', 'BKSC'])
                        ->whereRaw('LEFT(i.ccode, 1) <> "R"');
                    QueryHelper::applyCnClassRules($queryTextbook, $cnClasses);
                    $totalsTextbook = $queryTextbook->selectRaw("COUNT(DISTINCT i.biblionumber) as total_judul, COUNT(i.itemnumber) as total_eksemplar")->first();
                    if ($totalsTextbook && ($totalsTextbook->total_judul > 0 || $totalsTextbook->total_eksemplar > 0)) {
                        $prodiCounts['Textbook'] = ['judul' => $totalsTextbook->total_judul, 'eksemplar' => $totalsTextbook->total_eksemplar];
                    }
                    // 3. E-BOOK
                    $queryEbook = M_items::query()
                        ->from('items as i')
                        ->join('biblioitems as bi', 'i.biblionumber', '=', 'bi.biblionumber')
                        ->join('biblio as b', 'i.biblionumber', '=', 'b.biblionumber')
                        ->where('i.itemlost', 0)->where('i.withdrawn', 0)
                        ->where('i.itype', 'EB');
                    QueryHelper::applyCnClassRules($queryEbook, $cnClasses);
                    $totalsEbook = $queryEbook->selectRaw("COUNT(DISTINCT i.biblionumber) as total_judul, COUNT(i.itemnumber) as total_eksemplar")->first();
                    if ($totalsEbook && ($totalsEbook->total_judul > 0 || $totalsEbook->total_eksemplar > 0)) {
                        $prodiCounts['E-Book'] = ['judul' => $totalsEbook->total_judul, 'eksemplar' => $totalsEbook->total_eksemplar];
                    }

                    // 4. PROSIDING
                    $queryProsiding = M_items::query()
                        ->from('items as i')
                        ->join('biblioitems as bi', 'i.biblionumber', '=', 'bi.biblionumber')
                        ->join('biblio as b', 'i.biblionumber', '=', 'b.biblionumber')
                        ->where('i.itemlost', 0)->where('i.withdrawn', 0)
                        ->whereIn('i.itype', ['PR', 'EPR']);
                    QueryHelper::applyCnClassRules($queryProsiding, $cnClasses);
                    $totalsProsiding = $queryProsiding->selectRaw("COUNT(DISTINCT i.biblionumber) as total_judul, COUNT(i.itemnumber) as total_eksemplar")->first();
                    if ($totalsProsiding && ($totalsProsiding->total_judul > 0 || $totalsProsiding->total_eksemplar > 0)) {
                        $prodiCounts['Prosiding'] = ['judul' => $totalsProsiding->total_judul, 'eksemplar' => $totalsProsiding->total_eksemplar];
                    }

                    // 5. REFERENSI
                    $queryRef = M_items::query()
                        ->from('items as i')
                        ->join('biblioitems as bi', 'i.biblionumber', '=', 'bi.biblionumber')
                        ->join('biblio as b', 'i.biblionumber', '=', 'b.biblionumber')
                        ->where('i.itemlost', 0)->where('i.withdrawn', 0)
                        ->whereRaw('LEFT(i.itype,3) = "BKS"')
                        ->whereRaw('LEFT(i.ccode, 1) = ?', ['R']);
                    QueryHelper::applyCnClassRules($queryRef, $cnClasses);
                    $totalsRef = $queryRef->selectRaw("COUNT(DISTINCT i.biblionumber) as total_judul, COUNT(i.itemnumber) as total_eksemplar")->first();
                    if ($totalsRef && ($totalsRef->total_judul > 0 || $totalsRef->total_eksemplar > 0)) {
                        $prodiCounts['Referensi'] = ['judul' => $totalsRef->total_judul, 'eksemplar' => $totalsRef->total_eksemplar];
                    }

                    $order = [
                        'Textbook' => 1,
                        'E-Book' => 2,
                        'Jurnal' => 3,
                        'E-Jurnal' => 4,
                        'Prosiding' => 5,
                        'Referensi' => 6,
                        'Lainnya' => 7,
                    ];
                    uksort($prodiCounts, function ($a, $b) use ($order) {
                        return ($order[$a] ?? 99) <=> ($order[$b] ?? 99);
                    });

                    // Tambahkan hasil prodi ini ke array utama
                    if (!empty($prodiCounts)) {
                        $rekapDataPerProdi[] = [
                            'prodi_code' => $prodiCode,
                            'nama_prodi' => $namaProdi,
                            'counts' => $prodiCounts
                        ];
                    }
                } catch (\Exception $e) {
                    Log::error('Error processing counts for Prodi ' . $prodiCode . ': ' . $e->getMessage());
                }
            }

            $rekapData = collect($rekapDataPerProdi)->sortBy('nama_prodi')->values();
            Log::info('Final Aggregated Data (Hybrid Query):', $rekapData->toArray());
            // dd($rekapData->toArray());
        }



        return view('pages.dapus.rekap_fakultas', compact(
            'faculties',
            'selectedFaculty',
            'rekapData'
        ));
    }

    public function prosiding(Request $request)
    {
        $listprodi = M_Auv::where('category', 'PRODI')
            ->whereRaw('CHAR_LENGTH(lib) >= 13')
            ->onlyProdiTampil()
            ->orderBy('authorised_value', 'asc')
            ->get();

        $prodiOptionAll = new \stdClass();
        $prodiOptionAll->authorised_value = 'all';
        $prodiOptionAll->lib = 'Semua Program Studi';

        $listprodi->prepend($prodiOptionAll);

        $prodi = $request->input('prodi', 'initial');
        $tahunTerakhir = $request->input('tahun', 'all');

        $data = collect();
        $namaProdi = '';
        $dataExists = false;
        $totalJudul = 0;
        $totalEksemplar = 0;

        if ($prodi && $prodi !== 'initial') {
            $prodiMapping = $listprodi->pluck('lib', 'authorised_value')->toArray();
            $namaProdi = $prodiMapping[$prodi] ?? 'Tidak Ditemukan';

            $query = M_items::selectRaw(
                "
                bi.cn_class as Kelas,
                EXTRACTVALUE(bm.metadata,'//datafield[@tag=\"245\"]/subfield[@code=\"a\"]') as Judul_a,
                EXTRACTVALUE(bm.metadata,'//datafield[@tag=\"245\"]/subfield[@code=\"b\"]') as Judul_b,
                EXTRACTVALUE(bm.metadata,'//datafield[@tag=\"245\"]/subfield[@code=\"c\"]') as Judul_c,
                b.author as Pengarang,
                MAX(CONCAT(COALESCE(bi.publishercode,''), ' ', COALESCE(bi.place,''))) AS Penerbit,
                bi.publicationyear AS TahunTerbit,
                items.enumchron AS Nomor,
                COUNT(DISTINCT items.itemnumber) AS Issue,
                COUNT(items.itemnumber) AS Eksemplar,
                IF(
                EXTRACTVALUE(bm.metadata,'//datafield[@tag=\"856\"]/subfield[@code=\"a\"]') <> '',
                EXTRACTVALUE(bm.metadata,'//datafield[@tag=\"856\"]/subfield[@code=\"a\"]'),
                EXTRACTVALUE(bm.metadata,'//datafield[@tag=\"856\"]/subfield[@code=\"u\"]')
                ) AS Link_Prosiding,
                CASE
                WHEN items.homebranch = 'PUSAT' THEN 'Perpustakaan Pusat'
                WHEN items.homebranch = 'GIZI' THEN 'Perpustakaan Gizi'
                WHEN items.homebranch = 'FKG' THEN 'Perpustakaan Kedokteran Gigi'
                WHEN items.homebranch = 'PSIKO' THEN 'Perpustakaan Psikologi'
                WHEN items.homebranch = 'INF' THEN 'Perpustakaan Informatika'
                WHEN items.homebranch = 'FIK' THEN 'Perpustakaan FIK'
                WHEN items.homebranch = 'MATH' THEN 'Perpustakaan Matematika FKIP'
                WHEN items.homebranch = 'LIPK' THEN 'LIPK'
                WHEN items.homebranch = 'TILIB' THEN 'Perpustakaan Teknik Industri'
                WHEN items.homebranch = 'MAPRO' THEN 'Perpustakaan Magister Psikologi'
                WHEN items.homebranch = 'MEDLIB' THEN 'Perpustakaan Kedokteran'
                WHEN items.homebranch = 'PAUD' THEN 'Perpustakaan PAUD'
                WHEN items.homebranch = 'POG' THEN 'Perpustakaan Pendidikan Olahraga'
                WHEN items.homebranch = 'PESMA' THEN 'Perpustakaan Pesma Haji Mas Mansyur'
                WHEN items.homebranch = 'PGSDKRA' THEN 'Perpustakaan PGSD'
                WHEN items.homebranch = 'PASCA' THEN 'Perpustakaan Pasca Sarjana'
                WHEN items.homebranch = 'RSGM' THEN 'Perpustakaan Rumah Sakit Gigi dan Mulut'
                WHEN items.homebranch = 'PSI' THEN 'Perpustakaan Pusat Studi Psikologi Islam'
                WHEN items.homebranch = 'FG' THEN 'Perpustakaan Fakultas Geografi'
                ELSE items.homebranch
                END AS Lokasi"
            )
                ->join('biblioitems as bi', 'items.biblionumber', '=', 'bi.biblionumber')
                ->join('biblio as b', 'b.biblionumber', '=', 'bi.biblionumber')
                ->join('biblio_metadata as bm', 'b.biblionumber', '=', 'bm.biblionumber')
                ->where('items.itemlost', 0)
                ->where('items.withdrawn', 0)
                ->whereIn('items.itype', ['EPR', 'PR']);

            if ($prodi !== 'all') {
                $cnClasses = CnClassHelperr::getCnClassByProdi($prodi);
                QueryHelper::applyCnClassRules($query, $cnClasses);
            }

            if ($tahunTerakhir !== 'all') {
                $query->whereRaw('bi.publicationyear >= YEAR(CURDATE()) - ?', [$tahunTerakhir]);
            }

            $query->orderBy('TahunTerbit', 'desc');
            $query->groupBy('Judul_a', 'Judul_b', 'Judul_c', 'Pengarang', 'Nomor',  'TahunTerbit', 'Lokasi', 'Link_Prosiding', 'Kelas');

            $processedData = $query->get()->map(function ($row) {
                $fullJudul = $row->Judul_a;
                if (!empty($row->Judul_b)) {
                    $fullJudul .= ' : ' . $row->Judul_b;
                }
                if (!empty($row->Judul_c)) {
                    $fullJudul .= ' / ' . $row->Judul_c;
                }
                $row->Judul = $fullJudul;

                $row->Judul = html_entity_decode($row->Judul, ENT_QUOTES, 'UTF-8');
                $row->Penerbit = html_entity_decode($row->Penerbit, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $row->Pengarang = html_entity_decode($row->Pengarang, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                return $row;
            });

            $totalQuery = M_items::selectRaw("
                COUNT(DISTINCT b.biblionumber) as total_judul,
                COUNT(items.itemnumber) as total_eksemplar
            ")
                ->join('biblioitems as bi', 'items.biblionumber', '=', 'bi.biblionumber')
                ->join('biblio as b', 'b.biblionumber', '=', 'bi.biblionumber')
                ->where('items.itemlost', 0)
                ->where('items.withdrawn', 0)
                ->whereIn('items.itype', ['EPR', 'PR']);

            if ($prodi !== 'all') {
                $cnClasses = CnClassHelperr::getCnClassByProdi($prodi);
                // Terapkan aturan yang sama ke query total
                QueryHelper::applyCnClassRules($totalQuery, $cnClasses);
            }

            if ($tahunTerakhir !== 'all') {
                $totalQuery->whereRaw('bi.publicationyear >= YEAR(CURDATE()) - ?', [$tahunTerakhir]);
            }

            $totals = $totalQuery->first();
            $totalJudul = $totals->total_judul ?? 0;
            $totalEksemplar = $totals->total_eksemplar ?? 0;

            if ($request->has('export_csv')) {
                return $this->exportCsvProsiding($processedData, $namaProdi, $tahunTerakhir);
            } else {
                $data = $processedData;
                $dataExists = $data->isNotEmpty();
            }
        }

        return view('pages.dapus.prosiding', compact('data', 'prodi', 'listprodi', 'namaProdi', 'tahunTerakhir', 'dataExists', 'totalJudul', 'totalEksemplar'));
    }

    /**
     * Tampilkan data koleksi jurnal dan tangani ekspor CSV.
     *
     * @param Request $request
     * @return \Illuminate\View\View|\Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function jurnal(Request $request)
    {
        $listprodi = M_Auv::where('category', 'PRODI')
            ->whereRaw('CHAR_LENGTH(lib) >= 13')
            ->onlyProdiTampil()
            ->orderBy('authorised_value', 'asc')
            ->get();
        $prodiOptionAll = new \stdClass();
        $prodiOptionAll->authorised_value = 'all';
        $prodiOptionAll->lib = 'Semua Program Studi';
        $listprodi->prepend($prodiOptionAll);

        $prodi = $request->input('prodi', 'initial');
        $tahunTerakhir = $request->input('tahun', 'all');

        $data = collect();
        $namaProdi = '';
        $dataExists = false;
        $totalJudul = 0;
        $totalEksemplar = 0;

        if ($prodi && $prodi !== 'initial') {
            $prodiMapping = $listprodi->pluck('lib', 'authorised_value')->toArray();
            $namaProdi = $prodiMapping[$prodi] ?? 'Tidak Ditemukan';
            //  DB::raw("MAX(CONCAT(COALESCE(bi.publishercode,''), ' ', COALESCE(bi.place,''))) AS Penerbit"),
            $query = M_items::query()
                ->from('items as i')
                ->select(
                    'bi.cn_class AS Kelas',
                    DB::raw("GROUP_CONCAT(i.barcode ORDER BY i.barcode ASC SEPARATOR ', ') as Barcode"),
                    DB::raw("CONCAT_WS(' ', b.title, EXTRACTVALUE(bm.metadata, '//datafield[@tag=\"245\"]/subfield[@code=\"b\"]')) AS Judul"),
                    'bi.publishercode AS Penerbit',
                    'i.enumchron AS Nomor',
                    'av.lib AS Jenis_Koleksi',
                    'it.description AS Jenis_Item_Tipe',
                    DB::raw('COUNT(DISTINCT i.enumchron) AS Issue'),
                    DB::raw('COUNT(*) AS Eksemplar'),
                    DB::raw("CASE
                    WHEN i.homebranch = 'PUSAT' THEN 'Perpustakaan Pusat'
                    WHEN i.homebranch = 'GIZI' THEN 'Perpustakaan Gizi'
                    WHEN i.homebranch = 'FKG' THEN 'Perpustakaan Kedokteran Gigi'
                    WHEN i.homebranch = 'PSIKO' THEN 'Perpustakaan Psikologi'
                    WHEN i.homebranch = 'INF' THEN 'Perpustakaan Informatika'
                    WHEN i.homebranch = 'FIK' THEN 'Perpustakaan FIK'
                    WHEN i.homebranch = 'MATH' THEN 'Perpustakaan Matematika FKIP'
                    WHEN i.homebranch = 'LIPK' THEN 'LIPK'
                    WHEN i.homebranch = 'TILIB' THEN 'Perpustakaan Teknik Industri'
                    WHEN i.homebranch = 'MAPRO' THEN 'Perpustakaan Magister Psikologi'
                    WHEN i.homebranch = 'MEDLIB' THEN 'Perpustakaan Kedokteran'
                    WHEN i.homebranch = 'PAUD' THEN 'Perpustakaan PAUD'
                    WHEN i.homebranch = 'POG' THEN 'Perpustakaan Pendidikan Olahraga'
                    WHEN i.homebranch = 'PESMA' THEN 'Perpustakaan Pesma Haji Mas Mansyur'
                    WHEN i.homebranch = 'PGSDKRA' THEN 'Perpustakaan PGSD'
                    WHEN i.homebranch = 'PASCA' THEN 'Perpustakaan Pasca Sarjana'
                    WHEN i.homebranch = 'RSGM' THEN 'Perpustakaan Rumah Sakit Gigi dan Mulut'
                    WHEN i.homebranch = 'PSI' THEN 'Perpustakaan Pusat Studi Psikologi Islam'
                    WHEN i.homebranch = 'FG' THEN 'Perpustakaan Fakultas Geografi'
                    ELSE i.homebranch
                    END AS Lokasi"),
                    DB::raw("MAX(
                    COALESCE(
                        NULLIF(EXTRACTVALUE(bm.metadata,'//datafield[@tag=\"856\"]/subfield[@code=\"u\"]'), ''),
                        NULLIF(EXTRACTVALUE(bm.metadata,'//datafield[@tag=\"856\"]/subfield[@code=\"a\"]'), '')
                    )
                ) as Link_Jurnal")
                )
                ->join('biblio as b', 'b.biblionumber', '=', 'i.biblionumber')
                ->join('biblioitems as bi', 'bi.biblionumber', '=', 'i.biblionumber')
                ->join('biblio_metadata as bm', 'bm.biblionumber', '=', 'i.biblionumber')
                ->leftJoin('itemtypes as it', 'it.itemtype', '=', 'i.itype')
                ->leftJoin('authorised_values as av', function ($join) {
                    $join->on('av.authorised_value', '=', 'i.ccode')
                        ->where('av.category', '=', 'CCODE');
                })
                ->where('i.itemlost', 0)
                ->where('i.withdrawn', 0)
                ->whereIn('i.itype', ['JR', 'JRA', 'JRT'])
                ->where('i.barcode', 'not like', 'JE%')
                ->whereRaw("TRIM(i.enumchron) REGEXP '[0-9]{4}$'")
                ->orderBy('Judul', 'asc');
            if ($prodi !== 'all') {
                $cnClasses = CnClassHelperr::getCnClassByProdi($prodi);
                QueryHelper::applyCnClassRules($query, $cnClasses);
            }
            if ($tahunTerakhir !== 'all') {
                $query->whereRaw('RIGHT(i.enumchron, 4) >= ?', [date('Y') - (int)$tahunTerakhir]);
            }
            $query->groupBy('Judul', 'Kelas', 'bi.publishercode', 'Jenis_Koleksi', 'Jenis_Item_Tipe', 'Nomor', 'av.lib', 'it.description', 'i.homebranch');
            $query->orderBy('Judul', 'asc');
            $processedData = $query->get();
            if ($processedData->isNotEmpty()) {
                $totalJudul = $processedData->count();
                $totalEksemplar = $processedData->sum('Eksemplar');
            }

            if ($request->has('export_csv')) {
                return $this->exportCsvJurnal($processedData, $namaProdi, $tahunTerakhir);
            } else {
                $data = $processedData;
                $dataExists = $data->isNotEmpty();
            }
        }

        return view('pages.dapus.jurnal', compact('data', 'prodi', 'listprodi', 'namaProdi', 'tahunTerakhir', 'dataExists', 'totalJudul', 'totalEksemplar'));
    }

    public function ejurnal(Request $request)
    {
        $listprodi = M_Auv::where('category', 'PRODI')
            ->whereRaw('CHAR_LENGTH(lib) >= 13')
            ->onlyProdiTampil()
            ->orderBy('authorised_value', 'asc')
            ->get();
        $prodiOptionAll = new \stdClass();
        $prodiOptionAll->authorised_value = 'all';
        $prodiOptionAll->lib = 'Semua Program Studi';
        $listprodi->prepend($prodiOptionAll);

        $prodi = $request->input('prodi', 'initial');
        $tahunTerakhir = $request->input('tahun', 'all');

        $data = collect();
        $namaProdi = '';
        $dataExists = false;
        $totalJudul = 0;
        $totalEksemplar = 0;

        if ($prodi && $prodi !== 'initial') {
            $prodiMapping = $listprodi->pluck('lib', 'authorised_value')->toArray();
            $namaProdi = $prodiMapping[$prodi] ?? 'Tidak Ditemukan';

            $query = M_items::query()
                ->from('items as i')
                ->select(
                    'bi.cn_class AS Kelas',
                    DB::raw("GROUP_CONCAT(i.barcode ORDER BY i.barcode ASC SEPARATOR ', ') as Barcode"),
                    DB::raw("CONCAT_WS(' ', b.title, EXTRACTVALUE(bm.metadata, '//datafield[@tag=\"245\"]/subfield[@code=\"b\"]')) AS Judul"),
                    'bi.publishercode AS Penerbit',
                    'i.enumchron AS Nomor',
                    'av.lib AS Jenis_Koleksi',
                    'it.description AS Jenis_Item_Tipe',
                    DB::raw('COUNT(DISTINCT i.enumchron) AS Issue'),
                    DB::raw('COUNT(*) AS Eksemplar'),
                    DB::raw("CASE
                    WHEN i.homebranch = 'PUSAT' THEN 'Perpustakaan Pusat'
                    WHEN i.homebranch = 'GIZI' THEN 'Perpustakaan Gizi'
                    WHEN i.homebranch = 'FKG' THEN 'Perpustakaan Kedokteran Gigi'
                    WHEN i.homebranch = 'PSIKO' THEN 'Perpustakaan Psikologi'
                    WHEN i.homebranch = 'INF' THEN 'Perpustakaan Informatika'
                    WHEN i.homebranch = 'FIK' THEN 'Perpustakaan FIK'
                    WHEN i.homebranch = 'MATH' THEN 'Perpustakaan Matematika FKIP'
                    WHEN i.homebranch = 'LIPK' THEN 'LIPK'
                    WHEN i.homebranch = 'TILIB' THEN 'Perpustakaan Teknik Industri'
                    WHEN i.homebranch = 'MAPRO' THEN 'Perpustakaan Magister Psikologi'
                    WHEN i.homebranch = 'MEDLIB' THEN 'Perpustakaan Kedokteran'
                    WHEN i.homebranch = 'PAUD' THEN 'Perpustakaan PAUD'
                    WHEN i.homebranch = 'POG' THEN 'Perpustakaan Pendidikan Olahraga'
                    WHEN i.homebranch = 'PESMA' THEN 'Perpustakaan Pesma Haji Mas Mansyur'
                    WHEN i.homebranch = 'PGSDKRA' THEN 'Perpustakaan PGSD'
                    WHEN i.homebranch = 'PASCA' THEN 'Perpustakaan Pasca Sarjana'
                    WHEN i.homebranch = 'RSGM' THEN 'Perpustakaan Rumah Sakit Gigi dan Mulut'
                    WHEN i.homebranch = 'PSI' THEN 'Perpustakaan Pusat Studi Psikologi Islam'
                    WHEN i.homebranch = 'FG' THEN 'Perpustakaan Fakultas Geografi'
                    ELSE i.homebranch
                    END AS Lokasi"),
                    DB::raw("MAX(
                    COALESCE(
                        NULLIF(EXTRACTVALUE(bm.metadata,'//datafield[@tag=\"856\"]/subfield[@code=\"u\"]'), ''),
                        NULLIF(EXTRACTVALUE(bm.metadata,'//datafield[@tag=\"856\"]/subfield[@code=\"a\"]'), '')
                    )
                ) as Link_Ejurnal")
                )
                ->join('biblio as b', 'b.biblionumber', '=', 'i.biblionumber')
                ->join('biblioitems as bi', 'bi.biblionumber', '=', 'i.biblionumber')
                ->join('biblio_metadata as bm', 'bm.biblionumber', '=', 'i.biblionumber')
                ->leftJoin('itemtypes as it', 'it.itemtype', '=', 'i.itype')
                ->leftJoin('authorised_values as av', function ($join) {
                    $join->on('av.authorised_value', '=', 'i.ccode')
                        ->where('av.category', '=', 'CCODE');
                })
                ->where('i.itemlost', 0)
                ->where('i.withdrawn', 0)
                ->whereIn('i.itype', ['JR', 'JRA', 'JRT'])
                ->where('i.barcode', 'like', 'JE%')
                ->whereRaw("TRIM(i.enumchron) REGEXP '[0-9]{4}$'")
                ->orderBy('Judul', 'asc');
            if ($prodi !== 'all') {
                $cnClasses = CnClassHelperr::getCnClassByProdi($prodi);
                QueryHelper::applyCnClassRules($query, $cnClasses);
            }
            if ($tahunTerakhir !== 'all') {
                $query->whereRaw('RIGHT(i.enumchron, 4) >= ?', [date('Y') - (int)$tahunTerakhir]);
            }
            $query->groupBy('Judul', 'Kelas', 'bi.publishercode', 'Jenis_Koleksi', 'Jenis_Item_Tipe', 'Nomor', 'av.lib', 'it.description', 'i.homebranch');
            $query->orderBy('Judul', 'asc');
            $processedData = $query->get();
            if ($processedData->isNotEmpty()) {
                $totalJudul = $processedData->count();
                $totalEksemplar = $processedData->sum('Eksemplar');
            }

            if ($request->has('export_csv')) {
                return $this->exportCsvEjurnal($processedData, $namaProdi, $tahunTerakhir);
            } else {
                $data = $processedData;
                $dataExists = $data->isNotEmpty();
            }
        }

        return view('pages.dapus.ejurnal', compact('data', 'prodi', 'listprodi', 'namaProdi', 'tahunTerakhir', 'dataExists', 'totalJudul', 'totalEksemplar'));
    }

    public function ebook(Request $request)
    {
        $listprodi = M_Auv::where('category', 'PRODI')
            ->whereRaw('CHAR_LENGTH(lib) >= 13')
            ->onlyProdiTampil()
            ->orderBy('authorised_value', 'asc')
            ->get();

        $prodiOptionAll = new \stdClass();
        $prodiOptionAll->authorised_value = 'all';
        $prodiOptionAll->lib = 'Semua Program Studi';
        $listprodi->prepend($prodiOptionAll);

        $prodi = $request->input('prodi', 'initial');
        $tahunTerakhir = $request->input('tahun', 'all');

        $data = collect();
        $namaProdi = '';
        $dataExists = false;
        $totalJudul = 0;
        $totalEksemplar = 0;

        if ($prodi && $prodi !== 'initial') {
            $prodiMapping = $listprodi->pluck('lib', 'authorised_value')->toArray();
            $namaProdi = $prodiMapping[$prodi] ?? 'Tidak Ditemukan';

            $query = M_items::selectRaw("
                EXTRACTVALUE(bm.metadata,'//datafield[@tag=\"245\"]/subfield[@code=\"a\"]') as Judul_a,
                EXTRACTVALUE(bm.metadata,'//datafield[@tag=\"245\"]/subfield[@code=\"b\"]') as Judul_b,
                b.author as Pengarang,
                bi.place AS Kota_Terbit,
                MAX(bi.publishercode) AS Penerbit_Raw,
                MAX(bi.place) AS Place_Raw,
                MAX(CONCAT(COALESCE(bi.publishercode,''), ' ', COALESCE(bi.place,''))) AS Penerbit,
                bi.publicationyear AS Tahun_Terbit,
                COUNT(items.itemnumber) AS Eksemplar,
                IF(
                EXTRACTVALUE(bm.metadata,'//datafield[@tag=\"856\"]/subfield[@code=\"a\"]') <> '',
                EXTRACTVALUE(bm.metadata,'//datafield[@tag=\"856\"]/subfield[@code=\"a\"]'),
                EXTRACTVALUE(bm.metadata,'//datafield[@tag=\"856\"]/subfield[@code=\"u\"]')
            ) AS Link_Ebook,
                MAX(items.biblionumber) as biblionumber
            ")
                ->join('biblioitems as bi', 'items.biblionumber', '=', 'bi.biblionumber')
                ->join('biblio as b', 'b.biblionumber', '=', 'bi.biblionumber')
                ->join('biblio_metadata as bm', 'b.biblionumber', '=', 'bm.biblionumber')
                ->where('items.itemlost', 0)
                ->where('items.withdrawn', 0)
                ->where('items.itype', 'EB');

            if ($prodi !== 'all') {
                $cnClasses = CnClassHelperr::getCnClassByProdi($prodi);
                QueryHelper::applyCnClassRules($query, $cnClasses);
            }

            if ($tahunTerakhir !== 'all') {
                $query->whereRaw('bi.publicationyear >= YEAR(CURDATE()) - ?', [(int)$tahunTerakhir]);
            }

            $query->orderBy('Tahun_Terbit', 'desc')->orderBy('Judul_a', 'asc');
            $query->groupBy('Judul_a', 'Judul_b', 'Pengarang', 'Kota_Terbit', 'Tahun_Terbit', 'Link_Ebook');

            $processedData = $query->get()->map(function ($row) {
                $fullJudul = $row->Judul_a;
                if (!empty($row->Judul_b)) {
                    $fullJudul .= ' ' . $row->Judul_b;
                }
                $row->Judul = html_entity_decode($fullJudul, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $row->Pengarang = html_entity_decode($row->Pengarang, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $row->Penerbit = html_entity_decode($row->Penerbit, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $row->Kota_Terbit = html_entity_decode($row->Kota_Terbit, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                return $row;
            });

            $totalQuery = M_items::selectRaw("
                COUNT(DISTINCT b.biblionumber) as total_judul,
                COUNT(items.itemnumber) as total_eksemplar
            ")
                ->join('biblioitems as bi', 'items.biblionumber', '=', 'bi.biblionumber')
                ->join('biblio as b', 'b.biblionumber', '=', 'bi.biblionumber')
                ->where('items.itemlost', 0)
                ->where('items.withdrawn', 0)
                ->whereIn('items.itype', ['EB']);

            if ($prodi !== 'all') {
                $cnClasses = CnClassHelperr::getCnClassByProdi($prodi);
                // Terapkan juga helpernya ke query total
                QueryHelper::applyCnClassRules($totalQuery, $cnClasses);
            }

            if ($tahunTerakhir !== 'all') {
                $totalQuery->whereRaw('bi.publicationyear >= YEAR(CURDATE()) - ?', [(int)$tahunTerakhir]);
            }

            $totals = $totalQuery->first();
            $totalJudul = $totals->total_judul ?? 0;
            $totalEksemplar = $totals->total_eksemplar ?? 0;

            if ($request->has('export_csv')) {
                return $this->exportCsvEbook($processedData, $namaProdi, $tahunTerakhir);
            } else {
                $data = $processedData;
                $dataExists = $data->isNotEmpty();
            }
        }

        return view('pages.dapus.ebook', compact('data', 'prodi', 'listprodi', 'namaProdi', 'tahunTerakhir', 'dataExists', 'totalJudul', 'totalEksemplar'));
    }

    /**
     * Tampilkan data koleksi textbook dan tangani ekspor CSV.
     *
     * @param Request $request
     * @return \Illuminate\View\View|\Symfony\Component\HttpFoundation\StreamedResponse
     */

    public function textbook(Request $request)
    {

        $listprodi = M_Auv::where('category', 'PRODI')->whereRaw('CHAR_LENGTH(lib) >= 13')->onlyProdiTampil()
            ->orderBy('authorised_value', 'asc')->get();

        $prodiOptionAll = new \stdClass();
        $prodiOptionAll->authorised_value = 'all';
        $prodiOptionAll->lib = 'Semua Program Studi';

        $listprodi->prepend($prodiOptionAll);

        $prodi = $request->input('prodi', 'initial');
        $tahunTerakhir = $request->input('tahun', 'all');

        $data = collect();
        $namaProdi = '';
        $dataExists = false;
        $totalJudul = 0;
        $totalEksemplar = 0;

        if ($prodi && $prodi !== 'initial') {
            $prodiMapping = $listprodi->pluck('lib', 'authorised_value')->toArray();
            $namaProdi = $prodiMapping[$prodi] ?? 'Tidak Ditemukan';

            $query = M_items::selectRaw("
                EXTRACTVALUE(bm.metadata,'//datafield[@tag=\"245\"]/subfield[@code=\"a\"]') as Judul_a,
                EXTRACTVALUE(bm.metadata,'//datafield[@tag=\"245\"]/subfield[@code=\"b\"]') as Judul_b,
                b.author as Pengarang,
                bi.place AS Kota_Terbit,
                bi.publishercode AS Penerbit,
                bi.publicationyear AS Tahun_Terbit,
                COUNT(items.itemnumber) AS Eksemplar,
                CASE
                WHEN items.homebranch = 'PUSAT' THEN 'Perpustakaan Pusat'
                WHEN items.homebranch = 'GIZI' THEN 'Perpustakaan Gizi'
                WHEN items.homebranch = 'FKG' THEN 'Perpustakaan Kedokteran Gigi'
                WHEN items.homebranch = 'PSIKO' THEN 'Perpustakaan Psikologi'
                WHEN items.homebranch = 'INF' THEN 'Perpustakaan Informatika'
                WHEN items.homebranch = 'FIK' THEN 'Perpustakaan FIK'
                WHEN items.homebranch = 'MATH' THEN 'Perpustakaan Matematika FKIP'
                WHEN items.homebranch = 'LIPK' THEN 'LIPK'
                WHEN items.homebranch = 'TILIB' THEN 'Perpustakaan Teknik Industri'
                WHEN items.homebranch = 'MAPRO' THEN 'Perpustakaan Magister Psikologi'
                WHEN items.homebranch = 'MEDLIB' THEN 'Perpustakaan Kedokteran'
                WHEN items.homebranch = 'PAUD' THEN 'Perpustakaan PAUD'
                WHEN items.homebranch = 'POG' THEN 'Perpustakaan Pendidikan Olahraga'
                WHEN items.homebranch = 'PESMA' THEN 'Perpustakaan Pesma Haji Mas Mansyur'
                WHEN items.homebranch = 'PGSDKRA' THEN 'Perpustakaan PGSD'
                WHEN items.homebranch = 'PASCA' THEN 'Perpustakaan Postgraduate'
                WHEN items.homebranch = 'RSGM' THEN 'Perpustakaan Rumah Sakit Gigi dan Mulut'
                WHEN items.homebranch = 'PSI' THEN 'Perpustakaan Pusat Studi Psikologi Islam'
                WHEN items.homebranch = 'FG' THEN 'Perpustakaan Fakultas Geografi'
                ELSE items.homebranch
                END AS Lokasi
            ")
                ->join('biblioitems as bi', 'items.biblionumber', '=', 'bi.biblionumber')
                ->join('biblio as b', 'b.biblionumber', '=', 'bi.biblionumber')
                ->join('biblio_metadata as bm', 'b.biblionumber', '=', 'bm.biblionumber')
                ->where('items.itemlost', 0)
                ->where('items.withdrawn', 0)
                ->whereIn('items.itype', ['BKS', 'BKSA', 'BKSCA', 'BKSC']);
            // ->whereRaw('LEFT(items.ccode, 1) <> "R"');

            if ($prodi !== 'all') {
                $cnClasses = CnClassHelperr::getCnClassByProdi($prodi);
                QueryHelper::applyCnClassRules($query, $cnClasses);
            }


            if ($tahunTerakhir !== 'all') {
                $query->whereRaw('bi.publicationyear >= YEAR(CURDATE()) - ?', [(int)$tahunTerakhir]);
            }

            $query->orderBy('Tahun_Terbit', 'desc')
                ->orderBy('Judul_a', 'asc');
            $query->groupBy('Judul_a', 'Judul_b', 'Pengarang', 'Kota_Terbit', 'Tahun_Terbit', 'Lokasi', 'Penerbit');

            $processedData = $query->get()->map(function ($row) {
                $fullJudul = $row->Judul_a;
                if (!empty($row->Judul_b)) {
                    $fullJudul .= ' ' . $row->Judul_b;
                }

                $row->Judul = html_entity_decode($fullJudul, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $row->Pengarang = html_entity_decode($row->Pengarang, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $row->Penerbit = html_entity_decode($row->Penerbit, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $row->Kota_Terbit = html_entity_decode($row->Kota_Terbit, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                return $row;
            });

            $totalQuery = M_items::selectRaw("
                COUNT(DISTINCT b.biblionumber) as total_judul,
                COUNT(items.itemnumber) as total_eksemplar
            ")
                ->join('biblioitems as bi', 'items.biblionumber', '=', 'bi.biblionumber')
                ->join('biblio as b', 'b.biblionumber', '=', 'bi.biblionumber')
                ->where('items.itemlost', 0)
                ->where('items.withdrawn', 0)
                ->whereRaw('LEFT(items.itype, 3) = "BKS"')
                ->whereRaw('LEFT(items.ccode, 1) <> "R"');

            if ($prodi !== 'all') {
                $cnClasses = CnClassHelperr::getCnClassByProdi($prodi);
                QueryHelper::applyCnClassRules($totalQuery, $cnClasses);
            }

            if ($tahunTerakhir !== 'all') {
                $totalQuery->whereRaw('bi.publicationyear >= YEAR(CURDATE()) - ?', [(int)$tahunTerakhir]);
            }

            $totals = $totalQuery->first();
            $totalJudul = $totals->total_judul ?? 0;
            $totalEksemplar = $totals->total_eksemplar ?? 0;

            if ($request->has('export_csv')) {
                return $this->exportCsvTextbook($processedData, $namaProdi, $tahunTerakhir);
            } else {
                $data = $processedData;
                $dataExists = $data->isNotEmpty();
            }
        }

        return view('pages.dapus.textbook', compact('data', 'prodi', 'listprodi', 'namaProdi', 'tahunTerakhir', 'dataExists', 'totalJudul', 'totalEksemplar'));
    }



    public function periodikal(Request $request)
    {
        $listprodi = M_Auv::where('category', 'PRODI')->whereRaw('CHAR_LENGTH(lib) >= 13')->onlyProdiTampil()
            ->orderBy('authorised_value', 'asc')->get();

        $prodiOptionAll = new \stdClass();
        $prodiOptionAll->authorised_value = 'all';
        $prodiOptionAll->lib = 'Semua Program Studi';
        $listprodi->prepend($prodiOptionAll);

        $prodi = $request->input('prodi', 'initial');
        $tahunTerakhir = $request->input('tahun', 'all');

        $data = collect();
        $namaProdi = '';
        $dataExists = false;
        $totalJudul = 0;
        $totalEksemplar = 0;

        if ($prodi && $prodi !== 'initial') {
            $prodiMapping = $listprodi->pluck('lib', 'authorised_value')->toArray();
            $namaProdi = $prodiMapping[$prodi] ?? 'Tidak Ditemukan';

            $periodicalTypes = ['MJA', 'MJI', 'MJIP', 'MJP'];

            $query = M_items::select(
                'i.itype AS Jenis_kode',
                't.description AS Jenis',
                'bi.publishercode AS Penerbit',
                'bi.place AS Tempat_Terbit',
                'bi.publicationyear AS Tahun_Terbit',
                'bi.cn_class as Kelas',
                'i.enumchron AS Nomor',
                'i.homebranch as Lokasi'
            )
                ->selectRaw("EXTRACTVALUE(bm.metadata,'//datafield[@tag=\"245\"]/subfield[@code=\"a\"]') as Judul_a")
                ->selectRaw("EXTRACTVALUE(bm.metadata,'//datafield[@tag=\"245\"]/subfield[@code=\"b\"]') as Judul_b")
                ->selectRaw('COUNT(i.itemnumber) AS Issue')
                ->selectRaw('SUM(i.copynumber) AS Eksemplar')
                ->from('items as i')
                ->join('biblioitems as bi', 'i.biblionumber', '=', 'bi.biblionumber')
                ->join('biblio as b', 'i.biblionumber', '=', 'b.biblionumber')
                ->join('biblio_metadata as bm', 'b.biblionumber', '=', 'bm.biblionumber')
                ->join('itemtypes as t', 'i.itype', '=', 't.itemtype')
                ->where('i.itemlost', 0)
                ->where('i.withdrawn', 0)
                ->whereIn('i.itype', $periodicalTypes)
                ->groupBy('i.biblionumber');

            // Terapkan filter prodi menggunakan helper
            if ($prodi !== 'all') {
                $cnClasses = CnClassHelperr::getCnClassByProdi($prodi);
                QueryHelper::applyCnClassRules($query, $cnClasses);
            }

            // Terapkan filter tahun
            if ($tahunTerakhir !== 'all') {
                $query->whereRaw('bi.publicationyear >= YEAR(CURDATE()) - ?', [(int)$tahunTerakhir]);
            }

            // Group by untuk rekapitulasi yang benar
            $query->groupBy('Jenis_kode', 'Jenis', 'Judul_a', 'Judul_b', 'Nomor', 'Kelas', 'Lokasi', 'Penerbit', 'Tempat_Terbit', 'Tahun_Terbit');

            $processedData = $query->get()->map(function ($row) {
                $fullJudul = $row->Judul_a;
                if (!empty($row->Judul_b)) {
                    $fullJudul .= ' ' . $row->Judul_b;
                }
                $row->Judul = html_entity_decode($fullJudul, ENT_QUOTES | ENT_HTML5, 'UTF-8');

                $penerbit = $row->Penerbit;
                if (!empty($row->Tempat_Terbit)) {
                    $penerbit .= ' : ' . $row->Tempat_Terbit;
                }
                $row->Penerbit_Lengkap = html_entity_decode($penerbit, ENT_QUOTES | ENT_HTML5, 'UTF-8');

                if (empty($row->Tahun_Terbit) || $row->Tahun_Terbit == '0000') {
                    $row->Tahun_Terbit = 'n.d.';
                }

                return $row;
            });

            // Query untuk menghitung total
            $totalQuery = M_items::query()
                ->from('items as i')
                ->selectRaw("
                    COUNT(DISTINCT i.biblionumber) as total_judul,
                    COUNT(i.itemnumber) as total_eksemplar
                ")
                ->join('biblioitems as bi', 'i.biblionumber', '=', 'bi.biblionumber')
                ->where('i.itemlost', 0)
                ->where('i.withdrawn', 0)
                ->whereIn('i.itype', $periodicalTypes);

            // Terapkan filter prodi juga ke query total
            if ($prodi !== 'all') {
                $cnClasses = CnClassHelperr::getCnClassByProdi($prodi);
                QueryHelper::applyCnClassRules($totalQuery, $cnClasses);
            }

            // Terapkan filter tahun juga ke query total
            if ($tahunTerakhir !== 'all') {
                $totalQuery->whereRaw('bi.publicationyear >= YEAR(CURDATE()) - ?', [(int)$tahunTerakhir]);
            }

            $totals = $totalQuery->first();
            $totalJudul = $totals->total_judul ?? 0;
            $totalEksemplar = $totals->total_eksemplar ?? 0;

            if ($request->has('export_csv')) {
                return $this->exportCsvPeriodikal($processedData, $namaProdi, $tahunTerakhir);
            } else {
                $data = $processedData;
                $dataExists = $data->isNotEmpty();
            }
        }

        return view('pages.dapus.periodikal', compact('data', 'prodi', 'listprodi', 'namaProdi', 'tahunTerakhir', 'dataExists', 'totalJudul', 'totalEksemplar'));
    }


    public function referensi(Request $request)
    {
        $listprodi = M_Auv::where('category', 'PRODI')->whereRaw('CHAR_LENGTH(lib) >= 13')->onlyProdiTampil()
            ->orderBy('authorised_value', 'asc')->get();

        $prodiOptionAll = new \stdClass();
        $prodiOptionAll->authorised_value = 'all';
        $prodiOptionAll->lib = 'Semua Program Studi';

        $listprodi->prepend($prodiOptionAll);

        $prodi = $request->input('prodi', 'initial');
        $tahunTerakhir = $request->input('tahun', 'all');

        $data = collect();
        $namaProdi = '';
        $dataExists = false;
        $totalJudul = 0;
        $totalEksemplar = 0;

        if ($prodi && $prodi !== 'initial') {
            $prodiMapping = $listprodi->pluck('lib', 'authorised_value')->toArray();
            $namaProdi = $prodiMapping[$prodi] ?? 'Tidak Ditemukan';

            $query = M_items::selectRaw("
                bi.cn_class as Kelas,
                EXTRACTVALUE(bm.metadata,'//datafield[@tag=\"245\"]/subfield[@code=\"a\"]') as Judul_a,
                EXTRACTVALUE(bm.metadata,'//datafield[@tag=\"245\"]/subfield[@code=\"b\"]') as Judul_b,
                b.author as Pengarang,
                bi.place AS Kota_Terbit,
                MAX(bi.publishercode) AS Penerbit_Raw,
                MAX(bi.place) AS Place_Raw,
                MAX(CONCAT(COALESCE(bi.publishercode,''), ' ', COALESCE(bi.place,''))) AS Penerbit,
                bi.publicationyear AS Tahun_Terbit,
                COUNT(i.itemnumber) AS Eksemplar,
                CASE
                WHEN i.homebranch = 'PUSAT' THEN 'Perpustakaan Pusat'
                WHEN i.homebranch = 'GIZI' THEN 'Perpustakaan Gizi'
                WHEN i.homebranch = 'FKG' THEN 'Perpustakaan Kedokteran Gigi'
                WHEN i.homebranch = 'PSIKO' THEN 'Perpustakaan Psikologi'
                WHEN i.homebranch = 'INF' THEN 'Perpustakaan Informatika'
                WHEN i.homebranch = 'FIK' THEN 'Perpustakaan FIK'
                WHEN i.homebranch = 'MATH' THEN 'Perpustakaan Matematika FKIP'
                WHEN i.homebranch = 'LIPK' THEN 'LIPK'
                WHEN i.homebranch = 'TILIB' THEN 'Perpustakaan Teknik Industri'
                WHEN i.homebranch = 'MAPRO' THEN 'Perpustakaan Magister Psikologi'
                WHEN i.homebranch = 'MEDLIB' THEN 'Perpustakaan <Kedokter></Kedokter>an'
                WHEN i.homebranch = 'PAUD' THEN 'Perpustakaan PAUD'
                WHEN i.homebranch = 'POG' THEN 'Perpustakaan Pendidikan Olahraga'
                WHEN i.homebranch = 'PESMA' THEN 'Perpustakaan Pesma Haji Mas Mansyur'
                WHEN i.homebranch = 'PGSDKRA' THEN 'Perpustakaan PGSD'
                WHEN i.homebranch = 'PASCA' THEN 'Perpustakaan Postgraduate'
                WHEN i.homebranch = 'RSGM' THEN 'Perpustakaan Rumah Sakit Gigi dan Mulut'
                WHEN i.homebranch = 'PSI' THEN 'Perpustakaan Pusat Studi Psikologi Islam'
                WHEN i.homebranch = 'FG' THEN 'Perpustakaan Fakultas Geografi'
                ELSE i.homebranch
                END AS Lokasi
                ")
                // i.homebranch as Lokasi
                ->from('items as i')
                ->join('biblioitems as bi', 'i.biblionumber', '=', 'bi.biblionumber')
                ->join('biblio as b', 'i.biblionumber', '=', 'b.biblionumber')
                ->join('biblio_metadata as bm', 'b.biblionumber', '=', 'bm.biblionumber')
                ->where('i.itemlost', 0)
                ->where('i.withdrawn', 0)
                ->whereRaw('LEFT(i.itype,3) = "BKS"')
                ->whereRaw('LEFT(i.ccode,1) = "R"');

            if ($prodi !== 'all') {
                $cnClasses = CnClassHelperr::getCnClassByProdi($prodi);
                QueryHelper::applyCnClassRules($query, $cnClasses);
            }

            if ($tahunTerakhir !== 'all') {
                $query->whereRaw('bi.publicationyear >= YEAR(CURDATE()) - ?', [(int)$tahunTerakhir]);
            }

            $query->orderBy('Tahun_Terbit', 'desc');
            $query->groupBy('Judul_a', 'Judul_b', 'Pengarang', 'Kota_Terbit', 'Tahun_Terbit', 'Kelas', 'Lokasi');

            $processedData = $query->get()->map(function ($row) {
                $fullJudul = $row->Judul_a;
                if (!empty($row->Judul_b)) {
                    $fullJudul .= ' ' . $row->Judul_b;
                }
                $row->Judul = html_entity_decode($fullJudul, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $row->Pengarang = html_entity_decode($row->Pengarang, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $row->Penerbit = html_entity_decode($row->Penerbit, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $row->Kota_Terbit = html_entity_decode($row->Kota_Terbit, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                return $row;
            });

            $totalQuery = M_items::selectRaw("
                COUNT(DISTINCT b.biblionumber) as total_judul,
                COUNT(items.itemnumber) as total_eksemplar
            ")
                ->join('biblioitems as bi', 'items.biblionumber', '=', 'bi.biblionumber')
                ->join('biblio as b', 'b.biblionumber', '=', 'bi.biblionumber')
                ->where('items.itemlost', 0)
                ->where('items.withdrawn', 0)
                ->whereRaw('LEFT(items.itype,3) = "BKS"')
                ->whereRaw('LEFT(items.ccode,1) = "R"');

            if ($prodi !== 'all') {
                $cnClasses = CnClassHelperr::getCnClassByProdi($prodi);
                QueryHelper::applyCnClassRules($totalQuery, $cnClasses);
            }

            if ($tahunTerakhir !== 'all') {
                $totalQuery->whereRaw('bi.publicationyear >= YEAR(CURDATE()) - ?', [(int)$tahunTerakhir]);
            }

            $totals = $totalQuery->first();
            $totalJudul = $totals->total_judul ?? 0;
            $totalEksemplar = $totals->total_eksemplar ?? 0;

            if ($request->has('export_csv')) {
                return $this->exportCsvReferensi($processedData, $namaProdi, $tahunTerakhir);
            } else {
                $data = $processedData;
                $dataExists = $data->isNotEmpty();
            }
        }

        return view('pages.dapus.referensi', compact('data', 'prodi', 'listprodi', 'namaProdi', 'tahunTerakhir', 'dataExists', 'totalJudul', 'totalEksemplar'));
    }

    /**
     * Tampilkan data koleksi per prodi.
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function koleksiPerprodi(Request $request)
    {
        $listprodi = M_eprodi::all();
        $prodi = $request->input('prodi');
        $tahunTerakhir = $request->input('tahun', 'all');

        $data = collect();
        $namaProdi = 'Pilih Program Studi';
        $chartData = [];

        if ($prodi) {
            $prodiMapping = $listprodi->pluck('nama', 'kode')->toArray();
            $cnClasses = CnClassHelper::getCnClassByProdi($prodi);
            $namaProdi = $prodiMapping[$prodi] ?? 'Tidak Ditemukan';

            $query = M_items::select('t.description AS Jenis', 'i.ccode AS Koleksi')
                ->selectRaw('COUNT(DISTINCT i.biblionumber) AS Judul')
                ->selectRaw('COUNT(i.itemnumber) AS Eksemplar')
                ->from('items as i')
                ->join('biblioitems as bi', 'i.biblionumber', '=', 'bi.biblionumber')
                ->join('itemtypes as t', 'i.itype', '=', 't.itemtype')
                ->where('i.itemlost', 0)
                ->where('i.withdrawn', 0)
                ->whereIn('bi.cn_class', $cnClasses);

            if ($tahunTerakhir !== 'all') {
                $query->whereRaw('bi.publicationyear >= YEAR(CURDATE()) - ?', [$tahunTerakhir]);
            }

            $data = $query->groupBy('Jenis', 'Koleksi')
                ->orderBy('Jenis', 'asc')
                ->orderBy('Koleksi', 'asc')
                ->get();
            $chartData = $data->map(function ($item) {
                return [
                    'jenis' => $item->Jenis,
                    'judul' => $item->Judul,
                    'eksemplar' => $item->Eksemplar
                ];
            })->values()->all();
        }

        return view('pages.dapus.prodi', compact('namaProdi', 'listprodi', 'data', 'prodi', 'tahunTerakhir', 'chartData'));
    }

    /**
     * Tampilkan detail koleksi per prodi.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDetailKoleksi(Request $request)
    {
        $prodi = $request->input('prodi');
        $jenis = $request->input('jenis');
        $tahunTerakhir = $request->input('tahun', 'all');
        $page = $request->input('page', 1);
        $perPage = 10;

        $detailData = collect();

        if ($prodi && $jenis) {
            $cnClasses = CnClassHelper::getCnClassByProdi($prodi);

            $query = M_items::select(
                'b.title AS Judul',
                DB::raw('MIN(bi.cn_class) AS Kelas'),
                DB::raw('MIN(bi.publicationyear) AS TahunTerbit'),
                DB::raw('SUM(CASE WHEN i.itemlost = 0 AND i.withdrawn = 0 THEN 1 ELSE 0 END) AS Eksemplar'),
                DB::raw('GROUP_CONCAT(DISTINCT i.homebranch ORDER BY i.homebranch SEPARATOR ", ") AS Lokasi')
            )
                ->from('items as i')
                ->join('biblioitems as bi', 'i.biblionumber', '=', 'bi.biblionumber')
                ->join('biblio as b', 'i.biblionumber', '=', 'b.biblionumber')
                ->join('itemtypes as t', 'i.itype', '=', 't.itemtype')
                ->where('i.itemlost', 0)
                ->where('i.withdrawn', 0)
                ->whereIn('bi.cn_class', $cnClasses)
                ->where('t.description', $jenis);

            if ($tahunTerakhir !== 'all') {
                $query->whereRaw('bi.publicationyear >= YEAR(CURDATE()) - ?', [$tahunTerakhir]);
            }

            $detailData = $query->groupBy('b.title')
                ->orderBy('Kelas', 'asc')
                ->paginate($perPage, ['*'], 'page', $page);
        }

        return response()->json($detailData);
    }

    /**
     * Ekspor data jurnal ke format CSV.
     *
     * @param \Illuminate\Support\Collection $data
     * @param string $namaProdi
     * @param string $tahunTerakhir
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */

    public function eresource(Request $request)
    {
        return view('pages.dapus.eresource');
    }



    private function exportCsvJurnal($data, $namaProdi, $tahunTerakhir)
    {
        // --- 1. Siapkan Nama File ---
        $filename = "koleksi_jurnal_detail";
        if ($namaProdi && $namaProdi !== 'Pilih Program Studi' && $namaProdi !== 'Semua Program Studi') {
            $cleanProdiName = preg_replace('/[^a-zA-Z0-9_]/', '', str_replace(' ', '_', $namaProdi));
            $filename .= "_" . $cleanProdiName;
        }
        $filename .= "_" . ($tahunTerakhir !== 'all' ? $tahunTerakhir . "_tahun_terakhir" : "semua_tahun");
        $filename .= "_" . Carbon::now()->format('Ymd_His') . ".csv";

        // --- 2. Siapkan Header CSV (Pastikan Sesuai!) ---
        $headers = [
            'No',               // 0
            'Judul',            // 1
            'Penerbit',         // 2
            'Nomor Edisi',      // 3
            // 'Issue',         // Dihapus dari header
            'Eksemplar',        // 4
            'Jenis Koleksi',    // 5
            'Jenis Item Tipe',  // 6
            'Lokasi',           // 7
            'Link'              // 8
        ]; // Total 9 Kolom

        // --- 3. Definisikan Callback dengan Logika Pengosongan ---
        $callback = function () use ($data, $headers, $namaProdi, $tahunTerakhir) {
            if (ob_get_level()) {
                ob_end_clean();
            }
            $file = fopen('php://output', 'w');
            fputs($file, $bom = chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Judul file CSV
            $judulProdi = 'Daftar Koleksi Jurnal Detail - ' . ($namaProdi ?: 'Semua Program Studi');
            $judulTahun = ($tahunTerakhir !== 'all') ? ('Filter Tahun: ' . $tahunTerakhir . ' tahun terakhir') : 'Semua Tahun';
            fputcsv($file, [$judulProdi . ' - ' . $judulTahun], ';');
            fputcsv($file, [''], ';');
            fputcsv($file, $headers, ';');

            $i = 1;
            $previousJudul = null;

            foreach ($data as $row) {
                $rowData = [];

                if ($row->Judul !== $previousJudul) {
                    $rowData = [
                        $i++,
                        $row->Judul,
                        $row->Penerbit,
                        $row->Nomor,
                        // $row->Issue,
                        $row->Eksemplar,
                        $row->Jenis_Koleksi,
                        $row->Jenis_Item_Tipe,
                        $row->Lokasi,
                        $row->Link_Jurnal ?? '',
                    ];
                    $previousJudul = $row->Judul;
                } else {
                    $rowData = [
                        $i++,
                        '',
                        '',
                        $row->Nomor,
                        // $row->Issue,
                        $row->Eksemplar,
                        $row->Jenis_Koleksi,
                        $row->Jenis_Item_Tipe,
                        $row->Lokasi,
                        $row->Link_Jurnal ?? '',
                    ];
                }
                if (count($rowData) == count($headers)) {
                    fputcsv($file, $rowData, ';');
                } else {
                    Log::warning('CSV Export Jurnal: Column count mismatch for row.', ['expected' => count($headers), 'actual' => count($rowData), 'data' => $rowData]);
                }
            }
            fclose($file);
        };

        return response()->streamDownload($callback, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }


    private function exportCsvEjurnal($data, $namaProdi, $tahunTerakhir)
    {
        // --- 1. Siapkan Nama File ---
        $filename = "koleksi_e_jurnal_detail";
        if ($namaProdi && $namaProdi !== 'Pilih Program Studi' && $namaProdi !== 'Semua Program Studi') {
            $cleanProdiName = preg_replace('/[^a-zA-Z0-9_]/', '', str_replace(' ', '_', $namaProdi));
            $filename .= "_" . $cleanProdiName;
        }
        $filename .= "_" . ($tahunTerakhir !== 'all' ? $tahunTerakhir . "_tahun_terakhir" : "semua_tahun");
        $filename .= "_" . Carbon::now()->format('Ymd_His') . ".csv";

        // --- 2. Siapkan Header CSV (Pastikan Sesuai!) ---
        $headers = [
            'No',               // 0
            'Judul',            // 1
            'Penerbit',         // 2
            'Nomor Edisi',      // 3
            // 'Issue',         // Dihapus dari header
            'Eksemplar',        // 4
            'Jenis Koleksi',    // 5
            'Jenis Item Tipe',  // 6
            'Lokasi',           // 7
            'Link'              // 8
        ]; // Total 9 Kolom

        // --- 3. Definisikan Callback dengan Logika Pengosongan ---
        $callback = function () use ($data, $headers, $namaProdi, $tahunTerakhir) {

            if (ob_get_level()) {
                ob_end_clean();
            }
            $file = fopen('php://output', 'w');
            fputs($file, $bom = chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Judul file CSV
            $judulProdi = 'Daftar Koleksi E-Jurnal Detail - ' . ($namaProdi ?: 'Semua Program Studi');
            $judulTahun = ($tahunTerakhir !== 'all') ? ('Filter Tahun: ' . $tahunTerakhir . ' tahun terakhir') : 'Semua Tahun';
            fputcsv($file, [$judulProdi . ' - ' . $judulTahun], ';');
            fputcsv($file, [''], ';');
            fputcsv($file, $headers, ';');

            $i = 1;
            $previousJudul = null;

            foreach ($data as $row) {
                $rowData = [];

                if ($row->Judul !== $previousJudul) {
                    $rowData = [
                        $i++,
                        $row->Judul,
                        $row->Penerbit,
                        $row->Nomor,
                        // $row->Issue,
                        $row->Eksemplar,
                        $row->Jenis_Koleksi,
                        $row->Jenis_Item_Tipe,
                        $row->Lokasi,
                        $row->Link_Jurnal ?? '',
                    ];
                    $previousJudul = $row->Judul;
                } else {
                    $rowData = [
                        $i++,
                        '',
                        '',
                        $row->Nomor,
                        // $row->Issue,
                        $row->Eksemplar,
                        $row->Jenis_Koleksi,
                        $row->Jenis_Item_Tipe,
                        $row->Lokasi,
                        $row->Link_Jurnal ?? '',
                    ];
                }
                if (count($rowData) == count($headers)) {
                    fputcsv($file, $rowData, ';');
                } else {
                    Log::warning('CSV Export E-Jurnal: Column count mismatch for row.', ['expected' => count($headers), 'actual' => count($rowData), 'data' => $rowData]);
                }
            }
            fclose($file);
        };

        return response()->streamDownload($callback, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Ekspor data referensi ke format CSV.
     *
     * @param \Illuminate\Support\Collection $data
     * @param string $namaProdi
     * @param string $tahunTerakhir
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */

    private function exportCsvReferensi($data, $namaProdi, $tahunTerakhir)
    {
        $filename = "koleksi_referensi";
        if ($namaProdi && $namaProdi !== 'Pilih Program Studi' && $namaProdi !== 'Semua Program Studi') {
            $cleanProdiName = preg_replace('/[^a-zA-Z0-9 ]/', '', str_replace(' ', '_', $namaProdi));
            $filename .= "_" . $cleanProdiName;
        }
        $filename .= "_" . ($tahunTerakhir !== 'all' ? $tahunTerakhir . "_tahun_terakhir" : "semua_tahun");
        $filename .= "_" . Carbon::now()->format('Ymd_His') . ".csv";

        $headers = [
            'No',
            'Judul',
            'Pengarang',
            'Kota Terbit',
            'Penerbit',
            'Tahun Terbit',
            'Eksemplar',
            'Lokasi',
        ];

        $callback = function () use ($data, $headers, $namaProdi, $tahunTerakhir) {
            if (ob_get_level()) {
                ob_end_clean();
            }
            $file = fopen('php://output', 'w');
            fputs($file, $bom = chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Judul file CSV
            $judulProdi = 'Daftar Koleksi Referensi - ' . ($namaProdi ?: 'Semua Program Studi');
            $judulTahun = ($tahunTerakhir !== 'all') ? ('Tahun Terbit: ' . $tahunTerakhir . ' tahun terakhir') : 'Semua Tahun Terbit';
            fputcsv($file, [$judulProdi . ' - ' . $judulTahun], ';');
            fputcsv($file, [''], ';');
            fputcsv($file, $headers, ';');

            $i = 1;
            foreach ($data as $row) {
                $rowData = [
                    $i++,
                    $row->Judul,
                    $row->Pengarang,
                    $row->Kota_Terbit,
                    $row->Penerbit,
                    (int) $row->Tahun_Terbit,
                    (int) $row->Eksemplar,
                    $row->Lokasi
                ];
                fputcsv($file, $rowData, ';');
            }
            fclose($file);
        };

        return response()->streamDownload($callback, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }


    /**
     * Ekspor data textbook ke format CSV.
     *
     * @param \Illuminate\Support\Collection $data
     * @param string $namaProdi
     * @param string $tahunTerakhir
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    private function exportCsvTextbook($data, $namaProdi, $tahunTerakhir)
    {

        $filename = "koleksi_textbook";
        if ($namaProdi && $namaProdi !== 'Pilih Program Studi' && $namaProdi !== 'Semua Program Studi') {
            $cleanProdiName = preg_replace('/[^a-zA-Z0-9 ]/', '', str_replace(' ', '_', $namaProdi));
            $filename .= "_" . $cleanProdiName;
        }
        $filename .= "_" . ($tahunTerakhir !== 'all' ? $tahunTerakhir . "_tahun_terakhir" : "semua_tahun");
        $filename .= "_" . Carbon::now()->format('Ymd_His') . ".csv";

        $headers = [
            'No',
            'Judul',
            'Pengarang',
            'Kota Terbit',
            'Penerbit',
            'Tahun Terbit',
            'Eksemplar',
            'Lokasi'
        ];

        $callback = function () use ($data, $headers, $namaProdi, $tahunTerakhir) {
            if (ob_get_level()) {
                ob_end_clean();
            }
            $file = fopen('php://output', 'w');
            fputs($file, $bom = chr(0xEF) . chr(0xBB) . chr(0xBF));

            $judulProdi = 'Daftar Koleksi Buku Teks - ' . ($namaProdi ?: 'Semua Program Studi');
            $judulTahun = ($tahunTerakhir !== 'all') ? ('Tahun Terbit: ' . $tahunTerakhir . ' tahun terakhir') : 'Semua Tahun Terbit';
            fputcsv($file, [$judulProdi . ' - ' . $judulTahun], ';');
            fputcsv($file, [''], ';');
            fputcsv($file, $headers, ';');

            $i = 1;
            foreach ($data as $row) {
                $rowData = [
                    $i++,
                    $row->Judul,
                    $row->Pengarang,
                    $row->Kota_Terbit,
                    $row->Penerbit,
                    (int) $row->Tahun_Terbit,
                    (int) $row->Eksemplar,
                    $row->Lokasi
                ];
                fputcsv($file, $rowData, ';');
            }
            fclose($file);
        };

        return response()->streamDownload($callback, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Ekspor data e-book ke format CSV.
     *
     * @param \Illuminate\Support\Collection $data
     * @param string $namaProdi
     * @param string $tahunTerakhir
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    private function exportCsvEbook($data, $namaProdi, $tahunTerakhir)
    {
        $filename = "koleksi_ebook";
        if ($namaProdi && $namaProdi !== 'Pilih Program Studi' && $namaProdi !== 'Semua Program Studi') {
            $cleanProdiName = preg_replace('/[^a-zA-Z0-9 ]/', '', str_replace(' ', '_', $namaProdi));
            $filename .= "_" . $cleanProdiName;
        }
        $filename .= "_" . ($tahunTerakhir !== 'all' ? $tahunTerakhir . "_tahun_terakhir" : "semua_tahun");
        $filename .= "_" . Carbon::now()->format('Ymd_His') . ".csv";

        $headers = [
            'No',
            'Judul',
            'Pengarang',
            'Kota Terbit',
            'Penerbit',
            'Tahun Terbit',
            'Eksemplar',
            'Link'
        ];

        $callback = function () use ($data, $headers, $namaProdi, $tahunTerakhir) {
            if (ob_get_level()) {
                ob_end_clean();
            }
            $file = fopen('php://output', 'w');
            fputs($file, $bom = chr(0xEF) . chr(0xBB) . chr(0xBF));

            $judulProdi = 'Daftar Koleksi E-Book - ' . ($namaProdi ?: 'Semua Program Studi');
            $judulTahun = ($tahunTerakhir !== 'all') ? ('Tahun Terbit: ' . $tahunTerakhir . ' tahun terakhir') : 'Semua Tahun Terbit';
            fputcsv($file, [$judulProdi . ' - ' . $judulTahun], ';');
            fputcsv($file, [''], ';');
            fputcsv($file, $headers, ';');

            $i = 1;
            foreach ($data as $row) {
                $rowData = [
                    $i++,
                    $row->Judul,
                    $row->Pengarang,
                    $row->Kota_Terbit,
                    $row->Penerbit,
                    (int) $row->Tahun_Terbit,
                    (int) $row->Eksemplar,
                    $row->Link_Ebook
                ];
                fputcsv($file, $rowData, ';');
            }
            fclose($file);
        };

        return response()->streamDownload($callback, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Ekspor data prosiding ke format CSV.
     *
     * @param \Illuminate\Support\Collection $data
     * @param string $namaProdi
     * @param string $tahunTerakhir
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    private function exportCsvProsiding($data, $namaProdi, $tahunTerakhir)
    {

        $filename = "koleksi_prosiding";
        if ($namaProdi && $namaProdi !== 'Pilih Program Studi' && $namaProdi !== 'Semua Program Studi') {
            $cleanProdiName = preg_replace('/[^a-zA-Z0-9 ]/', '', str_replace(' ', '_', $namaProdi));
            $filename .= "_" . $cleanProdiName;
        }
        $filename .= "_" . ($tahunTerakhir !== 'all' ? $tahunTerakhir . "_tahun_terakhir" : "semua_tahun");
        $filename .= "_" . Carbon::now()->format('Ymd_His') . ".csv";

        // --- PERUBAHAN 1: Sesuaikan Headers CSV agar lengkap ---
        $headers = [
            'No',
            //'Kelas',
            'Judul',
            'Pengarang',
            'Penerbit',
            'Tahun Terbit',
            'Nomor',
            'Issue',
            'Eksemplar',
            'Lokasi',
            'Link'
        ];

        $callback = function () use ($data, $headers, $namaProdi, $tahunTerakhir) {
            if (ob_get_level()) {
                ob_end_clean();
            }
            $file = fopen('php://output', 'w');
            fputs($file, $bom = chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Penyesuaian minor pada judul daftar
            $judulProdi = 'Daftar Koleksi Prosiding - ' . ($namaProdi ?: 'Semua Program Studi');
            $judulTahun = ($tahunTerakhir !== 'all') ? ('Tahun Terbit: ' . $tahunTerakhir . ' tahun terakhir') : 'Semua Tahun Terbit';
            fputcsv($file, [$judulProdi . ' - ' . $judulTahun], ';');
            fputcsv($file, [''], ';'); // Baris kosong
            fputcsv($file, $headers, ';');

            $i = 1;
            foreach ($data as $row) {
                // --- PERUBAHAN 2: Sesuaikan Data per Baris agar lengkap ---
                $rowData = [
                    $i++,
                    //$row->Kelas,
                    $row->Judul,
                    $row->Pengarang,
                    $row->Penerbit,
                    (int) $row->TahunTerbit,
                    $row->Nomor,
                    (int) $row->Issue,
                    (int) $row->Eksemplar,
                    $row->Lokasi,
                    $row->Link
                ];
                fputcsv($file, $rowData, ';');
            }
            fclose($file);
        };

        return response()->streamDownload($callback, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Ekspor data periodikal ke format CSV.
     *
     * @param \Illuminate\Support\Collection $data
     * @param string $namaProdi
     * @param string $tahunTerakhir
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    private function exportCsvPeriodikal($data, $namaProdi, $tahunTerakhir)
    {

        $filename = "koleksi_periodikal";
        if ($namaProdi && $namaProdi !== 'Pilih Program Studi' && $namaProdi !== 'Semua Program Studi') {
            $cleanProdiName = preg_replace('/[^a-zA-Z0-9 ]/', '', str_replace(' ', '_', $namaProdi));
            $filename .= "_" . $cleanProdiName;
        }
        $filename .= "_" . ($tahunTerakhir !== 'all' ? $tahunTerakhir . "_tahun_terakhir" : "semua_tahun");
        $filename .= "_" . Carbon::now()->format('Ymd_His') . ".csv";

        // --- PERUBAHAN 1: Sesuaikan Headers CSV agar lengkap ---
        $headers = [
            'No',
            'Kelas',
            'Jenis',
            'Judul',
            'Penerbit',
            'Tahun Terbit',
            'Nomor',
            'Issue',
            'Eksemplar',
            'Lokasi'
        ];

        $callback = function () use ($data, $headers, $namaProdi, $tahunTerakhir) {
            if (ob_get_level()) {
                ob_end_clean();
            }
            $file = fopen('php://output', 'w');
            fputs($file, $bom = chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Penyesuaian minor pada judul daftar
            $judulProdi = 'Daftar Koleksi Periodikal - ' . ($namaProdi ?: 'Semua Program Studi');
            $judulTahun = ($tahunTerakhir !== 'all') ? ('Tahun Terbit: ' . $tahunTerakhir . ' tahun terakhir') : 'Semua Tahun Terbit';
            fputcsv($file, [$judulProdi . ' - ' . $judulTahun], ';');
            fputcsv($file, [''], ';'); // Baris kosong
            fputcsv($file, $headers, ';');

            $i = 1;
            foreach ($data as $row) {
                // --- PERUBAHAN 2: Sesuaikan Data per Baris agar lengkap ---
                $rowData = [
                    $i++,
                    $row->Kelas,
                    $row->Jenis,
                    $row->Judul,
                    $row->Penerbit_Lengkap, // Menggunakan gabungan penerbit & tempat terbit
                    $row->Tahun_Terbit,
                    $row->Nomor,
                    (int) $row->Issue,
                    (int) $row->Eksemplar,
                    $row->Lokasi,
                ];
                fputcsv($file, $rowData, ';');
            }
            fclose($file);
        };

        return response()->streamDownload($callback, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}
