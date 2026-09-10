<?php

namespace App\Filament\Resources\BlangkoCutis\Pages;

use App\Filament\Resources\BlangkoCutis\BlangkoCutiResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageBlangkoCutis extends ManageRecords
{
    protected static string $resource = BlangkoCutiResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
