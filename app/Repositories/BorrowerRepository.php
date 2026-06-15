<?php

namespace App\Repositories;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BorrowerRepository
{
    /**
     * Mengambil info borrower (termasuk atribut prodi) secara batch.
     * Menggunakan chunking untuk keamanan memori/DB.
     */
    public function getBorrowerInfoByCardnumbers(Collection $uniqueCards): array
    {
        $borrowerInfo = [];

        foreach ($uniqueCards->chunk(1000) as $chunk) {
            $rows = DB::connection('mysql2')->table('borrowers as b')
                ->leftJoin('borrower_attributes as ba', function ($j) {
                    $j->on('ba.borrowernumber', '=', 'b.borrowernumber')
                      ->where('ba.code', '=', 'PRODI');
                })
                ->whereIn('b.cardnumber', $chunk->all())
                ->select('b.cardnumber', 'b.categorycode', 'ba.attribute as prodi_code')
                ->get();

            foreach ($rows as $r) {
                $borrowerInfo[strtoupper(trim($r->cardnumber))] = $r;
            }
        }

        return $borrowerInfo;
    }

    /**
     * Mengambil info borrower (termasuk atribut prodi) berdasarkan borrowernumber (secara batch).
     * Digunakan oleh PeminjamanController/Statistics yang mengelompokkan berdasarkan borrowernumber.
     */
    public function getBorrowerInfoByBorrowerNumbers(Collection $uniqueBorrowerNumbers): array
    {
        $borrowerInfo = [];

        foreach ($uniqueBorrowerNumbers->chunk(1000) as $chunk) {
            $rows = DB::connection('mysql2')->table('borrowers as b')
                ->leftJoin('borrower_attributes as ba', function ($j) {
                    $j->on('ba.borrowernumber', '=', 'b.borrowernumber')
                        ->where('ba.code', '=', 'PRODI');
                })
                ->whereIn('b.borrowernumber', $chunk->all())
                ->select('b.borrowernumber', 'b.cardnumber', 'b.categorycode', 'ba.attribute as prodi_code')
                ->get();

            foreach ($rows as $r) {
                $borrowerInfo[$r->borrowernumber] = $r;
            }
        }

        return $borrowerInfo;
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
