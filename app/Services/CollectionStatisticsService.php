<?php

namespace App\Services;

use App\Models\M_items;
use App\Helpers\CnClassHelperr;
use App\Helpers\QueryHelper;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class CollectionStatisticsService
{
    public function __construct(
        private \App\Repositories\CollectionRepository $collectionRepository
    ) {}

    /**
     * Helper mapping untuk kriteria tipe koleksi.
     */
    public function getCollectionTypeConfig(string $type): array
    {
        $configs = [
            'prosiding' => [
                'itypes' => ['EPR', 'PR'],
                'isCcodeR' => false,
                'isNotCcodeR' => false,
                'isBarcodeJE' => false,
                'isNotBarcodeJE' => false,
                'enumchronEndWithYear' => false,
                'name' => 'Prosiding',
            ],
            'jurnal' => [
                'itypes' => ['JR', 'JRA', 'JRT', 'EJ'],
                'isCcodeR' => false,
                'isNotCcodeR' => false,
                'isBarcodeJE' => false,
                'isNotBarcodeJE' => true,
                'enumchronEndWithYear' => true,
                'name' => 'Jurnal',
            ],
            'ejurnal' => [
                'itypes' => ['JR', 'JRA', 'JRT', 'EJ'],
                'isCcodeR' => false,
                'isNotCcodeR' => false,
                'isBarcodeJE' => true,
                'isNotBarcodeJE' => false,
                'enumchronEndWithYear' => true,
                'name' => 'E-Jurnal',
            ],
            'ebook' => [
                'itypes' => ['EB'],
                'isCcodeR' => false,
                'isNotCcodeR' => false,
                'isBarcodeJE' => false,
                'isNotBarcodeJE' => false,
                'enumchronEndWithYear' => false,
                'name' => 'E-Book',
            ],
            'textbook' => [
                'itypes' => ['BKS', 'BKSA', 'BKSCA', 'BKSC'],
                'isCcodeR' => false,
                'isNotCcodeR' => true,
                'isBarcodeJE' => false,
                'isNotBarcodeJE' => false,
                'enumchronEndWithYear' => false,
                'name' => 'Textbook',
            ],
            'periodikal' => [
                'itypes' => ['BKS', 'BKSA', 'BKSCA', 'BKSC', 'BP', 'PBP', 'PR', 'EPR', 'KAS', 'MU', 'TAB'],
                'isCcodeR' => true,
                'isNotCcodeR' => false,
                'isBarcodeJE' => false,
                'isNotBarcodeJE' => false,
                'enumchronEndWithYear' => false,
                'name' => 'Periodikal',
            ],
            'referensi' => [
                'itypes' => ['BKS', 'BKSA', 'BKSCA', 'BKSC', 'KAS', 'MU'],
                'isCcodeR' => true,
                'isNotCcodeR' => false,
                'isBarcodeJE' => false,
                'isNotBarcodeJE' => false,
                'enumchronEndWithYear' => false,
                'name' => 'Referensi',
            ],
        ];

        return $configs[$type] ?? $configs['textbook'];
    }

    /**
     * Ambil data koleksi secara generic berdasarkan tipe
     */
    public function getCollectionData(string $type, string $prodi, string $tahunTerakhir, string $withdrawn = '0'): array
    {
        $withdrawn = in_array($withdrawn, ['0', '1', 'all'], true) ? $withdrawn : '0';
        $config = $this->getCollectionTypeConfig($type);
        $cacheKey = "stats_collection_generic_v5_{$type}_{$prodi}_{$tahunTerakhir}_w{$withdrawn}";

        return Cache::remember($cacheKey, 3600, function () use ($prodi, $tahunTerakhir, $withdrawn, $config, $type) {
            // Single Main Query (total judul & eksemplar dihitung dari hasil agregasi agar tidak scan tabel 2x)
            $query = $this->collectionRepository->getBaseQuery($prodi, $tahunTerakhir, $withdrawn);
            $this->collectionRepository->applyConfigFilters($query, $config, $tahunTerakhir);

            // Special join needed for some types like periodikal
            if (in_array($type, ['jurnal', 'ejurnal', 'periodikal', 'referensi'])) {
                $query->leftJoin('itemtypes as it', 'it.itemtype', '=', 'items.itype')
                    ->leftJoin('authorised_values as av', function ($join) {
                        $join->on('av.authorised_value', '=', 'items.ccode')
                            ->where('av.category', '=', 'CCODE');
                    });
            }

            $query->selectRaw($this->collectionRepository->getSelectRawString($type));

            // Grouping logic depends on the type (if serial/periodical or book)
            if ($config['enumchronEndWithYear'] || in_array($type, ['periodikal', 'prosiding'])) {
                $query->groupBy('items.biblionumber', 'items.enumchron');
            } else {
                $query->groupBy('items.biblionumber');
            }

            // Ordering
            if ($config['enumchronEndWithYear'] || in_array($type, ['jurnal', 'ejurnal'])) {
                $query->orderBy('b.title', 'asc')
                      ->orderBy('TahunTerbit', 'desc');
            } else {
                $query->orderBy('TahunTerbit', 'desc');
            }

            $processedData = $query->get()->map(function ($row) use ($type) {
                // Combine Title fields
                $fullJudul = $row->Judul_a;
                if (!empty($row->Judul_b)) $fullJudul .= ' : ' . $row->Judul_b;
                if (!empty($row->Judul_c)) $fullJudul .= ' / ' . $row->Judul_c;
                
                $row->Judul = html_entity_decode($fullJudul, ENT_QUOTES, 'UTF-8');
                $row->Kota_Terbit = html_entity_decode($row->Kota_Terbit ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $row->Penerbit = html_entity_decode($row->Penerbit ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $row->Pengarang = html_entity_decode($row->Pengarang ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $row->Tahun_Terbit = $row->TahunTerbit ?? '-';

                foreach (['Link_Ejurnal', 'Link_Ebook', 'Link_Prosiding'] as $linkField) {
                    if (isset($row->{$linkField})) {
                        $url = trim((string) $row->{$linkField});
                        $row->{$linkField} = preg_match('#^https?://#i', $url) ? $url : '';
                    }
                }

                return $row;
            });

            $isSerialGrouped = $config['enumchronEndWithYear'] || in_array($type, ['periodikal', 'prosiding']);

            return [
                'processedData' => $processedData,
                'totalJudul' => $isSerialGrouped
                    ? $processedData->pluck('biblionumber')->unique()->count()
                    : $processedData->count(),
                'totalEksemplar' => (int) $processedData->sum('Eksemplar'),
            ];
        });
    }

    /**
     * Hitung rekap judul & eksemplar seluruh kategori per prodi dalam 2 query gabungan
     * (menggantikan 6 scan terpisah yang sebelumnya duplikat di Web & API controller).
     */
    public function getRekapFakultasCounts(array $targetProdiCodes, string $tahunTerakhir = 'all'): array
    {
        $combinedRules = [];
        foreach ($targetProdiCodes as $prodiCode) {
            $rules = CnClassHelperr::getCnClassByProdi($prodiCode);
            if ($rules) {
                $combinedRules = array_merge($combinedRules, $rules);
            }
        }
        $combinedRules = array_unique($combinedRules, SORT_REGULAR);

        $countsPerProdi = [];
        foreach ($targetProdiCodes as $prodiCode) {
            $countsPerProdi[$prodiCode] = [
                'Jurnal'    => ['judul' => [], 'eksemplar' => 0],
                'E-Jurnal'  => ['judul' => [], 'eksemplar' => 0],
                'Textbook'  => ['judul' => [], 'eksemplar' => 0],
                'E-Book'    => ['judul' => [], 'eksemplar' => 0],
                'Prosiding' => ['judul' => [], 'eksemplar' => 0],
                'Referensi' => ['judul' => [], 'eksemplar' => 0],
            ];
        }

        $matchedProdisCache = [];
        $accumulate = function (string $category, $key, string $actualClass) use (&$countsPerProdi, &$matchedProdisCache, $targetProdiCodes) {
            if (!isset($matchedProdisCache[$actualClass])) {
                $matched = [];
                foreach ($targetProdiCodes as $prodiCode) {
                    if (CnClassHelperr::isValidCnClass($prodiCode, $actualClass)) {
                        $matched[] = $prodiCode;
                    }
                }
                $matchedProdisCache[$actualClass] = $matched;
            }

            foreach ($matchedProdisCache[$actualClass] as $prodiCode) {
                $countsPerProdi[$prodiCode][$category]['judul'][$key] = true;
                $countsPerProdi[$prodiCode][$category]['eksemplar']++;
            }
        };

        $buildBaseQuery = function (array $itypes, bool $joinMetadata) use ($combinedRules) {
            $q = M_items::query()
                ->from('items')
                ->join('biblioitems as bi', 'items.biblionumber', '=', 'bi.biblionumber')
                ->where('items.itemlost', 0)
                ->where('items.withdrawn', 0)
                ->whereIn('items.itype', $itypes);

            if ($joinMetadata) {
                $q->join('biblio_metadata as bm', 'bm.biblionumber', '=', 'items.biblionumber');
            }
            if (!empty($combinedRules)) {
                QueryHelper::applyCnClassRules($q, $combinedRules);
            }
            return $q;
        };

        // 1. Jurnal & E-Jurnal (1 query gabungan)
        $qSerial = $buildBaseQuery(['JR', 'JRA', 'JRT', 'EJ'], false)
            ->select('items.biblionumber', 'items.enumchron', 'items.barcode', 'bi.cn_class', 'items.itemcallnumber')
            ->whereRaw("TRIM(items.enumchron) REGEXP '[0-9]{4}$'");

        if ($tahunTerakhir !== 'all') {
            $qSerial->whereRaw('RIGHT(items.enumchron, 4) >= ?', [date('Y') - (int) $tahunTerakhir]);
        }

        foreach ($qSerial->cursor() as $row) {
            if ($row->barcode === null) continue;
            $actualClass = !empty($row->cn_class) ? $row->cn_class : $row->itemcallnumber;
            if (empty($actualClass)) continue;

            $category = str_starts_with(strtoupper((string) $row->barcode), 'JE') ? 'E-Jurnal' : 'Jurnal';
            $accumulate($category, $row->biblionumber . '_' . $row->enumchron, $actualClass);
        }

        // 2. Textbook, Referensi, E-Book & Prosiding (1 query gabungan; join biblio_metadata hanya bila perlu filter tahun)
        $needYearFilter = ($tahunTerakhir !== 'all');
        $qOther = $buildBaseQuery(['BKS', 'BKSA', 'BKSCA', 'BKSC', 'EB', 'PR', 'EPR'], $needYearFilter)
            ->select('items.biblionumber', 'items.enumchron', 'items.itype', 'items.ccode', 'bi.cn_class', 'items.itemcallnumber');

        if ($needYearFilter) {
            $qOther->whereRaw('CAST(ExtractValue(bm.metadata, \'//datafield[@tag="260"]/subfield[@code="c"]\') AS UNSIGNED) >= YEAR(CURDATE()) - ?', [(int) $tahunTerakhir]);
        }

        foreach ($qOther->cursor() as $row) {
            $actualClass = !empty($row->cn_class) ? $row->cn_class : $row->itemcallnumber;
            if (empty($actualClass)) continue;

            $itype = strtoupper((string) $row->itype);
            if ($itype === 'EB') {
                $accumulate('E-Book', $row->biblionumber, $actualClass);
            } elseif ($itype === 'PR' || $itype === 'EPR') {
                $accumulate('Prosiding', $row->biblionumber . '_' . $row->enumchron, $actualClass);
            } else {
                if ($row->ccode === null) continue;
                $category = str_starts_with(strtoupper((string) $row->ccode), 'R') ? 'Referensi' : 'Textbook';
                $accumulate($category, $row->biblionumber, $actualClass);
            }
        }

        $result = [];
        foreach ($countsPerProdi as $prodiCode => $categories) {
            foreach ($categories as $cat => $data) {
                $result[$prodiCode][$cat] = [
                    'judul'     => count($data['judul']),
                    'eksemplar' => $data['eksemplar'],
                ];
            }
        }

        return $result;
    }
}
