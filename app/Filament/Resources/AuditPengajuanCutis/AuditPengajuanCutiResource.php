<?php

namespace App\Filament\Resources\AuditPengajuanCutis;

use App\Filament\Resources\AuditPengajuanCutis\Pages\CreateAuditPengajuanCuti;
use App\Filament\Resources\AuditPengajuanCutis\Pages\EditAuditPengajuanCuti;
use App\Filament\Resources\AuditPengajuanCutis\Pages\ListAuditPengajuanCutis;
use App\Filament\Resources\AuditPengajuanCutis\Pages\ViewAuditPengajuanCuti;
use App\Filament\Resources\AuditPengajuanCutis\Schemas\AuditPengajuanCutiForm;
use App\Filament\Resources\AuditPengajuanCutis\Schemas\AuditPengajuanCutiInfolist;
use App\Filament\Resources\AuditPengajuanCutis\Tables\AuditPengajuanCutisTable;
use App\Models\PengajuanCuti;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Filament\Support\Icons\Heroicons;


class AuditPengajuanCutiResource extends Resource
{
    protected static ?string $model = PengajuanCuti::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'AuditPengajuanCutiResource';

    protected static ?string $navigationIcons = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationLabel = 'Log Cuti';
    protected static ?int $navigationSort = 5;

    public static function form(Schema $schema): Schema
    {
        return AuditPengajuanCutiForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return AuditPengajuanCutiInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AuditPengajuanCutisTable::configure($table);
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
            'index' => ListAuditPengajuanCutis::route('/'),
            // 'create' => CreateAuditPengajuanCuti::route('/create'),
            // 'view' => ViewAuditPengajuanCuti::route('/{record}'),
            // 'edit' => EditAuditPengajuanCuti::route('/{record}/edit'),
        ];
    }

    public static function canAccess(): bool
    {
        return auth()->user()->hasRole(['super_admin', 'admin']);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }
    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->withTrashed();
    }
}
