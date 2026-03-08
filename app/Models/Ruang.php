<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ruang extends Model
{
    use HasFactory;

    protected $table = 'm_ruang';
    public $timestamps = false;

    protected $fillable = [
        'area_id',
        'nama',
        'des',
    ];

    public function area()
    {
        return $this->belongsTo(Area::class, 'area_id');
    }
}
