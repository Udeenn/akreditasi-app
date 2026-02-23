<?php

namespace App\Models\Koha;

use Illuminate\Database\Eloquent\Model;

class Biblio extends Model
{
    protected $connection = 'mysql2';
    protected $table = 'biblio';
    protected $primaryKey = 'biblionumber';
    public $timestamps = false;

    /**
     * Relasi ke Eksemplar
     */
    public function items()
    {
        return $this->hasMany(Item::class, 'biblionumber', 'biblionumber');
    }

    /**
     * Relasi 1-1 ke BiblioItem
     */
    public function biblioItem()
    {
        return $this->hasOne(BiblioItem::class, 'biblionumber', 'biblionumber');
    }
}
