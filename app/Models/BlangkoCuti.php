<?php

namespace App\Models;

use App\Observers\BlangkoCutiObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

#[ObservedBy(BlangkoCutiObserver::class)]
class BlangkoCuti extends Model
{
    use HasUlids;

    protected $guarded = ['id'];

    protected $casts = [
        'tanggal_keputusan' => 'date',
    ];

    public function pengajuanCuti()
    {
        return $this->belongsTo(PengajuanCuti::class);
    }

    public function kabandara()
    {
        return $this->belongsTo(User::class, 'kabandara_id');
    }
}
