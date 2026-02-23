<?php

namespace App\Models\Koha;

use Illuminate\Database\Eloquent\Model;

class Borrower extends Model
{
    // Terhubung ke database Koha
    protected $connection = 'mysql2';
    protected $table = 'borrowers';
    protected $primaryKey = 'borrowernumber';
    
    // Non-incrementing / Custom Key Type if necessary
    // public $incrementing = true;
    
    // Koha tables usually don't have standard Laravel timestamps
    public $timestamps = false;

    /**
     * Relasi ke Atribut Peminjam (seperti PRODI)
     */
    public function attributes()
    {
        return $this->hasMany(BorrowerAttribute::class, 'borrowernumber', 'borrowernumber');
    }

    /**
     * Mengambil NIK/NIM
     */
    public function getCardnumberAttribute($value)
    {
        return strtoupper(trim($value));
    }
}
