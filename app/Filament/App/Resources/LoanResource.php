<?php

namespace App\Filament\App\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Currency;
use Filament\Forms\Form;
use App\Models\Loan\Loan;
use Filament\Tables\Table;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\Auth;
use Filament\Tables\Columns\TextColumn;
use Filament\Infolists\Components\Split;
use Illuminate\Database\Eloquent\Builder;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\Fieldset;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\Actions\Action;
use App\Filament\App\Resources\LoanResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\App\Resources\LoanResource\RelationManagers;
use App\Filament\App\Resources\LoanResource\RelationManagers\FilesRelationManager;
use App\Filament\App\Resources\LoanResource\RelationManagers\GuarantorsRelationManager;
use App\Filament\App\Resources\LoanResource\RelationManagers\TransactionsRelationManager;
use App\Filament\App\Resources\LoanResource\RelationManagers\RepaymentSchedulesRelationManager;

class LoanResource extends Resource
{
    protected static ?string $model = Loan::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('Loan ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('client.full_name')
                    ->label('Client Name')
                    ->searchable(['first_name','middle_name', 'last_name']),
                Tables\Columns\TextColumn::make('approved_amount')
                    ->label('Principal Amount')
                    ->money('KES')
                    ->sortable(),
                Tables\Columns\TextColumn::make('Balance')
                    ->getStateUsing(fn (Loan $record) => $record->getBalance($record->id))
                    ->money('KES')
                    ->sortable(),
                Tables\Columns\TextColumn::make('loan_product.name')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('disbursed_on_date')
                    ->label('Disbursement Date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->searchable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])->striped()
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
               // Tables\Actions\EditAction::make(),
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
                Split::make([
                    Section::make('Personal Information')

                    ->schema([
                        ImageEntry::make('photo')
                            ->height(60)
                            ->circular()
                            ->defaultImageUrl(function ($record) {
                                return 'https://ui-avatars.com/api/?background=random&name=' . urlencode($record->client->full_name);
                            }),
                        TextEntry::make('client.full_name')
                            ->color('info')
                            ->label('Full Name'),
                        TextEntry::make('account_number')
                            ->color('info'),
                        TextEntry::make('client.mobile')
                            ->color('info'),
                       TextEntry::make('loan_officer.fullname')
                            ->color('info'),
                    ])->columns(3),
                ]),
                Split::make([

                    Section::make('More Information')



                    ->schema([
                        TextEntry::make('status')
                            ->color('info'),
                        TextEntry::make('address')
                            ->color('info'),
                       TextEntry::make('city')
                            ->color('info'),
                        TextEntry::make('state')
                            ->color('info'),
                       TextEntry::make('email')
                            ->color('info'),
                       TextEntry::make('dob')
                            ->color('info')
                            ->label('Date of Birth'),
                       TextEntry::make('notes')
                            ->color('info'),
                        TextEntry::make('created_at')
                            ->color('info')
                            ->label('Created On'),
                    ])->columns(3),

                ]),


            ]);


    }

    public static function getRelations(): array
    {
        return [
            RepaymentSchedulesRelationManager::class,
            TransactionsRelationManager::class,
            GuarantorsRelationManager::class,
         
            FilesRelationManager::class,
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('loan_officer_id', Auth::user()->id);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLoans::route('/'),
            'create' => Pages\CreateLoan::route('/create'),
            'view' => Pages\ViewLoan::route('/{record}'),
            //'edit' => Pages\EditLoan::route('/{record}/edit'),
        ];
    }
    public static function getGloballySearchableAttributes(): array
    {
        return ['client.first_name', 'client.middle_name', 'client.last_name',  'client.account_number', 'client.mobile'];
    }
}
