<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Currency;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\JournalEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Actions\Action;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\JournalEntryResource\Pages;
use App\Filament\Resources\JournalEntryResource\RelationManagers;

class JournalEntryResource extends Resource
{
    protected static ?string $model = JournalEntry::class;
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $navigationIcon = null;
    protected static ?string $navigationGroup = 'Accounting';
    protected static ?string $name = 'Journal Entry';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Hidden::make('created_by_id')
                    ->default(auth()->id()),
                Forms\Components\Hidden::make('manual_entry')
                    ->default(1)
                    ->required(),
                Forms\Components\Hidden::make('credit')
                    ->default(0),
                Forms\Components\Hidden::make('currency_id')
                    ->default(1),
                Forms\Components\Hidden::make('transaction_number')
                    ->default(uniqid()),
                Forms\Components\Hidden::make('transaction_type')
                    ->default('manual_entry'),
                Forms\Components\Textarea::make('name')
                    ->maxLength(65535)
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('debit')
                    ->label('Amount')
                    ->required()
                    ->numeric(),
                Forms\Components\Select::make('credit_account')
                    ->label('Credit Account')
                    ->required()
                    ->relationship('chart_of_account', 'name'),
                Forms\Components\Select::make('chart_of_account_id')
                    ->label('Debit Account')
                    ->required()
                    ->relationship('chart_of_account', 'name'),
                Forms\Components\DatePicker::make('date')
                    ->required()
                    ->default(now())
                    ->native(false),
                Forms\Components\Toggle::make('active')
                    ->required(),
                Forms\Components\TextInput::make('receipt')
                    ->maxLength(255),
                Forms\Components\Textarea::make('notes')
                    ->maxLength(65535)
                    ->columnSpanFull(),
            ]);


    }


    public static function table(Table $table): Table
    {
        return $table
            ->columns([

                Tables\Columns\TextColumn::make('transaction_number')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name'),
                Tables\Columns\TextColumn::make('chart_of_account.account_type')
                    ->label('Account')
                    ->badge()
                    ,
                Tables\Columns\TextColumn::make('debit')
                    ->money(Currency::where('is_default', 1)->first()->symbol)
                    ->sortable(),
                Tables\Columns\TextColumn::make('credit')
                    ->money(Currency::where('is_default', 1)->first()->symbol)
                    ->sortable(),
                Tables\Columns\TextColumn::make('receipt')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_by.fullname')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                //Tables\Actions\EditAction::make(),
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
                Section::make('Journal Entry Details')
                ->columns([
                    'sm' => 3,
                    'xl' => 6,
                    '2xl' => 8,
                ])
                ->headerActions([
                    Action::make('Reverse')
                        ->color('danger')
                        ->icon('heroicon-o-trash')
                        ->modalHeading('Reverse Journal Entry')
                        ->visible(fn(JournalEntry $record) => $record['reversible'] === 1)
                        ->action(function(JournalEntry $record){
                            //dump($record['transaction_number']);
                            $id = $record['transaction_number'];
                            $record->reverseJournalEntry($id);

                            Notification::make()
                             ->success()
                             ->title('Journal Entry Reversed')
                             ->body('The Journal Entry has been Reversed successfully.')
                             ->send();
                        }),
                ])
                ->schema([
                    TextEntry::make('transaction_number')
                        ->label('Transaction Number')
                        ->color('info'),
                    TextEntry::make('name')
                        ->color('info'),
                    TextEntry::make('chart_of_account.name')
                        ->label('Account')
                        ->color('info'),
                    TextEntry::make('debit')
                        ->color('success')
                        ->badge()
                        ->money(Currency::where('is_default', 1)->first()->symbol),
                    TextEntry::make('credit')
                        ->color('success')
                        ->badge()
                        ->money(Currency::where('is_default', 1)->first()->symbol ),
                    TextEntry::make('receipt')
                        ->color('info'),
                    TextEntry::make('reference')
                        ->color('info'),
                    TextEntry::make('notes')
                        ->color('info'),
                    TextEntry::make('created_by.fullname')
                        ->label('Created By')
                        ->color('info'),
                ]),

            ]) ;




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
            'index' => Pages\ListJournalEntries::route('/'),
            'create' => Pages\CreateJournalEntry::route('/create'),
            'view' => Pages\ViewJournalEntry::route('/{record}'),
            'edit' => Pages\EditJournalEntry::route('/{record}/edit'),
        ];
    }
}
