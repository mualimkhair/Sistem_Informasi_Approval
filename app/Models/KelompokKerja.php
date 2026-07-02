<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KelompokKerja extends Model
{
    protected $guarded = ['id'];

    public function unitKerja()
    {
        return $this->belongsTo(UnitKerja::class);
    }
}
