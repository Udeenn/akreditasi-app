<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CnclassRuleset extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'rules'];

    protected $casts = [
        'rules' => 'array',
    ];

    public function mappings()
    {
        return $this->hasMany(CnclassProdiMapping::class, 'ruleset_id');
    }
}
