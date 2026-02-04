<?php

namespace App\Http\Controllers;

use App\Models\M_biblio;
use App\Models\M_eprodi;
use App\Models\M_items;
use App\Models\M_viscorner;
use App\Models\M_vishistory;
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

        return view('dashboard', compact('totalJurnal', 'kunjunganHarian',  'formatTotalEksemplar', 'formatTotalJudulBuku', 'formatTotalEbooks', 'formatTotalJurnal'));
    }
}
