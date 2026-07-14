<?php

namespace App\Filament\Resources\AuditPengajuanCutis\Pages;

use App\Filament\Resources\AuditPengajuanCutis\AuditPengajuanCutiResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewAuditPengajuanCuti extends ViewRecord
{
    protected static string $resource = AuditPengajuanCutiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
