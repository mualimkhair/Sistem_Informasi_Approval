<?php

namespace App\Filament\Resources\KelompokKerjas\Pages;

use App\Filament\Resources\KelompokKerjas\KelompokKerjaResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListKelompokKerjas extends ListRecords
{
    protected static string $resource = KelompokKerjaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
