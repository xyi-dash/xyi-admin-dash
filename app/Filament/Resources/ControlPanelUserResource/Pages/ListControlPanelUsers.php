<?php

namespace App\Filament\Resources\ControlPanelUserResource\Pages;

use App\Filament\Resources\ControlPanelUserResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListControlPanelUsers extends ListRecords
{
    protected static string $resource = ControlPanelUserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
