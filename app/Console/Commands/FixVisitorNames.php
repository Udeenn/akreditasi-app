<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixVisitorNames extends Command
{
    protected $signature = 'fix:visitor-names';
    protected $description = 'Replace dummy cardnumbers with real cardnumbers from Koha (borrowers)';

    public function handle()
    {
        $this->info("Memuat data pemustaka asli dari tabel borrowers (mysql2)...");

        // Ambil semua cardnumber dan categorycode asli dari database koha
        $realCards = DB::connection('mysql2')->table('borrowers')
            ->whereNotNull('cardnumber')
            ->where('cardnumber', '!=', '')
            ->select('cardnumber', 'categorycode')
            ->get();

        if ($realCards->isEmpty()) {
            $this->error("Tabel borrowers kosong! Tidak bisa melakukan mapping.");
            return;
        }

        $this->info("Berhasil memuat " . $realCards->count() . " cardnumber asli. Mengelompokkan...");

        // Kelompokkan berdasarkan prefix
        $pool = [];
        $fallbackPool = [];

        foreach ($realCards as $row) {
            $c = strtoupper(trim($row->cardnumber));
            $cat = strtoupper(trim($row->categorycode ?? ''));
            $fallbackPool[] = $c;

            // Jika kategori adalah DOSEN, TENDIK, dsb, masukkan ke pool tersebut
            $specials = ['DOSEN', 'TENDIK', 'KSP', 'LB', 'VIP', 'XA', 'XC'];
            if (in_array($cat, $specials)) {
                $pool[$cat][] = $c;
            }

            // Tetap kelompokkan berdasarkan 4 karakter awal NIM untuk mahasiswa
            if (strlen($c) >= 4) {
                $p4 = substr($c, 0, 4);
                $pool[$p4][] = $c;
            }
        }

        $this->info("Memproses update data pengunjung (Des 2025 - Jun 2026)...");

        // Ambil data dummy yang kita buat (mulai Des 2025 sampai 2026)
        $histories = DB::connection('mysql')->table('visitorhistory')
            ->where('visittime', '>=', '2025-12-01 00:00:00')
            ->select('id', 'cardnumber')
            ->get();

        $this->info("Menemukan " . $histories->count() . " baris riwayat. Memulai penggantian ke NIM/Cardnumber asli...");

        DB::connection('mysql')->beginTransaction();
        $updated = 0;
        
        // Chunk processing untuk performa super cepat
        $histories->chunk(1000)->each(function($chunk) use (&$updated, $pool, $fallbackPool) {
            $cases = [];
            $params = [];
            $ids = [];

            foreach ($chunk as $history) {
                $dummyCard = strtoupper($history->cardnumber);
                $realCard = null;

                $specials = ['DOSEN', 'TENDIK', 'KSP', 'LB', 'VIP', 'XA', 'XC'];
                foreach ($specials as $sp) {
                    if (str_starts_with($dummyCard, $sp)) {
                        if (isset($pool[$sp]) && count($pool[$sp]) > 0) {
                            $realCard = $pool[$sp][array_rand($pool[$sp])];
                        }
                        break;
                    }
                }

                if (!$realCard) {
                    $p4 = substr($dummyCard, 0, 4);
                    if (isset($pool[$p4]) && count($pool[$p4]) > 0) {
                        $realCard = $pool[$p4][array_rand($pool[$p4])];
                    }
                }

                if (!$realCard) {
                    $realCard = $fallbackPool[array_rand($fallbackPool)];
                }

                $cases[] = "WHEN id = ? THEN ?";
                $params[] = $history->id;
                $params[] = $realCard;
                $ids[] = $history->id;
            }

            if (!empty($ids)) {
                $idsStr = implode(',', array_fill(0, count($ids), '?'));
                $sql = "UPDATE visitorhistory SET cardnumber = CASE " . implode(' ', $cases) . " END WHERE id IN ($idsStr)";
                
                $finalParams = array_merge($params, $ids);
                DB::connection('mysql')->statement($sql, $finalParams);
                
                $updated += count($ids);
                $this->line("Sudah memproses {$updated} baris dengan super cepat...");
            }
        });

        DB::connection('mysql')->commit();

        $this->info("BERHASIL! {$updated} data kunjungan kini sudah menggunakan NIM/Cardnumber asli dan pasti memunculkan NAMA di dashboard.");
    }
}
