<?php

namespace App\Models\Koha;

use Illuminate\Database\Eloquent\Model;

class Statistic extends Model
{
    protected $connection = 'mysql2';
    protected $table = 'statistics';

    // Tabel statistics seringkali tidak punya primary key tunggal di schema Koha, 
    // hanya datetime yang dijadikan index. Oleh karena itu kita nonaktifkan primary key default.
    protected $primaryKey = null;
    public $incrementing = false;
    public $timestamps = false; // Karena menggunakan field `datetime` bawaan Koha

    /**
     * Relasi ke Item (Koleksi yang dipinjam/digunakan)
     */
    public function item()
    {
        return $this->belongsTo(Item::class, 'itemnumber', 'itemnumber');
    }

    /**
     * Relasi ke Peminjam (User)
     */
    public function borrower()
    {
        return $this->belongsTo(Borrower::class, 'borrowernumber', 'borrowernumber');
    }

    /**
     * Scope Lokal untuk filter Issue & Renew
     */
    public function scopePeminjaman($query)
    {
        return $query->whereIn('type', ['issue', 'renew']);
    }
}
