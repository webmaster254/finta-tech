<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Expense;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Infolists;
use App\Models\Currency;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\ChartOfAccount;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Awcodes\Curator\Models\Media;
use Filament\Forms\Components\Grid;
use Illuminate\Support\Facades\Auth;
use App\Enums\RepaymentFrequencyType;
use Filament\Forms\Components\Select;
use Filament\Infolists\Components\Split;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Enums\FiltersLayout;
use Illuminate\Database\Eloquent\Builder;
use Filament\Infolists\Components\Section;
use Filament\Tables\Columns\Summarizers\Sum;
use App\Filament\Resources\ExpenseResource\Pages;
use Awcodes\Curator\Components\Forms\CuratorPicker;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Awcodes\Curator\PathGenerators\DatePathGenerator;
use App\Filament\Resources\ExpenseResource\RelationManagers;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;

class ExpenseResource extends Resource
{
    protected static ?string $model = Expense::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
    protected static ?string $name = 'View Expenses';
    protected static bool $shouldRegisterNavigation = false;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Hidden::make('created_by_id')
                    ->default(Auth::id()),
                Forms\Components\Hidden::make('currency_id')
                    ->default(Currency::where('is_default', 1)->first()->id),

                Forms\Components\Textarea::make('description')
                    ->maxLength(65535)
                    ->columnSpanFull()

                    ->afterStateupdated(function (Set $set, Get $get)  {
                        $set('name',$get('description'));}),
                Forms\Components\Hidden::make('name'),
                Forms\Components\Select::make('expense_chart_of_account_id')
                    ->label('Expense Account')
                    ->preload()
                    ->searchable()
                    ->options(ChartOfAccount::where('account_type', 'expense')->get()->pluck('name', 'id')) ,
                Forms\Components\Select::make('asset_chart_of_account_id')
                    ->label('Asset')
                    ->preload()
                    ->searchable()
                    ->options(ChartOfAccount::where('account_type', 'asset')->get()->pluck('name', 'id')),
                Forms\Components\TextInput::make('amount')
                    ->required()
                    ->numeric(),
                Forms\Components\DatePicker::make('date')
                    ->native(false)
                    ->required(),
                Select::make('recurring')
                        ->options([
                            '1' => 'Yes',
                            '0' => 'No',
                        ])
                        ->required()
                        ->live()
                        ->afterStateUpdated(fn (Select $component) => $component
                            ->getContainer()
                            ->getComponent('dynamicTypeFields')
                            ->getChildComponentContainer()
                            ->fill()),


                            Grid::make(2)
                                ->schema(fn (Get $get): array => match ($get('recurring')) {
                                    '1' => [
                                        Forms\Components\TextInput::make('recur_frequency')
                                            ->numeric(),
                                        Forms\Components\Select::make('recur_type')
                                            ->options(RepaymentFrequencyType::class),
                                        Forms\Components\DatePicker::make('recur_start_date')
                                            ->native(false),
                                        Forms\Components\DatePicker::make('recur_end_date')
                                            ->native(false),


                                    ],
                                    '0' =>[],
                                    default => [],
                                })
                                ->key('dynamicTypeFields'),
                    CuratorPicker::make('files')
                    ->label('Upload Files')
                    ->pathGenerator(DatePathGenerator::class)
                    ,
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_by.fullname')
                    ->label('Created By')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('description')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount')
                    ->numeric()
                    ->money('KES')
                    ->sortable()
                    ->summarize(Sum::make()
                        ->money('KES')
                        ->label('Total Expenses')),
                Tables\Columns\TextColumn::make('date')
                    ->date()
                    ->sortable(),
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
                DateRangeFilter::make('date')
                                ->label('Date')
                                ->withIndicator(),
            ], layout: FiltersLayout::AboveContent)
            ->actions([
                ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                ])


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
                    Section::make('Expense Details')
                    ->schema([
                        infolists\Components\ImageEntry::make('files')
                            ->label('image')
                            ->getStateUsing(function($record) {
                                $media = Media::where('id', $record->files)->first();
                                return $media->path;
                            }),

                        Infolists\Components\TextEntry::make('name')
                            ->label('Description')
                            ->badge()
                            ->color('info')
                            ,
                        Infolists\Components\TextEntry::make('expense_chart.name')
                            ->label('Expense Account')
                            ->badge()
                            ->color('info'),
                        Infolists\Components\TextEntry::make('asset_chart.name')
                            ->label('Asset Account')
                            ->badge()
                            ->color('info'),
                        Infolists\Components\TextEntry::make('amount')
                            ->money(Currency::where('is_default', 1)->first()->symbol)
                            ->badge()
                            ->color('success'),
                        Infolists\Components\TextEntry::make('created_by.fullname')
                            ->label('Created By')
                            ->badge()
                            ->color('info'),


                    ])->columns(3),
                ]),
                    Split::make([
                    Section::make('Recurring')
                    ->schema([
                        Infolists\Components\TextEntry::make('date')
                            ,
                        Infolists\Components\TextEntry::make('recurring')
                            ,
                        Infolists\Components\TextEntry::make('recur_frequency')
                            ,
                        Infolists\Components\TextEntry::make('recur_start_date')
                            ,
                        Infolists\Components\TextEntry::make('recur_end_date')
                            ,
                        Infolists\Components\TextEntry::make('recur_next_date')
                            ,
                        Infolists\Components\TextEntry::make('recur_type')
                            ,
                    ])->columns(3),
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
            'index' => Pages\ListExpenses::route('/'),
            'create' => Pages\CreateExpense::route('/create'),
            'edit' => Pages\EditExpense::route('/{record}/edit'),
            'view' => Pages\ViewExpense::route('/{record}'),
        ];
    }
}
