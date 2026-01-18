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
                        'news_create', 'admin_appoint' => 'info',
                        'news_delete', 'admin_remove', 'cp_user_remove' => 'danger',
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
                    ->formatStateUsing(fn ($state) => $state ? json_encode($state, JSON_UNESCAPED_UNICODE) : '-')
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('action_type')
                    ->label('Type')
                    ->options(ActionLogService::getActionTypes()),
                Tables\Filters\SelectFilter::make('actor_server')
                    ->label('Server')
                    ->options([
                        'one' => '01',
                        'two' => '02',
                        'three' => '03',
                        'four' => '04',
                    ]),
                Tables\Filters\Filter::make('actor_name')
                    ->form([
                        Forms\Components\TextInput::make('nickname')->label('Nickname')
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['nickname'],
                            fn (Builder $q, $nick): Builder => $q->where(function ($q) use ($nick) {
                                $q->where('actor_name', 'like', "%{$nick}%")
                                  ->orWhere('target_name', 'like', "%{$nick}%");
                            })
                        );
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

    public static function canCreate(): bool { return false; }
    public static function canEdit($record): bool { return false; }
    public static function canDelete($record): bool { return false; }
}
