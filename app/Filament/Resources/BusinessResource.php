<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Client;
use App\Enums\Industry;
use App\Enums\Ownership;
use App\Models\Business;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Enums\BusinessType;
use App\Enums\BusinessStatus;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\Split;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;
use Filament\Infolists\Components\Fieldset;
use Filament\Infolists\Components\TextEntry;
use App\Filament\Resources\BusinessResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\BusinessResource\RelationManagers;
use Joaopaulolndev\FilamentPdfViewer\Infolists\Components\PdfViewerEntry;
use App\Filament\Resources\BusinessResource\RelationManagers\BusinessRelationManager;

class BusinessResource extends Resource
{
    protected static ?string $model = Business::class;

    protected static ?string $navigationLabel = 'Business Information';
    protected static ?string $navigationGroup = 'Clients Management';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Hidden::make('status')
            ->default('pending'),
                Forms\Components\Select::make('client_id')
                    ->options(Client::all()->pluck('full_name', 'id'))
                    ->label('Client')
                    ->required(),
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('business_type')
                    ->options(BusinessType::class)
                    ->required(),
                Forms\Components\TextInput::make('description')
                    ->maxLength(255),
                Forms\Components\Select::make('industry')
                    ->options(Industry::class)
                    ->required(),
                Forms\Components\DatePicker::make('establishment_date')
                    //->native(false)
                    ->required(),
                Forms\Components\TextInput::make('location')
                    ->maxLength(255),
                Forms\Components\Select::make('ownership')
                    ->options(Ownership::class)
                    ->required(),
                Forms\Components\Select::make('premise_ownership')
                    ->options([
                        'owned' => 'Owned',
                        'rented' => 'Rented',
                        'leased' => 'Leased',
                    ])
                    ->required(),
                Forms\Components\TextInput::make('employees')
                    ->numeric()
                    ->required(),
                Forms\Components\Select::make('sector')
                    ->options([
                        'msme' => 'MSME',
                        'sme' => 'SME',
                    ])
                    ->required(),
                Forms\Components\TextInput::make('major_products')
                    ->maxLength(255),
                Forms\Components\TextInput::make('major_suppliers')
                    ->maxLength(255),
                Forms\Components\TextInput::make('major_customers')
                    ->maxLength(255),
                Forms\Components\TextInput::make('major_competitors')
                    ->maxLength(255),
                Forms\Components\TextInput::make('strengths')
                    ->maxLength(255),
                Forms\Components\TextInput::make('weaknesses')
                    ->maxLength(255),
                Forms\Components\TextInput::make('opportunities')
                    ->maxLength(255),
                Forms\Components\TextInput::make('threats')
                    ->maxLength(255),
                Forms\Components\TextInput::make('mitigations')
                    ->maxLength(255),
                Forms\Components\TextInput::make('insurance')
                    ->maxLength(255),
                Forms\Components\FileUpload::make('trading_license'),
                Forms\Components\FileUpload::make('business_permit'),
                Forms\Components\FileUpload::make('certificate_of_incorporation'),
                Forms\Components\FileUpload::make('health_certificate'),
                Forms\Components\TextInput::make('registration_number')
                    ->maxLength(255),
                Forms\Components\Select::make('record_maintained')
                    ->options([
                        'none' => 'None',
                        'audited_books' => 'Audited Books',
                        'black_book' => 'Black Book',
                        'digital_book' => 'Digital Book',
                    ])
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->emptyStateHeading('No businesses yet')
            ->emptyStateDescription('You have not created any businesses yet.')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('business_type')
                    ->searchable(),
                Tables\Columns\TextColumn::make('industry')
                    ->searchable(),
                Tables\Columns\TextColumn::make('location')
                    ->searchable(),
                Tables\Columns\TextColumn::make('ownership')
                    ->searchable(),
                Tables\Columns\TextColumn::make('premise_ownership')
                    ->searchable(),
                Tables\Columns\TextColumn::make('employees')
                    ->searchable(),
                Tables\Columns\TextColumn::make('sector')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->modalHeading('View Business')
                    ->modalDescription('View business information')
                    ->modalIcon('heroicon-o-building-office-2')
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

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Split::make([
                Fieldset::make('Business Information')
                    ->schema([
                        TextEntry::make('name')
                    ->label('Business Name')
                    ->color('info'),
                TextEntry::make('business_type')
                    ->label('Business Type')
                    ->color('info'),
                TextEntry::make('industry')
                    ->label('Industry')
                    ->color('info'),
                TextEntry::make('location')
                    ->label('Location')
                    ->color('info'),
                TextEntry::make('ownership')
                    ->label('Ownership')
                    ->color('info'),
                TextEntry::make('premise_ownership')
                    ->label('Premise Ownership')
                    ->color('info'),
                TextEntry::make('employees')
                    ->label('Employees')
                    ->color('info'),
                TextEntry::make('sector')
                    ->label('Sector')
                    ->color('info'),
                TextEntry::make('status')
                    ->label('Status')
                    ->color('info'),
                TextEntry::make('registration_number')
                    ->label('Registration Number')
                    ->color('info'),
                TextEntry::make('establishment_date')
                    ->label('Establishment Date')
                    ->color('info'),
                TextEntry::make('establishment_date')
                    ->label('Business Age')
                    ->since()
                    ->color('info'),
                TextEntry::make('insurance')
                    ->label('Insurance')
                    ->color('info'),
                TextEntry::make('record_maintained')
                    ->label('Record Maintained')
                    ->color('info'),
                ])->columns(3),
                ]),
                Split::make([
                Fieldset::make('More Business Information')
                    ->schema([
                        
                        TextEntry::make('major_products')
                            ->label('Major Products')
                            ->color('info'),
                        TextEntry::make('major_suppliers')
                            ->label('Major Suppliers')
                            ->color('info'),
                        TextEntry::make('major_customers')
                            ->label('Major Customers')
                            ->color('info'),
                        TextEntry::make('major_competitors')
                            ->label('Major Competitors')
                            ->color('info'),
                        TextEntry::make('strengths')
                            ->label('Strengths')
                            ->color('info'),
                        TextEntry::make('weaknesses')
                            ->label('Weaknesses')
                            ->color('info'),
                        TextEntry::make('opportunities')
                            ->label('Opportunities')
                            ->color('info'),
                        TextEntry::make('threats')
                            ->label('Threats')
                            ->color('info'),
                        TextEntry::make('mitigations')
                            ->label('Mitigations')
                            ->color('info'),
                        
                    ])->columns(3),
                ]),
                Fieldset::make('Business Documents')
                    ->schema([
                        PdfViewerEntry::make('trading_license')
                            ->label('Trading License')
                            ->minHeight('40svh'),
                        PdfViewerEntry::make('business_permit')
                            ->label('Business Permit')
                            ->minHeight('40svh'),
                        PdfViewerEntry::make('certificate_of_incorporation')
                            ->label('Certificate of Incorporation')
                            ->minHeight('40svh'),
                        PdfViewerEntry::make('health_certificate')
                            ->label('Health Certificate')
                            ->minHeight('40svh'),
                    ])->columnSpanFull(),
               
            ]);
    }

    public static function getRelations(): array
    {
        return [
            BusinessRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBusinesses::route('/'),
            'create' => Pages\CreateBusiness::route('/create'),
            'edit' => Pages\EditBusiness::route('/{record}/edit'),
            'view' => Pages\ViewBusiness::route('/{record}'),
        ];
    }
}
