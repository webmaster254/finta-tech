<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Currency;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\Loan\LoanCharge;
use Filament\Resources\Resource;
use App\Models\Loan\LoanChargeType;
use Filament\Forms\Components\Card;
use App\Models\Loan\LoanChargeOption;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\LoanChargeResource\Pages;
use App\Filament\Clusters\Configuration;
use App\Filament\Resources\LoanChargeResource\RelationManagers;

class LoanChargeResource extends Resource
{
    protected static ?string $model = LoanCharge::class;

    protected static ?string $navigationIcon = null;
    protected static ?string $navigationGroup = 'Loans Management';
    protected static ?int $navigationSort = 4;
    protected static ?string $cluster = Configuration::class;
   

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Card::make()->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('currency_id')
                    ->options(Currency::all()->pluck('name', 'id'))
                    ->searchable()
                    ->preload()
                    ->label('Currency')
                    ->required(),
                Forms\Components\Select::make('loan_charge_type_id')
                    ->label('Charge Type')
                    ->options(LoanChargeType::all()->pluck('name', 'id'))
                    ->required(),
                Forms\Components\Select::make('loan_charge_option_id')
                    ->label('Charge Option')
                    ->options(LoanChargeOption::all()->pluck('name', 'id'))
                    ->required(),
                Forms\Components\TextInput::make('amount')
                    ->required()
                    ->numeric(),
                Forms\Components\Toggle::make('is_penalty'),
                Forms\Components\Toggle::make('active')
                    ->required(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Name'),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Amount')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('charge_type.name')
                    ->label('Charge Type')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('charge_option.name')
                    ->label('Charge Option')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_penalty')
                    ->boolean(),
                Tables\Columns\IconColumn::make('active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListLoanCharges::route('/'),
            'create' => Pages\CreateLoanCharge::route('/create'),
            'edit' => Pages\EditLoanCharge::route('/{record}/edit'),
        ];
    }
}
