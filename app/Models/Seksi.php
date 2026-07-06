<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Seksi extends Model
{
    protected $guarded = ['id'];

    public function kepalaSeksi()
    {
        return $this->belongsTo(User::class, 'kepala_seksi_id');
    }

    public function unitKerjas()
    {
        return $this->hasMany(UnitKerja::class);
    }
}
