<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Faculty extends Model
{
    protected $fillable = ['name'];

    public function mappings(): HasMany
    {
        return $table = $this->hasMany(FacultyProdiMapping::class, 'faculty_id');
    }
}
