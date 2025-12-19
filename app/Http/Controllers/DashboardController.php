<?php

namespace App\Http\Controllers;

use App\Models\M_biblio;
use App\Models\M_eprodi;
use App\Models\M_items;
use App\Models\M_viscorner;
use App\Models\M_vishistory;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function totalStatistik()
    {
        // TOTAL statistik
        $totalJurnal = 100;
        // $totalKunjungan = M_vishistory::where;
        // $kunjunganHarian = M_vishistory::whereDate('visittime', Carbon::today())->count();
        $historyCount = M_vishistory::whereDate('visittime', Carbon::today())->count();
        // $cornerCount = M_viscorner::whereDate('visittime', Carbon::today())->count();
        $kunjunganHarian = $historyCount;

        $totalJudulBuku = M_items::on('mysql2')
            ->select(
                DB::raw("COUNT(DISTINCT items.biblionumber) AS total_judul_buku")
            )
            ->leftJoin('biblioitems', 'biblioitems.biblioitemnumber', '=', 'items.biblioitemnumber')
            ->leftJoin('biblio', 'biblio.biblionumber', '=', 'items.biblionumber')
            ->where('items.damaged', 0)
            ->where('items.itemlost', 0)
            ->where('items.withdrawn', 0)
            ->where(DB::raw('LEFT(items.itype, 2)'), 'BK')
            // ->where('items.homebranch', 'PUSAT')
            ->value('total_judul_buku');

        $totalEksemplar = M_items::on('mysql2')
            ->select(
                DB::raw("COUNT(items.biblionumber) AS total_eksemplar")
            )
            ->leftJoin('biblioitems', 'biblioitems.biblioitemnumber', '=', 'items.biblioitemnumber')
            ->leftJoin('biblio', 'biblio.biblionumber', '=', 'items.biblionumber')
            ->where('items.damaged', 0)
            ->where('items.itemlost', 0)
            ->where('items.withdrawn', 0)
            ->where(DB::raw('LEFT(items.itype, 2)'), 'BK')
            // ->where('items.homebranch', 'PUSAT')
            ->value('total_eksemplar');


        $totalEbooks = M_items::on('mysql2')
            ->select(
                DB::raw("COUNT(items.biblionumber) AS total_ebooks")
            )
            ->leftJoin('biblioitems', 'biblioitems.biblioitemnumber', '=', 'items.biblioitemnumber')
            ->leftJoin('biblio', 'biblio.biblionumber', '=', 'items.biblionumber')
            ->where('items.damaged', 0)
            ->where('items.itemlost', 0)
            ->where('items.withdrawn', 0)
            ->where(DB::raw('LEFT(items.itype, 2)'), 'EB')
            // ->where('items.homebranch', 'PUSAT')
            ->value('total_ebooks');

        $totalJurnal = M_items::on('mysql2')
            ->select(
                DB::raw("COUNT(items.biblionumber) AS total_jurnal")
            )
            ->leftJoin('biblioitems', 'biblioitems.biblioitemnumber', '=', 'items.biblioitemnumber')
            ->leftJoin('biblio', 'biblio.biblionumber', '=', 'items.biblionumber')
            ->where('items.damaged', 0)
            ->where('items.itemlost', 0)
            ->where('items.withdrawn', 0)
            ->whereIn('items.itype', ['EJ', 'JRA', 'JR', 'JRT'])
            // ->where('items.homebranch', 'PUSAT')
            ->value('total_jurnal');

        $formatTotalJudulBuku = number_format($totalJudulBuku, 0, ',', '.');
        $formatTotalEksemplar = number_format($totalEksemplar, 0, ',', '.');
        $formatTotalEbooks = number_format($totalEbooks, 0, ',', '.');
        $formatTotalJurnal = number_format($totalJurnal, 0, ',', '.');






        return view('dashboard', compact('totalJurnal', 'kunjunganHarian',  'formatTotalEksemplar', 'formatTotalJudulBuku', 'formatTotalEbooks', 'formatTotalJurnal'));
    }
}
