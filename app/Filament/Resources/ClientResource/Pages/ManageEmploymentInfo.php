<?php

namespace App\Filament\Resources\ClientResource\Pages;

use Filament\Forms;
use Filament\Tables;
use Filament\Actions;
use Filament\Forms\Form;
use App\Models\Profession;
use Filament\Tables\Table;
use Filament\Infolists\Infolist;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\ClientResource;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ManageRelatedRecords;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Joaopaulolndev\FilamentPdfViewer\Infolists\Components\PdfViewerEntry;

class ManageEmploymentInfo extends ManageRelatedRecords
{
    protected static string $resource = ClientResource::class;

    protected static string $relationship = 'employment';
    protected ?string $heading = 'Customer Employment Information';

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    public static function getNavigationLabel(): string
    {
        return 'Employment';
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Hidden::make('client_id')
                    ->default($this->getOwnerRecord()->id),
                Forms\Components\TextInput::make('employer_name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('employment_type')
                    ->options([
                        'salaried' => 'Salaried',
                        'self_employed' => 'Self-employed',
                        'contract' => 'Contract',
                    ])
                    ->required(),
                Forms\Components\Select::make('occupation')
                    ->options(Profession::pluck('name', 'id'))
                    ->required(),
                Forms\Components\Select::make('designation')
                    ->options(['employee' => 'Employee', 
                    'assistant-manager' => 'Assistant Manager', 
                    'manager' => 'Manager',
                    'supervisor' => 'Supervisor',
                    'director' => 'Director',
                    'partner' => 'Partner',
                    'owner' => 'Owner'])
                    ->required(),
                Forms\Components\DatePicker::make('working_since')
                    ->required(),
                Forms\Components\TextInput::make('gross_income')
                    ->prefix('KES')
                    ->numeric()
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn(Forms\Get $get, Forms\Set $set, ?int $state) => self::updateNetIncome($get, $set, $state))
                    ->required(),
                Forms\Components\TextInput::make('other_income')
                    ->prefix('KES')
                    ->numeric()
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn(Forms\Get $get, Forms\Set $set, ?int $state) => self::updateNetIncome($get, $set, $state))
                    ->required(),
                Forms\Components\TextInput::make('expense')
                    ->prefix('KES')
                    ->numeric()
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn(Forms\Get $get, Forms\Set $set, ?int $state) => self::updateNetIncome($get, $set, $state))
                    ->required(),
                Forms\Components\TextInput::make('net_income')
                    ->prefix('KES')
                    ->numeric()
                    ->readOnly()
                    ->required(),
                Forms\Components\FileUpload::make('employment_letter')
                    ->label('Employment Letter')
                    ->required(),
                Forms\Components\FileUpload::make('pay_slip')
                    ->label('Salary Slip')
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->emptyStateHeading('No employment information yet')
            ->emptyStateDescription('You have not added any employment information yet.')
            ->columns([
                Tables\Columns\TextColumn::make('employer_name'),
                Tables\Columns\TextColumn::make('employment_type'),
                Tables\Columns\TextColumn::make('profession.name')
                    ->label('Occupation'),
                Tables\Columns\TextColumn::make('designation'),
                Tables\Columns\TextColumn::make('working_since'),
                Tables\Columns\TextColumn::make('gross_income')
                    ->prefix('KES ')
                    ->numeric(),
                Tables\Columns\TextColumn::make('other_income')
                    ->prefix('KES ')
                    ->numeric(),
                Tables\Columns\TextColumn::make('expense')
                    ->prefix('KES ')
                    ->numeric(),
                Tables\Columns\TextColumn::make('net_income')
                    ->prefix('KES ')
                    ->numeric(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                ->createAnother(false)
                ->label('Add Employment')
                ->modalHeading('Add Employment')
                ->modalDescription('Add new employment information')
                ->modalIcon('heroicon-o-academic-cap')
                ->modalIconColor('success')
                ->successNotificationTitle('Employment information added'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                ->modalHeading('View Employment')
                ->modalDescription('View employment information')
                ->modalIcon('heroicon-o-academic-cap')
                ->modalIconColor('success'),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                TextEntry::make('employer_name')
                    ->label('Employer Name')
                    ->color('info'),
                TextEntry::make('employment_type')
                    ->label('Employment Type')
                    ->color('info'),
                TextEntry::make('occupation')
                    ->label('Occupation')
                    ->color('info'),
                TextEntry::make('designation')
                    ->label('Designation')
                    ->color('info'),
                TextEntry::make('working_since')
                    ->label('Working Since')
                    ->color('info'),
                TextEntry::make('gross_income')
                    ->label('Gross Income')
                    ->prefix('KES ')
                    ->numeric()
                    ->color('info'),
                TextEntry::make('other_income')
                    ->label('Other Income')
                    ->prefix('KES ')
                    ->numeric()
                    ->color('info'),
                TextEntry::make('expense')
                    ->label('Expense')
                    ->prefix('KES ')
                    ->numeric()
                    ->color('info'),
                TextEntry::make('net_income')
                    ->label('Net Income')
                    ->prefix('KES ')
                    ->numeric()
                    ->color('info'),
                PdfViewerEntry::make('employment_letter')
                    ->label('View Employment letter')
                    ->minHeight('40svh')
                    ->columnSpan('full'),
                PdfViewerEntry::make('pay_slip')
                    ->label('View Pay Slip')
                    ->minHeight('40svh')
                    ->columnSpan('full'),
            ]);
    }

    private static function updateNetIncome(Forms\Get $get, Forms\Set $set, ?int $state):void
    {
        $set('net_income', $get('gross_income') + $get('other_income') - $get('expense'));
    }
}
