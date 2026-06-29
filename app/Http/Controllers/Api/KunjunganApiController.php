<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;
use App\Services\VisitStatisticsService;
use App\Services\DateFilterService;
use App\Services\ProdiService;
use App\Models\M_Auv;
use Illuminate\Support\Facades\Log;

class KunjunganApiController extends Controller
{
    public function __construct(
        private VisitStatisticsService $visitService,
        private DateFilterService $dateFilterService,
        private ProdiService $prodiService
    ) {}

    #[OA\Get(
        path: "/api/v1/kunjungan/fakultas",
        summary: "Get kunjungan data grouped by Fakultas",
        security: [["ApiKeyAuth" => []]],
        tags: ["Kunjungan"]
    )]
    #[OA\Parameter(name: "fakultas", in: "query", description: "Filter by Fakultas (default: semua)", required: false, schema: new OA\Schema(type: "string"))]
    #[OA\Response(response: 200, description: "Successful operation")]
    #[OA\Response(response: 401, description: "Unauthorized")]
    public function fakultas(Request $request): JsonResponse
    {
        try {
            $filters = $this->dateFilterService->parseFilter($request);
            $selectedFakultas = $request->input('fakultas', 'semua');
            $selectedLokasi   = $request->input('lokasi');

            $result = $this->visitService->getKunjunganFakultas(
                $filters['filterType'], 
                $filters['tanggalAwal'], 
                $filters['tanggalAkhir'], 
                $filters['tahunAwal'], 
                $filters['tahunAkhir'], 
                $selectedFakultas, 
                $selectedLokasi
            );

            return response()->json([
                'status' => 'success',
                'data' => [
                    'table_data' => $result['tableData'],
                    'chart_data' => $result['chartData'],
                    'total' => $result['total']
                ],
                'meta' => [
                    'filters' => $filters,
                    'selected_fakultas' => $selectedFakultas,
                    'selected_lokasi' => $selectedLokasi
                ]
            ]);

        } catch (\Throwable $e) {
            Log::error('API Kunjungan Fakultas Error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch fakultas data'
            ], 500);
        }
    }

    #[OA\Get(
        path: "/api/v1/kunjungan/prodi",
        summary: "Get kunjungan data grouped by Prodi",
        security: [["ApiKeyAuth" => []]],
        tags: ["Kunjungan"]
    )]
    #[OA\Parameter(name: "lokasi", in: "query", description: "Filter by branchcode", required: false, schema: new OA\Schema(type: "string"))]
    #[OA\Response(response: 200, description: "Successful operation")]
    #[OA\Response(response: 401, description: "Unauthorized")]
    public function prodi(Request $request): JsonResponse
    {
        try {
            $filters = $this->dateFilterService->parseFilter($request);
            $selectedLokasi = $request->input('lokasi');

            $result = $this->visitService->getKunjunganProdi(
                $filters['filterType'], 
                $filters['tanggalAwal'], 
                $filters['tanggalAkhir'], 
                $filters['tahunAwal'], 
                $filters['tahunAkhir'], 
                $selectedLokasi
            );

            return response()->json([
                'status' => 'success',
                'data' => [
                    'table_data' => $result['tableData'],
                    'chart_data' => $result['chartData'],
                    'total' => $result['total']
                ],
                'meta' => [
                    'filters' => $filters,
                    'selected_lokasi' => $selectedLokasi
                ]
            ]);

        } catch (\Throwable $e) {
            Log::error('API Kunjungan Prodi Error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch prodi data'
            ], 500);
        }
    }

    #[OA\Get(
        path: "/api/v1/kunjungan/harian",
        summary: "Get kunjungan data daily (Harian)",
        security: [["ApiKeyAuth" => []]],
        tags: ["Kunjungan"]
    )]
    #[OA\Parameter(name: "lokasi", in: "query", description: "Filter by branchcode", required: false, schema: new OA\Schema(type: "string"))]
    #[OA\Response(response: 200, description: "Successful operation")]
    #[OA\Response(response: 401, description: "Unauthorized")]
    public function harian(Request $request): JsonResponse
    {
        try {
            $filters = $this->dateFilterService->parseFilter($request);
            $selectedLokasi = $request->input('lokasi');

            $result = $this->visitService->getKunjunganHarian(
                $filters['filterType'], 
                $filters['tanggalAwal'], 
                $filters['tanggalAkhir'], 
                $filters['tahunAwal'], 
                $filters['tahunAkhir'], 
                $selectedLokasi
            );

            return response()->json([
                'status' => 'success',
                'data' => [
                    'table_data' => $result['tableData'],
                    'chart_data' => $result['chartData'],
                    'total' => $result['total']
                ],
                'meta' => [
                    'filters' => $filters,
                    'selected_lokasi' => $selectedLokasi
                ]
            ]);

        } catch (\Throwable $e) {
            Log::error('API Kunjungan Harian Error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch daily data'
            ], 500);
        }
    }
}
