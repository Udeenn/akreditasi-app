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
        $cornerCount = M_viscorner::whereDate('visittime', Carbon::today())->count();
        $kunjunganHarian = $historyCount + $cornerCount;

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




        // ============================ GRAFIK KUNJUNGAN TAHUNAN ============================ //
        // mengambil data kode prodi dan nama prodi
        $listProdi = M_eprodi::pluck('kode')->toArray();

        // mengambil data kunjungan prodi
        $thnDari = now()->year;
        // $thnDari = 2008;
        $data = M_vishistory::selectRaw('
            EXTRACT(YEAR_MONTH FROM visittime) as tahun_bulan,
            COUNT(visitorhistory.id) as jumlah_kunjungan
        ')
            ->leftJoin('borrowers as b', 'visitorhistory.cardnumber', '=', 'b.cardnumber')
            ->leftJoin('borrower_attributes as ba', 'ba.borrowernumber', '=', 'b.borrowernumber')
            ->leftJoin('authorised_values as av', function ($join) {
                $join->on('ba.code', '=', 'av.category')
                    ->on('ba.attribute', '=', 'av.authorised_value');
            })
            ->leftJoin('local_eprodi as le', 'le.kode', '=', 'av.authorised_value')
            ->where(DB::raw('YEAR(visitorhistory.visittime)'), $thnDari)
            ->whereIn('av.authorised_value', $listProdi)
            ->groupBy(DB::raw('EXTRACT(YEAR_MONTH FROM visitorhistory.visittime)'), 'av.authorised_value', 'le.nama')
            ->orderBy(DB::raw('EXTRACT(YEAR_MONTH FROM visitorhistory.visittime)'), 'asc')
            ->orderBy('av.authorised_value', 'asc')
            ->orderBy('le.nama', 'asc');

        // ============================ GRAFIK KUNJUNGAN TAHUNAN ============================ //

        return view('dashboard', compact('data', 'totalJurnal', 'kunjunganHarian',  'formatTotalEksemplar', 'formatTotalJudulBuku', 'formatTotalEbooks', 'formatTotalJurnal'));
    }
}
