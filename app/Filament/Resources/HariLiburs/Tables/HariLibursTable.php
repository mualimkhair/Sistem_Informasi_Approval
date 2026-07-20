<?php

namespace App\Filament\Resources\HariLiburs\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\Select;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Illuminate\Database\Eloquent\Builder;

class HariLibursTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('tanggal')->date()->sortable(),
                TextColumn::make('keterangan')->searchable()->sortable(),
                TextColumn::make('jenis')->badge()->searchable()->color(fn (string $state): string => match ($state) {
                    'libur_nasional' => 'danger',
                    'cuti_bersama' => 'warning',
                }),
            ])
            ->defaultSort('tanggal', 'asc')
            ->filters([
                Filter::make('tanggal_filter')
                    ->form([
                        Select::make('bulan')
                            ->label('Bulan')
                            ->options([
                                '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April',
                                '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus',
                                '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
                            ])->searchable(),
                        Select::make('tahun')
                            ->label('Tahun')
                            ->options(function () {
                                return \App\Models\HariLibur::selectRaw('YEAR(tanggal) as year')
                                    ->distinct()
                                    ->orderBy('year', 'desc')
                                    ->pluck('year', 'year')
                                    ->toArray();
                            })->searchable(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['bulan'],
                                fn (Builder $query, $month): Builder => $query->whereMonth('tanggal', $month),
                            )
                            ->when(
                                $data['tahun'],
                                fn (Builder $query, $year): Builder => $query->whereYear('tanggal', $year),
                            );
                    })
            ])
            ->filtersLayout(FiltersLayout::AboveContent)
            ->filtersFormColumns(2)
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
