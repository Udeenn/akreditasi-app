<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class RewardApiController extends Controller
{
    private function resolveKategori(?string $cat): ?string
    {
        if (!$cat) return null;
        if (str_starts_with($cat, 'STD')) return 'Mahasiswa';
        if (str_starts_with($cat, 'TC')) return 'Dosen';
        if (str_starts_with($cat, 'STAF') || $cat === 'LIBRARIAN') return 'Tendik';
        return null;
    }

    #[OA\Get(
        path: "/api/v1/reward/pemustaka",
        summary: "Get top pemustaka (visitors) per year",
        security: [["ApiKeyAuth" => []]],
        tags: ["Reward"]
    )]
    #[OA\Parameter(name: "tahun", in: "query", required: false, schema: new OA\Schema(type: "integer"))]
    #[OA\Parameter(name: "kategori", in: "query", description: "Mahasiswa, Dosen, or Tendik", required: false, schema: new OA\Schema(type: "string"))]
    #[OA\Response(response: 200, description: "Successful operation")]
    #[OA\Response(response: 401, description: "Unauthorized")]
    public function pemustaka(Request $request): JsonResponse
    {
        $tahun = max(2000, min(2100, (int) $request->input('tahun', Carbon::now()->year)));
        $kategoriFilter = $request->input('kategori'); // e.g. Mahasiswa, Dosen, Tendik

        try {
            $start = Carbon::createFromDate($tahun, 1, 1)->startOfDay();
            $end   = Carbon::createFromDate($tahun, 12, 31)->endOfDay();

            $sqlVisitor = "
                SELECT cardnumber, SUM(total) as total_kunjungan
                FROM (
                    SELECT cardnumber, COUNT(*) as total
                    FROM visitorhistory
                    WHERE visittime BETWEEN ? AND ?
                    GROUP BY cardnumber
                    UNION ALL
                    SELECT cardnumber, COUNT(*) as total
                    FROM visitorcorner
                    WHERE visittime BETWEEN ? AND ?
                    GROUP BY cardnumber
                ) as gabungan
                GROUP BY cardnumber
                ORDER BY total_kunjungan DESC
                LIMIT 25000
            ";

            $rawVisits = DB::connection('mysql')->select($sqlVisitor, [$start, $end, $start, $end]);
            $cardNumbers = collect($rawVisits)->pluck('cardnumber')->map(fn($c) => trim(strtolower($c)))->unique()->values();

            $borrowers = collect();
            foreach ($cardNumbers->chunk(1000) as $chunk) {
                $rows = DB::connection('mysql2')->table('borrowers')
                    ->select('cardnumber', 'surname', 'firstname', 'categorycode')
                    ->whereIn('cardnumber', $chunk->all())
                    ->get();
                foreach ($rows as $item) {
                    $borrowers->put(trim(strtolower($item->cardnumber)), $item);
                }
            }

            $pengunjungData = collect();
            foreach ($rawVisits as $visit) {
                $key = trim(strtolower($visit->cardnumber));
                $user = $borrowers->get($key);

                if (!$user) continue;

                $kategori = $this->resolveKategori($user->categorycode);

                if ($kategoriFilter && $kategori !== $kategoriFilter) continue;

                if ($kategori) {
                    $pengunjungData->push([
                        'kategori'   => $kategori,
                        'cardnumber' => $user->cardnumber,
                        'nama'       => trim($user->firstname . ' ' . $user->surname),
                        'jumlah'     => (int)$visit->total_kunjungan
                    ]);
                }
            }

            // Top 10 per category
            $pengunjungTeraktif = $pengunjungData->groupBy('kategori')->map(function ($items) {
                return $items->sortByDesc('jumlah')->take(10)->values();
            });

            return response()->json([
                'status' => 'success',
                'data' => $pengunjungTeraktif,
                'meta' => [
                    'tahun' => $tahun,
                    'kategori_filter' => $kategoriFilter
                ]
            ]);

        } catch (\Throwable $e) {
            Log::error('API Pemustaka Error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch pemustaka data'
            ], 500);
        }
    }

    #[OA\Get(
        path: "/api/v1/reward/peminjam",
        summary: "Get top peminjam (borrowers) per year",
        security: [["ApiKeyAuth" => []]],
        tags: ["Reward"]
    )]
    #[OA\Parameter(name: "tahun", in: "query", required: false, schema: new OA\Schema(type: "integer"))]
    #[OA\Parameter(name: "kategori", in: "query", description: "Mahasiswa, Dosen, or Tendik", required: false, schema: new OA\Schema(type: "string"))]
    #[OA\Response(response: 200, description: "Successful operation")]
    #[OA\Response(response: 401, description: "Unauthorized")]
    public function peminjam(Request $request): JsonResponse
    {
        $tahun = max(2000, min(2100, (int) $request->input('tahun', Carbon::now()->year)));
        $kategoriFilter = $request->input('kategori');

        try {
            $start = Carbon::createFromDate($tahun, 1, 1)->startOfDay();
            $end   = Carbon::createFromDate($tahun, 12, 31)->endOfDay();

            $sqlPeminjam = "
                SELECT borrowernumber, COUNT(*) as total_pinjam
                FROM statistics
                WHERE type IN ('issue', 'renew')
                AND datetime BETWEEN ? AND ?
                GROUP BY borrowernumber
                ORDER BY total_pinjam DESC
                LIMIT 25000
            ";
            
            $rawPeminjaman = DB::connection('mysql2')->select($sqlPeminjam, [$start, $end]);
            $borrowerNumbers = collect($rawPeminjaman)->pluck('borrowernumber')->filter()->unique()->values();

            $borrowers = collect();
            foreach ($borrowerNumbers->chunk(1000) as $chunk) {
                $rows = DB::connection('mysql2')->table('borrowers')
                    ->select('borrowernumber', 'cardnumber', 'surname', 'firstname', 'categorycode')
                    ->whereIn('borrowernumber', $chunk->all())
                    ->get();
                foreach ($rows as $item) {
                    $borrowers->put($item->borrowernumber, $item);
                }
            }

            $peminjamData = collect();
            foreach ($rawPeminjaman as $row) {
                $user = $borrowers->get($row->borrowernumber);
                if (!$user) continue;

                $kategori = $this->resolveKategori($user->categorycode);

                if ($kategoriFilter && $kategori !== $kategoriFilter) continue;

                if ($kategori) {
                    $peminjamData->push([
                        'kategori'   => $kategori,
                        'cardnumber' => $user->cardnumber,
                        'nama'       => trim($user->firstname . ' ' . $user->surname),
                        'jumlah'     => (int)$row->total_pinjam
                    ]);
                }
            }

            $peminjamTeraktif = $peminjamData->groupBy('kategori')->map(function ($items) {
                return $items->sortByDesc('jumlah')->take(10)->values();
            });

            return response()->json([
                'status' => 'success',
                'data' => $peminjamTeraktif,
                'meta' => [
                    'tahun' => $tahun,
                    'kategori_filter' => $kategoriFilter
                ]
            ]);

        } catch (\Throwable $e) {
            Log::error('API Peminjam Error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch peminjam data'
            ], 500);
        }
    }
}
