<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

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
        // Daftar KODE prodi yang HANYA boleh tampil
        $whitelistedCodes = [
            'A210',
            'A220',
            'A310',
            'A319',
            'A320',
            'A410',
            'A418',
            'A420',
            'A510',
            'A520',
            'A610',
            'A710',
            'A810',
            'B100',
            'B109',
            'B10A',
            'B200',
            'B300',
            'B400',
            'C100',
            'C200',
            'C300',
            'D100',
            'D10A',
            'D200',
            'D209',
            'D20A',
            'D300',
            'D400',
            'D500',
            'D600',
            'D800',
            'E100',
            'E200',
            'F100',
            'F107',
            'F109',
            'G000',
            'G100',
            'G108',
            // 'H100',
            'I000',
            'J120',
            'J128',
            'J130',
            'J210',
            'J218',
            'J230',
            'J310',
            'j317',
            'J410',
            'J500',
            'J508',
            'J510',
            'J520',
            'J530',
            'K100',
            'K109',
            'K110',
            'L100',
            'L200',
            'L280',
            'L300',
            'O100',
            'O200',
            'O300',
            'P100',
            'Q100',
            'Q200',
            'Q300',
            'R100',
            'R200',
            'S100',
            'S200',
            'S300',
            'S400',
            'T100',
            'U100',
            'U200',
            'V100',
            'W100'
        ];

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
