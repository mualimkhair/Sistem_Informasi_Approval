<?php

namespace App\Filament\Resources\PengajuanCutis\Pages;

use App\Filament\Resources\PengajuanCutis\PengajuanCutiResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPengajuanCuti extends EditRecord
{
    protected static string $resource = PengajuanCutiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
