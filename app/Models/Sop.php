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
        'tanggal_berlaku',
        'tanggal_kadaluarsa',
        'status',
        'status_sop',
        'pengawas_id',
        'total_poin',
    ];

    public function kategori()
    {
        return $this->belongsTo(KategoriSop::class, 'katsop_id');
    }

    public function pengawas()
    {
        return $this->belongsTo(User::class, 'pengawas_id');
    }

    public function langkah()
    {
        return $this->hasMany(SopLangkah::class, 'sop_id');
    }
}
