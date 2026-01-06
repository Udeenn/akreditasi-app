<?php

namespace App\Helpers;

use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Support\Facades\DB;

/**
 * Helper untuk membangun klausa query yang dinamis dan kompleks.
 */
class QueryHelper
{
    /**
     * Menerapkan aturan filter CN Class yang kompleks ke instance Query Builder.
     * Fungsi ini menangani aturan nilai persis (IN), rentang (BETWEEN), dan awalan (LIKE).
     *
     * @param EloquentBuilder|QueryBuilder $queryBuilder Instance query yang akan dimodifikasi.
     * @param array $rules Array aturan yang didapat dari CnClassHelper.
     * @return EloquentBuilder|QueryBuilder Instance query yang sudah dimodifikasi.
     */
    public static function applyCnClassRules($queryBuilder, array $rules)
    {
        // // Jika tidak ada aturan, jangan lakukan apa-apa
        // if (empty($rules)) {
        //     return $queryBuilder;
        // }

        // // Gunakan ->where() dengan closure untuk mengelompokkan semua logika OR
        // // Ini akan menghasilkan SQL seperti: ... AND (rule1 OR rule2 OR rule3)
        // return $queryBuilder->where(function ($q) use ($rules) {
        //     $exactMatches = []; // Untuk menampung nilai pencocokan persis (untuk klausa IN)

        //     foreach ($rules as $rule) {
        //         // Kasus 1: Aturan adalah rentang, misal ['100', '200']
        //         if (is_array($rule) && count($rule) === 2) {
        //             $q->orWhere(function ($subQuery) use ($rule) {
        //                 $subQuery->where('bi.cn_class', '>=', $rule[0])
        //                     ->where('bi.cn_class', '<', $rule[1]);
        //             });
        //         }
        //         // Kasus 2: Aturan adalah awalan (LIKE), misal '111.8*'
        //         elseif (is_string($rule) && str_ends_with($rule, '*')) {
        //             $prefix = rtrim($rule, '*');
        //             $q->orWhere('bi.cn_class', 'LIKE', $prefix . '%');
        //         }
        //         // Kasus 3: Aturan adalah nilai persis (IN), kumpulkan dulu
        //         else {
        //             $exactMatches[] = (string)$rule;
        //         }
        //     }

        //     // Setelah loop selesai, terapkan semua nilai persis dalam satu query orWhereIn yang efisien
        //     if (!empty($exactMatches)) {
        //         $q->orWhereIn('bi.cn_class', $exactMatches);
        //     }
        // });

        if (empty($rules)) {
            return $queryBuilder;
        }

        // DEFINISI KOLOM PINTAR (LOGIKA FALLBACK)
        // SQL Logic: Jika bi.cn_class tidak NULL dan tidak kosong (''), pakai bi.cn_class.
        // Jika kosong, pakai items.itemcallnumber.
        // COALESCE(NULLIF(x, ''), y) adalah cara standar SQL untuk "Jika X kosong/null, ambil Y".
        $targetColumn = DB::raw("COALESCE(NULLIF(bi.cn_class, ''), i.itemcallnumber)");

        return $queryBuilder->where(function ($q) use ($rules, $targetColumn) {
            $exactMatches = [];

            foreach ($rules as $rule) {
                // Kasus 1: Aturan adalah rentang (Range)
                if (is_array($rule) && count($rule) === 2) {
                    $q->orWhere(function ($subQuery) use ($rule, $targetColumn) {
                        $subQuery->where($targetColumn, '>=', $rule[0])
                            ->where($targetColumn, '<', $rule[1]);
                    });
                }
                // Kasus 2: Aturan adalah awalan (LIKE)
                elseif (is_string($rule) && str_ends_with($rule, '*')) {
                    $prefix = rtrim($rule, '*');
                    $q->orWhere($targetColumn, 'LIKE', $prefix . '%');
                }
                // Kasus 3: Aturan adalah nilai persis (IN)
                else {
                    $exactMatches[] = (string)$rule;
                }
            }

            // Terapkan pencocokan persis pada kolom pintar tadi
            if (!empty($exactMatches)) {
                $q->orWhereIn($targetColumn, $exactMatches);
            }
        });
    }

    // public static function applyCnClassRules($queryBuilder, array $rules)
    // {
    //     // Jika tidak ada aturan, jangan lakukan apa-apa
    //     if (empty($rules)) {
    //         return $queryBuilder;
    //     }

    //     // --- DEFINISI KOLOM PINTAR (SMART COLUMN) ---
    //     // Penjelasan Logic SQL:
    //     // 1. NULLIF(bi.cn_class, '')
    //     //    -> Cek apakah cn_class kosong string ('') atau NULL.
    //     //
    //     // 2. SUBSTRING_INDEX(items.itemcallnumber, ' ', 1)
    //     //    -> Ambil itemcallnumber, lalu potong berdasarkan spasi (' '), dan ambil kata pertama saja.
    //     //    -> Contoh: "363.7 Fan A 2008" akan menjadi "363.7"
    //     //
    //     // 3. COALESCE(..., ...)
    //     //    -> Prioritaskan cn_class. Jika cn_class kosong, baru pakai hasil potongan itemcallnumber.

    //     $targetColumn = DB::raw("COALESCE(NULLIF(bi.cn_class, ''), SUBSTRING_INDEX(items.itemcallnumber, ' ', 1))");

    //     // Gunakan ->where() dengan closure untuk mengelompokkan semua logika OR
    //     return $queryBuilder->where(function ($q) use ($rules, $targetColumn) {
    //         $exactMatches = []; // Untuk menampung nilai pencocokan persis

    //         foreach ($rules as $rule) {
    //             // Kasus 1: Aturan adalah rentang (Range), misal ['300', '400']
    //             if (is_array($rule) && count($rule) === 2) {
    //                 $q->orWhere(function ($subQuery) use ($rule, $targetColumn) {
    //                     $subQuery->where($targetColumn, '>=', $rule[0])
    //                         ->where($targetColumn, '<', $rule[1]);
    //                 });
    //             }
    //             // Kasus 2: Aturan adalah awalan (LIKE), misal '600*'
    //             elseif (is_string($rule) && str_ends_with($rule, '*')) {
    //                 $prefix = rtrim($rule, '*');
    //                 $q->orWhere($targetColumn, 'LIKE', $prefix . '%');
    //             }
    //             // Kasus 3: Aturan adalah nilai persis (IN), kumpulkan dulu
    //             else {
    //                 $exactMatches[] = (string)$rule;
    //             }
    //         }

    //         // Setelah loop selesai, terapkan semua nilai persis dalam satu query orWhereIn yang efisien
    //         if (!empty($exactMatches)) {
    //             $q->orWhereIn($targetColumn, $exactMatches);
    //         }
    //     });
    // }
}
