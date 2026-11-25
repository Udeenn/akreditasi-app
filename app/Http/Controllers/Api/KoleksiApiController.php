<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;
use App\Models\M_Auv;
use App\Models\M_eprodi;
use App\Models\M_items;
use App\Helpers\CnClassHelperr;
use App\Services\CollectionStatisticsService;
use App\Services\ProdiService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class KoleksiApiController extends Controller
{
    public function __construct(
        private CollectionStatisticsService $collectionService,
        private ProdiService $prodiService
    ) {
        ini_set('max_execution_time', 600);
        ini_set('memory_limit', '512M');
    }

    #[OA\Get(
        path: "/api/v1/koleksi/statistik",
        summary: "Get statistics of specific collection type",
        security: [["ApiKeyAuth" => []]],
        tags: ["Koleksi"]
    )]
    #[OA\Parameter(name: "type", in: "query", description: "Tipe koleksi: textbook, ebook, jurnal, ejurnal, prosiding, referensi (default: textbook)", required: false, schema: new OA\Schema(type: "string", default: "textbook"))]
    #[OA\Parameter(name: "prodi", in: "query", description: "Filter Kode Prodi (misal: A510, D100, atau 'all')", required: false, schema: new OA\Schema(type: "string", default: "all"))]
    #[OA\Parameter(name: "tahun", in: "query", description: "Filter rentang tahun penerbitan (misal 3 untuk 3 tahun terakhir, 5, atau 'all')", required: false, schema: new OA\Schema(type: "string", default: "all"))]
    #[OA\Parameter(name: "fakultas", in: "query", description: "Filter Nama Fakultas (misal: FT - Fakultas Teknik atau 'semua')", required: false, schema: new OA\Schema(type: "string", default: "semua"))]
    #[OA\Response(response: 200, description: "Successful operation")]
    #[OA\Response(response: 401, description: "Unauthorized")]
    public function statistik(Request $request): JsonResponse
    {
        $type = $request->input('type', 'textbook'); // textbook, ebook, jurnal, ejurnal, prosiding, referensi
        $tahun = $request->input('tahun', 'all');
        $fakultas = $request->input('fakultas', 'semua');

        $prodi = $request->input('prodi', 'all');

        try {
            $result = $this->collectionService->getCollectionData($type, $prodi, $tahun);

            return response()->json([
                'status'  => 'success',
                'message' => 'Detail statistik koleksi ' . $type,
                'data'    => [
                    'type'           => $type,
                    'prodi'          => $prodi,
                    'tahun'          => $tahun,
                    'totalJudul'     => $result['totalJudul'],
                    'totalEksemplar' => $result['totalEksemplar'],
                    'items'          => $result['processedData'],
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    #[OA\Get(
        path: "/api/v1/koleksi/fakultas",
        summary: "Get Rekap Koleksi Per Fakultas",
        security: [["ApiKeyAuth" => []]],
        tags: ["Koleksi"]
    )]
    #[OA\Parameter(name: "fakultas", in: "query", description: "Filter by Fakultas (default: semua)", required: false, schema: new OA\Schema(type: "string"))]
    #[OA\Parameter(name: "tahun", in: "query", description: "Year filter (e.g., 3 for last 3 years, or 'all')", required: false, schema: new OA\Schema(type: "string", default: "all"))]
    #[OA\Response(response: 200, description: "Successful operation")]
    #[OA\Response(response: 401, description: "Unauthorized")]
    public function fakultas(Request $request): JsonResponse
    {
        $selectedFaculty = $request->input('fakultas');
        $tahunTerakhir = $request->input('tahun', 'all');
        
        try {
            $listprodi = M_Auv::getCachedProdiList();
            $prodiToFacultyMap = $this->prodiService->getProdiToFacultyMap($listprodi);
            $prodiCodeToNameMap = collect($this->prodiService->getFullProdiList());
            
            $faculties = array_unique(array_values($prodiToFacultyMap));
            sort($faculties);
            
            $rekapData = collect();

            if ($selectedFaculty) {
                $cacheKey = "api_rekap_fakultas_v3:" . ($selectedFaculty ?: 'all') . ":" . $tahunTerakhir;
                
                $rekapData = Cache::remember($cacheKey, 3600, function () use ($prodiToFacultyMap, $selectedFaculty, $prodiCodeToNameMap, $tahunTerakhir) {
                    $targetProdiCodes = array_keys($prodiToFacultyMap, $selectedFaculty);
                    $countsPerProdi = $this->collectionService->getRekapFakultasCounts($targetProdiCodes, $tahunTerakhir);

                    $resultData = [];
                    foreach ($countsPerProdi as $prodiCode => $categories) {
                        $prodiName = $prodiCodeToNameMap[$prodiCode] ?? $prodiCode;

                        $row = [
                            'prodi_code' => $prodiCode,
                            'prodi_name' => $prodiName
                        ];

                        foreach ($categories as $catName => $data) {
                            $row[strtolower(str_replace('-', '', $catName)) . '_judul'] = $data['judul'];
                            $row[strtolower(str_replace('-', '', $catName)) . '_eksemplar'] = $data['eksemplar'];
                        }

                        $resultData[] = $row;
                    }

                    return collect($resultData)->sortBy('prodi_name')->values();
                });
            }

            return response()->json([
                'status' => 'success',
                'data' => [
                    'fakultas_list' => $faculties,
                    'rekap_data' => $rekapData
                ],
                'meta' => [
                    'selected_fakultas' => $selectedFaculty,
                    'tahun' => $tahunTerakhir
                ]
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch koleksi fakultas data',
                'debug' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
}
