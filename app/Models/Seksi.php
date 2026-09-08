<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Seksi extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'kepala_seksi_id' => 'integer',
    ];

    public function kepalaSeksi()
    {
        return $this->belongsTo(User::class, 'kepala_seksi_id');
    }

    public function unitKerjas()
    {
        return $this->hasMany(UnitKerja::class);
    }
}