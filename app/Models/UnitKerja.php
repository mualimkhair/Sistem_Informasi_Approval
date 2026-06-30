<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UnitKerja extends Model
{
    protected $guarded = ['id'];
    
    public function kelompokKerjas()
    {
        return $this->hasMany(KelompokKerja::class);
    }
}
