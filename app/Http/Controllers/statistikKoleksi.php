<?php

namespace App\Http\Controllers;

use App\Models\M_eprodi;
use App\Models\M_items;
use Illuminate\Http\Request;
use App\Helpers\CnClassHelper;
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

        // 2. Membuat objek untuk opsi "Semua Program Studi"
        // Kita gunakan stdClass agar lebih fleksibel dan sesuaikan nama propertinya
        $prodiOptionAll = new \stdClass();
        $prodiOptionAll->authorised_value = 'all'; // Dulu 'kode', sekarang 'authorised_value'
        $prodiOptionAll->lib = 'Semua Program Studi';   // Dulu 'nama', sekarang 'lib'

        // Tambahkan opsi "Semua" ke awal list
        $listprodi->prepend($prodiOptionAll);

        // --- PERUBAHAN SELESAI DI SINI ---


        $prodi = $request->input('prodi', 'initial');
        $tahunTerakhir = $request->input('tahun', 'all');

        $data = collect();
        $namaProdi = '';
        $dataExists = false;
        $totalJudul = 0;
        $totalEksemplar = 0;

        if ($prodi && $prodi !== 'initial') {
            // --- PERUBAHAN KECIL DI SINI ---

            // 3. Menyesuaikan pluck dengan nama kolom yang baru
            $prodiMapping = $listprodi->pluck('lib', 'authorised_value')->toArray();
            $namaProdi = $prodiMapping[$prodi] ?? 'Tidak Ditemukan';

            // --- SISA KODE DI BAWAH INI TIDAK PERLU DIUBAH ---

            $query = M_items::selectRaw("
            bi.cn_class as Kelas,
            EXTRACTVALUE(bm.metadata,'//datafield[@tag=\"245\"]/subfield[@code=\"a\"]') as Judul_a,
            EXTRACTVALUE(bm.metadata,'//datafield[@tag=\"245\"]/subfield[@code=\"b\"]') as Judul_b,
            EXTRACTVALUE(bm.metadata,'//datafield[@tag=\"245\"]/subfield[@code=\"c\"]') as Judul_c,
            b.author as Pengarang,
            MAX(CONCAT(COALESCE(bi.publishercode,''), ' ', COALESCE(bi.place,''))) AS Penerbit,
            bi.publicationyear AS TahunTerbit,
            items.enumchron AS Nomor,
            CONCAT('https://search-lib.ums.ac.id/cgi-bin/koha/opac-detail.pl?biblionumber=', b.biblionumber) AS Link,
            COUNT(DISTINCT items.itemnumber) AS Issue,
            SUM(items.copynumber) AS Eksemplar,
            items.homebranch as Lokasi")
                ->join('biblioitems as bi', 'items.biblionumber', '=', 'bi.biblionumber')
                ->join('biblio as b', 'b.biblionumber', '=', 'bi.biblionumber')
                ->join('biblio_metadata as bm', 'b.biblionumber', '=', 'bm.biblionumber')
                ->where('items.itemlost', 0)
                ->where('items.withdrawn', 0)
                ->whereRaw('LEFT(items.itype,2) = "PR"');

            if ($prodi !== 'all') {
                $cnClasses = CnClassHelper::getCnClassByProdi($prodi);
                $query->whereIn('bi.cn_class', $cnClasses);
            }

            if ($tahunTerakhir !== 'all') {
                $query->whereRaw('bi.publicationyear >= YEAR(CURDATE()) - ?', [$tahunTerakhir]);
            }

            $query->orderBy('TahunTerbit', 'desc');
            $query->groupBy('Judul_a', 'Judul_b', 'Judul_c', 'Pengarang', 'Nomor', 'Kelas', 'TahunTerbit', 'Lokasi', 'Link');

            $processedData = $query->get()->map(function ($row) {
                $fullJudul = $row->Judul_a;
                if (!empty($row->Judul_b)) {
                    $fullJudul .= ' : ' . $row->Judul_b;
                }
                if (!empty($row->Judul_c)) {
                    $fullJudul .= ' / ' . $row->Judul_c;
                }

                $row->Judul = html_entity_decode($row->Judul, ENT_QUOTES, 'UTF-8');
                $row->Penerbit = html_entity_decode($row->Penerbit, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $row->Pengarang = html_entity_decode($row->Pengarang, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                return $row;
            });
            // dd($processedData);
            $totalQuery = M_items::selectRaw("
            COUNT(DISTINCT b.biblionumber) as total_judul,
            SUM(items.copynumber) as total_eksemplar
        ")
                ->join('biblioitems as bi', 'items.biblionumber', '=', 'bi.biblionumber')
                ->join('biblio as b', 'b.biblionumber', '=', 'bi.biblionumber')
                ->where('items.itemlost', 0)
                ->where('items.withdrawn', 0)
                ->whereIn('items.itype', ['PR']);


            if ($prodi !== 'all') {
                $cnClasses = CnClassHelper::getCnClassByProdi($prodi);
                $totalQuery->whereIn('bi.cn_class', $cnClasses);
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
        // Bagian dropdown prodi, tidak perlu diubah
        $listprodi = M_auv::where('category', 'PRODI')
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

            // --- QUERY UTAMA DIMODIFIKASI DI SINI ---

            $query = M_items::query() // Kita mulai dari model M_items
                ->from('items as i')  // Alias 'i' agar sesuai dengan query SQL-mu
                ->select(
                    'bi.cn_class AS Kelas',
                    DB::raw("CONCAT_WS(' ', b.title, EXTRACTVALUE(bm.metadata, '//datafield[@tag=\"245\"]/subfield[@code=\"b\"]')) AS Judul"),
                    'bi.publishercode AS Penerbit',
                    'i.enumchron AS Nomor',
                    'av.lib AS Jenis_Koleksi',
                    'it.description AS Jenis_Item_Tipe',
                    'i.homebranch AS Lokasi'
                )
                ->addSelect(DB::raw('1 AS Issue')) // Issue per baris selalu 1
                ->addSelect(DB::raw('1 AS Eksemplar')) // Eksemplar per baris selalu 1
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

            // Filter prodi (tetap sama)
            if ($prodi !== 'all') {
                $cnClasses = CnClassHelper::getCnClassByProdi($prodi);
                $query->whereIn('bi.cn_class', $cnClasses);
            }

            // Filter tahun (disesuaikan sedikit untuk kolom publicationyear)
            if ($tahunTerakhir !== 'all') {
                $query->where('bi.publicationyear', '>=', date('Y') - $tahunTerakhir);
            }

            // Urutkan berdasarkan judul dan nomor
            $query->orderBy('Judul', 'asc')
                ->orderBy('i.enumchron', 'asc');

            $processedData = $query->get();

            // --- QUERY TOTAL JUGA PERLU DISESUAIKAN ---

            // Kita bisa dapatkan total dari query yang sudah difilter
            // Total Judul (unik berdasarkan judul) dan Total Eksemplar (jumlah baris)
            if ($processedData->isNotEmpty()) {
                $totalJudul = $processedData->pluck('Judul')->unique()->count();
                $totalEksemplar = $processedData->count();
            }

            if ($request->has('export_csv')) {
                // Pastikan fungsi exportCsvJurnal bisa menangani data baru
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

    public function ebook(Request $request)
    {
        // --- PERUBAHAN DIMULAI DI SINI ---

        // 1. Mengambil data prodi dari tabel authorised_values dengan kategori 'PRODI'
        $listprodi = M_auv::where('category', 'PRODI')
            ->whereRaw('CHAR_LENGTH(lib) >= 13')
            ->orderBy('authorised_value', 'asc')
            ->get();

        // 2. Membuat objek untuk opsi "Semua Program Studi"
        $prodiOptionAll = new \stdClass();
        $prodiOptionAll->authorised_value = 'all'; // Ganti dari 'kode'
        $prodiOptionAll->lib = 'Semua Program Studi';   // Ganti dari 'nama'

        // Tambahkan opsi "Semua" ke awal list
        $listprodi->prepend($prodiOptionAll);

        // --- PERUBAHAN SELESAI DI SINI ---


        $prodi = $request->input('prodi', 'initial');
        $tahunTerakhir = $request->input('tahun', 'all');

        $data = collect();
        $namaProdi = '';
        $dataExists = false;
        $totalJudul = 0;
        $totalEksemplar = 0;

        if ($prodi && $prodi !== 'initial') {
            // --- PERUBAHAN KECIL DI SINI ---

            // 3. Menyesuaikan pluck dengan nama kolom yang baru
            $prodiMapping = $listprodi->pluck('lib', 'authorised_value')->toArray();
            $namaProdi = $prodiMapping[$prodi] ?? 'Tidak Ditemukan';

            // --- SISA KODE DI BAWAH INI TIDAK PERLU DIUBAH ---

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
            MAX(items.biblionumber) as biblionumber
        ")
                ->join('biblioitems as bi', 'items.biblionumber', '=', 'bi.biblionumber')
                ->join('biblio as b', 'b.biblionumber', '=', 'bi.biblionumber')
                ->join('biblio_metadata as bm', 'b.biblionumber', '=', 'bm.biblionumber')
                ->where('items.itemlost', 0)
                ->where('items.withdrawn', 0)
                ->where('items.itype', 'EB');

            if ($prodi !== 'all') {
                $cnClasses = CnClassHelper::getCnClassByProdi($prodi);
                $query->whereIn('bi.cn_class', $cnClasses);
            }

            if ($tahunTerakhir !== 'all') {
                $query->whereRaw('bi.publicationyear >= YEAR(CURDATE()) - ?', [$tahunTerakhir]);
            }

            $query->orderBy('Tahun_Terbit', 'desc');
            $query->groupBy('Judul_a', 'Judul_b', 'Pengarang', 'Kota_Terbit', 'Tahun_Terbit');

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
                $cnClasses = CnClassHelper::getCnClassByProdi($prodi);
                $totalQuery->whereIn('bi.cn_class', $cnClasses);
            }

            if ($tahunTerakhir !== 'all') {
                $totalQuery->whereRaw('bi.publicationyear >= YEAR(CURDATE()) - ?', [$tahunTerakhir]);
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
        // --- PERUBAHAN DIMULAI DI SINI ---

        // 1. Mengambil data prodi dari tabel authorised_values dengan kategori 'PRODI'
        $listprodi = M_auv::where('category', 'PRODI')->whereRaw('CHAR_LENGTH(lib) >= 13')
            ->orderBy('authorised_value', 'asc')->get();

        // 2. Membuat objek untuk opsi "Semua Program Studi"
        $prodiOptionAll = new \stdClass();
        $prodiOptionAll->authorised_value = 'all'; // Ganti dari 'kode'
        $prodiOptionAll->lib = 'Semua Program Studi';   // Ganti dari 'nama'

        // Tambahkan opsi "Semua" ke awal list
        $listprodi->prepend($prodiOptionAll);

        // --- PERUBAHAN SELESAI DI SINI ---


        $prodi = $request->input('prodi', 'initial');
        $tahunTerakhir = $request->input('tahun', 'all');

        $data = collect();
        $namaProdi = '';
        $dataExists = false;
        $totalJudul = 0;
        $totalEksemplar = 0;

        if ($prodi && $prodi !== 'initial') {
            // --- PERUBAHAN KECIL DI SINI ---

            // 3. Menyesuaikan pluck dengan nama kolom yang baru
            $prodiMapping = $listprodi->pluck('lib', 'authorised_value')->toArray();
            $namaProdi = $prodiMapping[$prodi] ?? 'Tidak Ditemukan';

            // --- SISA KODE DI BAWAH INI TIDAK PERLU DIUBAH ---

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
                ->whereRaw('LEFT(items.itype, 3) = "BKS"')
                ->whereRaw('LEFT(items.ccode, 1) <> "R"');

            if ($prodi !== 'all') {
                $cnClasses = CnClassHelper::getCnClassByProdi($prodi);
                $query->whereIn('bi.cn_class', $cnClasses);
            }

            if ($tahunTerakhir !== 'all') {
                $query->whereRaw('bi.publicationyear >= YEAR(CURDATE()) - ?', [$tahunTerakhir]);
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

            if ($prodi !== 'all') {
                $cnClasses = CnClassHelper::getCnClassByProdi($prodi);
                $totalQuery->whereIn('bi.cn_class', $cnClasses);
            }

            if ($tahunTerakhir !== 'all') {
                $totalQuery->whereRaw('bi.publicationyear >= YEAR(CURDATE()) - ?', [$tahunTerakhir]);
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

    public function periodikal(Request $request)
    {
        // --- PERUBAHAN DIMULAI DI SINI ---

        // 1. Mengambil data prodi dari tabel authorised_values dengan kategori 'PRODI'
        $listprodi = M_auv::where('category', 'PRODI')->whereRaw('CHAR_LENGTH(lib) >= 13')
            ->orderBy('authorised_value', 'asc')->get();

        // 2. Membuat objek untuk opsi "Semua Program Studi"
        $prodiOptionAll = new \stdClass();
        $prodiOptionAll->authorised_value = 'all'; // Ganti dari 'kode'
        $prodiOptionAll->lib = 'Semua Program Studi';   // Ganti dari 'nama'

        // Tambahkan opsi "Semua" ke awal list
        $listprodi->prepend($prodiOptionAll);

        // --- PERUBAHAN SELESAI DI SINI ---


        $prodi = $request->input('prodi', 'initial');
        $tahunTerakhir = $request->input('tahun', 'all');

        $data = collect();
        $namaProdi = '';
        $dataExists = false;
        $totalJudul = 0;
        $totalEksemplar = 0;

        if ($prodi && $prodi !== 'initial') {
            // --- PERUBAHAN KECIL DI SINI ---

            // 3. Menyesuaikan pluck dengan nama kolom yang baru
            $prodiMapping = $listprodi->pluck('lib', 'authorised_value')->toArray();
            $namaProdi = $prodiMapping[$prodi] ?? 'Tidak Ditemukan';

            // --- SISA KODE DI BAWAH INI TIDAK PERLU DIUBAH ---

            $periodicalTypes = ['JR', 'JRA', 'MJA', 'MJI', 'MJIP', 'MJP'];

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

            if ($prodi !== 'all') {
                $cnClasses = CnClassHelper::getCnClassByProdi($prodi);
                $query->whereIn('bi.cn_class', $cnClasses);
            }

            if ($tahunTerakhir !== 'all') {
                $query->whereRaw('bi.publicationyear >= YEAR(CURDATE()) - ?', [$tahunTerakhir]);
            }

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
                $Tahun_Terbit = $row->Tahun_Terbit;
                if (empty($Tahun_Terbit) || $Tahun_Terbit == '0000') {
                    $row->Tahun_Terbit = 'n.d.';
                }

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
                ->whereIn('items.itype', $periodicalTypes);

            if ($prodi !== 'all') {
                $cnClasses = CnClassHelper::getCnClassByProdi($prodi);
                $totalQuery->whereIn('bi.cn_class', $cnClasses);
            }

            if ($tahunTerakhir !== 'all') {
                $totalQuery->whereRaw('bi.publicationyear >= YEAR(CURDATE()) - ?', [$tahunTerakhir]);
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


    public function referensi(Request $request)
    {
        // --- PERUBAHAN DIMULAI DI SINI ---

        // 1. Mengambil data prodi dari tabel authorised_values dengan kategori 'PRODI'
        $listprodi = M_auv::where('category', 'PRODI')->whereRaw('CHAR_LENGTH(lib) >= 13')
            ->orderBy('authorised_value', 'asc')->get();

        // 2. Membuat objek untuk opsi "Semua Program Studi"
        $prodiOptionAll = new \stdClass();
        $prodiOptionAll->authorised_value = 'all'; // Ganti dari 'kode'
        $prodiOptionAll->lib = 'Semua Program Studi';   // Ganti dari 'nama'

        // Tambahkan opsi "Semua" ke awal list
        $listprodi->prepend($prodiOptionAll);

        // --- PERUBAHAN SELESAI DI SINI ---


        $prodi = $request->input('prodi', 'initial');
        $tahunTerakhir = $request->input('tahun', 'all');

        $data = collect();
        $namaProdi = '';
        $dataExists = false;
        $totalJudul = 0;
        $totalEksemplar = 0;

        if ($prodi && $prodi !== 'initial') {
            // --- PERUBAHAN KECIL DI SINI ---

            // 3. Menyesuaikan pluck dengan nama kolom yang baru
            $prodiMapping = $listprodi->pluck('lib', 'authorised_value')->toArray();
            $namaProdi = $prodiMapping[$prodi] ?? 'Tidak Ditemukan';

            // --- SISA KODE DI BAWAH INI TIDAK PERLU DIUBAH ---

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
            i.homebranch as Lokasi
        ")
                ->from('items as i')
                ->join('biblioitems as bi', 'i.biblionumber', '=', 'bi.biblionumber')
                ->join('biblio as b', 'i.biblionumber', '=', 'b.biblionumber')
                ->join('biblio_metadata as bm', 'b.biblionumber', '=', 'bm.biblionumber')
                ->where('i.itemlost', 0)
                ->where('i.withdrawn', 0)
                ->whereRaw('LEFT(i.itype,3) = "BKS"')
                ->whereRaw('LEFT(i.ccode,1) = "R"');

            if ($prodi !== 'all') {
                $cnClasses = CnClassHelper::getCnClassByProdi($prodi);
                $query->whereIn('bi.cn_class', $cnClasses);
            }

            if ($tahunTerakhir !== 'all') {
                $query->whereRaw('bi.publicationyear >= YEAR(CURDATE()) - ?', [$tahunTerakhir]);
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
                $cnClasses = CnClassHelper::getCnClassByProdi($prodi);
                $totalQuery->whereIn('bi.cn_class', $cnClasses);
            }

            if ($tahunTerakhir !== 'all') {
                $totalQuery->whereRaw('bi.publicationyear >= YEAR(CURDATE()) - ?', [$tahunTerakhir]);
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
            $judulProdi = 'Laporan Koleksi Jurnal - ' . ($namaProdi ?: 'Semua Program Studi');
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
            'Penerbit',
            'Kota Terbit',
            'Tahun Terbit',
            'Eksemplar',
            'Lokasi',
        ];

        $callback = function () use ($data, $headers, $namaProdi, $tahunTerakhir) {
            $file = fopen('php://output', 'w');
            fputs($file, $bom = chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Judul file CSV
            $judulProdi = 'Laporan Koleksi Referensi - ' . ($namaProdi ?: 'Semua Program Studi');
            $judulTahun = ($tahunTerakhir !== 'all') ? ('Tahun Terbit: ' . $tahunTerakhir . ' tahun terakhir') : 'Semua Tahun Terbit';
            fputcsv($file, [$judulProdi . ' - ' . $judulTahun], ';');
            fputcsv($file, [''], ';'); // Baris kosong
            fputcsv($file, $headers, ';');

            $i = 1;
            foreach ($data as $row) {
                $rowData = [
                    $i++,
                    $row->Judul,
                    $row->Pengarang,
                    $row->Penerbit,
                    $row->Kota_Terbit, // <-- Data baru
                    (int) $row->Tahun_Terbit,
                    (int) $row->Eksemplar,
                    $row->Lokasi
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

        // Header sudah benar, tidak perlu diubah
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

            // --- PERBAIKAN 2: Judul laporan disamakan formatnya ---
            $judulProdi = 'Laporan Koleksi Buku Teks - ' . ($namaProdi ?: 'Semua Program Studi');
            $judulTahun = ($tahunTerakhir !== 'all') ? ('Tahun Terbit: ' . $tahunTerakhir . ' tahun terakhir') : 'Semua Tahun Terbit';
            fputcsv($file, [$judulProdi . ' - ' . $judulTahun], ';');
            fputcsv($file, [''], ';'); // Baris kosong
            fputcsv($file, $headers, ';');

            $i = 1;
            // Data per baris sudah benar, tidak perlu diubah
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

        // Header sudah benar, tidak perlu diubah
        $headers = [
            'No',
            'Judul',
            'Pengarang',
            'Kota Terbit',
            'Penerbit',
            'Tahun Terbit',
            'Eksemplar',
        ];

        $callback = function () use ($data, $headers, $namaProdi, $tahunTerakhir) {
            $file = fopen('php://output', 'w');
            fputs($file, $bom = chr(0xEF) . chr(0xBB) . chr(0xBF));

            // --- PERBAIKAN 2: Judul laporan disamakan formatnya ---
            $judulProdi = 'Laporan Koleksi E-Book - ' . ($namaProdi ?: 'Semua Program Studi');
            $judulTahun = ($tahunTerakhir !== 'all') ? ('Tahun Terbit: ' . $tahunTerakhir . ' tahun terakhir') : 'Semua Tahun Terbit';
            fputcsv($file, [$judulProdi . ' - ' . $judulTahun], ';');
            fputcsv($file, [''], ';'); // Baris kosong
            fputcsv($file, $headers, ';');

            $i = 1;
            // Data per baris sudah benar, tidak perlu diubah
            foreach ($data as $row) {
                $rowData = [
                    $i++,
                    $row->Judul,
                    $row->Pengarang,
                    $row->Kota_Terbit,
                    $row->Penerbit,
                    (int) $row->Tahun_Terbit,
                    (int) $row->Eksemplar,
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

            // Penyesuaian minor pada judul laporan
            $judulProdi = 'Laporan Koleksi Prosiding - ' . ($namaProdi ?: 'Semua Program Studi');
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

            // Penyesuaian minor pada judul laporan
            $judulProdi = 'Laporan Koleksi Periodikal - ' . ($namaProdi ?: 'Semua Program Studi');
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
