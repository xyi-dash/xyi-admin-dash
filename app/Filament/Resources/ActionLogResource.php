<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ActionLogResource\Pages;
use App\Models\ActionLog;
use App\Services\ActionLogService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ActionLogResource extends Resource
{
    protected static ?string $model = ActionLog::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Logs';
    protected static ?string $modelLabel = 'Log';
    protected static ?string $pluralModelLabel = 'Logs';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('d.m.Y H:i:s')
                    ->sortable(),
                Tables\Columns\TextColumn::make('action_type')
                    ->label('Action')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => 
                        ActionLogService::getActionTypes()[$state] ?? $state
                    )
                    ->color(fn (string $state): string => match($state) {
                        'login', 'admin_auth', 'cp_login' => 'success',
                        'logout' => 'gray',
                        'news_create', 'admin_appoint', 'admin_mark_support', 'admin_mark_youtuber' => 'info',
                        'news_delete', 'admin_remove', 'cp_user_remove', 'admin_remove_support', 'admin_remove_youtuber' => 'danger',
                        'admin_warn' => 'warning',
                        default => 'primary',
                    }),
                Tables\Columns\TextColumn::make('actor_name')
                    ->label('Actor')
                    ->searchable(),
                Tables\Columns\TextColumn::make('actor_server')
                    ->label('Server')
                    ->badge(),
                Tables\Columns\TextColumn::make('target_name')
                    ->label('Target')
                    ->searchable()
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('ip_address')
                    ->label('IP')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('details')
                    ->label('Details')
                    ->getStateUsing(function ($record) {
                        $state = $record->details;
                        if (!$state || !is_array($state)) return '-';
                        
                        $parts = [];
                        
                        if (isset($state['old_warns']) && isset($state['new_warns'])) {
                            $parts[] = "warns: {$state['old_warns']} → {$state['new_warns']}";
                        }
                        if (isset($state['old_level']) && isset($state['new_level'])) {
                            $parts[] = "lvl: {$state['old_level']} → {$state['new_level']}";
                        }
                        if (isset($state['level'])) {
                            $parts[] = "lvl {$state['level']}";
                        }
                        if (isset($state['reason']) && trim($state['reason']) !== '') {
                            $parts[] = "\"{$state['reason']}\"";
                        }
                        
                        return $parts ? implode(', ', $parts) : '-';
                    })
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('action_type')
                    ->label('Action Type')
                    ->options(ActionLogService::getActionTypes())
                    ->multiple(),
                Tables\Filters\SelectFilter::make('actor_server')
                    ->label('Server')
                    ->options([
                        'one' => '01',
                        'two' => '02',
                        'three' => '03',
                    ]),
                Tables\Filters\Filter::make('person_search')
                    ->form([
                        Forms\Components\TextInput::make('nickname')
                            ->label('Person Nickname')
                            ->placeholder('search by nickname...'),
                        Forms\Components\Select::make('search_mode')
                            ->label('Search Mode')
                            ->options([
                                'both' => 'Both (actor & target)',
                                'actor' => 'Actor only (what they did)',
                                'target' => 'Target only (what was done to them)',
                            ])
                            ->default('both'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (empty($data['nickname'])) {
                            return $query;
                        }
                        
                        $nick = $data['nickname'];
                        $mode = $data['search_mode'] ?? 'both';
                        
                        return match($mode) {
                            'actor' => $query->where('actor_name', 'like', "%{$nick}%"),
                            'target' => $query->where('target_name', 'like', "%{$nick}%"),
                            default => $query->where(function ($q) use ($nick) {
                                $q->where('actor_name', 'like', "%{$nick}%")
                                  ->orWhere('target_name', 'like', "%{$nick}%");
                            }),
                        };
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (empty($data['nickname'])) {
                            return null;
                        }
                        $mode = match($data['search_mode'] ?? 'both') {
                            'actor' => 'actor',
                            'target' => 'target',
                            default => 'any',
                        };
                        return "Person: {$data['nickname']} ({$mode})";
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListActionLogs::route('/'),
            'view' => Pages\ViewActionLog::route('/{record}'),
        ];
    }

    // logs are sacred. no touchy.
    public static function canCreate(): bool { return false; }
    public static function canEdit($record): bool { return false; }
    public static function canDelete($record): bool { return false; }
}
