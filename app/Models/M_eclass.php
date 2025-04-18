<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class M_eclass extends Model
{
    protected $connection = 'mysql2';
    use HasFactory;
    protected $table = 'local_eclass';
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = true;

    protected $fillable = [
        'kode',
        'nama',
        'status',
        'local_emakul_id',
        'semester'
    ];
    protected $dates = ['created_at', 'updated_at'];

}
