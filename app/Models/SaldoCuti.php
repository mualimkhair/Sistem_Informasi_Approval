<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaldoCuti extends Model
{
    protected $guarded = ['id'];

    protected static function boot()
    {
        parent::boot();
        
        static::saving(function ($saldo) {
            if ($saldo->user && $saldo->user->jenis_kelamin === 'laki-laki') {
                $saldo->saldo_cuti_melahirkan = 0;
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function ledgers()
    {
        return $this->hasMany(SaldoCutiLedger::class, 'user_id', 'user_id');
    }
}
