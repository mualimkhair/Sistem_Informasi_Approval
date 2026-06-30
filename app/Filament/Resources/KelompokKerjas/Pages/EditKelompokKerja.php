<?php

namespace App\Filament\Resources\KelompokKerjas\Pages;

use App\Filament\Resources\KelompokKerjas\KelompokKerjaResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditKelompokKerja extends EditRecord
{
    protected static string $resource = KelompokKerjaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
