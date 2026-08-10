<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Helpers\FacultyHelper;
use Illuminate\Support\Facades\Cache;

class TestFacultyEquality extends Command
{
    protected $signature = 'faculty:test-equality';
    protected $description = 'Membandingkan mapping prodi-fakultas dari hardcode vs database untuk memverifikasi kesamaan hasil';

    public function handle()
    {
        $this->info('=== Tes Kesamaan Data Mapping Fakultas & Prodi ===');
        $this->newLine();

        $testCodes = [
            'A510', 'A610', 'Q100', 'S400', 'W100', 'P100', 'U200', 'U100', 'S100', 'D100',
            'D200', 'D400', 'S300', 'T100', 'F100', 'I000', 'O100', 'O300', 'O200', 'O000',
            'G000', 'G100', 'G108', 'H100', 'R100', 'R200', 'C100', 'V100', 'K100', 'KSPMBKM',
            'KSPBIPA', 'J531', 'J521', 'J511', 'J110', 'A100', 'B100', 'C200', 'D300', 'E100',
            'F200', 'G200', 'K200', 'L100', 'XYZ99'
        ];

        $totalErrors = 0;

        foreach ($testCodes as $code) {
            // 1. Dapatkan dari DB
            Cache::forget('faculty_prodi_mapping_data');
            $dbResult = FacultyHelper::mapCodeToFaculty($code);

            // 2. Dapatkan dari Hardcode
            $hardRef = new \ReflectionMethod(FacultyHelper::class, 'mapCodeToFacultyHardcoded');
            $hardRef->setAccessible(true);
            $hardResult = $hardRef->invoke(null, $code);

            if ($dbResult === $hardResult) {
                $this->line("   ✓ Kode '{$code}': SAMA ('{$dbResult}')");
            } else {
                $this->warn("   ✗ Kode '{$code}': BERBEDA (DB='{$dbResult}', Hardcode='{$hardResult}')");
                $totalErrors++;
            }
        }

        $this->newLine();
        $this->info('=== KESIMPULAN ===');
        if ($totalErrors === 0) {
            $this->info('✅ BERHASIL! Semua kode prodi sampel menghasilkan Fakultas yang 100% IDENTIK.');
            $this->info('   Pemetaan DB & Cache berjalan aman.');
        } else {
            $this->error("❌ DITEMUKAN {$totalErrors} PERBEDAAN! Periksa output di atas.");
        }

        return $totalErrors === 0 ? Command::SUCCESS : Command::FAILURE;
    }
}
