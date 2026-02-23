<?php

namespace App\Models\Koha;

use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    protected $connection = 'mysql2';
    protected $table = 'items';
    protected $primaryKey = 'itemnumber';
    public $timestamps = false;

    /**
     * Relasi ke Bibliografi Induk
     */
    public function biblio()
    {
        return $this->belongsTo(Biblio::class, 'biblionumber', 'biblionumber');
    }

    /**
     * Relasi ke Item Bibliografi (tipe koleksi, call number)
     */
    public function biblioItem()
    {
        return $this->belongsTo(BiblioItem::class, 'biblionumber', 'biblionumber');
    }
}
