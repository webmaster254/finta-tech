<?php

namespace App\Filament\Pages;

use Carbon\Carbon;
use Filament\Pages\Page;
use App\Models\Loan\Loan;
use Filament\Tables\Table;
use App\Events\LoanDisbursed;
use App\Models\ChartOfAccount;
use Filament\Tables\Actions\Action;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Select;
use App\Filament\Exports\LoanExporter;
use App\Filament\Imports\LoanImporter;
use Filament\Forms\Components\Repeater;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\ActionGroup;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Tables\Actions\ExportAction;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\Wizard\Step;
use Filament\Tables\Columns\Summarizers\Sum;
use App\Jobs\SendLoanDisbursedNotificationJob;
use Filament\Tables\Concerns\InteractsWithTable;
use Awcodes\Curator\Components\Forms\CuratorPicker;
use Filament\Tables\Columns\Summarizers\Summarizer;
use Joaopaulolndev\FilamentPdfViewer\Forms\Components\PdfViewerField;

class ListApprovedLoans extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $view = 'filament.pages.list-approved-loans';

    protected static ?string $navigationGroup = 'Loans Management';
    protected static ?string $navigationLabel = 'Disburse loans';
    protected  ?string $heading = 'Approved Loans';
    protected static ?int $navigationSort = 4;

    public static function getNavigationBadge(): ?string
    {
        return  Loan::where('status', 'approved')->count();
    }
    public static function table(Table $table): Table
    {
        return $table
            ->query(Loan::query()->where('status', 'approved'))
            ->columns([
                TextColumn::make('loan_account_number')
                        ->label('Loan Account No')
                        ->sortable(),
                TextColumn::make('loan_officer.full_name')
                        ->sortable(),
                TextColumn::make('client.full_name')
                        ->sortable()
                        ->searchable(['first_name', 'middle_name', 'last_name']),
                TextColumn::make('approved_amount')
                        ->label('Approved Amount')
                        ->money('KES')
                        ->sortable()
                        ->summarize(Sum::make()
                                ->label('Total Approved Amount')
                                ->money('KES'))
                        ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('loan_product.name')
                        ->label('Loan Product')
                        ->sortable(),
                TextColumn::make('client.account_number')
                        ->label('Account Number')
                        ->sortable()
                        ->searchable(),
                TextColumn::make('client.mobile')
                        ->label('Phone Number')
                        ->searchable(),
                TextColumn::make('status')
                        ->badge()
                        ->searchable(),
                TextColumn::make('created_at')
                        ->dateTime()
                        ->sortable()
                        ->toggleable(isToggledHiddenByDefault: true),
                ])->striped()
                    ->defaultSort('created_at', 'desc')
                ->filters([
                    
                ])
            ->headerActions([])
            ->actions([
                ActionGroup::make([
                Action::make('disburse')
                    ->label('Disburse Loan')
                    ->icon('heroicon-o-check')
                    ->modalDescription('Are you sure you want to disburse this loan?')
                    ->color('success')
                    ->action(function (Loan $record, array $formdata) {
                        dd($formdata);
                        $data = [
                            'approved_amount' => $record->approved_amount,
                            'disbursed_by_user_id' => Auth::id(),
                            'disbursed_on_date' => Carbon::now(),
                            'fund_id' => $formdata['fund_source_id'],
                            'first_payment_date' => $formdata['first_repayment_date'],
                        ];
                        $record->disburseLoan($data,$record);
                        $record->update([
                                'first_payment_date' => $formdata['first_repayment_date'],
                        ]);
                        $record->save();
                        event(new LoanDisbursed($record));
                        SendLoanDisbursedNotificationJob::dispatch($record);
                           Notification::make()
                             ->title('Loan Disbursed Successfully')
                             ->success()
                             ->body('The Loan has been disbursed ')
                             ->send();
                       
                    })
                    ->modalWidth(MaxWidth::SevenExtraLarge)
                    ->fillForm(fn (Loan $record): array => [
                        'approved_amount' => $record->approved_amount,
                        'loan_product_id' => $record->loan_product->name,
                        'client_id' => $record->client->full_name,
                        'loan_account_number' => $record->loan_account_number,
                        'client_type_id' => $record->client_type->name,
                        'term' => $record->loan_term,
                        'repayment_frequency' => $record->repayment_frequency,
                        'repayment_frequency_type' => $record->repayment_frequency_type,
                        'interest_rate' => $record->interest_rate,
                        'interest_rate_type' => $record->interest_rate_type,
                        'interest_methodology' => $record->interest_methodology,
                        'amortization_method' => $record->amortization_method,
                        'first_repayment_date' => $record->expected_first_payment_date,
                        'status' => $record->status,
                        'guarantors' => $record->guarantors && $record->guarantors->count() > 0 ? $record->guarantors->map(function($guarantor) {
                            return [
                                'first_name' => $guarantor->first_name,
                                'last_name' => $guarantor->last_name,
                                'middle_name' => $guarantor->middle_name,
                                'mobile' => $guarantor->mobile,
                                'email' => $guarantor->email,
                                'id_number' => $guarantor->id_number,
                                'address' => $guarantor->address, 
                                'guaranteed_amount' => $guarantor->guaranteed_amount,
                            ];
                        }) : [],
                        'collaterals' => $record->collateral && $record->collateral->count() > 0 ? $record->collateral->map(function($cl) {
                            // Ensure the relationship is loaded
                            $cl->load('collateral_type');
                            return [
                                'collateral_type_id' => $cl->collateral_type ? $cl->collateral_type->name : '',
                                'description' => $cl->description ?? '',
                                'value' => $cl->value ?? 0,
                                'forced_value' => $cl->forced_value ?? 0,
                                'status' => $cl->status,
                                'file' => $cl->file ?? '',
                            ];
                        }) : [],
                        // 'files' => $record->files && $record->files->count() > 0 ? $record->files->map(function($file) {
                        //     return [
                        //         'name' => $file->name,
                        //         'description' => $file->description ?? '',
                        //         'file' => $file->file,
                        //     ];
                        // }) : [],
                    ])
                    
                    ->steps([
                                Step::make('Loan information')
                                ->schema([
                                        TextInput::make('approved_amount')
                                                ->label('Approved Amount')
                                                ->disabled()
                                                ->numeric(),
                                        TextInput::make('loan_account_number')
                                                ->label('Loan Account Number')
                                                ->disabled(),
                                        TextInput::make('client_type_id')
                                                ->label('Client Type')
                                                ->disabled(),
                                        TextInput::make('client_id')
                                                ->label('Client name')
                                                ->disabled(),
                                        TextInput::make('loan_product_id')
                                                ->label('Loan Product')
                                                ->disabled(),
                                        TextInput::make('term')
                                                ->label('Loan Term')
                                                ->suffix('days')
                                                ->disabled(),
                                        TextInput::make('repayment_frequency')
                                                ->label('Repayment Frequency')
                                                ->disabled(),
                                        TextInput::make('repayment_frequency_type')
                                                ->label('Repayment Frequency Type')
                                                ->disabled(),
                                        TextInput::make('interest_rate')
                                                ->label('Interest Rate')
                                                ->suffix('%')
                                                ->disabled(),
                                        TextInput::make('interest_rate_type')
                                                ->label('Interest Rate Type')
                                                ->disabled(),
                                        TextInput::make('interest_methodology')
                                                ->label('Interest Methodology')
                                                ->disabled(),
                                        TextInput::make('amortization_method')
                                                ->label('Amortization Method')
                                                ->disabled(),
                                        DatePicker::make('first_repayment_date')
                                                ->label('First Repayment Date')
                                                ->disabled(),
                                        TextInput::make('status')
                                                ->label('Status')
                                                ->disabled(),
                                ])->columns(3),

                                Step::make('Guarantors')
                                ->schema([
                                        Repeater::make('guarantors')
                                        ->schema([
                                                TextInput::make('first_name')
                                                ->label('First Name')
                                                ->disabled(),
                                                TextInput::make('last_name')
                                                ->label('Last Name')
                                                ->disabled(),
                                                TextInput::make('middle_name')
                                                ->label('Middle Name')
                                                ->disabled(),
                                                TextInput::make('mobile')
                                                ->label('Phone Number')
                                                ->disabled(),
                                                TextInput::make('email')
                                                ->label('Email')
                                                ->disabled(),
                                                TextInput::make('id_number')
                                                ->label('ID Number')
                                                ->disabled(),
                                                TextInput::make('address')
                                                ->label('Address')
                                                ->disabled(),
                                                TextInput::make('guaranteed_amount')
                                                ->label('Guaranteed Amount')
                                                ->disabled(),
                                        ])
                                        ->columns(3)
                                        ->itemLabel(fn (array $state): ?string => $state['first_name'] ?? null)
                                        ->required(),
                                ]),
                                Step::make('Collaterals')
                                        ->schema([
                                                Repeater::make('collaterals')
                                                ->schema([
                                                        TextInput::make('collateral_type_id')
                                                                ->label('Collateral Type')
                                                                ->disabled(),
                                                        TextInput::make('description')
                                                                ->label('Description')
                                                                ->disabled(),
                                                        TextInput::make('value')
                                                                ->label('Value')
                                                                ->disabled(),
                                                        TextInput::make('forced_value')
                                                                ->label('Forced Value')
                                                                ->disabled(),
                                                        FileUpload::make('file')
                                                                ->label('File')
                                                                ->disabled(),
                                                ])
                                                ->columns(2)
                                                ->itemLabel(fn (array $state): ?string => $state['collateral_type_id'] ?? null)
                                                ->required(),
                                        ]),
                                Step::make('fund')
                                        ->description('GL  Account')
                                        ->schema([
                                                Select::make('fund_source_id')
                                                        ->label('Fund Source')
                                                        ->options(ChartOfAccount::where('category', 'asset')->get()->pluck('name', 'id'))
                                                        ->preload()
                                                        ->searchable()
                                                        ->required(),
                                        ]),
                                // Step::make('Files')
                                //         ->description('Required Documents')
                                //         ->schema([
                                //                 Repeater::make('files')
                                //                 ->schema([
                                //                         TextInput::make('name')
                                //                                 ->label('Name')
                                //                                 ->required(),
                                //                         TextInput::make('description')
                                //                                 ->label('Description')
                                //                                 ->required(),
                                //                         PdfViewerField::make('file')
                                //                                 ->label('View the File')
                                //                                 ->minHeight('40svh')
                                //                                 ->required(),
                                //                 ])
                                //                 ->columns(2)
                                //                 ->minItems(1)
                                //                 ->itemLabel(fn (array $state): ?string => $state['name'] ?? null)
                                //                 ->required(),
                                //         ]),
                         ]),
                   

                // Action::make('undisburse')
                //     ->label('Undisburse')
                //     ->icon('heroicon-o-x-circle')
                //     ->requiresConfirmation(),
                         ]),
            ]);
    }

    public static function getLoanInformation(): array
    {
        return [
                TextInput::make('approved_amount')
                        ->label('Approved Amount')
                        ->required()
                        ->disabled()
                        ->numeric(),
                TextInput::make('loan_account_number')
                        ->label('Loan Account Number')
                        ->required()
                        ->disabled(),
                TextInput::make('client_type')
                        ->label('Client Type')
                        ->required(),
                TextInput::make('client_id')
                        ->label('Client name')
                        ->required()
                        ->disabled(),
                TextInput::make('loan_product_id')
                        ->label('Loan Product')
                        ->required()
                        ->disabled(),
                TextInput::make('term')
                        ->label('Loan Term')
                        ->disabled()
                        ->required(),
                TextInput::make('repayment_frequency')
                        ->label('Repayment Frequency')
                        ->required()
                        ->disabled(),
                TextInput::make('repayment_frequency_type')
                        ->label('Repayment Frequency Type')
                        ->required()
                        ->disabled(),
                TextInput::make('interest_rate')
                        ->label('Interest Rate')
                        ->required()
                        ->disabled(),
                TextInput::make('interest_rate_type')
                        ->label('Interest Rate Type')
                        ->required()
                        ->disabled(),
                TextInput::make('interest_methodology')
                        ->label('Interest Methodology')
                        ->required()
                        ->disabled(),
                TextInput::make('amortization_method')
                        ->label('Amortization Method')
                        ->required()
                        ->disabled(),
                TextInput::make('first_repayment_date')
                        ->label('First Repayment Date')
                        ->required()
                        ->disabled(),
                TextInput::make('status')
                        ->label('Status')
                        ->required()
                        ->disabled(),
        ];
    }
}
