<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;
use App\Services\BorrowingStatisticsService;
use App\Services\DateFilterService;
use App\Services\ProdiService;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class PeminjamanApiController extends Controller
{
    public function __construct(
        private BorrowingStatisticsService $borrowingService,
        private DateFilterService $dateFilterService,
        private ProdiService $prodiService
    ) {}

    #[OA\Get(
        path: "/api/v1/peminjaman/keseluruhan",
        summary: "Get statistics of total borrowings (keseluruhan)",
        security: [["ApiKeyAuth" => []]],
        tags: ["Peminjaman"]
    )]
    #[OA\Parameter(name: "filter_type", in: "query", description: "daily or monthly", required: false, schema: new OA\Schema(type: "string", default: "daily"))]
    #[OA\Parameter(name: "start_date", in: "query", required: false, schema: new OA\Schema(type: "string"))]
    #[OA\Parameter(name: "end_date", in: "query", required: false, schema: new OA\Schema(type: "string"))]
    #[OA\Response(response: 200, description: "Successful operation")]
    #[OA\Response(response: 401, description: "Unauthorized")]
    public function keseluruhan(Request $request): JsonResponse
    {
        $filterType = $request->input('filter_type', 'daily');
        $startDate = $request->input('start_date', Carbon::now()->subDays(30)->format('Y-m-d'));
        $endDate   = $request->input('end_date', Carbon::now()->format('Y-m-d'));
        $startYear = $request->input('start_year', Carbon::now()->year);
        $endYear   = $request->input('end_year', Carbon::now()->year);

        try {
            if ($filterType == 'daily') {
                $start = Carbon::parse($startDate)->startOfDay();
                $end   = Carbon::parse($endDate)->endOfDay();
            } else {
                if ($startYear > $endYear) {
                    [$startYear, $endYear] = [$endYear, $startYear];
                }
                $start = Carbon::createFromDate($startYear, 1, 1)->startOfDay();
                $end   = Carbon::createFromDate($endYear, 12, 31)->endOfDay();
            }

            if ($start->greaterThan($end)) {
                [$start, $end] = [$end, $start];
            }

            // Summary (Total)
            $summaryData = DB::connection('mysql2')->table('statistics as s')
                ->select(
                    DB::raw('COUNT(CASE WHEN s.type IN ("issue", "renew") THEN 1 END) as total_books'),
                    DB::raw('COUNT(CASE WHEN s.type = "return" THEN 1 END) as total_returns'),
                    DB::raw('COUNT(DISTINCT CASE WHEN s.type IN ("issue", "renew") THEN s.borrowernumber END) as total_borrowers')
                )
                ->whereBetween('s.datetime', [$start, $end])
                ->first();

            $totalBooks = $summaryData->total_books ?? 0;
            $totalReturns = $summaryData->total_returns ?? 0;
            $totalBorrowers = $summaryData->total_borrowers ?? 0;
            $totalCirculation = $totalBooks + $totalReturns;

            // Chart Data Query
            $query = DB::connection('mysql2')->table('statistics as s')
                ->whereIn('s.type', ['issue', 'renew', 'return'])
                ->whereBetween('s.datetime', [$start, $end]);

            if ($filterType == 'daily') {
                $query->select(
                    DB::raw('DATE(s.datetime) as periode'),
                    DB::raw('SUM(CASE WHEN s.type = "issue" THEN 1 ELSE 0 END) as jumlah_issue'),
                    DB::raw('SUM(CASE WHEN s.type = "renew" THEN 1 ELSE 0 END) as jumlah_renew'),
                    DB::raw('SUM(CASE WHEN s.type = "return" THEN 1 ELSE 0 END) as jumlah_pengembalian'),
                    DB::raw('COUNT(*) as total_sirkulasi')
                )->groupBy(DB::raw('DATE(s.datetime)'));
            } else {
                $query->select(
                    DB::raw('MONTH(s.datetime) as periode_bulan'),
                    DB::raw('YEAR(s.datetime) as periode_tahun'),
                    DB::raw('SUM(CASE WHEN s.type = "issue" THEN 1 ELSE 0 END) as jumlah_issue'),
                    DB::raw('SUM(CASE WHEN s.type = "renew" THEN 1 ELSE 0 END) as jumlah_renew'),
                    DB::raw('SUM(CASE WHEN s.type = "return" THEN 1 ELSE 0 END) as jumlah_pengembalian'),
                    DB::raw('COUNT(*) as total_sirkulasi')
                )->groupBy(DB::raw('YEAR(s.datetime), MONTH(s.datetime)'));
            }

            $chartData = $query->orderBy('periode' . ($filterType == 'monthly' ? '_tahun' : ''), 'asc')
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => [
                    'summary' => [
                        'total_peminjaman' => $totalBooks,
                        'total_pengembalian' => $totalReturns,
                        'total_peminjam' => $totalBorrowers,
                        'total_sirkulasi' => $totalCirculation
                    ],
                    'chart_data' => $chartData
                ],
                'meta' => [
                    'filter_type' => $filterType,
                    'start_date' => $start->format('Y-m-d'),
                    'end_date' => $end->format('Y-m-d')
                ]
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch peminjaman keseluruhan data',
                'debug' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    #[OA\Get(
        path: "/api/v1/peminjaman/berlangsung",
        summary: "Get ongoing borrowing data (Berlangsung)",
        security: [["ApiKeyAuth" => []]],
        tags: ["Peminjaman"]
    )]
    #[OA\Parameter(name: "limit", in: "query", description: "Limit data returned", required: false, schema: new OA\Schema(type: "integer", default: 100))]
    #[OA\Response(response: 200, description: "Successful operation")]
    #[OA\Response(response: 401, description: "Unauthorized")]
    public function berlangsung(Request $request): JsonResponse
    {
        try {
            $limit = $request->input('limit', 100);
            
            // Get all current issues
            $issues = DB::connection('mysql2')->table('issues')
                ->select(
                    'issues.issue_id',
                    'issues.borrowernumber',
                    'issues.itemnumber',
                    'issues.date_due',
                    'issues.branchcode',
                    'issues.issuedate',
                    'issues.returndate',
                    'issues.lastreneweddate',
                    'issues.renewals',
                    'issues.auto_renew',
                    'issues.timestamp'
                )
                ->limit($limit)
                ->get();
                
            return response()->json([
                'status' => 'success',
                'data' => [
                    'total_berlangsung' => $issues->count(),
                    'items' => $issues
                ]
            ]);
            
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch peminjaman berlangsung data',
                'debug' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
}
