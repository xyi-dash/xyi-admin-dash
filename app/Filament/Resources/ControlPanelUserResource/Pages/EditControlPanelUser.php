<?php

namespace App\Filament\Resources\ControlPanelUserResource\Pages;

use App\Filament\Resources\ControlPanelUserResource;
use App\Services\ActionLogService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditControlPanelUser extends EditRecord
{
    protected static string $resource = ControlPanelUserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()->visible(fn () => ! $this->record->isRoot()),
        ];
    }

    protected function afterSave(): void
    {
        $user = Auth::user();
        if ($user) {
            app(ActionLogService::class)->log(
                ActionLogService::CP_USER_UPDATE,
                $user->game_account_id,
                $user->game_account_name,
                $user->server,
                targetName: $this->record->nickname,
                targetServer: $this->record->server,
                ip: request()->ip()
            );
        }
    }
}
