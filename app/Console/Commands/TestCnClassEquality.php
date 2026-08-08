<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Helpers\CnClassHelperr;
use Illuminate\Support\Facades\Cache;

class TestCnClassEquality extends Command
{
    protected $signature = 'cnclass:test-equality';
    protected $description = 'Membandingkan data CN Class dari hardcode vs database untuk memverifikasi kesamaan hasil';

    public function handle()
    {
        $this->info('=== Tes Kesamaan Data CN Class ===');
        $this->newLine();

        // 1. Ambil data dari hardcode
        $this->info('1. Membaca data hardcode dari CnClassHelperr::getHardcodedProdiData()...');
        $hardcodeMethod = new \ReflectionMethod(CnClassHelperr::class, 'getHardcodedProdiData');
        $hardcodeMethod->setAccessible(true);
        [$hardRulesets, $hardAlias] = $hardcodeMethod->invoke(null);
        $this->info('   ✓ Jumlah rulesets (hardcode): ' . count($hardRulesets));
        $this->info('   ✓ Jumlah alias/mapping (hardcode): ' . count($hardAlias));

        // 2. Ambil data dari database (buang cache dulu)
        $this->newLine();
        $this->info('2. Membaca data dari Database (cache di-flush terlebih dahulu)...');
        Cache::forget('cnclass_all_prodi_data');
        $dbMethod = new \ReflectionMethod(CnClassHelperr::class, 'getAllProdiData');
        $dbMethod->setAccessible(true);
        [$dbRulesets, $dbAlias] = $dbMethod->invoke(null);
        $this->info('   ✓ Jumlah rulesets (database): ' . count($dbRulesets));
        $this->info('   ✓ Jumlah alias/mapping (database): ' . count($dbAlias));

        // 3. Bandingkan jumlah
        $this->newLine();
        $this->info('3. Membandingkan hasil...');
        $totalErrors = 0;

        // Cek rulesets yg ada di hardcode tapi tidak ada di DB
        $missingInDb = array_diff(array_keys($hardRulesets), array_keys($dbRulesets));
        if (!empty($missingInDb)) {
            $this->warn('   ✗ Ruleset yang ada di hardcode tapi TIDAK ADA di database:');
            foreach ($missingInDb as $k) {
                $this->warn('     - ' . $k);
            }
            $totalErrors += count($missingInDb);
        }

        // Cek rulesets yg ada di DB tapi tidak ada di hardcode
        $extraInDb = array_diff(array_keys($dbRulesets), array_keys($hardRulesets));
        if (!empty($extraInDb)) {
            $this->warn('   ✗ Ruleset yang ada di database tapi TIDAK ADA di hardcode:');
            foreach ($extraInDb as $k) {
                $this->warn('     - ' . $k);
            }
            $totalErrors += count($extraInDb);
        }

        // Cek rules di tiap ruleset
        $rulesetMismatch = 0;
        foreach ($hardRulesets as $name => $hardRules) {
            if (!isset($dbRulesets[$name])) continue;
            $dbRules = $dbRulesets[$name];

            $hardFlat = collect($hardRules)->map(fn($r) => is_array($r) ? implode('..', $r) : $r)->sort()->values()->toArray();
            $dbFlat = collect($dbRules)->map(fn($r) => is_array($r) ? implode('..', $r) : $r)->sort()->values()->toArray();

            if ($hardFlat !== $dbFlat) {
                $rulesetMismatch++;
                $this->warn("   ✗ Aturan berbeda pada ruleset '{$name}':");
                $onlyInHard = array_diff($hardFlat, $dbFlat);
                $onlyInDb = array_diff($dbFlat, $hardFlat);
                if ($onlyInHard) $this->warn('     - Hanya di hardcode: ' . implode(', ', array_slice($onlyInHard, 0, 5)));
                if ($onlyInDb) $this->warn('     - Hanya di database: ' . implode(', ', array_slice($onlyInDb, 0, 5)));
            }
        }
        $totalErrors += $rulesetMismatch;

        // Cek alias/mapping
        $missingAlias = array_diff_key($hardAlias, $dbAlias);
        $extraAlias = array_diff_key($dbAlias, $hardAlias);
        if (!empty($missingAlias)) {
            $this->warn('   ✗ Alias yang hilang di database: ' . implode(', ', array_keys($missingAlias)));
            $totalErrors += count($missingAlias);
        }
        if (!empty($extraAlias)) {
            $this->warn('   ✗ Alias tambahan di database: ' . implode(', ', array_keys($extraAlias)));
        }

        // Cek nilai alias
        $aliasMismatch = 0;
        foreach ($hardAlias as $code => $rulesetName) {
            if (isset($dbAlias[$code]) && $dbAlias[$code] !== $rulesetName) {
                $aliasMismatch++;
                $this->warn("   ✗ Nilai alias berbeda untuk '{$code}': hardcode='{$rulesetName}', db='{$dbAlias[$code]}'");
            }
        }
        $totalErrors += $aliasMismatch;

        // 4. Test fungsi getCnClassByProdi() pada beberapa prodi sampel
        $this->newLine();
        $this->info('4. Spot-check getCnClassByProdi() pada beberapa kode prodi sampel...');
        $samples = array_slice(array_keys($hardAlias), 0, 10); // Ambil 10 sampel alias pertama
        $spotErrors = 0;
        foreach ($samples as $prodiCode) {
            Cache::forget('cnclass_all_prodi_data');
            $dbResult = CnClassHelperr::getCnClassByProdi($prodiCode);

            // Reset static cache dari helper
            $cacheRef = new \ReflectionProperty(CnClassHelperr::class, 'CACHE');
            $cacheRef->setAccessible(true);
            $cacheRef->setValue(null, []);
            Cache::forever('cnclass_all_prodi_data', [$hardRulesets, $hardAlias]);
            $hardResult = CnClassHelperr::getCnClassByProdi($prodiCode);

            $dbFlat = collect($dbResult)->map(fn($r) => is_array($r) ? implode('..', $r) : $r)->sort()->values()->toArray();
            $hardFlat = collect($hardResult)->map(fn($r) => is_array($r) ? implode('..', $r) : $r)->sort()->values()->toArray();

            if ($dbFlat === $hardFlat) {
                $this->line("   ✓ Prodi '{$prodiCode}': SAMA (" . count($dbFlat) . " aturan)");
            } else {
                $this->warn("   ✗ Prodi '{$prodiCode}': BERBEDA (DB=" . count($dbFlat) . ", Hard=" . count($hardFlat) . ")");
                $spotErrors++;
            }
        }
        $totalErrors += $spotErrors;

        // Restore cache ke DB source
        Cache::forget('cnclass_all_prodi_data');
        $cacheRef = new \ReflectionProperty(CnClassHelperr::class, 'CACHE');
        $cacheRef->setAccessible(true);
        $cacheRef->setValue(null, []);

        // 5. Kesimpulan
        $this->newLine();
        $this->info('=== KESIMPULAN ===');
        if ($totalErrors === 0) {
            $this->info('✅ BERHASIL! Semua data dari database IDENTIK dengan data hardcode.');
            $this->info('   Tidak ada perbedaan ditemukan. Migrasi data aman.');
        } else {
            $this->error("❌ DITEMUKAN {$totalErrors} PERBEDAAN! Periksa output di atas.");
        }

        return $totalErrors === 0 ? Command::SUCCESS : Command::FAILURE;
    }
}
