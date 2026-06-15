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
    public function getCollectionData(string $type, string $prodi, string $tahunTerakhir): array
    {
        $config = $this->getCollectionTypeConfig($type);
        $cacheKey = "stats_collection_generic_{$type}_{$prodi}_{$tahunTerakhir}";

        return Cache::remember($cacheKey, 3600, function () use ($prodi, $tahunTerakhir, $config, $type) {
            // 1. Total Query
            $totalQuery = $this->collectionRepository->getBaseTotalQuery($prodi, $tahunTerakhir);
            $this->collectionRepository->applyConfigFilters($totalQuery, $config, $tahunTerakhir);
            
            $totals = $totalQuery->selectRaw("
                COUNT(DISTINCT items.biblionumber) as total_judul,
                COUNT(items.itemnumber) as total_eksemplar
            ")->first();

            // 2. Main Query
            $query = $this->collectionRepository->getBaseQuery($prodi, $tahunTerakhir);
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
                
                return $row;
            });

            return [
                'processedData' => $processedData,
                'totalJudul' => $processedData->count(),
                'totalEksemplar' => $totals->total_eksemplar ?? 0
            ];
        });
    }
}
