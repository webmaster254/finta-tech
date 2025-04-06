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
use Filament\Resources\Resource;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\BusinessResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\BusinessResource\RelationManagers;
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
                Tables\Columns\TextColumn::make('status'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
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
        ];
    }
}
