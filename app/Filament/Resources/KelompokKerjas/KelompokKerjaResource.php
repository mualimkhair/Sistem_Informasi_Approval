<?php

namespace App\Filament\Resources\KelompokKerjas;

use App\Filament\Resources\KelompokKerjas\Pages\CreateKelompokKerja;
use App\Filament\Resources\KelompokKerjas\Pages\EditKelompokKerja;
use App\Filament\Resources\KelompokKerjas\Pages\ListKelompokKerjas;
use App\Filament\Resources\KelompokKerjas\Schemas\KelompokKerjaForm;
use App\Filament\Resources\KelompokKerjas\Tables\KelompokKerjasTable;
use App\Models\KelompokKerja;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class KelompokKerjaResource extends Resource
{
    protected static ?string $model = KelompokKerja::class;

    public static function getNavigationIcon(): string | \BackedEnum | null
    {
        return 'heroicon-o-rectangle-stack';
    }

    public static function canAccess(): bool
    {
        return auth()->user()->hasRole(['super_admin', 'admin']);
    }

    public static function form(Schema $schema): Schema
    {
        return KelompokKerjaForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return KelompokKerjasTable::configure($table);
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
            'index' => ListKelompokKerjas::route('/'),
            'create' => CreateKelompokKerja::route('/create'),
            'edit' => EditKelompokKerja::route('/{record}/edit'),
        ];
    }
}
