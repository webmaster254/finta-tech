<?php

namespace App\Filament\App\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Client;
use App\Models\Currency;
use Filament\Forms\Form;
use App\Models\Loan\Loan;
use Filament\Tables\Table;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\Auth;
use Filament\Infolists\Components\Split;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\Fieldset;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\Actions\Action;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\App\Resources\ClientResource\Pages;
use App\Filament\App\Resources\ClientResource\RelationManagers;
use App\Filament\App\Resources\ClientResource\RelationManagers\LoansRelationManager;

class ClientResource extends Resource
{
    protected static ?string $model = Client::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

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
                    Tables\Columns\ImageColumn::make('photo')
                        ->circular()
                        ->defaultImageUrl(function ($record) {
                            return 'https://ui-avatars.com/api/?background=random&name=' . urlencode($record->fullname);
                        }),
                    Tables\Columns\TextColumn::make('fullname')
                        ->label('Full Name')
                        ->searchable(['first_name', 'middle_name', 'last_name']),
                    Tables\Columns\TextColumn::make('mobile')
                        ->searchable(),
                    Tables\Columns\TextColumn::make('account_number')
                        ->searchable(),
                    Tables\Columns\TextColumn::make('loan_officer.fullname')
                        ->label('Loan Officer')
                        ->sortable(),
                    Tables\Columns\TextColumn::make('status')
                        ->badge()
                        ->sortable(),
                  Tables\Columns\TextColumn::make('suggested_loan_limit')
                   ->label('Loan Limit')
                   ->money('KES')
                   ->badge('info'),
                    Tables\Columns\TextColumn::make('created_at')
                        ->dateTime()
                        ->sortable()
                        ->toggleable(isToggledHiddenByDefault: true),
                ])
            ->filters([
                //
            ])
            ->actions([
                //Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                   // Tables\Actions\DeleteBulkAction::make(),
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
                                return 'https://ui-avatars.com/api/?background=random&name=' . urlencode($record->fullname);
                            }),
                        TextEntry::make('fullname')
                            ->color('info')
                            ->label('Full Name')
                            ->columnSpan(2),
                        TextEntry::make('account_number')
                            ->color('info'),
                        TextEntry::make('mobile')
                            ->color('info'),
                        TextEntry::make('loan_officer.fullname')
                            ->color('info'),
                      TextEntry::make('suggested_loan_limit')
                            ->label('Loan Limit')
                            ->money('KES')
                            ->color('success')
                            ->badge(),
                      TextEntry::make('score')
                            ->color('success')
                            ->badge()
                            ->label('Credit Score')
                            ->suffix(' %')
                            ->getStateUsing(function($record) {
                                return $record->calculatePaymentHabitScore();
                            }),
                        TextEntry::make('loan')
                            ->color('success')
                            ->badge()
                            ->label('closed Loans')
                            ->getStateUsing(function($record) {
                                return $record->loans()->where('status', 'closed')->count();
                            }),
                    ])->columns(3),
                ]),
                Split::make([

                    Section::make('More Information')
                     ->headerActions([
                        Action::make('Check limit')
                            ->label('Check Loan Limit')
                            ->action(function (Client $record) {
                                $score = $record->calculatePaymentHabitScore();
                                $record->calculateSuggestedLoanLimit($score);

                                Notification::make()
                                    ->success()
                                    ->title('Check Loan Limit')
                                    ->body('The Loan Limit has Been refreshed successfully.')
                                    ->send();

                            })
                            ->requiresConfirmation(),
                    ])
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
                            ->color('info')
                            ->columnSpan(2),
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
            LoansRelationManager::class
        ];
    }
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('loan_officer_id', Auth::user()->id);
    }


    public static function getPages(): array
    {
        return [
            'index' => Pages\ListClients::route('/'),
            //'create' => Pages\CreateClient::route('/create'),
            //'edit' => Pages\EditClient::route('/{record}/edit'),
            'view' => Pages\ViewClient::route('/{record}'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['first_name', 'middle_name', 'last_name',  'account_number', 'mobile'];
    }
}
