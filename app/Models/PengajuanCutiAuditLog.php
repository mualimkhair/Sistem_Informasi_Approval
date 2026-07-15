<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PengajuanCutiAuditLog extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'changes' => 'array',
    ];

    public function pengajuanCuti()
    {
        return $this->belongsTo(PengajuanCuti::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
