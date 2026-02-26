<?php

namespace App\Http\Controllers;

use App\Models\M_biblio;
use App\Models\M_eprodi;
use App\Models\M_items;
use App\Models\M_viscorner;
use App\Models\M_vishistory;
use App\Models\M_Auv;
use App\Helpers\FacultyHelper;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function totalStatistik()
    {
        // Kunjungan Harian - Cache 5 menit (berubah sering)
        $kunjunganHarian = Cache::remember('dashboard_kunjungan_harian_' . Carbon::today()->toDateString(), 300, function() {
            return M_vishistory::whereDate('visittime', Carbon::today())->count();
        });

        // Total Judul Buku - Cache 1 jam (jarang berubah)
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

        // Total Ebooks - Cache 1 jam
        $totalEbooks = Cache::remember('dashboard_total_ebooks', 3600, function() {
            return M_items::on('mysql2')
                ->select(DB::raw("COUNT(items.biblionumber) AS total_ebooks"))
                ->leftJoin('biblioitems', 'biblioitems.biblioitemnumber', '=', 'items.biblioitemnumber')
                ->leftJoin('biblio', 'biblio.biblionumber', '=', 'items.biblionumber')
                ->where('items.damaged', 0)
                ->where('items.itemlost', 0)
                ->where('items.withdrawn', 0)
                ->where(DB::raw('LEFT(items.itype, 2)'), 'EB')
                ->value('total_ebooks');
        });

        // Total Jurnal - Cache 1 jam
        $totalJurnal = Cache::remember('dashboard_total_jurnal', 3600, function() {
            return M_items::on('mysql2')
                ->select(DB::raw("COUNT(items.biblionumber) AS total_jurnal"))
                ->leftJoin('biblioitems', 'biblioitems.biblioitemnumber', '=', 'items.biblioitemnumber')
                ->leftJoin('biblio', 'biblio.biblionumber', '=', 'items.biblionumber')
                ->where('items.damaged', 0)
                ->where('items.itemlost', 0)
                ->where('items.withdrawn', 0)
                ->whereIn('items.itype', ['EJ', 'JRA', 'JR', 'JRT'])
                ->value('total_jurnal');
        });

        $formatTotalJudulBuku = number_format($totalJudulBuku, 0, ',', '.');
        $formatTotalEksemplar = number_format($totalEksemplar, 0, ',', '.');
        $formatTotalEbooks = number_format($totalEbooks, 0, ',', '.');
        $formatTotalJurnal = number_format($totalJurnal, 0, ',', '.');

        // === KUNJUNGAN PER FAKULTAS TAHUN 2026 ===
        $kunjunganFakultas = Cache::remember('dashboard_kunjungan_fakultas_2026', 3600, function () {
            $tahun = 2026;
            $start = Carbon::createFromDate($tahun, 1, 1)->startOfDay();
            $end = Carbon::createFromDate($tahun, 12, 31)->endOfDay();

            // Query visitorhistory + visitorcorner
            $qHistory = DB::connection('mysql')->table('visitorhistory')
                ->select('cardnumber', DB::raw('COUNT(*) as cnt'))
                ->whereBetween('visittime', [$start, $end])
                ->groupBy('cardnumber');

            $qCorner = DB::connection('mysql')->table('visitorcorner')
                ->select('cardnumber', DB::raw('COUNT(*) as cnt'))
                ->whereBetween('visittime', [$start, $end])
                ->groupBy('cardnumber');

            $rawData = $qHistory->unionAll($qCorner)->get();

            // Fetch borrower info with PRODI attribute
            $cardNumbers = $rawData->pluck('cardnumber')->unique()->values();
            $borrowerInfo = [];
            foreach ($cardNumbers->chunk(1000) as $chunk) {
                $rows = DB::connection('mysql2')->table('borrowers as b')
                    ->leftJoin('borrower_attributes as ba', function ($j) {
                        $j->on('ba.borrowernumber', '=', 'b.borrowernumber')->where('ba.code', '=', 'PRODI');
                    })
                    ->whereIn('b.cardnumber', $chunk->all())
                    ->select('b.cardnumber', 'b.categorycode', 'ba.attribute as prodi_code')
                    ->get();
                foreach ($rows as $r) {
                    $borrowerInfo[strtoupper(trim($r->cardnumber))] = $r;
                }
            }

            // Map to faculty
            $fakultasCount = [];
            foreach ($rawData as $row) {
                $cn = strtoupper(trim($row->cardnumber));
                $info = $borrowerInfo[$cn] ?? null;
                $cat = strtoupper(trim($info->categorycode ?? ''));
                $prodi = trim($info->prodi_code ?? '');

                // Determine prodi code
                $kode = null;
                if (str_starts_with($cat, 'TC') || str_starts_with($cat, 'DOSEN')) $kode = 'DOSEN';
                elseif (str_starts_with($cat, 'STAF') || str_contains($cat, 'LIB') || $cat === 'LIBRARIAN') $kode = 'TENDIK';
                elseif (!empty($prodi)) $kode = strtoupper(trim($prodi));

                if (!$kode) {
                    if (str_starts_with($cn, 'VIP')) $kode = 'DOSEN';
                    elseif (strlen($cn) <= 9 && !preg_match('/^[A-Z]\d{3}/', $cn)) $kode = 'TENDIK';
                    elseif (strlen($cn) >= 4) $kode = substr($cn, 0, 4);
                    else $kode = 'UNKNOWN';
                }
                $kode = strtoupper(trim($kode));

                $fakultas = FacultyHelper::mapCodeToFaculty($kode);

                // Exclude non-faculty entries
                $blacklist = ['Lainnya', 'Dosen', 'Dosen & Pengajar', 'Tendik', 'Tenaga Kependidikan'];
                if (in_array($fakultas, $blacklist)) continue;

                if (!isset($fakultasCount[$fakultas])) $fakultasCount[$fakultas] = 0;
                $fakultasCount[$fakultas] += (int) $row->cnt;
            }

            arsort($fakultasCount);
            return $fakultasCount;
        });

        // === PEMINJAMAN PER FAKULTAS TAHUN 2026 ===
        $peminjamanFakultas = Cache::remember('dashboard_peminjaman_fakultas_2026', 3600, function () {
            $tahun = 2026;
            $start = Carbon::createFromDate($tahun, 1, 1)->startOfDay();
            $end = Carbon::createFromDate($tahun, 12, 31)->endOfDay();

            $rawData = DB::connection('mysql2')->table('statistics as s')
                ->leftJoin('borrowers as b', 'b.borrowernumber', '=', 's.borrowernumber')
                ->leftJoin('borrower_attributes as ba', function ($join) {
                    $join->on('ba.borrowernumber', '=', 'b.borrowernumber')
                        ->where('ba.code', '=', 'PRODI');
                })
                ->whereIn('s.type', ['issue', 'renew'])
                ->whereBetween('s.datetime', [$start, $end])
                ->select('b.cardnumber', 'b.categorycode', 'ba.attribute as prodi_code', DB::raw('COUNT(*) as cnt'))
                ->groupBy('b.cardnumber', 'b.categorycode', 'ba.attribute')
                ->get();

            $fakultasCount = [];
            foreach ($rawData as $row) {
                $catCode = strtoupper(trim($row->categorycode ?? ''));
                $cardnumber = strtoupper(trim($row->cardnumber ?? ''));
                $prodiCode = trim($row->prodi_code ?? '');

                $kode = null;
                if (str_starts_with($catCode, 'TC') || str_starts_with($catCode, 'DOSEN')) $kode = 'DOSEN';
                elseif (str_starts_with($catCode, 'STAF') || str_contains($catCode, 'LIB') || $catCode === 'LIBRARIAN') $kode = 'TENDIK';
                elseif (!empty($prodiCode)) $kode = strtoupper(trim($prodiCode));
                elseif (strlen($cardnumber) >= 4 && preg_match('/^[A-Z]\d{3}/', $cardnumber)) $kode = substr($cardnumber, 0, 4);

                if (!$kode) $kode = 'UNKNOWN';

                $fakultas = FacultyHelper::mapCodeToFaculty($kode);

                $blacklist = ['Lainnya', 'Dosen', 'Dosen & Pengajar', 'Tendik', 'Tenaga Kependidikan'];
                if (in_array($fakultas, $blacklist)) continue;

                if (!isset($fakultasCount[$fakultas])) $fakultasCount[$fakultas] = 0;
                $fakultasCount[$fakultas] += (int) $row->cnt;
            }

            arsort($fakultasCount);
            return $fakultasCount;
        });

        return view('dashboard', compact(
            'totalJurnal', 'kunjunganHarian',
            'formatTotalEksemplar', 'formatTotalJudulBuku', 'formatTotalEbooks', 'formatTotalJurnal',
            'kunjunganFakultas', 'peminjamanFakultas'
        ));
    }
}
