<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;

use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use App\Models\Loan\LoanTransaction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\LoanTransactionResource\Pages;
use App\Filament\Resources\LoanTransactionResource\RelationManagers;

class LoanTransactionResource extends Resource
{
    protected static ?string $model = LoanTransaction::class;

    protected static ?string $navigationIcon = null;
    protected static ?string $navigationGroup = 'Back office';
    //protected static bool $shouldRegisterNavigation = false;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
        ->modifyQueryUsing(fn (Builder $query) => $query->where('name', 'Repayment'))
          ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('first_name')
                    ->sortable(),
                Tables\Columns\TextColumn::make('account_number'),
                Tables\Columns\TextColumn::make('amount')
                    ->numeric(),
                Tables\Columns\TextColumn::make('reference'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime(),
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
            'index' => Pages\ListLoanTransactions::route('/'),
            //'create' => Pages\CreateLoanTransaction::route('/create'),
           // 'edit' => Pages\EditLoanTransaction::route('/{record}/edit'),
        ];
    }
}
