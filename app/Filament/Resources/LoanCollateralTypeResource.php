<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use App\Models\Loan\LoanCollateralType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\LoanCollateralTypeResource\Pages;
use App\Filament\Resources\LoanCollateralTypeResource\RelationManagers;
use App\Filament\Clusters\Configuration;

class LoanCollateralTypeResource extends Resource
{
    protected static ?string $model = LoanCollateralType::class;

    protected static ?string $navigationIcon = null;

    protected static ?string $navigationGroup = 'Loans Management';
    protected static ?int $navigationSort = 5;
    protected static ?string $cluster = Configuration::class;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->placeholder('Enter Collateral Type Name'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),

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
            'index' => Pages\ListLoanCollateralTypes::route('/'),
            'create' => Pages\CreateLoanCollateralType::route('/create'),
            'edit' => Pages\EditLoanCollateralType::route('/{record}/edit'),
        ];
    }
}
