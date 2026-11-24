<?php

namespace App\Repositories;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class BorrowerRepository
{
    private static array $cardCache = [];
    private static array $borrowerNumberCache = [];

    /**
     * Mengambil info borrower (termasuk atribut prodi) secara batch.
     * Menggunakan chunking, memory cache, dan Redis Cache untuk efisiensi cross-database.
     */
    public function getBorrowerInfoByCardnumbers(Collection $uniqueCards): array
    {
        $normalized = $uniqueCards->map(fn($c) => strtoupper(trim((string)$c)))->unique()->values();
        if ($normalized->isEmpty()) {
            return [];
        }

        // 1. Cek in-memory cache
        $missing = $normalized->reject(fn($c) => isset(self::$cardCache[$c]))->values();

        // 2. Cek Redis / Persistent Cache untuk yang belum ada di memory
        if ($missing->isNotEmpty()) {
            $cacheKeys = $missing->mapWithKeys(fn($c) => ["bw_c_{$c}" => $c])->all();
            try {
                $fromCache = Cache::many(array_keys($cacheKeys));
                foreach ($fromCache as $k => $val) {
                    if ($val !== null) {
                        $card = $cacheKeys[$k];
                        self::$cardCache[$card] = $val;
                    }
                }
            } catch (\Throwable $e) {}
        }

        // 3. Hanya query ke DB Koha (mysql2) untuk cardnumber yang benar-benar belum di-cache
        $stillMissing = $normalized->reject(fn($c) => isset(self::$cardCache[$c]))->values();

        if ($stillMissing->isNotEmpty()) {
            // Batasi ukuran static cache agar worker/batch besar tidak memakan memori berlebih
            if (count(self::$cardCache) > 20000) {
                self::$cardCache = [];
            }
            $toCache = [];
            foreach ($stillMissing->chunk(1000) as $chunk) {
                $rows = DB::connection('mysql2')->table('borrowers as b')
                    ->leftJoin('borrower_attributes as ba', function ($j) {
                        $j->on('ba.borrowernumber', '=', 'b.borrowernumber')
                          ->where('ba.code', '=', 'PRODI');
                    })
                    ->whereIn('b.cardnumber', $chunk->all())
                    ->select('b.cardnumber', 'b.categorycode', 'ba.attribute as prodi_code')
                    ->get();

                foreach ($rows as $r) {
                    $key = strtoupper(trim($r->cardnumber));
                    self::$cardCache[$key] = $r;
                    $toCache["bw_c_{$key}"] = $r;
                }
            }

            if (!empty($toCache)) {
                try {
                    Cache::putMany($toCache, 86400); // Simpan 24 jam di Redis
                } catch (\Throwable $e) {}
            }
        }

        $result = [];
        foreach ($normalized as $c) {
            if (isset(self::$cardCache[$c])) {
                $result[$c] = self::$cardCache[$c];
            }
        }
        return $result;
    }

    /**
     * Mengambil info borrower (termasuk atribut prodi) berdasarkan borrowernumber (secara batch).
     * Menggunakan chunking, memory cache, dan Redis Cache untuk efisiensi cross-database.
     */
    public function getBorrowerInfoByBorrowerNumbers(Collection $uniqueBorrowerNumbers): array
    {
        $unique = $uniqueBorrowerNumbers->unique()->values();
        if ($unique->isEmpty()) {
            return [];
        }

        $missing = $unique->reject(fn($id) => isset(self::$borrowerNumberCache[$id]))->values();

        if ($missing->isNotEmpty()) {
            $cacheKeys = $missing->mapWithKeys(fn($id) => ["bw_id_{$id}" => $id])->all();
            try {
                $fromCache = Cache::many(array_keys($cacheKeys));
                foreach ($fromCache as $k => $val) {
                    if ($val !== null) {
                        $id = $cacheKeys[$k];
                        self::$borrowerNumberCache[$id] = $val;
                    }
                }
            } catch (\Throwable $e) {}
        }

        $stillMissing = $unique->reject(fn($id) => isset(self::$borrowerNumberCache[$id]))->values();

        if ($stillMissing->isNotEmpty()) {
            if (count(self::$borrowerNumberCache) > 20000) {
                self::$borrowerNumberCache = [];
            }
            $toCache = [];
            foreach ($stillMissing->chunk(1000) as $chunk) {
                $rows = DB::connection('mysql2')->table('borrowers as b')
                    ->leftJoin('borrower_attributes as ba', function ($j) {
                        $j->on('ba.borrowernumber', '=', 'b.borrowernumber')
                            ->where('ba.code', '=', 'PRODI');
                    })
                    ->whereIn('b.borrowernumber', $chunk->all())
                    ->select('b.borrowernumber', 'b.cardnumber', 'b.categorycode', 'ba.attribute as prodi_code')
                    ->get();

                foreach ($rows as $r) {
                    self::$borrowerNumberCache[$r->borrowernumber] = $r;
                    $toCache["bw_id_{$r->borrowernumber}"] = $r;
                }
            }

            if (!empty($toCache)) {
                try {
                    Cache::putMany($toCache, 86400); // Simpan 24 jam di Redis
                } catch (\Throwable $e) {}
            }
        }

        $result = [];
        foreach ($unique as $id) {
            if (isset(self::$borrowerNumberCache[$id])) {
                $result[$id] = self::$borrowerNumberCache[$id];
            }
        }
        return $result;
    }

    /**
     * Fetch categorycode saja (ringan).
     */
    public function getCategoryCodesByCardnumbers(Collection $uniqueCards): Collection
    {
        return DB::connection('mysql2')->table('borrowers')
            ->select('cardnumber', 'categorycode')
            ->whereIn('cardnumber', $uniqueCards)
            ->get()
            ->mapWithKeys(function ($item) {
                return [strtoupper(trim($item->cardnumber)) => $item->categorycode];
            });
    }

    /**
     * Fetch detail satu borrower.
     */
    public function getBorrowerByCardnumber(string $cardnumber): ?object
    {
        return DB::connection('mysql2')->table('borrowers')
            ->select('borrowernumber', 'cardnumber', 'firstname', 'surname', 'email', 'categorycode')
            ->where('cardnumber', $cardnumber)
            ->first();
    }
}
