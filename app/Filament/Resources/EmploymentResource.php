<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\EmploymentInfo;
use Filament\Resources\Resource;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\FileUpload;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\EmploymentResource\Pages;
use App\Filament\Resources\EmploymentResource\RelationManagers;
use App\Models\Client;
use App\Models\Profession;

class EmploymentResource extends Resource
{
    protected static ?string $model = EmploymentInfo::class;
    protected static ?string $navigationLabel = 'Employment Information';
    protected static ?string $navigationGroup = 'Clients Management';
    protected ?string $heading = 'Customer Employment Information';
    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Hidden::make('status')
                   ->default('pending'),
                Select::make('client_id')
                ->options(Client::where('source_of_income', 'Employed')->get()->mapWithKeys(function ($client) {
                    return [$client->id => $client->first_name . ' ' . $client->last_name];
                }))
                    ->label('Client')
                    ->required(),
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

    public static function table(Table $table): Table
    {
        return $table
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmployments::route('/'),
            'create' => Pages\CreateEmployment::route('/create'),
            'edit' => Pages\EditEmployment::route('/{record}/edit'),
        ];
    }
}
