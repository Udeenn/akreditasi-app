<?php

namespace App\Repositories;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BorrowerRepository
{
    private static array $cardCache = [];
    private static array $borrowerNumberCache = [];

    /**
     * Mengambil info borrower (termasuk atribut prodi) secara batch.
     * Menggunakan chunking dan memory cache untuk efisiensi cross-database.
     */
    public function getBorrowerInfoByCardnumbers(Collection $uniqueCards): array
    {
        $normalized = $uniqueCards->map(fn($c) => strtoupper(trim((string)$c)))->unique()->values();
        $missing = $normalized->reject(fn($c) => isset(self::$cardCache[$c]))->values();

        if ($missing->isNotEmpty()) {
            foreach ($missing->chunk(1000) as $chunk) {
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
                }
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
     * Menggunakan chunking dan memory cache untuk efisiensi cross-database.
     */
    public function getBorrowerInfoByBorrowerNumbers(Collection $uniqueBorrowerNumbers): array
    {
        $unique = $uniqueBorrowerNumbers->unique()->values();
        $missing = $unique->reject(fn($id) => isset(self::$borrowerNumberCache[$id]))->values();

        if ($missing->isNotEmpty()) {
            foreach ($missing->chunk(1000) as $chunk) {
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
                }
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
