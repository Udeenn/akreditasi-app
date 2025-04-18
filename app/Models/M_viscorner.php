<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use PhpParser\Node\Expr\FuncCall;

class M_viscorner extends Model
{
    protected $connection = 'mysql2';
    use HasFactory;

    protected $table = 'visitorcorner';
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = true;
    
    protected $fillable = [
        'visittime',
        'cardnumber',
        'notes'
    ];

    public function cardnum(){
        return $this->belongsTo(M_card::class, 'cardnumber', 'cardnumber');
    }

    protected $dates = ['created_at', 'updated_at', 'visittime'];
}
