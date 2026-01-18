<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ControlPanelUserResource\Pages;
use App\Models\ControlPanelUser;
use App\Services\ActionLogService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class ControlPanelUserResource extends Resource
{
    protected static ?string $model = ControlPanelUser::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationLabel = 'CP Users';
    protected static ?string $modelLabel = 'CP User';
    protected static ?string $pluralModelLabel = 'CP Users';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('nickname')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('server')
                    ->options([
                        'one' => '01',
                        'two' => '02',
                        'three' => '03',
                        'four' => '04',
                    ])
                    ->required()
                    ->default('one'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nickname')->searchable(),
                Tables\Columns\TextColumn::make('server')->badge(),
                Tables\Columns\IconColumn::make('is_root')
                    ->label('Root')
                    ->state(fn (ControlPanelUser $record): bool => $record->isRoot())
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_by')->placeholder('system'),
                Tables\Columns\TextColumn::make('created_at')->dateTime('d.m.Y H:i')->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('server')
                    ->options(['one' => '01', 'two' => '02', 'three' => '03', 'four' => '04']),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->visible(fn () => self::canManage()),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (ControlPanelUser $record) => self::canManage() && !$record->isRoot())
                    ->after(function (ControlPanelUser $record) {
                        $user = Auth::user();
                        if ($user) {
                            app(ActionLogService::class)->log(
                                ActionLogService::CP_USER_REMOVE,
                                $user->game_account_id,
                                $user->game_account_name,
                                $user->server,
                                targetName: $record->nickname,
                                targetServer: $record->server,
                                ip: request()->ip()
                            );
                        }
                    }),
            ])
            ->bulkActions([]);
    }

    public static function getRelations(): array { return []; }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListControlPanelUsers::route('/'),
            'create' => Pages\CreateControlPanelUser::route('/create'),
            'edit' => Pages\EditControlPanelUser::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool { return self::canManage(); }
    
    public static function canEdit($record): bool
    {
        if ($record instanceof ControlPanelUser && $record->isRoot()) return false;
        return self::canManage();
    }

    public static function canDelete($record): bool
    {
        if ($record instanceof ControlPanelUser && $record->isRoot()) return false;
        return self::canManage();
    }

    private static function canManage(): bool
    {
        $user = Auth::user();
        if (!$user) return false;
        $cpUser = ControlPanelUser::findByNickname($user->game_account_name, $user->server);
        return $cpUser && $cpUser->isRoot();
    }
}
