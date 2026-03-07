<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SopPelaksana extends Model
{
    protected $table = 'm_sop_pelaksana';

    protected $fillable = [
        'area_id',
        'sop_id',
        'sop_langkah_id',
        'ruang_id',
        'status_sop',
        'user_id',
        'poin',
        'des',
        'url',
        'deadline_waktu',
        'toleransi_waktu_sebelum',
        'toleransi_waktu_sesudah',
        'waktu_mulai',
        'waktu_selesai',
    ];

    public function area()
    {
        return $this->belongsTo(Area::class, 'area_id');
    }

    public function sop()
    {
        return $this->belongsTo(Sop::class, 'sop_id');
    }

    public function langkah()
    {
        return $this->belongsTo(SopLangkah::class, 'sop_langkah_id');
    }

    public function ruang()
    {
        return $this->belongsTo(Ruang::class, 'ruang_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
