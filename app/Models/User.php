<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

use Filament\Models\Contracts\HasName;

class User extends Authenticatable implements FilamentUser, HasName
{
    use HasFactory, Notifiable, HasRoles;

    public function getFilamentName(): string
    {
        return $this->nama ?? '';
    }

    protected $guarded = ['id'];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'is_profile_completed' => 'boolean',
        'tanggal_masuk' => 'date',
    ];

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }

    public function unitKerja()
    {
        return $this->belongsTo(UnitKerja::class);
    }
    
    public function saldoCuti()
    {
        return $this->hasOne(SaldoCuti::class);
    }
    
    public function pengajuanCutis()
    {
        return $this->hasMany(PengajuanCuti::class);
    }

    public function seksi()
    {
        return $this->belongsTo(Seksi::class);
    }
}
