<?php

namespace App\Helpers;

use App\Models\FacultyProdiMapping;
use Illuminate\Support\Facades\Cache;

class FacultyHelper
{
    /**
     * Memory cache for current HTTP request lifecycle
     */
    private static ?array $memoryCache = null;

    /**
     * Standard mapping for Prodi prefixes to Faculty names (Hardcoded Fallback)
     */
    private static $facultyMapping = [
        'A' => 'FKIP - Fakultas Keguruan dan Ilmu Pendidikan',
        'B' => 'FEB - Fakultas Ekonomi dan Bisnis',
        'C' => 'FHIP - Fakultas Hukum dan Ilmu Politik',
        'D' => 'FT - Fakultas Teknik',
        'E' => 'FG - Fakultas Geografi',
        'F' => 'FPsi - Fakultas Psikologi',
        'G' => 'FAI - Fakultas Agama Islam',
        'H' => 'FAI - Fakultas Agama Islam',
        'K' => 'FF - Fakultas Farmasi',
        'L' => 'FKI - Fakultas Komunikasi dan Informatika',
    ];

    /**
     * Map a Prodi Code to its Parent Faculty Name
     */
    public static function mapCodeToFaculty($prodiCode)
    {
        $prodiCode = strtoupper(trim((string) $prodiCode));
        if ($prodiCode === '') return 'Lainnya';

        // 1. Ambil dari static memory cache / Cache::rememberForever
        if (self::$memoryCache === null) {
            try {
                self::$memoryCache = Cache::rememberForever('faculty_prodi_mapping_data', function () {
                    return FacultyProdiMapping::with('faculty')
                        ->get()
                        ->map(function ($item) {
                            return [
                                'faculty_name' => $item->faculty->name ?? 'Lainnya',
                                'prodi_code'   => strtoupper(trim($item->prodi_code)),
                            ];
                        })
                        ->toArray();
                });
            } catch (\Throwable $e) {
                self::$memoryCache = [];
            }
        }

        $mappings = self::$memoryCache;

        if (!empty($mappings)) {
            // A. Cek Exact Match
            foreach ($mappings as $map) {
                if ($map['prodi_code'] === $prodiCode) {
                    return $map['faculty_name'];
                }
            }

            // B. Cek Wildcard / Prefix Match (Urutkan prefix terpanjang lebih dahulu)
            $prefixMatches = [];
            foreach ($mappings as $map) {
                $code = $map['prodi_code'];
                if (str_ends_with($code, '*')) {
                    $prefix = substr($code, 0, -1);
                    if ($prefix !== '' && str_starts_with($prodiCode, $prefix)) {
                        $prefixMatches[] = [
                            'prefix_len'   => strlen($prefix),
                            'faculty_name' => $map['faculty_name']
                        ];
                    }
                }
            }

            if (!empty($prefixMatches)) {
                usort($prefixMatches, fn($a, $b) => $b['prefix_len'] <=> $a['prefix_len']);
                return $prefixMatches[0]['faculty_name'];
            }

            return 'Lainnya';
        }

        // 2. FALLBACK: Logika Hardcode asli
        return self::mapCodeToFacultyHardcoded($prodiCode);
    }

    /**
     * Clear memory cache (misal saat testing)
     */
    public static function clearMemoryCache(): void
    {
        self::$memoryCache = null;
    }

    /**
     * Hardcoded fallback logic.
     */
    private static function mapCodeToFacultyHardcoded(string $prodiCode): string
    {
        $firstLetter = substr($prodiCode, 0, 1);
        $firstTwoLetters = substr($prodiCode, 0, 2);
        $firstThreeLetters = substr($prodiCode, 0, 3);

        // FKIP
        if (in_array($prodiCode, ['A510', 'A610', 'KIP/PSKGJ PAUD', 'Q100', 'S400', 'Q200', 'Q300', 'S200', 'A921', 'A922', 'A931', 'A932', 'A941', 'A942', 'A951', 'A952', 'A961', 'A971', 'A981'])) {
            return 'FKIP - Fakultas Keguruan dan Ilmu Pendidikan';
        }

        // FEB
        if (in_array($prodiCode, ['W100', 'P100'])) {
            return 'FEB - Fakultas Ekonomi dan Bisnis';
        }

        // FT
        if (in_array($prodiCode, ['U200', 'U100', 'S100', 'D100', 'D200', 'D400'])) {
            return 'FT - Fakultas Teknik';
        }

        // FPsi
        if (in_array($prodiCode, ['S300', 'T100', 'F100'])) {
            return 'FPsi - Fakultas Psikologi';
        }

        // FAI
        if (in_array($prodiCode, ['I000', 'O100', 'O300', 'O200', 'O000', 'G000', 'G100', 'G108', 'H100'])) {
            return 'FAI - Fakultas Agama Islam';
        }

        // FHIP
        if (in_array($prodiCode, ['R100', 'R200', 'C100'])) {
            return 'FHIP - Fakultas Hukum dan Ilmu Politik';
        }

        // FF
        if (in_array($prodiCode, ['V100', 'K100'])) {
            return 'FF - Fakultas Farmasi';
        }

        if (str_starts_with($prodiCode, 'KSP') || $prodiCode === 'KSP' || str_contains($prodiCode, 'BIPA')) {
            return 'Lainnya'; 
        }

        if (in_array($firstThreeLetters, ['J53', 'J52'])) return 'FKG - Fakultas Kedokteran Gigi';
        if ($firstTwoLetters === 'J5') return 'FK - Fakultas Kedokteran';
        if ($firstLetter === 'J') return 'FIK - Fakultas Ilmu Kesehatan';

        if (in_array($firstLetter, ['I', 'O', 'H'])) return 'FAI - Fakultas Agama Islam';

        if (isset(self::$facultyMapping[$firstLetter])) {
            return self::$facultyMapping[$firstLetter];
        }

        return 'Lainnya';
    }
}
