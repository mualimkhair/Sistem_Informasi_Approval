<?php

namespace App\Filament\Resources\KelompokKerjas\Pages;

use App\Filament\Resources\KelompokKerjas\KelompokKerjaResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageKelompokKerjas extends ManageRecords
{
    protected static string $resource = KelompokKerjaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
