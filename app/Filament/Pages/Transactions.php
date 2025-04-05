<?php

namespace App\Filament\Pages;

use Exception;
use Filament\Forms;
use Filament\Tables;
use Filament\Actions;
use App\Models\Branch;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms\Form;
use Filament\Pages\Page;

use Filament\Tables\Table;
use App\Facades\Accounting;
use App\Models\BankAccount;
use App\Models\Transaction;
use Illuminate\Support\Str;
use App\Enums\TransactionType;
use App\Models\ChartOfAccount;
use Filament\Facades\Filament;
use Illuminate\Support\Carbon;
use App\Enums\JournalEntryType;
use Awcodes\TableRepeater\Header;
use Filament\Support\Colors\Color;
use Illuminate\Support\Collection;
use App\Enums\ChartAccountCategory;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Tabs;
use Illuminate\Contracts\View\View;
use Filament\Support\Enums\IconSize;
use Filament\Support\Enums\MaxWidth;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\FontWeight;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Contracts\HasTable;
use App\Concerns\HasJournalEntryActions;
use Filament\Forms\Components\TextInput;
use Filament\Support\Enums\IconPosition;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;
use App\Utilities\Currency\CurrencyAccessor;
use App\Utilities\Currency\CurrencyConverter;
use Filament\Tables\Concerns\InteractsWithTable;


use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use App\Filament\Forms\Components\JournalEntryRepeater;
use Filament\Forms\Components\Actions\Action as FormAction;

/**
 * @property Form $form
 */
class Transactions extends Page implements HasTable
{
    use HasPageShield;
    use HasJournalEntryActions;
    use InteractsWithTable;

    protected static string $view = 'filament.pages.transactions';

    protected static ?string $model = Transaction::class;

    protected static ?string $navigationGroup = 'Accounting';

    public ?string $bankAccountIdFiltered = 'all';

    public string $fiscalYearStartDate = '';

    public string $fiscalYearEndDate = '';

    public function mount(): void
    {
        /** @var Company $company */
        $branch = Filament::getTenant();
        // $this->fiscalYearStartDate = $this->fiscalYearStartDate();
        // $this->fiscalYearEndDate = $this->fiscalYearEndDate();
    }
    public function fiscalYearEndDate(): string
    {
        $today = now();
        $fiscalYearEndThisYear = Carbon::createFromDate($today->year, setting('fiscal_year_end_month'), setting('fiscal_year_end_day'));

        if ($today->gt($fiscalYearEndThisYear)) {
            return $fiscalYearEndThisYear->copy()->addYear()->toDateString();
        }

        return $fiscalYearEndThisYear->toDateString();
    }


    public function fiscalYearStartDate(): string
    {
        return Carbon::parse($this->fiscalYearEndDate())->subYear()->addDay()->toDateString();
    }

    public static function getModel(): string
    {
        return static::$model;
    }

    public static function getEloquentQuery(): Builder
    {
        return static::getModel()::query();
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->buildTransactionAction('addIncome', 'Add Income', TransactionType::Deposit),
            $this->buildTransactionAction('addExpense', 'Add Expense', TransactionType::Withdrawal),
            Actions\ActionGroup::make([
                Actions\CreateAction::make('addJournalTransaction')
                    ->label('Add Journal Transaction')
                    ->fillForm(fn (): array => $this->getFormDefaultsForType(TransactionType::Journal))
                    ->modalWidth(MaxWidth::Screen)
                    ->model(static::getModel())
                    ->form(fn (Form $form) => $this->journalTransactionForm($form))
                    ->modalSubmitAction(fn (Actions\StaticAction $action) => $action->disabled(! $this->isJournalEntryBalanced()))
                    ->groupedIcon(null)
                    ->modalHeading('Journal Entry')
                    ->mutateFormDataUsing(static fn (array $data) => array_merge($data, ['type' => TransactionType::Journal]))
                    ->afterFormFilled(fn () => $this->resetJournalEntryAmounts())
                    ->after(fn (Transaction $transaction) => $transaction->updateAmountIfBalanced()),
                Actions\Action::make('connectBank')
                    ->label('Connect Your Bank'),
                   // ->url(ConnectedAccount::getUrl()),
            ])
                ->label('More')
                ->button()
                ->outlined()
                ->dropdownWidth('max-w-fit')
                ->dropdownPlacement('bottom-end')
                ->icon('heroicon-c-chevron-down')
                ->iconSize(IconSize::Small)
                ->iconPosition(IconPosition::After),
        ];
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('bankAccountIdFiltered')
                    ->label('Account')
                    ->hiddenLabel()
                    ->allowHtml()
                    ->options(fn () => $this->getBankAccountOptions(true, true))
                    ->live()
                    ->selectablePlaceholder(false)
                    ->columnSpan(4),
            ])
            ->columns(14);
    }

    public function transactionForm(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\DatePicker::make('posted_at')
                    ->label('Date')
                    ->native(false)
                    ->required(),
                Forms\Components\TextInput::make('description')
                    ->label('Description'),
                Forms\Components\Select::make('bank_account_id')
                    ->label('Account')
                    ->options(fn () => $this->getBankAccountOptions())
                    ->live()
                    ->searchable()
                    ->afterStateUpdated(function (Set $set, $state, $old, Get $get) {
                        $amount = CurrencyConverter::convertAndSet(
                            BankAccount::find($state)->account->currency_code,
                            BankAccount::find($old)->account->currency_code ?? CurrencyAccessor::getDefaultCurrency(),
                            $get('amount')
                        );

                        if ($amount !== null) {
                            $set('amount', $amount);
                        }
                    })
                    ->required(),
                Forms\Components\Select::make('type')
                    ->label('Type')
                    ->live()
                    ->options([
                        TransactionType::Deposit->value => TransactionType::Deposit->getLabel(),
                        TransactionType::Withdrawal->value => TransactionType::Withdrawal->getLabel(),
                    ])
                    ->required()
                    ->afterStateUpdated(static fn (Forms\Set $set, $state) => $set('account_id', static::getUncategorizedAccountByType(TransactionType::parse($state))?->id)),
                Forms\Components\TextInput::make('amount')
                    ->label('Amount')
                    ->prefix(static fn (Forms\Get $get) => BankAccount::find($get('bank_account_id'))?->chartOfAccount?->currency_code ?? CurrencyAccessor::getDefaultCurrency())
                    ->required(),
                Forms\Components\Select::make('account_id')
                    ->label('Category')
                    ->options(fn (Forms\Get $get) => $this->getChartAccountOptions(type: TransactionType::parse($get('type')), nominalAccountsOnly: true))
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\Textarea::make('notes')
                    ->label('Notes')
                    ->autosize()
                    ->rows(10)
                    ->columnSpanFull(),
            ])
            ->columns();
    }

    public function journalTransactionForm(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('Tabs')
                    ->contained(false)
                    ->tabs([
                        $this->getJournalTransactionFormEditTab(),
                        $this->getJournalTransactionFormNotesTab(),
                    ]),
            ])
            ->columns(1);
    }

    /**
     * @throws Exception
     */
    public function table(Table $table): Table
    {
        return $table
            ->query(static::getEloquentQuery())
            ->modifyQueryUsing(function (Builder $query) {
                if ($this->bankAccountIdFiltered !== 'all') {
                    $query->where('bank_account_id', $this->bankAccountIdFiltered);
                }
            })
            ->columns([
                Tables\Columns\TextColumn::make('posted_at')
                    ->label('Date Posted')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('description')
                    ->limit(30)
                    ->label('Description'),
                Tables\Columns\TextColumn::make('bankAccount.chartOfAccount.name')
                    ->label('Account'),
                Tables\Columns\TextColumn::make('account.name')
                    ->label('Category')
                    ->state(static fn (Transaction $transaction) => $transaction->account->name ?? 'Journal Entry'),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Amount')
                    ->weight(static fn (Transaction $transaction) => $transaction->reviewed ? null : FontWeight::SemiBold)
                    ->color(
                        static fn (Transaction $transaction) => match ($transaction->type) {
                            TransactionType::Deposit => Color::rgb('rgb(' . Color::Green[700] . ')'),
                            TransactionType::Journal => 'primary',
                            default => null,
                        }
                    )
                    ->prefix(static fn (Transaction $transaction) => $transaction->bankAccount->account->currency_code ?? CurrencyAccessor::getDefaultCurrency(), true),
            ])
            ->recordClasses(static fn (Transaction $transaction) => $transaction->reviewed ? 'bg-primary-300/10' : null)
            ->defaultSort('posted_at', 'desc')
            ->filters([


            ], layout: Tables\Enums\FiltersLayout::Modal)
            ->deferFilters()
            ->filtersFormColumns(2)
            ->filtersTriggerAction(
                fn (Tables\Actions\Action $action) => $action
                    ->stickyModalHeader()
                    ->stickyModalFooter()
                    ->modalWidth(MaxWidth::ThreeExtraLarge)
                    ->modalFooterActionsAlignment(Alignment::End)
                    ->modalCancelAction(false)
                    ->extraModalFooterActions(function (Table $table) use ($action) {
                        return [
                            $table->getFiltersApplyAction()
                                ->close(),
                            Actions\StaticAction::make('cancel')
                                ->label($action->getModalCancelActionLabel())
                                ->button()
                                ->close()
                                ->color('gray'),
                            Tables\Actions\Action::make('resetFilters')
                                ->label(__('Clear All'))
                                ->color('primary')
                                ->link()
                                ->extraAttributes([
                                    'class' => 'me-auto',
                                ])
                                ->action('resetTableFiltersForm'),
                        ];
                    })
            )
            ->actions([
                Tables\Actions\Action::make('markAsReviewed')
                    ->label('Mark as Reviewed')
                    ->view('filament.components.tables.actions.mark-as-reviewed')
                    ->icon(static fn (Transaction $transaction) => $transaction->reviewed ? 'heroicon-s-check-circle' : 'heroicon-o-check-circle')
                    ->color(static fn (Transaction $transaction, Tables\Actions\Action $action) => match (static::determineTransactionState($transaction, $action)) {
                        'reviewed' => 'primary',
                        'unreviewed' => Color::rgb('rgb(' . Color::Gray[600] . ')'),
                        'uncategorized' => 'gray',
                    })
                    ->tooltip(static fn (Transaction $transaction, Tables\Actions\Action $action) => match (static::determineTransactionState($transaction, $action)) {
                        'reviewed' => 'Reviewed',
                        'unreviewed' => 'Mark as Reviewed',
                        'uncategorized' => 'Categorize first to mark as reviewed',
                    })
                    ->disabled(fn (Transaction $transaction): bool => $transaction->isUncategorized())
                    ->action(fn (Transaction $transaction) => $transaction->update(['reviewed' => ! $transaction->reviewed])),
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make('updateTransaction')
                        ->label('Edit Transaction')
                        ->modalHeading('Edit Transaction')
                        ->modalWidth(MaxWidth::ThreeExtraLarge)
                        ->form(fn (Form $form) => $this->transactionForm($form))
                        ->hidden(static fn (Transaction $transaction) => $transaction->type->isJournal()),
                    Tables\Actions\EditAction::make('updateJournalTransaction')
                        ->label('Edit Journal Transaction')
                        ->modalHeading('Journal Entry')
                        ->modalWidth(MaxWidth::Screen)
                        ->form(fn (Form $form) => $this->journalTransactionForm($form))
                        ->afterFormFilled(function (Transaction $transaction) {
                            $debitAmounts = $transaction->journalEntries->sumDebits()->getAmount();
                            $creditAmounts = $transaction->journalEntries->sumCredits()->getAmount();

                            $this->setDebitAmount($debitAmounts);
                            $this->setCreditAmount($creditAmounts);
                        })
                        ->modalSubmitAction(fn (Actions\StaticAction $action) => $action->disabled(! $this->isJournalEntryBalanced()))
                        ->after(fn (Transaction $transaction) => $transaction->updateAmountIfBalanced())
                        ->visible(static fn (Transaction $transaction) => $transaction->type->isJournal()),
                    Tables\Actions\DeleteAction::make(),
                    Tables\Actions\ReplicateAction::make()
                        ->excludeAttributes(['created_by', 'updated_by', 'created_at', 'updated_at'])
                        ->modal(false)
                        ->beforeReplicaSaved(static function (Transaction $transaction) {
                            $transaction->description = '(Copy of) ' . $transaction->description;
                        }),
                ])
                    ->dropdownPlacement('bottom-start')
                    ->dropdownWidth('max-w-fit'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    protected function buildTransactionAction(string $name, string $label, TransactionType $type): Actions\CreateAction
    {
        return Actions\CreateAction::make($name)
            ->label($label)
            ->modalWidth(MaxWidth::ThreeExtraLarge)
            ->model(static::getModel())
            ->fillForm(fn (): array => $this->getFormDefaultsForType($type))
            ->form(fn (Form $form) => $this->transactionForm($form))
            ->button()
            ->outlined();
    }

    protected function getFormDefaultsForType(TransactionType $type): array
    {
        $commonDefaults = [
            'posted_at' => now()->format('Y-m-d'),
        ];

        return match ($type) {
            TransactionType::Deposit, TransactionType::Withdrawal => array_merge($commonDefaults, $this->transactionDefaults($type)),
            TransactionType::Journal => array_merge($commonDefaults, $this->journalEntryDefaults()),
        };
    }

    protected function journalEntryDefaults(): array
    {
        return [
            'journalEntries' => [
                $this->defaultEntry(JournalEntryType::Debit),
                $this->defaultEntry(JournalEntryType::Credit),
            ],
        ];
    }

    protected function defaultEntry(JournalEntryType $journalEntryType): array
    {
        return [
            'type' => $journalEntryType,
            'account_id' => static::getUncategorizedAccountByType($journalEntryType->isDebit() ? TransactionType::Withdrawal : TransactionType::Deposit)?->id,
            'amount' => '0.00',
        ];
    }

    protected function transactionDefaults(TransactionType $type): array
    {
        return [
            'type' => $type,
            'bank_account_id' => BankAccount::where('enabled', true)->first()?->id,
            'amount' => '0.00',
            'account_id' => static::getUncategorizedAccountByType($type)?->id,
        ];
    }

    protected static function getUncategorizedAccountByType(TransactionType $type): ?ChartOfAccount
    {
        [$category, $accountName] = match ($type) {
            TransactionType::Deposit => [ChartAccountCategory::Revenue, 'Uncategorized Income'],
            TransactionType::Withdrawal => [ChartAccountCategory::Expense, 'Uncategorized Expense'],
            default => [null, null],
        };

        return ChartOfAccount::where('category', $category)
            ->where('name', $accountName)
            ->first();
    }

    protected function getJournalTransactionFormEditTab(): Tab
    {
        return Tab::make('Edit')
            ->label('Edit')
            ->icon('heroicon-o-pencil-square')
            ->schema([
                $this->getTransactionDetailsGrid(),
                $this->getJournalEntriesTableRepeater(),
            ]);
    }

    protected function getJournalTransactionFormNotesTab(): Tab
    {
        return Tab::make('Notes')
            ->label('Notes')
            ->icon('heroicon-o-clipboard')
            ->id('notes')
            ->schema([
                $this->getTransactionDetailsGrid(),
                Textarea::make('notes')
                    ->label('Notes')
                    ->rows(10)
                    ->autosize(),
            ]);
    }

    protected function getTransactionDetailsGrid(): Grid
    {
        return Grid::make(8)
            ->schema([
                DatePicker::make('posted_at')
                    ->label('Date')
                    ->native(false)
                    ->softRequired()
                    ->displayFormat('Y-m-d'),
                TextInput::make('description')
                    ->label('Description')
                    ->columnSpan(2),
            ]);
    }

    protected function getJournalEntriesTableRepeater(): JournalEntryRepeater
    {
        return JournalEntryRepeater::make('journalEntries')
            ->relationship('journalEntries')
            ->hiddenLabel()
            ->columns(4)
            ->headers($this->getJournalEntriesTableRepeaterHeaders())
            ->schema($this->getJournalEntriesTableRepeaterSchema())
            ->streamlined()
            ->deletable(fn (JournalEntryRepeater $repeater) => $repeater->getItemsCount() > 2)
            ->deleteAction(function (Forms\Components\Actions\Action $action) {
                return $action
                    ->action(function (array $arguments, JournalEntryRepeater $component): void {
                        $items = $component->getState();

                        $amount = $items[$arguments['item']]['amount'];
                        $type = $items[$arguments['item']]['type'];

                        $this->updateJournalEntryAmount(JournalEntryType::parse($type), '0.00', $amount);

                        unset($items[$arguments['item']]);

                        $component->state($items);

                        $component->callAfterStateUpdated();
                    });
            })
            ->minItems(2)
            ->defaultItems(2)
            ->addable(false)
            ->footerItem(fn (): View => $this->getJournalTransactionModalFooter())
            ->extraActions([
                $this->buildAddJournalEntryAction(JournalEntryType::Debit),
                $this->buildAddJournalEntryAction(JournalEntryType::Credit),
            ]);
    }

    protected function getJournalEntriesTableRepeaterHeaders(): array
    {
        return [
            Header::make('type')
                ->width('150px')
                ->label('Type'),
            Header::make('description')
                ->width('320px')
                ->label('Description'),
            Header::make('account_id')
                ->width('320px')
                ->label('Account'),
            Header::make('amount')
                ->width('192px')
                ->label('Amount'),
        ];
    }

    protected function getJournalEntriesTableRepeaterSchema(): array
    {
        return [
            Hidden::make('branch_id')
              ->default(Filament::getTenant()->id),
             Select::make('type')
                ->label('Type')
                ->options(JournalEntryType::class)
                ->live()
                ->afterStateUpdated(function (Get $get, Set $set, ?JournalEntryType $state, ?JournalEntryType $old) {
                    $this->adjustJournalEntryAmountsForTypeChange(JournalEntryType::parse($state), JournalEntryType::parse($old), $get('amount'));
                })
                ->softRequired(),
            TextInput::make('description')
                ->label('Description'),
            Select::make('chart_of_account_id')
                ->label('Account')
                ->options(fn (): array => $this->getChartAccountOptions())
                ->live()
                ->softRequired()
                ->searchable(),
            TextInput::make('amount')
                ->label('Amount')
                ->live()
                ->prefix(CurrencyAccessor::getDefaultCurrency())
                ->afterStateUpdated(function (Get $get, Set $set, ?string $state, ?string $old) {
                    $this->updateJournalEntryAmount(JournalEntryType::parse($get('type')), $state, $old);
                })
                ->softRequired(),

        ];
    }

    protected function buildAddJournalEntryAction(JournalEntryType $type): FormAction
    {
        $typeLabel = $type->getLabel();

        return FormAction::make("add{$typeLabel}Entry")
            ->label("Add {$typeLabel} Entry")
            ->button()
            ->outlined()
            ->color($type->isDebit() ? 'primary' : 'gray')
            ->iconSize(IconSize::Small)
            ->iconPosition(IconPosition::Before)
            ->action(function (JournalEntryRepeater $component) use ($type) {
                $state = $component->getState();
                $newUuid = (string) Str::uuid();
                $state[$newUuid] = $this->defaultEntry($type);

                $component->state($state);
            });
    }

    public function getJournalTransactionModalFooter(): View
    {
        return view(
            'filament.components.actions.journal-entry-footer',
            [
                'debitAmount' => $this->getFormattedDebitAmount(),
                'creditAmount' => $this->getFormattedCreditAmount(),
                'difference' => $this->getFormattedBalanceDifference(),
                'isJournalBalanced' => $this->isJournalEntryBalanced(),
            ],
        );
    }

    /**
     * @throws Exception
     */
    protected function buildDateRangeFilter(string $fieldPrefix, string $label, bool $hasBottomBorder = false): Tables\Filters\Filter
    {
        return Tables\Filters\Filter::make($fieldPrefix)
            ->columnSpanFull()
            ->form([
                Grid::make()
                    ->live()
                    ->schema([
                        DateRangeFilter ::make("{$fieldPrefix}_date_range")
                            ->label($label)
                            ->native(false)
                            ->selectablePlaceholder(false)
                            ->placeholder('Select a date range')
                            ->startDateField("{$fieldPrefix}_start_date")
                            ->endDateField("{$fieldPrefix}_end_date"),
                        DatePicker::make("{$fieldPrefix}_start_date")
                            ->label("{$label} From")
                            ->displayFormat('Y-m-d')
                            ->native(false)
                            ->columnStart(1)
                            ->afterStateUpdated(static function (Set $set) use ($fieldPrefix) {
                                $set("{$fieldPrefix}_date_range", 'Custom');
                            }),
                        DatePicker::make("{$fieldPrefix}_end_date")
                            ->label("{$label} To")
                            ->native(false)
                            ->displayFormat('Y-m-d')
                            ->afterStateUpdated(static function (Set $set) use ($fieldPrefix) {
                                $set("{$fieldPrefix}_date_range", 'Custom');
                            }),
                    ])
                    ->extraAttributes($hasBottomBorder ? ['class' => 'border-b border-gray-200 dark:border-white/10 pb-8'] : []),
            ])
            ->query(function (Builder $query, array $data) use ($fieldPrefix): Builder {
                $query
                    ->when($data["{$fieldPrefix}_start_date"], fn (Builder $query, $startDate) => $query->whereDate($fieldPrefix, '>=', $startDate))
                    ->when($data["{$fieldPrefix}_end_date"], fn (Builder $query, $endDate) => $query->whereDate($fieldPrefix, '<=', $endDate));

                return $query;
            })
            ->indicateUsing(function (array $data) use ($fieldPrefix, $label): array {
                $indicators = [];

                $this->addIndicatorForDateRange($data, "{$fieldPrefix}_start_date", "{$fieldPrefix}_end_date", $label, $indicators);

                return $indicators;
            });

    }

    protected function addIndicatorForSingleSelection($data, $key, $label, &$indicators): void
    {
        if (filled($data[$key])) {
            $indicators[] = Tables\Filters\Indicator::make($label)
                ->removeField($key);
        }
    }

    protected function addMultipleSelectionIndicator($data, $key, callable $labelRetriever, $field, &$indicators): void
    {
        if (filled($data[$key])) {
            $labels = collect($data[$key])->map($labelRetriever);
            $additionalCount = $labels->count() - 1;
            $indicatorLabel = $additionalCount > 0 ? "{$labels->first()} + {$additionalCount}" : $labels->first();
            $indicators[] = Tables\Filters\Indicator::make($indicatorLabel)
                ->removeField($field);
        }
    }

    protected function addIndicatorForDateRange($data, $startKey, $endKey, $labelPrefix, &$indicators): void
    {
        $formattedStartDate = filled($data[$startKey]) ? Carbon::parse($data[$startKey])->toFormattedDateString() : null;
        $formattedEndDate = filled($data[$endKey]) ? Carbon::parse($data[$endKey])->toFormattedDateString() : null;
        if ($formattedStartDate && $formattedEndDate) {
            // If both start and end dates are set, show the combined date range as the indicator, no specific field needs to be removed since the entire filter will be removed
            $indicators[] = Tables\Filters\Indicator::make("{$labelPrefix}: {$formattedStartDate} - {$formattedEndDate}");
        } else {
            if ($formattedStartDate) {
                $indicators[] = Tables\Filters\Indicator::make("{$labelPrefix} After: {$formattedStartDate}")
                    ->removeField($startKey);
            }

            if ($formattedEndDate) {
                $indicators[] = Tables\Filters\Indicator::make("{$labelPrefix} Before: {$formattedEndDate}")
                    ->removeField($endKey);
            }
        }
    }

    protected static function determineTransactionState(Transaction $transaction, Tables\Actions\Action $action): string
    {
        if ($transaction->reviewed) {
            return 'reviewed';
        }

        if ($transaction->reviewed === false && $action->isEnabled()) {
            return 'unreviewed';
        }

        return 'uncategorized';
    }

    protected function getChartAccountOptions(?TransactionType $type = null, bool $nominalAccountsOnly = false): array
    {
        $excludedCategory = match ($type) {
            TransactionType::Deposit => ChartAccountCategory::Expense,
            TransactionType::Withdrawal => ChartAccountCategory::Revenue,
            default => null,
        };

return ChartOfAccount::query()
            ->when($nominalAccountsOnly, fn (Builder $query) => $query->whereNull('accountable_type'))
            ->when($excludedCategory, fn (Builder $query) => $query->whereNot('category', $excludedCategory))
            ->get()
            ->groupBy(fn (ChartOfAccount $account) => $account->category->getPluralLabel())
            ->map(fn (Collection $accounts) => $accounts->pluck('name', 'id'))
            ->toArray();
    }

    protected function getBankAccountOptions(?bool $onlyWithTransactions = null, bool $isFilter = false): array
    {
        $onlyWithTransactions ??= false;

        $options = $isFilter ? [
            '' => ['all' => "All Accounts <span class='float-right'></span>"],
        ] : [];

        $bankAccountOptions = BankAccount::with('chartOfAccount.subtype')
            ->when($onlyWithTransactions, fn (Builder $query) => $query->has('transactions'))
            ->get()
            ->groupBy('chartOfAccount.subtype.name')
            ->mapWithKeys(function (Collection $bankAccounts, string $subtype) use ($isFilter) {
                return [$subtype => $bankAccounts->mapWithKeys(static function (BankAccount $bankAccount) use ($isFilter) {
                    $label = $bankAccount->chartOfAccount?->name?? 'No Account';
                    if ($isFilter) {
                        $balance = optional($bankAccount->chartOfAccount)->ending_balance?->convert()->formatWithCode(true);
                        $label .= "<span class='float-right'>{$balance}</span>";
                    }

                    return [$bankAccount->id => $label];
                })];
            })
            ->toArray();

        return array_merge($options, $bankAccountOptions);
    }

    protected function getBalanceForAllAccounts(): string
    {
        return Accounting::getTotalBalanceForAllBankAccounts($this->fiscalYearStartDate, $this->fiscalYearEndDate)->format();
    }
}
