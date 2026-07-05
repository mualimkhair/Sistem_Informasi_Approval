<?php

namespace App\Filament\Resources\Seksis\Pages;

use App\Filament\Resources\Seksis\SeksiResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSeksi extends EditRecord
{
    protected static string $resource = SeksiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
