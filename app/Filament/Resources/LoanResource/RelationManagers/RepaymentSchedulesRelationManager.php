<?php

namespace App\Filament\Resources\LoanResource\RelationManagers;

use Filament\Forms;
use Filament\Tables;
use App\Models\Currency;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Tables\Components\Icon;
use Filament\Tables\Columns\IconColumn;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Loan\LoanRepaymentSchedule;
use Filament\Tables\Columns\Summarizers\Sum;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Resources\RelationManagers\RelationManager;

class RepaymentSchedulesRelationManager extends RelationManager
{
    protected static string $relationship = 'repayment_schedules';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                // Forms\Components\TextInput::make('from_date'),
                // Forms\Components\TextInput::make('paid_by_date'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->striped()
            ->heading('Amortization Schedule')
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
                // Tables\Columns\TextColumn::make('penalties'),
                // Tables\Columns\TextColumn::make('fees')
                //     ->label('Fees')
                //     ->summarize([
                //         Tables\Columns\Summarizers\Sum::make()
                //             ->money(Currency::where('is_default', 1)->first()->symbol)
                //             ->label('Total'),
                //     ]),
                Tables\Columns\TextColumn::make('total_installment')
                ->label('Total Installment')
                ->getStateUsing(fn (LoanRepaymentSchedule $record) => $record->getTotalInstallment())
                ->money(Currency::where('is_default', 1)->first()->symbol),
                
                Tables\Columns\TextColumn::make('total_paid')
                       ->getStateUsing(fn (LoanRepaymentSchedule $record) => $record->getTotalPaid()),
                Tables\Columns\TextColumn::make('total_due')
                      ->label('Total Balance')
                      ->money(Currency::where('is_default', 1)->first()->symbol),
                Tables\Columns\TextColumn::make('payoff')
                      ->label('Payoff')
                      ->money(Currency::where('is_default', 1)->first()->symbol),
                Tables\Columns\TextColumn::make('due_date'),
                Tables\Columns\TextColumn::make('paid_by_date')
                       ->getStateUsing(fn (LoanRepaymentSchedule $record) => $record->getPaidByDate())
                       ->color(fn (LoanRepaymentSchedule $record) =>
                        $record->isOverdue() ? 'danger' : null
                    ),
                // Tables\Columns\TextColumn::make('status')
                //       ->getStateUsing(fn (LoanRepaymentSchedule $record) => $record->getRepaymentStatus())
                //       ->icon(fn (string $state): string => match ($state) {
                //         'On time Payment' => 'heroicon-o-check-circle',
                //         'Late Payment' => 'heroicon-s-exclamation-circle',
                //     }),
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
