<?php

namespace App\Filament\Resources;

use Filament\Forms;
use App\Models\User;
use Filament\Tables;
use App\Enums\Gender;
use Filament\Infolists;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Awcodes\Curator\Models\Media;
use Filament\Forms\Components\Card;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Filament\Forms\Components\Hidden;
use Filament\Tables\Actions\ActionGroup;
use Illuminate\Database\Eloquent\Builder;
use Filament\Infolists\Components\Section;
use App\Filament\Resources\UserResource\Pages;
use Illuminate\Contracts\Auth\Authenticatable;
use STS\FilamentImpersonate\Tables\Actions\Impersonate;
use Awcodes\Curator\Components\Forms\CuratorPicker;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Filament\Resources\UserResource\RelationManagers\BranchesRelationManager;
use Tapp\FilamentAuthenticationLog\RelationManagers\AuthenticationLogsRelationManager;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $modelLabel = 'Staffs';
    protected static ?string $navigationLabel = 'View Staffs';
    protected static ?string $navigationGroup = 'Staff Management';
    protected static ?int $navigationSort = -1;

    public static function getNavigationBadge(): ?string
{
    return static::getModel()::count();
}




    public static function form(Form $form): Form
    {


        return $form

            ->schema([
                Card::make()->schema([
                    Forms\Components\TextInput::make('first_name')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\TextInput::make('middle_name')
                        ->maxLength(255),
                    Forms\Components\TextInput::make('last_name')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\TextInput::make('name')
                        ->label('Username')
                        ->unique(ignoreRecord: true)
                        ->required()
                        ->maxLength(255),
                    Forms\Components\TextInput::make('email')
                        ->email()
                        ->unique(ignoreRecord: true)
                        ->required()
                        ->maxLength(255),
                    Forms\Components\TextInput::make('password')
                        ->password()
                        ->required()
                        ->dehydrateStateUsing(fn (string $state): string => Hash::make($state))
                        ->dehydrated(fn (?string $state): bool => filled($state))
                        ->required(fn (string $operation): bool => $operation === 'create'),
                    Forms\Components\TextInput::make('full_name')
                        ->label('Staff Name')
                        ->default(Auth::user()->first_name . ' ' . Auth::user()->last_name)
                        ->disabled(),
                    Forms\Components\Hidden::make('created_by_id')
                         ->default(Auth::id()),

                    Forms\Components\TextInput::make('phone')
                        ->tel()
                        ->maxLength(255),
                    Forms\Components\Select::make('roles')
                        ->relationship('roles', 'name')
                        ->preload()
                        ->searchable(),
                    Forms\Components\Textarea::make('address')
                        ->maxLength(65535)
                        ->columnSpanFull(),
                    Forms\Components\TextInput::make('city')
                        ->maxLength(255),
                    Forms\Components\select::make('gender')
                        ->required()
                        ->options(Gender::class),
                    Forms\Components\RichEditor::make('notes')
                        ->maxLength(65535)
                        ->columnSpanFull(),
                    CuratorPicker::make('photo')
                        ,

                    ])->columns(2),

            ]);
    }


    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('photo')
                   ->circular()
                   ->getStateUsing(function($record) {
                    $media = Media::where('id', $record->photo)->first();

                        if ($media) {
                            return $media->path;
                        } else {
                    return 'https://ui-avatars.com/api/?background=random&name=' . urlencode($record->fullname);
                        }
                }),
                Tables\Columns\TextColumn::make('fullname')
                    ->label('Full Name')
                    ->searchable(['first_name', 'middle_name', 'last_name']),
                 Tables\Columns\TextColumn::make('roles.name')
                    ->label('Roles')
                    ->sortable()
                    ->badge()
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone')
                    ->searchable(),
                Tables\Columns\TextColumn::make('city')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('gender')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email_verified_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),


            ])
            ->filters([
                //
            ])
            ->actions([
                Impersonate::make(),
                //->redirectTo(route('/app')),
                ActionGroup::make([
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                    Tables\Actions\ViewAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
              ->schema([
                    Section::make('Staff Details')
                        ->columns([
                            'sm' => 3,
                            'xl' => 5,
                            '2xl' => 8,
                        ])
                        ->schema([
                            Infolists\Components\ImageEntry::make('photo')
                                ->height(60)
                                ->circular()
                                ->defaultImageUrl(function ($record) {
                                    return 'https://ui-avatars.com/api/?background=random&name=' . urlencode($record->fullname);
                                }),
                            Infolists\Components\TextEntry::make('fullname')
                                ->label('Full Name')
                                ->color('info')
                                ->columnSpan(2),
                            Infolists\Components\TextEntry::make('email')
                                ->color('info')
                                ->columnSpan(2),
                            Infolists\Components\TextEntry::make('phone')
                                ->color('info'),
                            Infolists\Components\TextEntry::make('address')
                                ->color('info'),
                            Infolists\Components\TextEntry::make('city')
                                ->color('info'),
                            Infolists\Components\TextEntry::make('gender')
                                ->color('info'),
                            Infolists\Components\TextEntry::make('roles.name')
                                ->label('Roles')
                                ->color('info')
                                ->badge(),
                    ])


              ]);

              }

    public static function getRelations(): array
    {
        return [
            //AuthenticationLogsRelationManager::class,
            BranchesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
            'view' => Pages\ViewUser::route('/{record}'),
        ];
    }
}
