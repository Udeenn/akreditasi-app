<?php

namespace App\Models\Koha;

use Illuminate\Database\Eloquent\Model;

class BiblioItem extends Model
{
    protected $connection = 'mysql2';
    protected $table = 'biblioitems';
    protected $primaryKey = 'biblioitemnumber';
    public $timestamps = false;

    /**
     * Relasi ke Biblio
     */
    public function biblio()
    {
        return $this->belongsTo(Biblio::class, 'biblionumber', 'biblionumber');
    }
}
