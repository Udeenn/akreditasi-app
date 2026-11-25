<?php

namespace App\Helpers;

/**
 * Helper untuk mengelola dan memvalidasi aturan Call Number (CN Class)
 * berdasarkan program studi (prodi).
 */
final class CnClassHelperr
{
    private static array $CACHE = [];

    public static function getCnClassByProdi(string $prodi): array
    {
        [$rulesets, $alias] = self::getAllProdiData();

        // jika prodi adalah alias ke sebuah ruleset (mis. HES)
        if (isset($alias[$prodi]) && isset($rulesets[$alias[$prodi]])) {
            return $rulesets[$alias[$prodi]];
        }

        // jika prodi langsung punya rules sendiri (opsional)
        return $rulesets[$prodi] ?? [];
    }

    public static function isValidCnClass(string $prodi, string $cn): bool
    {
        $rules = self::getCnClassByProdi($prodi);
        if (!$rules) return false;

        $val = self::normalizeCn($cn);

        // OPTIMASI: Cek lookup cache untuk exact match dulu (O(1))
        static $exactMatchCache = [];
        $cacheKey = $prodi . '|exact';

        if (!isset($exactMatchCache[$cacheKey])) {
            $exactMatchCache[$cacheKey] = [];
            foreach ($rules as $r) {
                if (!is_array($r) && !str_ends_with((string)$r, '*')) {
                    $exactMatchCache[$cacheKey][self::normalizeCn((string)$r)] = true;
                }
            }
        }

        if (isset($exactMatchCache[$cacheKey][$val])) {
            return true;
        }

        foreach ($rules as $r) {
            if (is_array($r) && count($r) === 2) {
                $min = self::normalizeCn($r[0]);
                $max = self::normalizeCn($r[1]);
                if (version_compare($val, $min) >= 0 && version_compare($val, $max) < 0) return true;
            } else {
                $rule = (string)$r;
                if (str_ends_with($rule, '*')) {
                    $prefix = self::normalizeCn(substr($rule, 0, -1));
                    if ($prefix !== '' && str_starts_with($val, $prefix)) return true;
                }
            }
        }
        return false;
    }

    /** @return array{0: array<string, array>, 1: array<string,string>} [$rulesets, $alias] */
    private static function getAllProdiData(): array
    {
        if (!empty(self::$CACHE)) return self::$CACHE;

        try {
            $data = \Illuminate\Support\Facades\Cache::rememberForever('cnclass_all_prodi_data', function () {
                $rulesets = [];
                $alias = [];

                $dbRulesets = \App\Models\CnclassRuleset::with('mappings')->get();

                if ($dbRulesets->isEmpty()) {
                    return self::getHardcodedProdiData();
                }

                foreach ($dbRulesets as $rs) {
                    $rulesets[$rs->name] = $rs->rules ?? [];
                    foreach ($rs->mappings as $mapping) {
                        $alias[$mapping->prodi_code] = $rs->name;
                    }
                }

                return [$rulesets, $alias];
            });

            if (!empty($data) && count($data) === 2) {
                return self::$CACHE = $data;
            }
        } catch (\Throwable $e) {
            // Fallback gracefully to seed data file
        }

        return self::$CACHE = self::getHardcodedProdiData();
    }

    private static function getHardcodedProdiData(): array
    {
        if (!empty(self::$CACHE)) return self::$CACHE;

        $dataPath = function_exists('database_path')
            ? database_path('data/cnclass_prodi_data.php')
            : dirname(__DIR__, 2) . '/database/data/cnclass_prodi_data.php';

        self::$CACHE = require $dataPath;
        return self::$CACHE;
    }

    private static function normalizeCn(string $cn): string
    {
        $cn = trim($cn);
        // HANYA ambil bagian pertama sebelum spasi untuk membuang author/tahun
        $parts = explode(' ', $cn);
        $cn = $parts[0] ?? '';
        return rtrim($cn, '.');
    }
}
