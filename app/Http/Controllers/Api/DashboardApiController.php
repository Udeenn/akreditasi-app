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
            // Kunjungan Harian - Cache 5 menit
            $kunjunganHarian = Cache::remember('dashboard_kunjungan_harian_' . Carbon::today()->toDateString(), 300, function() {
                return M_vishistory::whereDate('visittime', Carbon::today())->count();
            });

            // Total Judul Buku - Cache 1 jam
            $totalJudulBuku = Cache::remember('dashboard_total_judul_buku', 3600, function() {
                return M_items::on('mysql2')
                    ->select(DB::raw("COUNT(DISTINCT items.biblionumber) AS total_judul_buku"))
                    ->leftJoin('biblioitems', 'biblioitems.biblioitemnumber', '=', 'items.biblioitemnumber')
                    ->leftJoin('biblio', 'biblio.biblionumber', '=', 'items.biblionumber')
                    ->where('items.damaged', 0)
                    ->where('items.itemlost', 0)
                    ->where('items.withdrawn', 0)
                    ->where(DB::raw('LEFT(items.itype, 2)'), 'BK')
                    ->value('total_judul_buku');
            });

            // Total Eksemplar - Cache 1 jam
            $totalEksemplar = Cache::remember('dashboard_total_eksemplar', 3600, function() {
                return M_items::on('mysql2')
                    ->select(DB::raw("COUNT(items.biblionumber) AS total_eksemplar"))
                    ->leftJoin('biblioitems', 'biblioitems.biblioitemnumber', '=', 'items.biblioitemnumber')
                    ->leftJoin('biblio', 'biblio.biblionumber', '=', 'items.biblionumber')
                    ->where('items.damaged', 0)
                    ->where('items.itemlost', 0)
                    ->where('items.withdrawn', 0)
                    ->where(DB::raw('LEFT(items.itype, 2)'), 'BK')
                    ->value('total_eksemplar');
            });

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
            $totalSirkulasi = Cache::remember('dashboard_sirkulasi_harian_' . Carbon::today()->toDateString(), 300, function() {
                $todayStr = Carbon::today()->toDateString();
                
                $issuesToday = M_issues::on('mysql2')
                    ->whereDate('issuedate', $todayStr)
                    ->count();
                    
                $oldIssuesToday = M_oldissues::on('mysql2')
                    ->whereDate('issuedate', $todayStr)
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
