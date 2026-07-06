<?php

namespace App\Filament\Resources\PersetujuanCutis;

use App\Filament\Resources\PersetujuanCutis\Pages\ListPersetujuanCutis;
use App\Filament\Resources\PersetujuanCutis\Tables\PersetujuanCutisTable;
use App\Models\PengajuanCuti;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Tables\Table;

class PersetujuanCutiResource extends Resource
{
    protected static ?string $model = PengajuanCuti::class;

    protected static ?string $slug = 'persetujuan-cuti';

    protected static ?string $navigationLabel = 'Persetujuan Cuti';

    protected static ?string $modelLabel = 'Persetujuan Cuti';

    public static function getNavigationIcon(): string | \BackedEnum | null
    {
        return 'heroicon-o-clipboard-document-check';
    }

    public static function canViewAny(): bool
    {
        $user = auth()->user();
        return $user->hasRole(['super_admin', 'admin', 'kanit', 'kasubag', 'pejabat_berwenang']);
    }

    public static function table(Table $table): Table
    {
        return PersetujuanCutisTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPersetujuanCutis::route('/'),
        ];
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if ($user->hasRole(['super_admin', 'admin'])) {
            return $query;
        }

        if ($user->hasRole('kasubag')) {
            return $query->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhereHas('seksi', function($q2) use ($user) {
                      $q2->where('kepala_seksi_id', $user->id);
                  });
            });
        }

        if ($user->hasRole('pejabat_berwenang')) {
            return $query->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhere(function($q2) {
                      $q2->where('keputusan_kanit', 'disetujui')
                         ->where('keputusan_kasubag', 'disetujui');
                  });
            });
        }

        if ($user->hasRole('kanit')) {
            return $query->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhereHas('unitKerja', function($q2) use ($user) {
                      $q2->where('kepala_unit_id', $user->id);
                  });
            });
        }

        return $query->where('user_id', $user->id);
    }
}
