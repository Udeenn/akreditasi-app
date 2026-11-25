<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Cache;

class M_Auv extends Model
{
    use HasFactory;
    protected $connection = 'mysql2';
    protected $table = 'authorised_values';

    protected $fillable = [
        'category',
        'authorised_value',
        'lib',
        'imageurl',
    ];

    /**
     * Get cached prodi list untuk menghindari query berulang.
     * Cache disimpan selama 1 jam.
     * 
     * @param bool $forceRefresh Force refresh cache
     * @return \Illuminate\Support\Collection
     */
    public static function getCachedProdiList(bool $forceRefresh = false)
    {
        $cacheKey = 'prodi_list_tampil_cached_v2';
        
        if ($forceRefresh) {
            Cache::forget($cacheKey);
        }
        
        return Cache::remember($cacheKey, 3600, function () {
            return static::where('category', 'PRODI')
            ->whereRaw('CHAR_LENGTH(lib) >= 5') 
                ->onlyProdiTampil()
                ->orderBy('lib', 'asc')
                ->get();
        });
    }

    /**
     * Ambil seluruh map nama PRODI dari Koha authorised_values tanpa filter whitelist,
     * agar kode prodi non-standar/lama yang ada di Koha tidak tampil "Tidak Dikenal".
     */
    public static function getCachedAllProdiNameMap(bool $forceRefresh = false): array
    {
        $cacheKey = 'prodi_all_name_map_v1';

        if ($forceRefresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember($cacheKey, 3600, function () {
            return static::where('category', 'PRODI')
                ->whereRaw('CHAR_LENGTH(TRIM(lib)) >= 3')
                ->pluck('lib', 'authorised_value')
                ->mapWithKeys(fn($lib, $code) => [strtoupper(trim((string) $code)) => trim((string) $lib)])
                ->toArray();
        });
    }

    public const DEFAULT_WHITELISTED_CODES = [
        'A210', 'A220', 'A310', 'A319', 'A320', 'A410', 'A418', 'A420', 'A510', 'A520',
        'A610', 'A710', 'A810', 'A921', 'A922', 'A931', 'A932', 'A941', 'A942', 'A951',
        'A952', 'A961', 'A971', 'A981', 'B100', 'B109', 'B10A', 'B200', 'B300', 'B400',
        'C100', 'C200', 'C300', 'D100', 'D10A', 'D200', 'D209', 'D20A', 'D300', 'D400',
        'D500', 'D600', 'D800', 'E100', 'E200', 'F100', 'F107', 'F109', 'G000', 'G100',
        'G108', 'H100', 'I000', 'J120', 'J128', 'J130', 'J210', 'J218', 'J230', 'J310',
        'j317', 'J410', 'J500', 'J508', 'J510', 'J520', 'J530', 'K100', 'K109', 'K110',
        'L100', 'L200', 'L208', 'L300', 'L400', 'L900', 'O100', 'O200', 'O300', 'P100',
        'Q100', 'Q200', 'Q300', 'R100', 'R200', 'S100', 'S200', 'S300', 'S400', 'T100',
        'U100', 'U200', 'V100', 'W100',
    ];

    public static function getWhitelistStoragePath(): string
    {
        return storage_path('app/prodi_whitelist.json');
    }

    public static function getDefaultWhitelistedCodes(): array
    {
        return self::DEFAULT_WHITELISTED_CODES;
    }

    public static function getWhitelistedCodes(): array
    {
        return Cache::remember('prodi_whitelist_codes_v1', 3600, function () {
            $path = static::getWhitelistStoragePath();
            if (is_file($path)) {
                $decoded = json_decode((string) file_get_contents($path), true);
                if (is_array($decoded)) {
                    return array_values(array_unique(array_filter(array_map(fn($c) => trim((string) $c), $decoded))));
                }
            }
            return self::DEFAULT_WHITELISTED_CODES;
        });
    }

    public static function saveWhitelistedCodes(array $codes): void
    {
        $clean = array_values(array_unique(array_filter(array_map(fn($c) => trim((string) $c), $codes))));
        $path = static::getWhitelistStoragePath();
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents($path, json_encode($clean, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL);
        static::clearProdiCaches();
    }

    public static function resetWhitelistedCodes(): void
    {
        $path = static::getWhitelistStoragePath();
        if (is_file($path)) {
            unlink($path);
        }
        static::clearProdiCaches();
    }

    public static function clearProdiCaches(): void
    {
        Cache::forget('prodi_whitelist_codes_v1');
        Cache::forget('prodi_list_tampil_cached_v2');
        Cache::forget('prodi_all_name_map_v1');
        Cache::forget('prodi_full_list_v5');
    }

    public function scopeExcludeProdi($query)
    {
        $excludedCodes = [
            // 'O200',
            // 'G108',
            // 'O100',
            // 'O300'
        ];

        return $query->whereNotIn('authorised_value', $excludedCodes);
    }

    public function scopeOnlyProdiTampil($query)
    {
        $whitelistedCodes = static::getWhitelistedCodes();
        if (empty($whitelistedCodes)) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn('authorised_value', $whitelistedCodes);
    }

    public function borrowers()
    {
        return $this->hasMany(M_borrowers::class, 'cardnumber', 'authorised_value')
            ->whereRaw("LEFT(cardnumber, 4) = authorised_values.authorised_value");
    }

    public function visitorCorners()
    {
        return $this->hasMany(M_viscorner::class, 'cardnumber', 'authorised_value')
            ->whereRaw("LEFT(cardnumber, 4) = authorised_values.authorised_value");
    }

    public function visitorHistories()
    {
        return $this->hasMany(M_vishistory::class, 'cardnumber', 'authorised_value')
            ->whereRaw("LEFT(cardnumber, 4) = authorised_values.authorised_value");
    }


    public function borrowerAttributes(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(M_BorrowerAttribute::class, 'attribute', 'authorised_value')
            ->whereColumn('borrower_attributes.code', '=', 'authorised_values.category');
    }
    public static function findByCategoryAndValue($category, $value)
    {
        return static::where('category', $category)
            ->where('authorised_value', $value)
            ->first();
    }
}
