<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\M_biblio;
use App\Models\M_eprodi;
use App\Models\M_items;
use App\Models\M_viscorner;
use App\Models\M_vishistory;
use App\Models\M_issues;
use App\Models\M_oldissues;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class DashboardApiController extends Controller
{
    #[OA\Get(
        path: "/api/v1/dashboard",
        summary: "Get dashboard summary statistics",
        security: [["ApiKeyAuth" => []]],
        tags: ["Dashboard"]
    )]
    #[OA\Response(response: 200, description: "Successful operation")]
    #[OA\Response(response: 401, description: "Unauthorized")]
    public function index(): JsonResponse
    {
        try {
            $todayStart = Carbon::today()->startOfDay();
            $todayEnd   = Carbon::today()->endOfDay();

            // Kunjungan Harian - Cache 5 menit
            $kunjunganHarian = Cache::remember('dashboard_kunjungan_harian_' . Carbon::today()->toDateString(), 300, function() use ($todayStart, $todayEnd) {
                return M_vishistory::whereBetween('visittime', [$todayStart, $todayEnd])->count();
            });

            // Total Judul & Eksemplar Buku (1 query tanpa unused joins) - Cache 1 jam
            $bkTotals = Cache::remember('dashboard_api_bk_totals_v1', 3600, function() {
                $row = M_items::on('mysql2')
                    ->selectRaw("
                        COUNT(DISTINCT items.biblionumber) AS total_judul_buku,
                        COUNT(items.biblionumber) AS total_eksemplar
                    ")
                    ->where('items.damaged', 0)
                    ->where('items.itemlost', 0)
                    ->where('items.withdrawn', 0)
                    ->where('items.itype', 'LIKE', 'BK%')
                    ->first();

                return [
                    'judul'     => (int) ($row->total_judul_buku ?? 0),
                    'eksemplar' => (int) ($row->total_eksemplar ?? 0),
                ];
            });
            $totalJudulBuku = $bkTotals['judul'];
            $totalEksemplar = $bkTotals['eksemplar'];

            // Anggota Aktif - Cache 1 jam
            $totalAnggotaAktif = Cache::remember('dashboard_total_anggota', 3600, function() {
                return DB::connection('mysql2')->table('borrowers')
                    ->where('dateenrolled', '<=', Carbon::today()->toDateString())
                    ->where('dateexpiry', '>=', Carbon::today()->toDateString())
                    ->count();
            });

            // Peminjaman Berlangsung - Cache 15 menit
            $totalPeminjaman = Cache::remember('dashboard_total_peminjaman', 900, function() {
                return M_issues::on('mysql2')->count();
            });

            // Peminjaman Hari Ini - Cache 5 menit
            $totalSirkulasi = Cache::remember('dashboard_sirkulasi_harian_' . Carbon::today()->toDateString(), 300, function() use ($todayStart, $todayEnd) {
                $issuesToday = M_issues::on('mysql2')
                    ->whereBetween('issuedate', [$todayStart, $todayEnd])
                    ->count();

                $oldIssuesToday = M_oldissues::on('mysql2')
                    ->whereBetween('issuedate', [$todayStart, $todayEnd])
                    ->count();

                return $issuesToday + $oldIssuesToday;
            });

            return response()->json([
                'status' => 'success',
                'data' => [
                    'kunjungan_hari_ini' => $kunjunganHarian,
                    'total_judul_buku' => $totalJudulBuku,
                    'total_eksemplar' => $totalEksemplar,
                    'anggota_aktif' => $totalAnggotaAktif,
                    'peminjaman_berlangsung' => $totalPeminjaman,
                    'peminjaman_hari_ini' => $totalSirkulasi
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch dashboard data',
                'debug' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
}
