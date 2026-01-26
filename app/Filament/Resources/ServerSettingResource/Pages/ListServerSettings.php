<?php

namespace App\Filament\Resources\ServerSettingResource\Pages;

use App\Filament\Resources\ServerSettingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListServerSettings extends ListRecords
{
    protected static string $resource = ServerSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
