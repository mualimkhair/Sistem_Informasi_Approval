<?php

namespace App\Filament\Resources\Seksis\Pages;

use App\Filament\Resources\Seksis\SeksiResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSeksis extends ListRecords
{
    protected static string $resource = SeksiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
