<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sop extends Model
{
    use HasFactory;

    protected $table = 'm_sop';

    protected $fillable = [
        'katsop_id',
        'kode',
        'nama',
        'deskripsi',
        'dokumen',
        'versi',
        'status',
        'status_sop',
    ];

    public function kategori()
    {
        return $this->belongsTo(KategoriSop::class, 'katsop_id');
    }
}
