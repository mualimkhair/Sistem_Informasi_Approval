<?php

namespace App\Filament\Resources\UnitKerjas;

use App\Filament\Resources\UnitKerjas\Pages\ManageUnitKerjas;
use App\Models\UnitKerja;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;

class UnitKerjaResource extends Resource
{
    protected static ?string $model = UnitKerja::class;

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
        return $schema
            ->components([
                TextInput::make('nama_unit')
                    ->required(),
                Select::make('jenis')
                    ->options(['administrasi' => 'Administrasi', 'operasional' => 'Operasional'])
                    ->searchable()
                    ->required(),
                Select::make('seksi_id')
                    ->label('Seksi Induk')
                    ->relationship('seksi', 'nama_seksi')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('kepala_unit_id')
                    ->label('Kepala Unit / Koordinator')
                    ->relationship('kepalaUnit', 'nama')
                    ->searchable()
                    ->preload()
                    ->nullable()
                    ->unique(ignoreRecord: true),
                \Filament\Forms\Components\Toggle::make('is_active')
                    ->label('Aktif')
                    ->default(true)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nama_unit')
                    ->searchable(),
                TextColumn::make('seksi.nama_seksi')
                    ->label('Seksi')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('kepalaUnit.nama')
                    ->label('Kanit/Koordinator')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('jenis')
                    ->badge(),
                \Filament\Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('jenis')
                    ->options([
                        'administrasi' => 'Administrasi',
                        'operasional' => 'Operasional',
                    ]),
            ])
            ->filtersLayout(FiltersLayout::AboveContent)
            ->filtersFormColumns(2)
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageUnitKerjas::route('/'),
        ];
    }
}
