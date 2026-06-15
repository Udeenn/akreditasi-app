<?php

namespace App\Repositories;

use App\Models\M_items;
use App\Helpers\CnClassHelperr;
use App\Helpers\QueryHelper;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class CollectionRepository
{
    /**
     * Get Base Collection Query
     */
    public function getBaseQuery(string $prodi, string $tahunTerakhir = 'all'): Builder
    {
        $query = M_items::query()
            ->from('items')
            ->join('biblioitems as bi', 'items.biblionumber', '=', 'bi.biblionumber')
            ->join('biblio as b', 'b.biblionumber', '=', 'items.biblionumber')
            ->join('biblio_metadata as bm', 'bm.biblionumber', '=', 'items.biblionumber')
            ->where('items.itemlost', 0)
            ->where('items.withdrawn', 0);

        if ($prodi !== 'all') {
            $cnClasses = CnClassHelperr::getCnClassByProdi($prodi);
            QueryHelper::applyCnClassRules($query, $cnClasses);
        }

        return $query;
    }

    /**
     * Get Base Collection Query WITHOUT metadata for fast counting.
     */
    public function getBaseTotalQuery(string $prodi, string $tahunTerakhir = 'all'): Builder
    {
        $query = M_items::query()
            ->from('items')
            ->join('biblioitems as bi', 'items.biblionumber', '=', 'bi.biblionumber')
            ->join('biblio as b', 'b.biblionumber', '=', 'items.biblionumber')
            ->join('biblio_metadata as bm', 'bm.biblionumber', '=', 'items.biblionumber')
            ->where('items.itemlost', 0)
            ->where('items.withdrawn', 0);

        if ($prodi !== 'all') {
            $cnClasses = CnClassHelperr::getCnClassByProdi($prodi);
            QueryHelper::applyCnClassRules($query, $cnClasses);
        }

        return $query;
    }

    public function applyConfigFilters(Builder &$query, array $config, string $tahunTerakhir)
    {
        $query->whereIn('items.itype', $config['itypes']);

        if ($config['isCcodeR']) {
            $query->whereRaw('LEFT(items.ccode, 1) = ?', ['R']);
        }
        if ($config['isNotCcodeR']) {
            $query->whereRaw('LEFT(items.ccode, 1) <> "R"');
        }
        if ($config['isBarcodeJE']) {
            $query->where('items.barcode', 'like', 'JE%');
        }
        if ($config['isNotBarcodeJE']) {
            $query->where('items.barcode', 'not like', 'JE%');
        }
        if ($config['enumchronEndWithYear']) {
            $query->whereRaw("TRIM(items.enumchron) REGEXP '[0-9]{4}$'");
        }

        if ($tahunTerakhir !== 'all') {
            if ($config['enumchronEndWithYear']) {
                $query->whereRaw('RIGHT(items.enumchron, 4) >= ?', [date('Y') - (int)$tahunTerakhir]);
            } else {
                $query->whereRaw('CAST(ExtractValue(bm.metadata, \'//datafield[@tag="260"]/subfield[@code="c"]\') AS UNSIGNED) >= YEAR(CURDATE()) - ?', [(int)$tahunTerakhir]);
            }
        }
    }

    public function getSelectRawString(string $type): string
    {
        $base = "
            MAX(bi.cn_class) as Kelas,
            MAX(b.title) as Judul_a,
            MAX(EXTRACTVALUE(bm.metadata,'//datafield[@tag=\"245\"]/subfield[@code=\"b\"]')) as Judul_b,
            MAX(EXTRACTVALUE(bm.metadata,'//datafield[@tag=\"245\"]/subfield[@code=\"c\"]')) as Judul_c,
            MAX(b.author) as Pengarang,
            MAX(CONCAT(COALESCE(bi.publishercode,''), ' ', COALESCE(bi.place,''))) AS Penerbit,
            MAX(CASE WHEN EXTRACTVALUE(bm.metadata,'//datafield[@tag=\"260\"]/subfield[@code=\"c\"]') REGEXP '[0-9]{4}' THEN EXTRACTVALUE(bm.metadata,'//datafield[@tag=\"260\"]/subfield[@code=\"c\"]') ELSE bi.publicationyear END) AS TahunTerbit,
            COUNT(items.itemnumber) AS Eksemplar,
            GROUP_CONCAT(DISTINCT CASE
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
            END SEPARATOR ', ') AS Lokasi
        ";

        if (in_array($type, ['prosiding', 'jurnal', 'ejurnal', 'periodikal'])) {
            $base .= ", items.enumchron AS Nomor, COUNT(DISTINCT items.itemnumber) AS Issue";
        }

        if (in_array($type, ['periodikal', 'referensi'])) {
            $base .= ", MAX(it.description) as Jenis_Koleksi, MAX(av.lib) as Koleksi";
        }
        
        if (in_array($type, ['ebook'])) {
            $base .= ", MAX(EXTRACTVALUE(bm.metadata,'//datafield[@tag=\"856\"]/subfield[@code=\"u\"]')) AS Link_Ebook";
        }
        
        if (in_array($type, ['prosiding'])) {
            $base .= ", MAX(IF(
                EXTRACTVALUE(bm.metadata,'//datafield[@tag=\"856\"]/subfield[@code=\"a\"]') <> '',
                EXTRACTVALUE(bm.metadata,'//datafield[@tag=\"856\"]/subfield[@code=\"a\"]'),
                EXTRACTVALUE(bm.metadata,'//datafield[@tag=\"856\"]/subfield[@code=\"u\"]')
            )) AS Link_Prosiding";
        }

        return $base;
    }
}
