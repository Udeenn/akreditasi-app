<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ImportVisitorData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:visitors {file : Path ke file CSV}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import rekap kunjungan dari CSV dan generate log raw ke visitorhistory';

    private $bulanIndo = [
        'Januari' => '01',
        'Februari' => '02',
        'Maret' => '03',
        'April' => '04',
        'Mei' => '05',
        'Juni' => '06',
        'Juli' => '07',
        'Agustus' => '08',
        'September' => '09',
        'Oktober' => '10',
        'November' => '11',
        'Desember' => '12',
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $file = $this->argument('file');

        if (!file_exists($file)) {
            $this->error("File tidak ditemukan: {$file}");
            return;
        }

        $this->info("Memulai import data dari {$file}...");
        
        // Baca file CSV
        $handle = fopen($file, 'r');
        $header = fgetcsv($handle, 1000, ';'); // separator semicolon ;

        $totalInserted = 0;
        $batch = [];

        // Disable query log to save memory
        DB::connection('mysql')->disableQueryLog();

        while (($data = fgetcsv($handle, 1000, ';')) !== false) {
            // Abaikan header atau baris kosong
            if (count($data) < 5) continue;
            
            // Periksa apakah ini baris header
            if (strtolower(trim($data[0])) === 'no' || strtolower(trim($data[0])) === 'periode harian:') {
                continue;
            }

            // Deteksi format 5 kolom vs 6 kolom
            // Format 5: No ; Periode ; Kode Prodi ; Nama Prodi ; Jumlah
            // Format 6: No ; Hari ; Tanggal ; Kode Prodi ; Nama Prodi ; Jumlah
            if (count($data) >= 6 && is_numeric(trim($data[5]))) {
                $periodeStr = trim($data[1]) . ', ' . trim($data[2]); // "Rabu, 1 April 2026"
                $kodeProdi = trim($data[3]);
                $jumlah = (int)$data[5];
            } else {
                $periodeStr = $data[1]; 
                $kodeProdi = trim($data[2]); 
                $jumlah = (int)$data[4]; 
            }

            if ($jumlah <= 0 || empty($kodeProdi)) continue;

            // Parse Date
            $date = $this->parseIndoDate($periodeStr);
            if (!$date) {
                $this->warn("Gagal parse tanggal: {$periodeStr}. Baris dilewati.");
                continue;
            }

            // Generate N records
            for ($i = 0; $i < $jumlah; $i++) {
                // Random time between 08:00:00 and 16:59:59
                $h = str_pad(rand(8, 16), 2, '0', STR_PAD_LEFT);
                $m = str_pad(rand(0, 59), 2, '0', STR_PAD_LEFT);
                $s = str_pad(rand(0, 59), 2, '0', STR_PAD_LEFT);
                $visittime = "{$date} {$h}:{$m}:{$s}";

                // Random Cardnumber
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

        // Insert sisa batch
        if (count($batch) > 0) {
            DB::connection('mysql')->table('visitorhistory')->insert($batch);
            $totalInserted += count($batch);
        }

        fclose($handle);

        $this->info("Berhasil menggenerate dan menginsert {$totalInserted} baris ke visitorhistory!");
    }

    private function parseIndoDate($str)
    {
        // Format bisa "Jumat, 2 Januari 2026" atau "2 Januari 2026"
        $parts = explode(', ', $str);
        
        $dateString = count($parts) >= 2 ? trim($parts[1]) : trim($str);
        $dateParts = explode(' ', $dateString);
        
        if (count($dateParts) < 3) return null;

        $day = str_pad($dateParts[0], 2, '0', STR_PAD_LEFT);
        $monthStr = $dateParts[1];
        $year = $dateParts[2];

        $month = $this->bulanIndo[$monthStr] ?? '01';

        return "{$year}-{$month}-{$day}";
    }

    private function generateCardNumber($kodeProdi)
    {
        // Jika DOSEN, TENDIK, KSP, LB, dll
        $special = ['DOSEN', 'TENDIK', 'KSP', 'LB', 'KSPMBKM', 'VIP', 'XA', 'XC'];
        if (in_array(strtoupper($kodeProdi), $special)) {
            return strtoupper($kodeProdi) . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
        }

        // Pastikan kode prodi diambil 4 karakter (misal L200, J500, dsb)
        // Kadang ada spasi atau format lain, kita potong 4 huruf
        $base = substr(str_pad(strtoupper($kodeProdi), 4, '0', STR_PAD_RIGHT), 0, 4);

        // Jika D100 -> D10023xxxx sampai D10025xxxx (10 karakter total)
        $years = ['23', '24', '25']; // Sesuai permintaan angkatan 23-25
        $y = $years[array_rand($years)];
        $randomNum = str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

        return $base . $y . $randomNum;
    }
}
