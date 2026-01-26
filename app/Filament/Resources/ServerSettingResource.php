<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ServerSettingResource\Pages;
use App\Models\ControlPanelUser;
use App\Models\ServerSetting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class ServerSettingResource extends Resource
{
    protected static ?string $model = ServerSetting::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?int $navigationSort = 3;

    public static function getNavigationLabel(): string
    {
        return __('cp.server_settings');
    }

    public static function getModelLabel(): string
    {
        return __('cp.server_setting');
    }

    public static function getPluralModelLabel(): string
    {
        return __('cp.server_settings');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('server')
                    ->options([
                        'one' => 'Server 01',
                        'two' => 'Server 02',
                        'three' => 'Server 03',
                    ])
                    ->required(),
                Forms\Components\Select::make('key')
                    ->options([
                        ServerSetting::KEY_ADMIN_CONVERSATION_ID => 'Admin Conversation ID',
                    ])
                    ->required(),
                Forms\Components\TextInput::make('value')
                    ->required()
                    ->maxLength(255)
                    ->helperText('ID беседы администрации в VK (например: 2000000002). Используется для получения нормы.'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('server')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'one' => '01',
                        'two' => '02',
                        'three' => '03',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('key')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        ServerSetting::KEY_ADMIN_CONVERSATION_ID => 'Admin Conversation ID',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('value'),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('server')
                    ->options(['one' => '01', 'two' => '02', 'three' => '03']),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->visible(fn () => self::canManage()),
                Tables\Actions\DeleteAction::make()->visible(fn () => self::canManage()),
            ])
            ->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListServerSettings::route('/'),
            'create' => Pages\CreateServerSetting::route('/create'),
            'edit' => Pages\EditServerSetting::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return self::canManage();
    }

    public static function canEdit($record): bool
    {
        return self::canManage();
    }

    public static function canDelete($record): bool
    {
        return self::canManage();
    }

    private static function canManage(): bool
    {
        $user = Auth::user();
        if (! $user) {
            return false;
        }
        $cpUser = ControlPanelUser::findByNickname($user->game_account_name, $user->server);

        return $cpUser && $cpUser->isRoot();
    }
}
