<?php

namespace App\Filament\Resources\ClientResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use App\Enums\Relationship;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Cheesegrits\FilamentPhoneNumbers;
use Brick\PhoneNumber\PhoneNumberFormat;

class RefereesRelationManager extends RelationManager
{
    protected static string $relationship = 'referees';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                    Forms\Components\Hidden::make('client_id')
                    ->default($this->getOwnerRecord()->id),
                Forms\Components\TextInput::make('name')
                    ->label('Name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('relationship')
                    ->label('Relationship')
                    ->options(Relationship::class)
                    ->required(),
                FilamentPhoneNumbers\Forms\Components\PhoneNumber::make('mobile')
                    ->label('Mobile')
                    ->region('KE')
                    ->displayFormat(PhoneNumberFormat::E164)
                    ->mask('9999999999')
                    ->required(),
                Forms\Components\TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->maxLength(255),
                Forms\Components\Textarea::make('address')
                    ->label('Address')
                    ->maxLength(1000),
            ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('relationship')
                    ->label('Relationship'),
                Tables\Columns\TextColumn::make('mobile')
                    ->label('Mobile'),
                Tables\Columns\TextColumn::make('email')
                    ->label('Email'),
                Tables\Columns\TextColumn::make('address')
                    ->label('Address'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Add Referee')
                    ->icon('heroicon-o-plus-circle'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
    public function isReadOnly(): bool
    {
        return false;
    }
}
