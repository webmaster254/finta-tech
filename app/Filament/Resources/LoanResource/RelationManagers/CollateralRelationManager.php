<?php

namespace App\Filament\Resources\LoanResource\RelationManagers;

use Filament\Forms;
use Filament\Tables;
use App\Models\Currency;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Enums\CollateralStatus;
use Illuminate\Support\Facades\Auth;
use Filament\Tables\Actions\ActionGroup;
use Illuminate\Database\Eloquent\Builder;
use Awcodes\Curator\Components\Forms\CuratorPicker;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Awcodes\Curator\Components\Tables\CuratorColumn;
use Filament\Resources\RelationManagers\RelationManager;

class CollateralRelationManager extends RelationManager
{
    protected static string $relationship = 'collateral';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Hidden::make('loan_id')
                    ->default($this->getOwnerRecord()->id),
                Forms\Components\Hidden::make('created_by_id')
                    ->default(Auth::id()),
                Forms\Components\Select::make('loan_collateral_type_id')
                    ->relationship('collateral_type', 'name'),
                Forms\Components\TextInput::make('value')
                    ->required()
                    ->numeric(),
                Forms\Components\Textarea::make('description'),
                CuratorPicker::make('file')
                    ->required(),
                Forms\Components\Select::make('status')
                    ->options(CollateralStatus::class)
                    ->default('active'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('loan_id')
            ->columns([
                Tables\Columns\TextColumn::make('collateral_type.name')
                    ->label('Collateral Type'),
                Tables\Columns\TextColumn::make('value')
                    ->money(Currency::where('is_default', 1)->first()->symbol),
                Tables\Columns\TextColumn::make('description'),
                CuratorColumn::make('file'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                ActionGroup::make([
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
    public function isReadOnly(): bool
    {
        return false;
    }
}
