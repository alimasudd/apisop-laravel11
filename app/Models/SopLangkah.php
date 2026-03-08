<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SopLangkah extends Model
{
    use HasFactory;

    protected $table = 'm_sop_langkah';
    public $timestamps = false;

    protected $fillable = [
        'sop_id',
        'ruang_id',
        'user_id',
        'jabatan_id',
        'urutan',
        'deskripsi_langkah',
        'wajib',
        'poin',
        'deadline_waktu',
        'toleransi_waktu_sebelum',
        'toleransi_waktu_sesudah',
        'wa_reminder',
        'wa_jam_kirim',
    ];

    public function sop()
    {
        return $this->belongsTo(Sop::class, 'sop_id');
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
