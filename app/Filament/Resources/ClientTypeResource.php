<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use App\Models\ClientType;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use App\Filament\Clusters\Configuration;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\ClientTypeResource\Pages;
use App\Filament\Resources\ClientTypeResource\RelationManagers;

class ClientTypeResource extends Resource
{
    protected static ?string $model = ClientType::class;

    protected static ?string $navigationIcon = null;

    protected static ?string $navigationGroup = 'Clients Management';
    protected static ?int $navigationSort = 4;
    protected static ?string $cluster = Configuration::class;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListClientTypes::route('/'),
            // 'create' => Pages\CreateClientType::route('/create'),
            // 'edit' => Pages\EditClientType::route('/{record}/edit'),
        ];
    }
}
