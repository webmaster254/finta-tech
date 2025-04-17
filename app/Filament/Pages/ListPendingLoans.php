<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\Loan\Loan;
use Filament\Tables\Table;
use App\Events\LoanDisbursed;
use Filament\Tables\Actions\Action;
use Filament\Support\Enums\MaxWidth;
use App\Filament\Exports\LoanExporter;
use App\Filament\Imports\LoanImporter;
use Filament\Forms\Components\Repeater;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\ExportAction;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\Wizard\Step;
use Filament\Tables\Columns\Summarizers\Sum;
use App\Jobs\SendLoanDisbursedNotificationJob;
use Filament\Tables\Concerns\InteractsWithTable;
use Awcodes\Curator\Components\Forms\CuratorPicker;
use Filament\Tables\Columns\Summarizers\Summarizer;
use Joaopaulolndev\FilamentPdfViewer\Forms\Components\PdfViewerField;

class ListPendingLoans extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $view = 'filament.pages.list-pending-loans';

    protected static ?string $navigationGroup = 'Loans Management';
    protected static ?string $navigationLabel = 'Approve loans';
    protected  ?string $heading = 'Pending Loans';
    protected static ?int $navigationSort = 2;

    public static function getNavigationBadge(): ?string
    {
        return  Loan::where('status', 'pending')->count();
    }
    public static function table(Table $table): Table
    {
        return $table
            ->query(Loan::query()->where('status', 'pending'))
            ->columns([
                TextColumn::make('loan_account_number')
                        ->label('Loan Account No')
                        ->sortable(),
                TextColumn::make('loan_officer.full_name')
                        ->label('Relationship Officer'),
                TextColumn::make('client.full_name')
                        ->sortable()
                        ->searchable(['first_name', 'middle_name', 'last_name']),
                TextColumn::make('client.account_number')
                        ->label('Account Number')
                        ->sortable()
                        ->searchable(),
                TextColumn::make('applied_amount')
                        ->label('Applied Amount')
                        ->money('KES')
                        ->sortable()
                        ->summarize(Sum::make()
                                ->label('Total Applied Amount')
                                ->money('KES')),
                TextColumn::make('loan_product.name')
                        ->label('Loan Product')
                        ->sortable(),
                
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
                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check')
                    ->requiresConfirmation()
                    ->modalHeading('Approve Loan')
                    ->modalDescription('Are you sure you want to approve this loan?')
                    ->modalWidth(MaxWidth::SevenExtraLarge)
                    ->action(function (Loan $record) {
                        $data = [
                                'approved_amount' => $record->principal,
                                'approved_by_user_id' => Auth::id(),
                                'approved_on_date' => Carbon::now(),
                            ];
                        $record->approveLoan($data);
                       
                        Notification::make()
                        ->title('Loan Approved Successfully')
                        ->success()
                        ->body('The Loan has been Approved ')
                        ->send();
                    })
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
                                'file' => $cl->file ?? '',
                            ];
                        }) : [],
                        'files' => $record->files && $record->files->count() > 0 ? $record->files->map(function($file) {
                            return [
                                'name' => $file->name,
                                'description' => $file->description ?? '',
                                'file' => $file->file,
                            ];
                        }) : [],
                    ])
                    ->steps([
                                Step::make('Loan information')
                                ->disabled()
                                ->schema([
                                        TextInput::make('client_type_id')
                                                ->label('Client Type')
                                                ->required(),
                                        TextInput::make('approved_amount')
                                                ->label('Approved Amount')
                                                ->required()
                                                ->numeric(),
                                        TextInput::make('loan_account_number')
                                                ->label('Loan Account Number')
                                                ->required(),
                                        
                                        TextInput::make('client_id')
                                                ->label('Client name')
                                                ->required()
                                                ->disabled(),
                                        TextInput::make('loan_product_id')
                                                ->label('Loan Product')
                                                ->required(),
                                        TextInput::make('term')
                                                ->label('Loan Term')
                                                ->suffix('days')
                                                ->required(),
                                        TextInput::make('repayment_frequency')
                                                ->label('Repayment Frequency')
                                                ->required(),
                                        TextInput::make('repayment_frequency_type')
                                                ->label('Repayment Frequency Type')
                                                ->required(),
                                        TextInput::make('interest_rate')
                                                ->label('Interest Rate')
                                                ->suffix('%')
                                                ->required(),
                                        TextInput::make('interest_rate_type')
                                                ->label('Interest Rate Type')
                                                ->required(),
                                        TextInput::make('interest_methodology')
                                                ->label('Interest Methodology')
                                                ->required(),
                                        TextInput::make('amortization_method')
                                                ->label('Amortization Method')
                                                ->required(),
                                        TextInput::make('first_repayment_date')
                                                ->label('First Repayment Date')
                                                ->required(),
                                        TextInput::make('status')
                                                ->label('Status')
                                                ->required(),
                                ])->columns(3),

                                Step::make('Guarantors')
                                ->disabled()
                                ->schema([
                                        Repeater::make('guarantors')
                                        ->schema([
                                                TextInput::make('first_name')
                                                ->label('First Name')
                                                ->required(),
                                                TextInput::make('last_name')
                                                ->label('Last Name')
                                                ->required(),
                                                TextInput::make('middle_name')
                                                ->label('Middle Name')
                                                ->required(),
                                                TextInput::make('mobile')
                                                ->label('Phone Number')
                                                ->required(),
                                                TextInput::make('email')
                                                ->label('Email'),
                                                TextInput::make('id_number')
                                                ->label('ID Number')
                                                ->required(),
                                                TextInput::make('address')
                                                ->label('Address'),
                                                TextInput::make('guaranteed_amount')
                                                ->label('Guaranteed Amount')
                                                ->required(),
                                        ])
                                        ->columns(3)
                                        ->itemLabel(fn (array $state): ?string => $state['first_name'] ?? null)
                                        ->required(),
                                ]),
                                Step::make('Collaterals')
                                        ->disabled()
                                        ->schema([
                                                Repeater::make('collaterals')
                                                ->schema([
                                                        TextInput::make('collateral_type_id')
                                                                ->label('Collateral Type')
                                                                ->required(),
                                                        TextInput::make('description')
                                                                ->label('Description')
                                                                ->required(),
                                                        TextInput::make('value')
                                                                ->label('Value')
                                                                ->required(),
                                                        CuratorPicker::make('file')
                                                                ->label('File')
                                                                ->required(),
                                                ])
                                                ->columns(2)
                                                ->itemLabel(fn (array $state): ?string => $state['collateral_type_id'] ?? null)
                                                ->required(),
                                        ]),
                                Step::make('Files')
                                        ->description('Required Documents')
                                        ->disabled()
                                        ->schema([
                                                Repeater::make('files')
                                                ->schema([
                                                        TextInput::make('name')
                                                                ->label('Name')
                                                                ->required(),
                                                        TextInput::make('description')
                                                                ->label('Description')
                                                                ->required(),
                                                        PdfViewerField::make('file')
                                                                ->label('View the File')
                                                                ->minHeight('40svh')
                                                                ->required(),
                                                ])
                                                ->columns(2)
                                                ->minItems(1)
                                                ->itemLabel(fn (array $state): ?string => $state['name'] ?? null)
                                                ->required(),
                                        ]),
                         ]),
                   
                Action::make('rts')
                    ->label('RTS')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(function (Loan $record) {
                        $record->status = 'submitted';
                        $record->save();
                    }),
                ]),
            ]);
    }
}
