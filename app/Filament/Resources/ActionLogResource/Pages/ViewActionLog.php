<?php

namespace App\Filament\Resources\ActionLogResource\Pages;

use App\Filament\Resources\ActionLogResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Section;
use App\Services\ActionLogService;

class ViewActionLog extends ViewRecord
{
    protected static string $resource = ActionLogResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make()
                    ->schema([
                        TextEntry::make('action_type')
                            ->label('Action')
                            ->badge()
                            ->size(TextEntry\TextEntrySize::Large)
                            ->formatStateUsing(fn (string $state): string => 
                                strtoupper(ActionLogService::getActionTypes()[$state] ?? $state)
                            )
                            ->color(fn (string $state): string => match($state) {
                                'login', 'admin_auth', 'cp_login' => 'success',
                                'logout' => 'gray',
                                'news_create', 'admin_appoint', 'admin_confirm' => 'info',
                                'news_delete', 'admin_remove', 'cp_user_remove' => 'danger',
                                'admin_warn' => 'warning',
                                'admin_promote', 'admin_give_ga' => 'success',
                                'admin_demote', 'admin_remove_ga' => 'warning',
                                default => 'primary',
                            }),
                    ]),
                Section::make('Info')
                    ->schema([
                        TextEntry::make('created_at')->label('Date')->dateTime('d.m.Y H:i:s'),
                        TextEntry::make('ip_address')->label('IP'),
                    ])
                    ->columns(2),
                Section::make('Actor')
                    ->schema([
                        TextEntry::make('actor_name')->label('Name'),
                        TextEntry::make('actor_server')->label('Server'),
                        TextEntry::make('actor_id')->label('ID'),
                    ])
                    ->columns(3),
                Section::make('Target')
                    ->schema([
                        TextEntry::make('target_name')->label('Name')->placeholder('-'),
                        TextEntry::make('target_server')->label('Server')->placeholder('-'),
                        TextEntry::make('target_id')->label('ID')->placeholder('-'),
                    ])
                    ->columns(3),
                Section::make('Details')
                    ->schema(function ($record) {
                        $details = $record->details;
                        
                        if (!$details || !is_array($details)) {
                            return [
                                TextEntry::make('no_details')
                                    ->label('')
                                    ->default('No details')
                                    ->columnSpanFull(),
                            ];
                        }
                        
                        $labels = [
                            'old_warns' => 'Warns before',
                            'new_warns' => 'Warns after',
                            'old_level' => 'Level before',
                            'new_level' => 'Level after',
                            'level' => 'Level',
                            'reason' => 'Reason',
                            'server' => 'Server',
                            'donate_multiplier' => 'Donate multiplier',
                            'discounts_enabled' => 'Discounts',
                            'ads_enabled' => 'Ads',
                            'confirmed_admin' => 'Confirmed admin',
                        ];
                        
                        $entries = [];
                        foreach ($details as $key => $value) {
                            $label = $labels[$key] ?? ucfirst(str_replace('_', ' ', $key));
                            if (is_bool($value)) {
                                $value = $value ? 'Yes' : 'No';
                            }
                            $entries[] = TextEntry::make("detail_{$key}")
                                ->label($label)
                                ->default((string) $value);
                        }
                        
                        return $entries;
                    })
                    ->columns(3),
            ]);
    }
}
