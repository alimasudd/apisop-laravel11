<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KategoriSop extends Model
{
    use HasFactory;

    protected $table = 'm_kat_sop';

    protected $fillable = [
        'kode',
        'nama',
        'deskripsi',
        'status',
    ];

    public function sops()
    {
        return $this->hasMany(Sop::class, 'katsop_id');
    }
}
