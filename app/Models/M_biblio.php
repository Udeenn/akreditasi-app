<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class M_biblio extends Model
{
    protected $connection = 'mysql2';
    protected $table = 'biblio';
    protected $primaryKey = 'biblionumber';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;
    protected $fillable = [
        'frameworkcode',
        'author',
        'title',
        'unititle',
        'notes',
        'serial',
        'seriestitle',
        'copyrightdate',
        'datecreated',
        'abstract',
        'language'
    ];
    protected $dates = ['datecreated'];
    const CREATED_AT = 'datecreated';
    const UPDATED_AT = 'timestamp';

    public function metadata() {
        return $this->hasOne(M_bibliometa::class, 'biblionumber', 'biblionumber');
    }
}
