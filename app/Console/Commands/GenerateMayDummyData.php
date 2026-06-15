<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class GenerateMayDummyData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generate:june';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate dummy data untuk 1 Juni hingga 11 Juni 2026';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Memulai proses pembuatan Dummy Data untuk Juni 2026 (sampai hari ini)...");

        // Tanggal libur di bulan Juni 2026
        // 1 Juni: Hari Lahir Pancasila
        $holidays = [
            '2026-06-01',
            // Hari minggu otomatis dilewati
        ];

        // Daftar prodi favorit
        $prodiWeights = [
            'B200' => 50,
            'F100' => 50,
            'J500' => 50,
            'D300' => 40,
            'D100' => 40,
            'L200' => 30,
            'B100' => 30,
            'G000' => 30,
            'D500' => 20,
            'D600' => 20,
            'KSP'  => 15,
            'TENDIK' => 15,
            'DOSEN' => 10,
            'L100' => 10,
            'C100' => 10,
        ];

        $prodiPool = [];
        foreach ($prodiWeights as $kode => $weight) {
            for ($i = 0; $i < $weight; $i++) {
                $prodiPool[] = $kode;
            }
        }

        DB::connection('mysql')->disableQueryLog();
        $totalInserted = 0;
        $batch = [];

        // Looping tanggal 1 s.d 11 Juni 2026
        for ($day = 1; $day <= 11; $day++) {
            $dateStr = '2026-06-' . str_pad($day, 2, '0', STR_PAD_LEFT);
            $carbonDate = Carbon::parse($dateStr);

            if ($carbonDate->isSunday() || in_array($dateStr, $holidays)) {
                $this->line("Melewati {$dateStr} (Libur/Minggu).");
                continue;
            }

            if ($carbonDate->isSaturday()) {
                $dailyVisitors = rand(100, 300);
            } else {
                $dailyVisitors = rand(500, 900);
            }

            $this->line("Generate {$dailyVisitors} kunjungan untuk {$dateStr}...");

            for ($i = 0; $i < $dailyVisitors; $i++) {
                $h = str_pad(rand(8, 16), 2, '0', STR_PAD_LEFT);
                $m = str_pad(rand(0, 59), 2, '0', STR_PAD_LEFT);
                $s = str_pad(rand(0, 59), 2, '0', STR_PAD_LEFT);
                
                if ($h == '16' && $m > 30) {
                    $m = str_pad(rand(0, 29), 2, '0', STR_PAD_LEFT);
                }

                $visittime = "{$dateStr} {$h}:{$m}:{$s}";

                $kodeProdi = $prodiPool[array_rand($prodiPool)];
                $cardnumber = $this->generateCardNumber($kodeProdi);

                $batch[] = [
                    'visittime'  => $visittime,
                    'cardnumber' => $cardnumber,
                    'location'   => 'Perpustakaan UMS',
                    'created_at' => $visittime,
                    'updated_at' => $visittime,
                ];

                if (count($batch) >= 500) {
                    DB::connection('mysql')->table('visitorhistory')->insert($batch);
                    $totalInserted += count($batch);
                    $batch = [];
                }
            }
        }

        if (count($batch) > 0) {
            DB::connection('mysql')->table('visitorhistory')->insert($batch);
            $totalInserted += count($batch);
        }

        $this->info("BERHASIL! {$totalInserted} data kunjungan dummy untuk 1-11 Juni 2026 telah dibuat.");
    }

    private function generateCardNumber($kodeProdi)
    {
        // Jika DOSEN, TENDIK, KSP
        $special = ['DOSEN', 'TENDIK', 'KSP', 'LB'];
        if (in_array(strtoupper($kodeProdi), $special)) {
            return strtoupper($kodeProdi) . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
        }

        $base = substr(str_pad(strtoupper($kodeProdi), 4, '0', STR_PAD_RIGHT), 0, 4);

        $years = ['23', '24', '25']; 
        $y = $years[array_rand($years)];
        $randomNum = str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

        return $base . $y . $randomNum;
    }
}
