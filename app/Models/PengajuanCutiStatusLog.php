<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PengajuanCutiStatusLog extends Model
{
    protected $guarded = ['id'];

    public function pengajuanCuti()
    {
        return $this->belongsTo(PengajuanCuti::class);
    }

    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
