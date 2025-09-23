<?php

namespace App\Helpers;

use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;

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
        // Jika tidak ada aturan, jangan lakukan apa-apa
        if (empty($rules)) {
            return $queryBuilder;
        }

        // Gunakan ->where() dengan closure untuk mengelompokkan semua logika OR
        // Ini akan menghasilkan SQL seperti: ... AND (rule1 OR rule2 OR rule3)
        return $queryBuilder->where(function ($q) use ($rules) {
            $exactMatches = []; // Untuk menampung nilai pencocokan persis (untuk klausa IN)

            foreach ($rules as $rule) {
                // Kasus 1: Aturan adalah rentang, misal ['100', '200']
                if (is_array($rule) && count($rule) === 2) {
                    $q->orWhere(function ($subQuery) use ($rule) {
                        $subQuery->where('bi.cn_class', '>=', $rule[0])
                            ->where('bi.cn_class', '<', $rule[1]);
                    });
                }
                // Kasus 2: Aturan adalah awalan (LIKE), misal '111.8*'
                elseif (is_string($rule) && str_ends_with($rule, '*')) {
                    $prefix = rtrim($rule, '*');
                    $q->orWhere('bi.cn_class', 'LIKE', $prefix . '%');
                }
                // Kasus 3: Aturan adalah nilai persis (IN), kumpulkan dulu
                else {
                    $exactMatches[] = (string)$rule;
                }
            }

            // Setelah loop selesai, terapkan semua nilai persis dalam satu query orWhereIn yang efisien
            if (!empty($exactMatches)) {
                $q->orWhereIn('bi.cn_class', $exactMatches);
            }
        });
    }
}
