<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ActivityLogController extends Controller
{
    /**
     * Tampilkan halaman Audit Trail (khusus librarian).
     */
    public function index(Request $request)
    {
        [$query, $dateFrom, $dateTo] = $this->buildQuery($request);

        // ── Paginate ──────────────────────────────────────────────
        $logs = $query->paginate(50)->withQueryString();

        // ── Summary stats (untuk hari yang difilter) ──────────────
        $statsQuery = ActivityLog::whereBetween('created_at', [
            $dateFrom . ' 00:00:00',
            $dateTo   . ' 23:59:59',
        ]);

        $stats = [
            'total'      => (clone $statsQuery)->count(),
            'users'      => (clone $statsQuery)->distinct('username')->count('username'),
            'librarians' => (clone $statsQuery)->where('user_role', 'librarian')->count(),
            'patrons'    => (clone $statsQuery)->where('user_role', 'patron')->count(),
        ];

        // ── Top 5 halaman paling banyak dikunjungi ────────────────
        $topPages = (clone $statsQuery)
            ->selectRaw('route_name, url, COUNT(*) as hits')
            ->whereNotNull('route_name')
            ->groupBy('route_name', 'url')
            ->orderByDesc('hits')
            ->limit(5)
            ->get();

        return view('pages.activity-log.index', compact(
            'logs', 'stats', 'topPages', 'dateFrom', 'dateTo'
        ));
    }

    /**
     * Export log sebagai file CSV (streaming, aman untuk data besar).
     */
    public function export(Request $request): StreamedResponse
    {
        [$query, $dateFrom, $dateTo] = $this->buildQuery($request);

        $filename = 'audit-trail_' . $dateFrom . '_sd_' . $dateTo . '.csv';

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');

            // BOM agar Excel bisa buka UTF-8 dengan benar
            fputs($out, "\xEF\xBB\xBF");

            // Header kolom
            fputcsv($out, [
                'No', 'Waktu', 'Username', 'Nama', 'Role',
                'Metode', 'Route', 'URL', 'IP Address',
                'Status Code', 'User Agent',
            ]);

            // Stream data tanpa load semua ke memori
            $no = 1;
            $query->chunk(200, function ($rows) use ($out, &$no) {
                foreach ($rows as $log) {
                    fputcsv($out, [
                        $no++,
                        $log->created_at->format('d/m/Y H:i:s'),
                        $log->username     ?? '-',
                        $log->user_name    ?? '-',
                        $log->user_role    ?? '-',
                        $log->method,
                        $log->route_name   ?? '-',
                        $log->url,
                        $log->ip_address   ?? '-',
                        $log->status_code  ?? '-',
                        $log->user_agent   ?? '-',
                    ]);
                }
            });

            fclose($out);
        }, $filename, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    // ── Helpers ───────────────────────────────────────────────────────

    /**
     * Bangun query berdasarkan filter request — dipakai bersama index() & export().
     */
    private function buildQuery(Request $request): array
    {
        $query = ActivityLog::query()->latest();

        // Filter: username
        if ($request->filled('username')) {
            $query->byUser($request->username);
        }

        // Filter: role
        if ($request->filled('role') && in_array($request->role, ['librarian', 'patron'])) {
            $query->byRole($request->role);
        }

        // Filter: tanggal
        $dateFrom = $request->input('date_from', Carbon::today()->toDateString());
        $dateTo   = $request->input('date_to',   Carbon::today()->toDateString());
        $query->byDate($dateFrom, $dateTo);

        // Filter: route / url
        if ($request->filled('route')) {
            $query->where(function ($q) use ($request) {
                $q->where('route_name', 'like', "%{$request->route}%")
                  ->orWhere('url', 'like', "%{$request->route}%");
            });
        }

        return [$query, $dateFrom, $dateTo];
    }
}
