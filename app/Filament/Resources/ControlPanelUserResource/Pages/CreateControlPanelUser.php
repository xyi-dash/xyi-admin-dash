<?php

namespace App\Filament\Resources\ControlPanelUserResource\Pages;

use App\Filament\Resources\ControlPanelUserResource;
use App\Services\ActionLogService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateControlPanelUser extends CreateRecord
{
    protected static string $resource = ControlPanelUserResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = Auth::user();
        $data['created_by'] = $user?->game_account_name ?? 'hakurei_shrine';
        return $data;
    }

    protected function afterCreate(): void
    {
        $user = Auth::user();
        if ($user) {
            app(ActionLogService::class)->log(
                ActionLogService::CP_USER_ADD,
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
