<?php

namespace App\Filament\Resources\PengajuanCutis;

use App\Filament\Resources\PengajuanCutis\Pages\CreatePengajuanCuti;
use App\Filament\Resources\PengajuanCutis\Pages\EditPengajuanCuti;
use App\Filament\Resources\PengajuanCutis\Pages\ListPengajuanCutis;
use App\Filament\Resources\PengajuanCutis\Schemas\PengajuanCutiForm;
use App\Filament\Resources\PengajuanCutis\Tables\PengajuanCutisTable;
use App\Models\PengajuanCuti;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PengajuanCutiResource extends Resource
{
    protected static ?string $model = PengajuanCuti::class;

    public static function getNavigationIcon(): string | \BackedEnum | null
    {
        return 'heroicon-o-rectangle-stack';
    }

    public static function form(Schema $schema): Schema
    {
        return PengajuanCutiForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PengajuanCutisTable::configure($table);
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
            'index' => ListPengajuanCutis::route('/'),
            'create' => CreatePengajuanCuti::route('/create'),
            'edit' => EditPengajuanCuti::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if ($user->hasRole(['super_admin', 'admin'])) {
            return $query;
        }

        return $query->where('user_id', $user->id);
    }
}
