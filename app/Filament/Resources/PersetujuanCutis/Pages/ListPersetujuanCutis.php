<?php

namespace App\Filament\Resources\PersetujuanCutis\Pages;

use App\Filament\Resources\PersetujuanCutis\PersetujuanCutiResource;
use Filament\Resources\Pages\ListRecords;

class ListPersetujuanCutis extends ListRecords
{
    protected static string $resource = PersetujuanCutiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No Create action here since it's just for approval
        ];
    }
}
