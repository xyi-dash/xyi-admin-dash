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
                Section::make('Info')
                    ->schema([
                        TextEntry::make('created_at')->label('Date')->dateTime('d.m.Y H:i:s'),
                        TextEntry::make('action_type')->label('Action')
                            ->formatStateUsing(fn (string $state): string => 
                                ActionLogService::getActionTypes()[$state] ?? $state
                            ),
                        TextEntry::make('ip_address')->label('IP'),
                    ])
                    ->columns(3),
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
                    ->schema([
                        TextEntry::make('details')->label('')
                            ->formatStateUsing(fn ($state) => 
                                $state ? json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : 'none'
                            )
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
