<?php

namespace App\Filament\Pages;


use Filament\Pages\Page;

use Filament\Infolists\Infolist;
use Filament\Support\Colors\Color;
use App\Infolists\Components\ReportEntry;
use Filament\Infolists\Components\Section;
use App\Filament\Pages\Reports\BalanceSheet;
use App\Filament\Pages\Reports\TrialBalance;
use App\Filament\Pages\Reports\AccountBalances;
use App\Filament\Pages\Reports\IncomeStatement;
use App\Filament\Pages\Reports\CashFlowStatement;
use App\Filament\Pages\Reports\AccountTransactions;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;

class Reports extends Page
{
    use HasPageShield;
    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';
    protected static ?string $navigationGroup = 'Reports';

    protected static string $view = 'filament.pages.reports';

    public function reportsInfolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->state([])
            ->schema([
                Section::make('Financial Statements')
                    ->aside()
                    ->description('Key financial statements that provide a snapshot of your company’s financial health.')
                    ->extraAttributes(['class' => 'es-report-card'])
                    ->schema([
                        ReportEntry::make('income_statement')
                            ->hiddenLabel()
                            ->heading('Income Statement')
                            ->description('Tracks revenue and expenses to show profit or loss over a specific period of time.')
                            ->icon('heroicon-o-chart-bar')
                            ->iconColor(Color::Indigo)
                            ->url(IncomeStatement::getUrl()),
                        ReportEntry::make('balance_sheet')
                            ->hiddenLabel()
                            ->heading('Balance Sheet')
                            ->description('Snapshot of assets, liabilities, and equity at a specific point in time.')
                            ->icon('heroicon-o-clipboard-document-list')
                            ->iconColor(Color::Emerald)
                            ->url(BalanceSheet::getUrl()),
                        ReportEntry::make('cash_flow_statement')
                            ->hiddenLabel()
                            ->heading('Cash Flow Statement')
                            ->description('Shows cash inflows and outflows over a specific period of time.')
                            ->icon('heroicon-o-currency-dollar')
                            ->iconColor(Color::Cyan)
                            ->url(CashFlowStatement::getUrl()),
                    ]),
                Section::make('Detailed Reports')
                    ->aside()
                    ->description('Dig into the details of your company’s transactions, balances, and accounts.')
                    ->extraAttributes(['class' => 'es-report-card'])
                    ->schema([
                        ReportEntry::make('account_balances')
                            ->hiddenLabel()
                            ->heading('Account Balances')
                            ->description('Summary view of balances and activity for all accounts.')
                            ->icon('heroicon-o-currency-dollar')
                            ->iconColor(Color::Teal)
                            ->url(AccountBalances::getUrl()),
                        ReportEntry::make('trial_balance')
                            ->hiddenLabel()
                            ->heading('Trial Balance')
                            ->description('The sum of all debit and credit balances for all accounts on a single day. This helps to ensure that the books are in balance.')
                            ->icon('heroicon-o-scale')
                            ->iconColor(Color::Sky)
                            ->url(TrialBalance::getUrl()),
                        ReportEntry::make('account_transactions')
                            ->hiddenLabel()
                            ->heading('Account Transactions')
                            ->description('A record of all transactions for a company. The general ledger is the core of a company\'s financial records.')
                            ->icon('heroicon-o-adjustments-horizontal')
                            ->iconColor(Color::Amber)
                            ->url("#"),
                    ]),
            ]);
    }
}

