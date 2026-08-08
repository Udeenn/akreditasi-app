<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CnclassProdiMapping extends Model
{
    use HasFactory;

    protected $fillable = ['prodi_code', 'ruleset_id'];

    public function ruleset()
    {
        return $this->belongsTo(CnclassRuleset::class, 'ruleset_id');
    }
}
