<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SopTugas extends Model
{
    protected $table = 'm_sop_tugas';
    public $timestamps = true;

    protected $fillable = [
        'sop_id',
        'sop_langkah_id',
        'user_id',
    ];

    public function sop()
    {
        return $this->belongsTo(Sop::class, 'sop_id');
    }

    public function langkah()
    {
        return $this->belongsTo(SopLangkah::class, 'sop_langkah_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
