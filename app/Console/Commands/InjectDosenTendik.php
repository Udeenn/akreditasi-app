<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class InjectDosenTendik extends Command
{
    protected $signature = 'fix:inject-dosen';
    protected $description = 'Inject DOSEN and TENDIK visitors randomly into the dataset';

    public function handle()
    {
        $this->info("Mencari cardnumber Dosen/Tendik yang valid (pas 6 karakter, tidak KELUAR)...");

        // Ambil cardnumber DOSEN yang VALID (6 karakter, ada nama, bukan KELUAR)
        $validDosenCards = DB::connection('mysql2')->table('borrowers')
            ->whereNotNull('cardnumber')
            ->whereRaw('LENGTH(cardnumber) = 6')
            ->whereNotNull('surname')
            ->where('surname', '!=', '')
            ->where('categorycode', 'like', 'TC%')
            ->where('categorycode', '!=', 'KELUAR')
            ->pluck('cardnumber')
            ->toArray();

        // Ambil cardnumber TENDIK yang VALID
        $validTendikCards = DB::connection('mysql2')->table('borrowers')
            ->whereNotNull('cardnumber')
            ->whereRaw('LENGTH(cardnumber) = 6')
            ->whereNotNull('surname')
            ->where('surname', '!=', '')
            ->where('categorycode', 'like', 'STAF%')
            ->where('categorycode', '!=', 'KELUAR')
            ->pluck('cardnumber')
            ->toArray();

        if (empty($validDosenCards)) {
            $this->error("Tidak menemukan Dosen valid dengan 6 karakter!");
            return;
        }

        // Ambil data sampah (garbage) DOSEN/TENDIK yang terlanjur kita masukkan sebelumnya (yang panjangnya bukan 6)
        $garbageCards = DB::connection('mysql2')->table('borrowers')
            ->whereNotNull('cardnumber')
            ->whereRaw('LENGTH(cardnumber) != 6')
            ->where(function($q) {
                $q->where('categorycode', 'like', 'TC%')
                  ->orWhere('categorycode', 'like', 'STAF%');
            })
            ->pluck('cardnumber')
            ->toArray();

        $this->info("Menemukan " . count($garbageCards) . " jenis cardnumber sampah di Koha yang mungkin terlanjur masuk.");

        // Cari riwayat kunjungan yang pakai cardnumber sampah ini
        $historyIdsToFix = DB::connection('mysql')->table('visitorhistory')
            ->where('visittime', '>=', '2025-12-01 00:00:00')
            ->whereIn('cardnumber', $garbageCards)
            ->pluck('id')
            ->toArray();

        $this->info("Ditemukan " . count($historyIdsToFix) . " baris riwayat kunjungan yang tercemar data sampah. Mulai memperbaiki...");

        if (count($historyIdsToFix) == 0) {
            $this->info("Tidak ada data sampah yang perlu diperbaiki. Sistem bersih!");
            return;
        }

        DB::connection('mysql')->beginTransaction();
        $updated = 0;

        // Kita akan timpa ID yang tercemar dengan data validDosenCards
        $cases = [];
        $params = [];
        foreach ($historyIdsToFix as $id) {
            $cases[] = "WHEN id = ? THEN ?";
            $params[] = $id;
            
            // Randomly pick Dosen or Tendik valid
            if (!empty($validTendikCards) && rand(1, 100) <= 40) {
                $params[] = $validTendikCards[array_rand($validTendikCards)];
            } else {
                $params[] = $validDosenCards[array_rand($validDosenCards)];
            }
        }

        if (!empty($cases)) {
            $chunksCases = array_chunk($cases, 1000);
            $chunksParams = array_chunk($params, 2000);
            $chunksIds = array_chunk($historyIdsToFix, 1000);

            foreach ($chunksCases as $index => $chunkCase) {
                $idsStr = implode(',', array_fill(0, count($chunksIds[$index]), '?'));
                $sql = "UPDATE visitorhistory SET cardnumber = CASE " . implode(' ', $chunkCase) . " END WHERE id IN ($idsStr)";
                $finalParams = array_merge($chunksParams[$index], $chunksIds[$index]);
                DB::connection('mysql')->statement($sql, $finalParams);
            }
            $updated += count($historyIdsToFix);
        }

        DB::connection('mysql')->commit();

        $this->info("BERHASIL! {$updated} kunjungan sampah telah dibersihkan dan diganti dengan 6 digit asli!");

        // --- TAMBAHAN: Hapus yang category-nya KELUAR ---
        $this->info("Sedang mencari dan menghapus kunjungan dari member yang berstatus KELUAR...");
        
        $keluarCards = DB::connection('mysql2')->table('borrowers')
            ->whereNotNull('cardnumber')
            ->where('categorycode', 'KELUAR')
            ->pluck('cardnumber')
            ->toArray();

        if (!empty($keluarCards)) {
            $deleted = DB::connection('mysql')->table('visitorhistory')
                ->where('visittime', '>=', '2025-12-01 00:00:00')
                ->whereIn('cardnumber', $keluarCards)
                ->delete();

            $this->info("BERHASIL menghapus {$deleted} baris kunjungan dari member dengan kategori KELUAR.");
        } else {
            $this->info("Tidak ada member dengan kategori KELUAR.");
        }
    }
}
