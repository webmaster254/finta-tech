<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use App\Models\AssetType;
use Filament\Tables\Table;
use App\Enums\AssetTypeEnum;
use App\Models\ChartOfAccount;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\AssetTypeResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\AssetTypeResource\RelationManagers;

class AssetTypeResource extends Resource
{
    protected static ?string $model = AssetType::class;

    protected static ?string $navigationIcon = null;
    protected static ?string $navigationGroup = 'Assets Management';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->maxLength(255),
                Forms\Components\Select::make('chart_of_account_asset_id')
                    ->label('Cash Account')
                    ->options(ChartOfAccount::all()->pluck('name', 'id')),
                Forms\Components\Select::make('type')
                    ->options(AssetTypeEnum::class),
                Forms\Components\Select::make('chart_of_account_fixed_asset_id')
                    ->label('Fixed Asset Account')
                    ->options(ChartOfAccount::all()->pluck('name', 'id')),
                Forms\Components\Select::make('chart_of_account_expense_id')
                    ->label('Expense Account')
                    ->options(ChartOfAccount::all()->pluck('name', 'id')),
                Forms\Components\Select::make('chart_of_account_contra_asset_id')
                    ->label('Accumulated Depreciation Account ')
                    ->options(ChartOfAccount::all()->pluck('name', 'id')),
                Forms\Components\Select::make('chart_of_account_income_id')
                    ->label('Income Account ')
                    ->options(ChartOfAccount::all()->pluck('name', 'id')),
                Forms\Components\Textarea::make('notes')
                    ->maxLength(65535)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('type')
                    ->searchable(),
                Tables\Columns\TextColumn::make('chartOfAccountAsset.name')
                    ->label('Cash Account')
                    ->sortable(),
                Tables\Columns\TextColumn::make('chartOfAccountFixedAsset.name')
                    ->label('Fixed Asset Account')
                    ->sortable(),
                Tables\Columns\TextColumn::make('chartOfAccountContraAsset.name')
                    ->label('Accumulated Depreciation Account')
                    ->sortable(),
                Tables\Columns\TextColumn::make('chartOfAccountExpense.name')
                    ->label('Expense Account')
                    ->sortable(),
                Tables\Columns\TextColumn::make('chartOfAccountIncome.name')
                    ->label('Income Account')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
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
            'index' => Pages\ListAssetTypes::route('/'),
            'create' => Pages\CreateAssetType::route('/create'),
            'edit' => Pages\EditAssetType::route('/{record}/edit'),
        ];
    }
}
