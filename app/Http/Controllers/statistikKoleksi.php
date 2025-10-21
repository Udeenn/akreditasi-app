<?php

namespace App\Http\Controllers;

use App\Models\M_eprodi;
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
    /**
     * Tampilkan data koleksi prosiding dan tangani ekspor CSV.
     *
     * @param Request $request
     * @return \Illuminate\View\View|\Symfony\Component\HttpFoundation\StreamedResponse
     */


    public function prosiding(Request $request)
    {
        $listprodi = M_Auv::where('category', 'PRODI')
            ->whereRaw('CHAR_LENGTH(lib) >= 13')
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
                // bi.cn_class as Kelas,
                "
                EXTRACTVALUE(bm.metadata,'//datafield[@tag=\"245\"]/subfield[@code=\"a\"]') as Judul_a,
                EXTRACTVALUE(bm.metadata,'//datafield[@tag=\"245\"]/subfield[@code=\"b\"]') as Judul_b,
                EXTRACTVALUE(bm.metadata,'//datafield[@tag=\"245\"]/subfield[@code=\"c\"]') as Judul_c,
                b.author as Pengarang,
                MAX(CONCAT(COALESCE(bi.publishercode,''), ' ', COALESCE(bi.place,''))) AS Penerbit,
                bi.publicationyear AS TahunTerbit,
                items.enumchron AS Nomor,
                COUNT(DISTINCT items.itemnumber) AS Issue,
                SUM(items.copynumber) AS Eksemplar,
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
                ELSE items.homebranch
                END AS Lokasi"
            )
                ->join('biblioitems as bi', 'items.biblionumber', '=', 'bi.biblionumber')
                ->join('biblio as b', 'b.biblionumber', '=', 'bi.biblionumber')
                ->join('biblio_metadata as bm', 'b.biblionumber', '=', 'bm.biblionumber')
                ->where('items.itemlost', 0)
                ->where('items.withdrawn', 0)
                ->whereRaw('LEFT(items.itype,2) = "PR"');

            if ($prodi !== 'all') {
                $cnClasses = CnClassHelperr::getCnClassByProdi($prodi);
                QueryHelper::applyCnClassRules($query, $cnClasses);
            }

            if ($tahunTerakhir !== 'all') {
                $query->whereRaw('bi.publicationyear >= YEAR(CURDATE()) - ?', [$tahunTerakhir]);
            }

            $query->orderBy('TahunTerbit', 'desc');
            $query->groupBy('Judul_a', 'Judul_b', 'Judul_c', 'Pengarang', 'Nomor',  'TahunTerbit', 'Lokasi', 'Link');

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
                SUM(items.copynumber) as total_eksemplar
            ")
                ->join('biblioitems as bi', 'items.biblionumber', '=', 'bi.biblionumber')
                ->join('biblio as b', 'b.biblionumber', '=', 'bi.biblionumber')
                ->where('items.itemlost', 0)
                ->where('items.withdrawn', 0)
                ->whereIn('items.itype', ['PR']);


            // --- PERUBAHAN DI SINI JUGA ---
            if ($prodi !== 'all') {
                $cnClasses = CnClassHelperr::getCnClassByProdi($prodi);
                // Terapkan aturan yang sama ke query total
                QueryHelper::applyCnClassRules($totalQuery, $cnClasses);
            }
            // --- AKHIR PERUBAHAN ---

            if ($tahunTerakhir !== 'all') {
                $totalQuery->whereRaw('bi.publicationyear >= YEAR(CURDATE()) - ?', [$tahunTerakhir]);
            }

            $totals = $totalQuery->first();
            $totalJudul = $totals->total_judul ?? 0;
            $totalEksemplar = $totals->total_eksemplar ?? 0;

            if ($request->has('export_csv')) {
                // Pastikan method exportCsvProsiding ada di controller ini
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
        // Bagian dropdown prodi, tidak perlu diubah
        $listprodi = M_Auv::where('category', 'PRODI')
            ->whereRaw('CHAR_LENGTH(lib) >= 13')
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
                    DB::raw("CONCAT_WS(' ', b.title, EXTRACTVALUE(bm.metadata, '//datafield[@tag=\"245\"]/subfield[@code=\"b\"]')) AS Judul"),
                    DB::raw("MAX(CONCAT(COALESCE(bi.publishercode,''), ' ', COALESCE(bi.place,''))) AS Penerbit"),
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
                    ELSE i.homebranch
                    END AS Lokasi"),
                    DB::raw("EXTRACTVALUE(bm.metadata,'//datafield[@tag=\"856\"]/subfield[@code=\"u\"]') as Link_Jurnal")
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
                ->whereIn('i.itype', ['JR', 'JRA', 'EJ', 'JRT'])
                ->whereRaw("TRIM(i.enumchron) REGEXP '[0-9]{4}$'");

            if ($prodi !== 'all') {
                $cnClasses = CnClassHelperr::getCnClassByProdi($prodi);
                QueryHelper::applyCnClassRules($query, $cnClasses);
            }

            if ($tahunTerakhir !== 'all') {
                $query->whereRaw('RIGHT(i.enumchron, 4) >= ?', [date('Y') - (int)$tahunTerakhir]);
            }


            // KUNCI UTAMA: Group by untuk semua kolom non-agregat
            $query->groupBy('Judul', 'Kelas', 'Jenis_Koleksi', 'Jenis_Item_Tipe', 'i.enumchron', 'av.lib', 'it.description', 'i.homebranch', 'Link_Jurnal');

            // Urutkan berdasarkan judul
            $query->orderBy('Judul', 'asc');

            $processedData = $query->get();

            // Logika total disesuaikan untuk data rekapitulasi
            if ($processedData->isNotEmpty()) {
                $totalJudul = $processedData->count(); // Total judul adalah jumlah baris hasil rekap
                $totalEksemplar = $processedData->sum('Eksemplar'); // Total eksemplar adalah jumlah dari kolom Eksemplar
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

    /**
     * Tampilkan data koleksi e-book dan tangani ekspor CSV.
     *
     * @param Request $request
     * @return \Illuminate\View\View|\Symfony\Component\HttpFoundation\StreamedResponse
     */

    // public function ebook(Request $request)
    // {
    //     // 1. Mengambil data prodi dari tabel authorised_values dengan kategori 'PRODI'
    //     $listprodi = M_auv::where('category', 'PRODI')
    //         ->whereRaw('CHAR_LENGTH(lib) >= 13')
    //         ->orderBy('authorised_value', 'asc')
    //         ->get();

    //     // 2. Membuat objek untuk opsi "Semua Program Studi"
    //     $prodiOptionAll = new \stdClass();
    //     $prodiOptionAll->authorised_value = 'all'; // Ganti dari 'kode'
    //     $prodiOptionAll->lib = 'Semua Program Studi';   // Ganti dari 'nama'


    //     $listprodi->prepend($prodiOptionAll);



    //     $prodi = $request->input('prodi', 'initial');
    //     $tahunTerakhir = $request->input('tahun', 'all');

    //     $data = collect();
    //     $namaProdi = '';
    //     $dataExists = false;
    //     $totalJudul = 0;
    //     $totalEksemplar = 0;

    //     if ($prodi && $prodi !== 'initial') {


    //         // 3. Menyesuaikan pluck dengan nama kolom yang baru
    //         $prodiMapping = $listprodi->pluck('lib', 'authorised_value')->toArray();
    //         $namaProdi = $prodiMapping[$prodi] ?? 'Tidak Ditemukan';


    //         $query = M_items::selectRaw("
    //         EXTRACTVALUE(bm.metadata,'//datafield[@tag=\"245\"]/subfield[@code=\"a\"]') as Judul_a,
    //         EXTRACTVALUE(bm.metadata,'//datafield[@tag=\"245\"]/subfield[@code=\"b\"]') as Judul_b,
    //         b.author as Pengarang,
    //         bi.place AS Kota_Terbit,
    //         MAX(bi.publishercode) AS Penerbit_Raw,
    //         MAX(bi.place) AS Place_Raw,
    //         MAX(CONCAT(COALESCE(bi.publishercode,''), ' ', COALESCE(bi.place,''))) AS Penerbit,
    //         bi.publicationyear AS Tahun_Terbit,
    //         COUNT(items.itemnumber) AS Eksemplar,
    //         MAX(items.biblionumber) as biblionumber
    //     ")
    //             ->join('biblioitems as bi', 'items.biblionumber', '=', 'bi.biblionumber')
    //             ->join('biblio as b', 'b.biblionumber', '=', 'bi.biblionumber')
    //             ->join('biblio_metadata as bm', 'b.biblionumber', '=', 'bm.biblionumber')
    //             ->where('items.itemlost', 0)
    //             ->where('items.withdrawn', 0)
    //             ->where('items.itype', 'EB');

    //         if ($prodi !== 'all') {
    //             $cnClasses = CnClassHelper::getCnClassByProdi($prodi);
    //             $query->whereIn('bi.cn_class', $cnClasses);
    //         }

    //         if ($tahunTerakhir !== 'all') {
    //             $query->whereRaw('bi.publicationyear >= YEAR(CURDATE()) - ?', [$tahunTerakhir]);
    //         }

    //         $query->orderBy('Tahun_Terbit', 'desc');
    //         $query->groupBy('Judul_a', 'Judul_b', 'Pengarang', 'Kota_Terbit', 'Tahun_Terbit');

    //         $processedData = $query->get()->map(function ($row) {
    //             $fullJudul = $row->Judul_a;
    //             if (!empty($row->Judul_b)) {
    //                 $fullJudul .= ' ' . $row->Judul_b;
    //             }

    //             $row->Judul = html_entity_decode($fullJudul, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    //             $row->Pengarang = html_entity_decode($row->Pengarang, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    //             $row->Penerbit = html_entity_decode($row->Penerbit, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    //             $row->Kota_Terbit = html_entity_decode($row->Kota_Terbit, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    //             return $row;
    //         });

    //         $totalQuery = M_items::selectRaw("
    //         COUNT(DISTINCT b.biblionumber) as total_judul,
    //         COUNT(items.itemnumber) as total_eksemplar
    //     ")
    //             ->join('biblioitems as bi', 'items.biblionumber', '=', 'bi.biblionumber')
    //             ->join('biblio as b', 'b.biblionumber', '=', 'bi.biblionumber')
    //             ->where('items.itemlost', 0)
    //             ->where('items.withdrawn', 0)
    //             ->whereIn('items.itype', ['EB']);

    //         if ($prodi !== 'all') {
    //             $cnClasses = CnClassHelper::getCnClassByProdi($prodi);
    //             $totalQuery->whereIn('bi.cn_class', $cnClasses);
    //         }

    //         if ($tahunTerakhir !== 'all') {
    //             $totalQuery->whereRaw('bi.publicationyear >= YEAR(CURDATE()) - ?', [$tahunTerakhir]);
    //         }

    //         $totals = $totalQuery->first();
    //         $totalJudul = $totals->total_judul ?? 0;
    //         $totalEksemplar = $totals->total_eksemplar ?? 0;

    //         if ($request->has('export_csv')) {
    //             return $this->exportCsvEbook($processedData, $namaProdi, $tahunTerakhir);
    //         } else {
    //             $data = $processedData;
    //             $dataExists = $data->isNotEmpty();
    //         }
    //     }

    //     return view('pages.dapus.ebook', compact('data', 'prodi', 'listprodi', 'namaProdi', 'tahunTerakhir', 'dataExists', 'totalJudul', 'totalEksemplar'));
    // }

    public function ebook(Request $request)
    {
        // Bagian dropdown prodi, tidak perlu diubah
        $listprodi = M_Auv::where('category', 'PRODI')
            ->whereRaw('CHAR_LENGTH(lib) >= 13')
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

            // Query utama untuk data Ebook
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
                EXTRACTVALUE(bm.metadata,'//datafield[@tag=\"856\"]/subfield[@code=\"u\"]') as Link_Ebook,
                MAX(items.biblionumber) as biblionumber
            ")
                ->join('biblioitems as bi', 'items.biblionumber', '=', 'bi.biblionumber')
                ->join('biblio as b', 'b.biblionumber', '=', 'bi.biblionumber')
                ->join('biblio_metadata as bm', 'b.biblionumber', '=', 'bm.biblionumber')
                ->where('items.itemlost', 0)
                ->where('items.withdrawn', 0)
                ->where('items.itype', 'EB');

            // --- PERUBAHAN UTAMA DI SINI (BLOK 1) ---
            if ($prodi !== 'all') {
                $cnClasses = CnClassHelperr::getCnClassByProdi($prodi);
                // Ganti whereIn dengan QueryHelper yang lebih pintar
                QueryHelper::applyCnClassRules($query, $cnClasses);
            }
            // --- AKHIR PERUBAHAN ---

            if ($tahunTerakhir !== 'all') {
                $query->whereRaw('bi.publicationyear >= YEAR(CURDATE()) - ?', [(int)$tahunTerakhir]);
            }

            $query->orderBy('Tahun_Terbit', 'desc');
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

            // Query untuk menghitung total
            $totalQuery = M_items::selectRaw("
                COUNT(DISTINCT b.biblionumber) as total_judul,
                COUNT(items.itemnumber) as total_eksemplar
            ")
                ->join('biblioitems as bi', 'items.biblionumber', '=', 'bi.biblionumber')
                ->join('biblio as b', 'b.biblionumber', '=', 'bi.biblionumber')
                ->where('items.itemlost', 0)
                ->where('items.withdrawn', 0)
                ->whereIn('items.itype', ['EB']);

            // --- PERUBAHAN UTAMA DI SINI (BLOK 2) ---
            if ($prodi !== 'all') {
                $cnClasses = CnClassHelperr::getCnClassByProdi($prodi);
                // Terapkan juga helpernya ke query total
                QueryHelper::applyCnClassRules($totalQuery, $cnClasses);
            }
            // --- AKHIR PERUBAHAN ---

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

    // public function textbook(Request $request)
    // {


    //     // 1. Mengambil data prodi dari tabel authorised_values dengan kategori 'PRODI'
    //     $listprodi = M_auv::where('category', 'PRODI')->whereRaw('CHAR_LENGTH(lib) >= 13')
    //         ->orderBy('authorised_value', 'asc')->get();

    //     // 2. Membuat objek untuk opsi "Semua Program Studi"
    //     $prodiOptionAll = new \stdClass();
    //     $prodiOptionAll->authorised_value = 'all';
    //     $prodiOptionAll->lib = 'Semua Program Studi';

    //     // Tambahkan opsi "Semua" ke awal list
    //     $listprodi->prepend($prodiOptionAll);


    //     $prodi = $request->input('prodi', 'initial');
    //     $tahunTerakhir = $request->input('tahun', 'all');

    //     $data = collect();
    //     $namaProdi = '';
    //     $dataExists = false;
    //     $totalJudul = 0;
    //     $totalEksemplar = 0;

    //     if ($prodi && $prodi !== 'initial') {

    //         // 3. Menyesuaikan pluck dengan nama kolom yang baru
    //         $prodiMapping = $listprodi->pluck('lib', 'authorised_value')->toArray();
    //         $namaProdi = $prodiMapping[$prodi] ?? 'Tidak Ditemukan';


    //         $query = M_items::selectRaw("
    //         EXTRACTVALUE(bm.metadata,'//datafield[@tag=\"245\"]/subfield[@code=\"a\"]') as Judul_a,
    //         EXTRACTVALUE(bm.metadata,'//datafield[@tag=\"245\"]/subfield[@code=\"b\"]') as Judul_b,
    //         b.author as Pengarang,
    //         bi.place AS Kota_Terbit,
    //         MAX(bi.publishercode) AS Penerbit_Raw,
    //         MAX(bi.place) AS Place_Raw,
    //         MAX(CONCAT(COALESCE(bi.publishercode,''), ' ', COALESCE(bi.place,''))) AS Penerbit,
    //         bi.publicationyear AS Tahun_Terbit,
    //         COUNT(items.itemnumber) AS Eksemplar,
    //         items.homebranch as Lokasi
    //     ")
    //             ->join('biblioitems as bi', 'items.biblionumber', '=', 'bi.biblionumber')
    //             ->join('biblio as b', 'b.biblionumber', '=', 'bi.biblionumber')
    //             ->join('biblio_metadata as bm', 'b.biblionumber', '=', 'bm.biblionumber')
    //             ->where('items.itemlost', 0)
    //             ->where('items.withdrawn', 0)
    //             ->whereIn('items.itype', ['BKS', 'BKSA', 'BKSCA', 'BKSC'])
    //             // ->whereRaw('LEFT(items.itype, 3) = "BKS"')
    //             ->whereRaw('LEFT(items.ccode, 1) <> "R"');

    //         if ($prodi !== 'all') {
    //             $cnClasses = CnClassHelper::getCnClassByProdi($prodi);
    //             $query->whereIn('bi.cn_class', $cnClasses);
    //         }

    //         if ($tahunTerakhir !== 'all') {
    //             $query->whereRaw('bi.publicationyear >= YEAR(CURDATE()) - ?', [$tahunTerakhir]);
    //         }

    //         $query->orderBy('Tahun_Terbit', 'desc');
    //         $query->groupBy('Judul_a', 'Judul_b', 'Pengarang', 'Kota_Terbit', 'Tahun_Terbit', 'Lokasi');

    //         $processedData = $query->get()->map(function ($row) {
    //             $fullJudul = $row->Judul_a;
    //             if (!empty($row->Judul_b)) {
    //                 $fullJudul .= ' ' . $row->Judul_b;
    //             }

    //             $row->Judul = html_entity_decode($fullJudul, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    //             $row->Pengarang = html_entity_decode($row->Pengarang, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    //             $row->Penerbit = html_entity_decode($row->Penerbit, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    //             $row->Kota_Terbit = html_entity_decode($row->Kota_Terbit, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    //             return $row;
    //         });

    //         $totalQuery = M_items::selectRaw("
    //         COUNT(DISTINCT b.biblionumber) as total_judul,
    //         COUNT(items.itemnumber) as total_eksemplar
    //     ")
    //             ->join('biblioitems as bi', 'items.biblionumber', '=', 'bi.biblionumber')
    //             ->join('biblio as b', 'b.biblionumber', '=', 'bi.biblionumber')
    //             ->where('items.itemlost', 0)
    //             ->where('items.withdrawn', 0)
    //             ->whereRaw('LEFT(items.itype, 3) = "BKS"')
    //             ->whereRaw('LEFT(items.ccode, 1) <> "R"');

    //         if ($prodi !== 'all') {
    //             $cnClasses = CnClassHelper::getCnClassByProdi($prodi);
    //             $totalQuery->whereIn('bi.cn_class', $cnClasses);
    //         }

    //         if ($tahunTerakhir !== 'all') {
    //             $totalQuery->whereRaw('bi.publicationyear >= YEAR(CURDATE()) - ?', [$tahunTerakhir]);
    //         }

    //         $totals = $totalQuery->first();
    //         $totalJudul = $totals->total_judul ?? 0;
    //         $totalEksemplar = $totals->total_eksemplar ?? 0;

    //         if ($request->has('export_csv')) {
    //             return $this->exportCsvTextbook($processedData, $namaProdi, $tahunTerakhir);
    //         } else {
    //             $data = $processedData;
    //             $dataExists = $data->isNotEmpty();
    //         }
    //     }

    //     return view('pages.dapus.textbook', compact('data', 'prodi', 'listprodi', 'namaProdi', 'tahunTerakhir', 'dataExists', 'totalJudul', 'totalEksemplar'));
    // }

    public function textbook(Request $request)
    {
        // 1. Mengambil data prodi dari tabel authorised_values dengan kategori 'PRODI'
        $listprodi = M_Auv::where('category', 'PRODI')->whereRaw('CHAR_LENGTH(lib) >= 13')
            ->orderBy('authorised_value', 'asc')->get();

        // 2. Membuat objek untuk opsi "Semua Program Studi"
        $prodiOptionAll = new \stdClass();
        $prodiOptionAll->authorised_value = 'all';
        $prodiOptionAll->lib = 'Semua Program Studi';

        // Tambahkan opsi "Semua" ke awal list
        $listprodi->prepend($prodiOptionAll);

        $prodi = $request->input('prodi', 'initial');
        $tahunTerakhir = $request->input('tahun', 'all');

        $data = collect();
        $namaProdi = '';
        $dataExists = false;
        $totalJudul = 0;
        $totalEksemplar = 0;

        if ($prodi && $prodi !== 'initial') {
            // 3. Menyesuaikan pluck dengan nama kolom yang baru
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
                items.homebranch as Lokasi
            ")
                ->join('biblioitems as bi', 'items.biblionumber', '=', 'bi.biblionumber')
                ->join('biblio as b', 'b.biblionumber', '=', 'bi.biblionumber')
                ->join('biblio_metadata as bm', 'b.biblionumber', '=', 'bm.biblionumber')
                ->where('items.itemlost', 0)
                ->where('items.withdrawn', 0)
                ->whereIn('items.itype', ['BKS', 'BKSA', 'BKSCA', 'BKSC'])
                ->whereRaw('LEFT(items.ccode, 1) <> "R"');

            // --- PERUBAHAN UTAMA DI SINI (BLOK 1) ---
            if ($prodi !== 'all') {
                $cnClasses = CnClassHelperr::getCnClassByProdi($prodi);
                QueryHelper::applyCnClassRules($query, $cnClasses);
            }
            // --- AKHIR PERUBAHAN ---

            if ($tahunTerakhir !== 'all') {
                $query->whereRaw('bi.publicationyear >= YEAR(CURDATE()) - ?', [(int)$tahunTerakhir]);
            }

            $query->orderBy('Tahun_Terbit', 'desc');
            $query->groupBy('Judul_a', 'Judul_b', 'Pengarang', 'Kota_Terbit', 'Tahun_Terbit', 'Lokasi');

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

            // --- PERUBAHAN UTAMA DI SINI (BLOK 2) ---
            if ($prodi !== 'all') {
                $cnClasses = CnClassHelperr::getCnClassByProdi($prodi);
                QueryHelper::applyCnClassRules($totalQuery, $cnClasses);
            }
            // --- AKHIR PERUBAHAN ---

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

    /**
     * Tampilkan data koleksi periodikal dan tangani ekspor CSV.
     *
     * @param Request $request
     * @return \Illuminate\View\View|\Symfony\Component\HttpFoundation\StreamedResponse
     */

    // public function periodikal(Request $request)
    // {
    //     // --- PERUBAHAN DIMULAI DI SINI ---

    //     // 1. Mengambil data prodi dari tabel authorised_values dengan kategori 'PRODI'
    //     $listprodi = M_auv::where('category', 'PRODI')->whereRaw('CHAR_LENGTH(lib) >= 13')
    //         ->orderBy('authorised_value', 'asc')->get();

    //     // 2. Membuat objek untuk opsi "Semua Program Studi"
    //     $prodiOptionAll = new \stdClass();
    //     $prodiOptionAll->authorised_value = 'all'; // Ganti dari 'kode'
    //     $prodiOptionAll->lib = 'Semua Program Studi';   // Ganti dari 'nama'

    //     // Tambahkan opsi "Semua" ke awal list
    //     $listprodi->prepend($prodiOptionAll);

    //     // --- PERUBAHAN SELESAI DI SINI ---


    //     $prodi = $request->input('prodi', 'initial');
    //     $tahunTerakhir = $request->input('tahun', 'all');

    //     $data = collect();
    //     $namaProdi = '';
    //     $dataExists = false;
    //     $totalJudul = 0;
    //     $totalEksemplar = 0;

    //     if ($prodi && $prodi !== 'initial') {

    //         // 3. Menyesuaikan pluck dengan nama kolom yang baru
    //         $prodiMapping = $listprodi->pluck('lib', 'authorised_value')->toArray();
    //         $namaProdi = $prodiMapping[$prodi] ?? 'Tidak Ditemukan';


    //         $periodicalTypes = ['JR', 'JRA', 'MJA', 'MJI', 'MJIP', 'MJP'];

    //         $query = M_items::select(
    //             'i.itype AS Jenis_kode',
    //             't.description AS Jenis',
    //             'bi.publishercode AS Penerbit',
    //             'bi.place AS Tempat_Terbit',
    //             'bi.publicationyear AS Tahun_Terbit',
    //             'bi.cn_class as Kelas',
    //             'i.enumchron AS Nomor',
    //             'i.homebranch as Lokasi'
    //         )
    //             ->selectRaw("EXTRACTVALUE(bm.metadata,'//datafield[@tag=\"245\"]/subfield[@code=\"a\"]') as Judul_a")
    //             ->selectRaw("EXTRACTVALUE(bm.metadata,'//datafield[@tag=\"245\"]/subfield[@code=\"b\"]') as Judul_b")
    //             ->selectRaw('COUNT(i.itemnumber) AS Issue')
    //             ->selectRaw('SUM(i.copynumber) AS Eksemplar')
    //             ->from('items as i')
    //             ->join('biblioitems as bi', 'i.biblionumber', '=', 'bi.biblionumber')
    //             ->join('biblio as b', 'i.biblionumber', '=', 'b.biblionumber')
    //             ->join('biblio_metadata as bm', 'b.biblionumber', '=', 'bm.biblionumber')
    //             ->join('itemtypes as t', 'i.itype', '=', 't.itemtype')
    //             ->where('i.itemlost', 0)
    //             ->where('i.withdrawn', 0)
    //             ->whereIn('i.itype', $periodicalTypes)
    //             ->groupBy('i.biblionumber');

    //         if ($prodi !== 'all') {
    //             $cnClasses = CnClassHelper::getCnClassByProdi($prodi);
    //             $query->whereIn('bi.cn_class', $cnClasses);
    //         }

    //         if ($tahunTerakhir !== 'all') {
    //             $query->whereRaw('bi.publicationyear >= YEAR(CURDATE()) - ?', [$tahunTerakhir]);
    //         }

    //         $query->groupBy('Jenis_kode', 'Jenis', 'Judul_a', 'Judul_b', 'Nomor', 'Kelas', 'Lokasi', 'Penerbit', 'Tempat_Terbit', 'Tahun_Terbit');

    //         $processedData = $query->get()->map(function ($row) {
    //             $fullJudul = $row->Judul_a;
    //             if (!empty($row->Judul_b)) {
    //                 $fullJudul .= ' ' . $row->Judul_b;
    //             }
    //             $row->Judul = html_entity_decode($fullJudul, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    //             $penerbit = $row->Penerbit;
    //             if (!empty($row->Tempat_Terbit)) {
    //                 $penerbit .= ' : ' . $row->Tempat_Terbit;
    //             }
    //             $row->Penerbit_Lengkap = html_entity_decode($penerbit, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    //             $Tahun_Terbit = $row->Tahun_Terbit;
    //             if (empty($Tahun_Terbit) || $Tahun_Terbit == '0000') {
    //                 $row->Tahun_Terbit = 'n.d.';
    //             }

    //             return $row;
    //         });

    //         $totalQuery = M_items::selectRaw("
    //         COUNT(DISTINCT b.biblionumber) as total_judul,
    //         COUNT(items.itemnumber) as total_eksemplar
    //     ")
    //             ->join('biblioitems as bi', 'items.biblionumber', '=', 'bi.biblionumber')
    //             ->join('biblio as b', 'b.biblionumber', '=', 'bi.biblionumber')
    //             ->where('items.itemlost', 0)
    //             ->where('items.withdrawn', 0)
    //             ->whereIn('items.itype', $periodicalTypes);

    //         if ($prodi !== 'all') {
    //             $cnClasses = CnClassHelper::getCnClassByProdi($prodi);
    //             $totalQuery->whereIn('bi.cn_class', $cnClasses);
    //         }

    //         if ($tahunTerakhir !== 'all') {
    //             $totalQuery->whereRaw('bi.publicationyear >= YEAR(CURDATE()) - ?', [$tahunTerakhir]);
    //         }

    //         $totals = $totalQuery->first();
    //         $totalJudul = $totals->total_judul ?? 0;
    //         $totalEksemplar = $totals->total_eksemplar ?? 0;

    //         if ($request->has('export_csv')) {
    //             return $this->exportCsvPeriodikal($processedData, $namaProdi, $tahunTerakhir);
    //         } else {
    //             $data = $processedData;
    //             $dataExists = $data->isNotEmpty();
    //         }
    //     }

    //     return view('pages.dapus.periodikal', compact('data', 'prodi', 'listprodi', 'namaProdi', 'tahunTerakhir', 'dataExists', 'totalJudul', 'totalEksemplar'));
    // }

    public function periodikal(Request $request)
    {
        // 1. Mengambil data prodi, sama seperti fungsi lainnya
        $listprodi = M_Auv::where('category', 'PRODI')->whereRaw('CHAR_LENGTH(lib) >= 13')
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

            // Query utama untuk rekapitulasi data periodikal
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

    /**
     * Tampilkan data koleksi referensi dan tangani ekspor CSV.
     *
     * @param Request $request
     * @return \Illuminate\View\View|\Symfony\Component\HttpFoundation\StreamedResponse
     */


    // public function referensi(Request $request)
    // {
    //     // --- PERUBAHAN DIMULAI DI SINI ---

    //     // 1. Mengambil data prodi dari tabel authorised_values dengan kategori 'PRODI'
    //     $listprodi = M_auv::where('category', 'PRODI')->whereRaw('CHAR_LENGTH(lib) >= 13')
    //         ->orderBy('authorised_value', 'asc')->get();

    //     // 2. Membuat objek untuk opsi "Semua Program Studi"
    //     $prodiOptionAll = new \stdClass();
    //     $prodiOptionAll->authorised_value = 'all'; // Ganti dari 'kode'
    //     $prodiOptionAll->lib = 'Semua Program Studi';   // Ganti dari 'nama'

    //     // Tambahkan opsi "Semua" ke awal list
    //     $listprodi->prepend($prodiOptionAll);

    //     // --- PERUBAHAN SELESAI DI SINI ---


    //     $prodi = $request->input('prodi', 'initial');
    //     $tahunTerakhir = $request->input('tahun', 'all');

    //     $data = collect();
    //     $namaProdi = '';
    //     $dataExists = false;
    //     $totalJudul = 0;
    //     $totalEksemplar = 0;

    //     if ($prodi && $prodi !== 'initial') {
    //         // --- PERUBAHAN KECIL DI SINI ---

    //         // 3. Menyesuaikan pluck dengan nama kolom yang baru
    //         $prodiMapping = $listprodi->pluck('lib', 'authorised_value')->toArray();
    //         $namaProdi = $prodiMapping[$prodi] ?? 'Tidak Ditemukan';

    //         // --- SISA KODE DI BAWAH INI TIDAK PERLU DIUBAH ---

    //         $query = M_items::selectRaw("
    //         bi.cn_class as Kelas,
    //         EXTRACTVALUE(bm.metadata,'//datafield[@tag=\"245\"]/subfield[@code=\"a\"]') as Judul_a,
    //         EXTRACTVALUE(bm.metadata,'//datafield[@tag=\"245\"]/subfield[@code=\"b\"]') as Judul_b,
    //         b.author as Pengarang,
    //         bi.place AS Kota_Terbit,
    //         MAX(bi.publishercode) AS Penerbit_Raw,
    //         MAX(bi.place) AS Place_Raw,
    //         MAX(CONCAT(COALESCE(bi.publishercode,''), ' ', COALESCE(bi.place,''))) AS Penerbit,
    //         bi.publicationyear AS Tahun_Terbit,
    //         COUNT(i.itemnumber) AS Eksemplar,
    //         i.homebranch as Lokasi
    //     ")
    //             ->from('items as i')
    //             ->join('biblioitems as bi', 'i.biblionumber', '=', 'bi.biblionumber')
    //             ->join('biblio as b', 'i.biblionumber', '=', 'b.biblionumber')
    //             ->join('biblio_metadata as bm', 'b.biblionumber', '=', 'bm.biblionumber')
    //             ->where('i.itemlost', 0)
    //             ->where('i.withdrawn', 0)
    //             ->whereRaw('LEFT(i.itype,3) = "BKS"')
    //             ->whereRaw('LEFT(i.ccode,1) = "R"');

    //         if ($prodi !== 'all') {
    //             $cnClasses = CnClassHelper::getCnClassByProdi($prodi);
    //             $query->whereIn('bi.cn_class', $cnClasses);
    //         }

    //         if ($tahunTerakhir !== 'all') {
    //             $query->whereRaw('bi.publicationyear >= YEAR(CURDATE()) - ?', [$tahunTerakhir]);
    //         }

    //         $query->orderBy('Tahun_Terbit', 'desc');
    //         $query->groupBy('Judul_a', 'Judul_b', 'Pengarang', 'Kota_Terbit', 'Tahun_Terbit', 'Kelas', 'Lokasi');

    //         $processedData = $query->get()->map(function ($row) {
    //             $fullJudul = $row->Judul_a;
    //             if (!empty($row->Judul_b)) {
    //                 $fullJudul .= ' ' . $row->Judul_b;
    //             }

    //             $row->Judul = html_entity_decode($fullJudul, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    //             $row->Pengarang = html_entity_decode($row->Pengarang, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    //             $row->Penerbit = html_entity_decode($row->Penerbit, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    //             $row->Kota_Terbit = html_entity_decode($row->Kota_Terbit, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    //             return $row;
    //         });

    //         $totalQuery = M_items::selectRaw("
    //         COUNT(DISTINCT b.biblionumber) as total_judul,
    //         COUNT(items.itemnumber) as total_eksemplar
    //     ")
    //             ->join('biblioitems as bi', 'items.biblionumber', '=', 'bi.biblionumber')
    //             ->join('biblio as b', 'b.biblionumber', '=', 'bi.biblionumber')
    //             ->where('items.itemlost', 0)
    //             ->where('items.withdrawn', 0)
    //             ->whereRaw('LEFT(items.itype,3) = "BKS"')
    //             ->whereRaw('LEFT(items.ccode,1) = "R"');

    //         if ($prodi !== 'all') {
    //             $cnClasses = CnClassHelper::getCnClassByProdi($prodi);
    //             $totalQuery->whereIn('bi.cn_class', $cnClasses);
    //         }

    //         if ($tahunTerakhir !== 'all') {
    //             $totalQuery->whereRaw('bi.publicationyear >= YEAR(CURDATE()) - ?', [$tahunTerakhir]);
    //         }

    //         $totals = $totalQuery->first();
    //         $totalJudul = $totals->total_judul ?? 0;
    //         $totalEksemplar = $totals->total_eksemplar ?? 0;

    //         if ($request->has('export_csv')) {
    //             return $this->exportCsvReferensi($processedData, $namaProdi, $tahunTerakhir);
    //         } else {
    //             $data = $processedData;
    //             $dataExists = $data->isNotEmpty();
    //         }
    //     }

    //     return view('pages.dapus.referensi', compact('data', 'prodi', 'listprodi', 'namaProdi', 'tahunTerakhir', 'dataExists', 'totalJudul', 'totalEksemplar'));
    // }

    public function referensi(Request $request)
    {

        $listprodi = M_Auv::where('category', 'PRODI')->whereRaw('CHAR_LENGTH(lib) >= 13')
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
                WHEN i.homebranch = 'MEDLIB' THEN 'Perpustakaan Kedokteran'
                WHEN i.homebranch = 'PAUD' THEN 'Perpustakaan PAUD'
                WHEN i.homebranch = 'POG' THEN 'Perpustakaan Pendidikan Olahraga'
                WHEN i.homebranch = 'PESMA' THEN 'Perpustakaan Pesma Haji Mas Mansyur'
                WHEN i.homebranch = 'PGSDKRA' THEN 'Perpustakaan PGSD'
                WHEN i.homebranch = 'PASCA' THEN 'Perpustakaan Postgraduate'
                WHEN i.homebranch = 'RSGM' THEN 'Perpustakaan Rumah Sakit Gigi dan Mulut'
                WHEN i.homebranch = 'PSI' THEN 'Perpustakaan Pusat Studi Psikologi Islam'
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

            // --- PERUBAHAN UTAMA DI SINI (BLOK 2) ---
            if ($prodi !== 'all') {
                $cnClasses = CnClassHelperr::getCnClassByProdi($prodi);
                QueryHelper::applyCnClassRules($totalQuery, $cnClasses);
            }
            // --- AKHIR PERUBAHAN ---

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

    private function exportCsvJurnal($data, $namaProdi, $tahunTerakhir)
    {
        // Bagian membuat nama file tidak perlu diubah, sudah bagus.
        $filename = "koleksi_jurnal";
        if ($namaProdi && $namaProdi !== 'Pilih Program Studi' && $namaProdi !== 'Semua Program Studi') {
            $cleanProdiName = preg_replace('/[^a-zA-Z0-9 ]/', '', str_replace(' ', '_', $namaProdi));
            $filename .= "_" . $cleanProdiName;
        }
        $filename .= "_" . ($tahunTerakhir !== 'all' ? $tahunTerakhir . "_tahun_terakhir" : "semua_tahun");
        $filename .= "_" . Carbon::now()->format('Ymd_His') . ".csv";

        // --- PERUBAHAN 1: Sesuaikan Headers CSV ---
        $headers = [
            'No',
            'Kelas',
            'Judul',
            'Penerbit',
            'Nomor',
            'Issue',
            'Eksemplar',
            'Jenis Koleksi',
            'Jenis Item Tipe',
            'Lokasi'
        ];

        $callback = function () use ($data, $headers, $namaProdi, $tahunTerakhir) {
            $file = fopen('php://output', 'w');
            fputs($file, $bom = chr(0xEF) . chr(0xBB) . chr(0xBF)); // Untuk kompatibilitas Excel

            // Bagian judul file CSV, tidak ada perubahan
            $judulProdi = 'Daftar Koleksi Jurnal - ' . ($namaProdi ?: 'Semua Program Studi');
            $judulTahun = ($tahunTerakhir !== 'all') ? ('Tahun Terbit: ' . $tahunTerakhir . ' tahun terakhir') : 'Semua Tahun Terbit';
            fputcsv($file, [$judulProdi . ' - ' . $judulTahun], ';');
            fputcsv($file, [''], ';'); // Baris kosong sebagai pemisah
            fputcsv($file, $headers, ';');

            $i = 1;
            foreach ($data as $row) {
                // --- PERUBAHAN 2: Sesuaikan Data per Baris ---
                $rowData = [
                    $i++,
                    $row->Kelas,
                    $row->Judul,
                    $row->Penerbit,
                    $row->Nomor,
                    $row->Issue,
                    $row->Eksemplar,
                    $row->Jenis_Koleksi,
                    $row->Jenis_Item_Tipe,
                    $row->Lokasi,
                ];
                fputcsv($file, $rowData, ';');
            }
            fclose($file);
        };

        // Bagian response tidak perlu diubah.
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
            'Kelas',
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
                    $row->Kelas,
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
