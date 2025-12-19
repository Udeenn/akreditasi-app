<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class M_vishistory extends Model
{
    protected $connection = 'mysql';
    use HasFactory;

    protected $table = 'visitorhistory';
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = true;

    protected $fillable = [
        'visittime',
        'cardnumber',
        'location'
    ];

    public function cardnum()
    {
        return $this->belongsTo(M_card::class, 'cardnumber', 'cardnumber');
    }
    public function borrowers()
    {
        return $this->belongsTo(M_borrowers::class, 'cardnumber', 'cardnumber');
    }

    public function programStudi()
    {
        return $this->belongsTo(M_Auv::class, 'cardnumber', 'authorised_value')
            ->whereRaw("LEFT(cardnumber, 4) = authorised_value");
    }

    protected $dates = ['created_at', 'updated_at', 'visittime'];
}
