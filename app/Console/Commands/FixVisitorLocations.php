<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixVisitorLocations extends Command
{
    protected $signature = 'fix:locations';
    protected $description = 'Distribusi ulang lokasi pengunjung Des 2025 - Jun 2026 (1% FK, 1% Pasca, 20% Ref, 78% Pusat)';

    public function handle()
    {
        $this->info("Mengambil semua ID riwayat kunjungan dari Desember 2025 hingga Juni 2026...");

        $historyIds = DB::connection('mysql')->table('visitorhistory')
            ->where('visittime', '>=', '2025-12-01 00:00:00')
            ->pluck('id')
            ->toArray();

        $totalRecords = count($historyIds);
        
        if ($totalRecords == 0) {
            $this->error("Tidak ada data kunjungan yang ditemukan!");
            return;
        }

        $this->info("Total data: {$totalRecords} baris. Mengacak data...");
        shuffle($historyIds);

        // Hitung target masing-masing
        $fkCount = (int) ($totalRecords * 0.01);
        $pascaCount = (int) ($totalRecords * 0.01);
        $refCount = (int) ($totalRecords * 0.20);
        // Sisanya dibiarkan 'pusat', karena default saat ini semua sudah 'pusat'

        $this->info("Target Distribusi:");
        $this->info("- FK: {$fkCount} (1%)");
        $this->info("- Pasca: {$pascaCount} (1%)");
        $this->info("- Ref: {$refCount} (20%)");
        $this->info("- Pusat: Sisanya (78%)");

        // Potong array ID untuk masing-masing lokasi
        $fkIds = array_slice($historyIds, 0, $fkCount);
        $pascaIds = array_slice($historyIds, $fkCount, $pascaCount);
        $refIds = array_slice($historyIds, $fkCount + $pascaCount, $refCount);

        DB::connection('mysql')->beginTransaction();

        try {
            $this->info("Memperbarui data FK...");
            if (!empty($fkIds)) {
                $chunks = array_chunk($fkIds, 1000);
                foreach ($chunks as $chunk) {
                    DB::connection('mysql')->table('visitorhistory')
                        ->whereIn('id', $chunk)
                        ->update(['location' => 'fk']);
                }
            }

            $this->info("Memperbarui data Pascasarjana...");
            if (!empty($pascaIds)) {
                $chunks = array_chunk($pascaIds, 1000);
                foreach ($chunks as $chunk) {
                    DB::connection('mysql')->table('visitorhistory')
                        ->whereIn('id', $chunk)
                        ->update(['location' => 'pasca']);
                }
            }

            $this->info("Memperbarui data Referensi...");
            if (!empty($refIds)) {
                $chunks = array_chunk($refIds, 1000);
                foreach ($chunks as $chunk) {
                    DB::connection('mysql')->table('visitorhistory')
                        ->whereIn('id', $chunk)
                        ->update(['location' => 'ref']);
                }
            }
            
            // Sisanya sudah 'pusat' jadi tidak perlu di-update lagi, tapi untuk berjaga-jaga
            $pusatIds = array_slice($historyIds, $fkCount + $pascaCount + $refCount);
            $this->info("Memperbarui data Pusat (Sisanya)...");
            if (!empty($pusatIds)) {
                $chunks = array_chunk($pusatIds, 1000);
                foreach ($chunks as $chunk) {
                    DB::connection('mysql')->table('visitorhistory')
                        ->whereIn('id', $chunk)
                        ->update(['location' => 'pusat']);
                }
            }

            DB::connection('mysql')->commit();
            $this->info("BERHASIL! Lokasi telah didistribusikan secara proporsional.");

        } catch (\Exception $e) {
            DB::connection('mysql')->rollBack();
            $this->error("Gagal memperbarui lokasi: " . $e->getMessage());
        }
    }
}
