<?php

namespace App\Filament\App\Resources\LoanResource\RelationManagers;

use Filament\Forms;
use Filament\Tables;
use App\Models\Currency;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Loan\LoanRepaymentSchedule;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Resources\RelationManagers\RelationManager;

class RepaymentSchedulesRelationManager extends RelationManager
{
    protected static string $relationship = 'repayment_schedules';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('loan_id')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->striped()
            ->recordTitleAttribute('loan_id')
            ->columns([
                Tables\Columns\TextColumn::make('installment'),
                Tables\Columns\TextColumn::make('principal')
                    ->label('Principal')
                    ->summarize([
                        Tables\Columns\Summarizers\Sum::make()
                            ->money(Currency::where('is_default', 1)->first()->symbol)
                            ->label('Total'),
                    ]),
                Tables\Columns\TextColumn::make('interest')
                    ->label('Interest')
                    ->summarize([
                        Tables\Columns\Summarizers\Sum::make()
                            ->money(Currency::where('is_default', 1)->first()->symbol)
                            ->label('Total'),
                    ]),
                Tables\Columns\TextColumn::make('penalties'),
                Tables\Columns\TextColumn::make('fees')
                    ->label('Fees')
                    ->summarize([
                        Tables\Columns\Summarizers\Sum::make()
                            ->money(Currency::where('is_default', 1)->first()->symbol)
                            ->label('Total'),
                    ]),
                Tables\Columns\TextColumn::make('total_paid')
                       ->getStateUsing(fn (LoanRepaymentSchedule $record) => $record->getTotalPaid()),
                Tables\Columns\TextColumn::make('total_due')
                      ->label('Total Outstanding')
                     ->summarize([
                       Tables\Columns\Summarizers\Sum::make()
                        ->money(Currency::where('is_default', 1)->first()->symbol)
                        ->label('Total'),
                ]),
                Tables\Columns\TextColumn::make('due_date'),
                Tables\Columns\TextColumn::make('paid_by_date')
                       ->getStateUsing(fn (LoanRepaymentSchedule $record) => $record->getPaidByDate())
                       ->color(fn (LoanRepaymentSchedule $record) =>
                        $record->isOverdue() ? 'danger' : null
                    ),

                    ])


             ->paginated(false)
            ->filters([
                //
            ]);
            // ->headerActions([
            //     Tables\Actions\CreateAction::make(),
            // ])
            // ->actions([
            //     Tables\Actions\EditAction::make(),
            //     Tables\Actions\DeleteAction::make(),
            // ])
            // ->bulkActions([
            //     Tables\Actions\BulkActionGroup::make([
            //         Tables\Actions\DeleteBulkAction::make(),
            //     ]),
            // ]);
    }
}
