<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ijazah extends Model
{
    protected $connection = 'mysql2';
    protected $table = 'tb_ijazah';
    protected $primaryKey = 'id';

    protected $fillable = ['id_staf', 'judul_transkrip', 'file_dokumen', 'tahun'];

    public function staff(){
        return $this->belongsTo(Staff::class, 'id_staf');
    }
}
