<?php

namespace App\Filament\Resources\AuditPengajuanCutis\Pages;

use App\Filament\Resources\AuditPengajuanCutis\AuditPengajuanCutiResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditAuditPengajuanCuti extends EditRecord
{
    protected static string $resource = AuditPengajuanCutiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
