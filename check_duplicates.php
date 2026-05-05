<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$prodi = 'A220';
$cnClasses = App\Helpers\CnClassHelperr::getCnClassByProdi($prodi);

$query = App\Models\M_items::query()
    ->from('items')
    ->join('biblioitems as bi', 'items.biblionumber', '=', 'bi.biblionumber')
    ->join('biblio as b', 'b.biblionumber', '=', 'items.biblionumber')
    ->where('items.itemlost', 0)
    ->where('items.withdrawn', 0)
    ->whereRaw('LEFT(items.itype, 3) = "BKS"')
    ->whereRaw('LEFT(items.ccode, 1) = "R"');

App\Helpers\QueryHelper::applyCnClassRules($query, $cnClasses);

$results = $query->select('b.title', 'items.homebranch')->get();

$grouped = $results->groupBy('title')->filter(function ($items) {
    return $items->pluck('homebranch')->unique()->count() > 1;
});

echo "===== BUKU REFERENSI PRODI A210 YANG ADA DI LEBIH DARI 1 LOKASI =====\n\n";

if ($grouped->isEmpty()) {
    echo "Tidak ada buku yang duplikat lokasi.\n";
} else {
    foreach ($grouped as $title => $items) {
        echo "Judul: " . $title . "\n";
        echo "Lokasi: " . $items->pluck('homebranch')->unique()->implode(', ') . "\n\n";
    }
}
echo "Total Judul Unik Keseluruhan: " . $results->pluck('title')->unique()->count() . "\n";
echo "Total Baris jika dipisah per Lokasi: " . $results->count() . "\n";
