<?php

namespace App\Models\Koha;

use Illuminate\Database\Eloquent\Model;

class BorrowerAttribute extends Model
{
    protected $connection = 'mysql2';
    protected $table = 'borrower_attributes';
    
    // Tabel ini mungkin tidak punya single primary key (biasanya composite)
    // Jika tidak butuh $model->find($id), matikan primaryKey
    protected $primaryKey = null;
    public $incrementing = false;
    public $timestamps = false;

    /**
     * Relasi balik ke Peminjam
     */
    public function borrower()
    {
        return $this->belongsTo(Borrower::class, 'borrowernumber', 'borrowernumber');
    }
}
