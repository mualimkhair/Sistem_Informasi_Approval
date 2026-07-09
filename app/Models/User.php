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

    public const PANGKAT_GOLONGAN = [
        'Juru Muda (I/a)' => 'Juru Muda (I/a)',
        'Juru Muda Tk.I (I/b)' => 'Juru Muda Tk.I (I/b)',
        'Juru (I/c)' => 'Juru (I/c)',
        'Juru Tk.I (I/d)' => 'Juru Tk.I (I/d)',
        'Pengatur Muda (II/a)' => 'Pengatur Muda (II/a)',
        'Pengatur Muda Tk.I (II/b)' => 'Pengatur Muda Tk.I (II/b)',
        'Pengatur (II/c)' => 'Pengatur (II/c)',
        'Pengatur Tk.I (II/d)' => 'Pengatur Tk.I (II/d)',
        'Penata Muda (III/a)' => 'Penata Muda (III/a)',
        'Penata Muda Tk.I (III/b)' => 'Penata Muda Tk.I (III/b)',
        'Penata (III/c)' => 'Penata (III/c)',
        'Penata Tk.I (III/d)' => 'Penata Tk.I (III/d)',
        'Pembina (IV/a)' => 'Pembina (IV/a)',
        'Pembina Tk.I (IV/b)' => 'Pembina Tk.I (IV/b)',
        'Pembina Utama Muda (IV/c)' => 'Pembina Utama Muda (IV/c)',
        'Pembina Utama Madya (IV/d)' => 'Pembina Utama Madya (IV/d)',
        'Pembina Utama (IV/e)' => 'Pembina Utama (IV/e)',
    ];

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
