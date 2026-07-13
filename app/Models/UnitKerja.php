<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UnitKerja extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'kepala_unit_id' => 'integer',
        'seksi_id' => 'integer',
    ];

    public function kelompokKerjas()
    {
        return $this->hasMany(KelompokKerja::class);
    }

    public function seksi()
    {
        return $this->belongsTo(Seksi::class);
    }

    public function kepalaUnit()
    {
        return $this->belongsTo(User::class, 'kepala_unit_id');
    }
}
