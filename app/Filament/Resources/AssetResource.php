<?php

namespace App\Filament\Resources;

use auth;
use Filament\Forms;
use Filament\Tables;
use App\Models\Asset;
use App\Models\Currency;
use Filament\Forms\Form;
use App\Enums\AssetStatus;
use Filament\Tables\Table;
use App\Models\BankAccount;
use App\Models\ChartOfAccount;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use App\Filament\Resources\AssetResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\AssetResource\RelationManagers;

class AssetResource extends Resource
{
    protected static ?string $model = Asset::class;

    protected static ?string $navigationIcon = null;
    protected static ?string $navigationGroup = 'Assets Management';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Hidden::make('created_by_id')
                    ->default(auth()->id()),
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('asset_type_id')
                    ->label('Asset Type')
                    ->required()
                    ->options(ChartOfAccount::where('account_type', 'asset')->get()->pluck('name', 'id')),

                Forms\Components\DatePicker::make('purchase_date')
                    ->required()
                    ->native(false),
                Forms\Components\TextInput::make('purchase_price')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('replacement_value')
                    ->numeric(),
                Forms\Components\TextInput::make('value')
                    ->numeric(),
                Forms\Components\TextInput::make('life_span')
                    ->numeric(),
                Forms\Components\TextInput::make('salvage_value')
                    ->numeric(),
                Forms\Components\TextInput::make('serial_number')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('bought_from')
                    ->maxLength(255),
                Forms\Components\TextInput::make('purchase_year')
                    ->maxLength(255),
                Forms\Components\Textarea::make('notes')
                    ->maxLength(65535)
                    ->columnSpanFull(),
                Forms\Components\Select::make('status')
                    ->options(AssetStatus::class),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('createdBy.fullname')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('assetType.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('purchase_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('purchase_price')
                    ->numeric()
                    ->money(Currency::where('is_default', 1)->first()->symbol)
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                ->after(function (Asset $record) {
                    $fundsAssetAccount = BankAccount::find($record->asset_type_id);
                    $fundsAssetAccount->balance -= $record->purchase_price;
                    $fundsAssetAccount->save();
                }),
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
                Section::make('Asset Details')
                ->columns([
                    'sm' => 3,
                    'xl' => 6,
                    '2xl' => 8,
                ])
                ->schema([
                    TextEntry::make('name')
                        ->label('Asset Name')
                        ->color('info'),
                    TextEntry::make('assetType.chart_of_account_asset_id')
                        ->label('Asset Type')
                        ->color('info'),
                    TextEntry::make('serial_number')
                        ->label('Serial Number')
                        ->color('info'),
                    TextEntry::make('purchase_date')
                        ->label('Purchase Date')
                        ->color('info'),
                    TextEntry::make('purchase_price')
                        ->label('Purchase Price')
                        ->money(Currency::where('is_default', 1)->first()->symbol)
                        ->color('info'),
                    TextEntry::make('replacement_value')
                        ->label('Replacement Value')
                        ->money(Currency::where('is_default', 1)->first()->symbol)
                        ->color('info'),
                    TextEntry::make('value')
                        ->label('Value')
                        ->money(Currency::where('is_default', 1)->first()->symbol)
                        ->color('info'),
                    TextEntry::make('life_span')
                        ->label('Life Span')
                        ->color('info'),
                    TextEntry::make('salvage_value')
                        ->label('Salvage Value')
                        ->color('info'),
                    TextEntry::make('bought_from')
                        ->label('Bought From')
                        ->color('info'),
                    TextEntry::make('status')
                        ->label('Status')
                        ->color('success'),
                    TextEntry::make('createdBy.fullname')
                        ->label('Created By')
                        ->color('info'),
                    TextEntry::make('created_at')
                        ->label('Created At')
                        ->color('info'),
                    TextEntry::make('updated_at')
                        ->label('Updated At')
                        ->color('info'),

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
            'index' => Pages\ListAssets::route('/'),
            'create' => Pages\CreateAsset::route('/create'),
            'edit' => Pages\EditAsset::route('/{record}/edit'),
            'view' => Pages\ViewAsset::route('/{record}'),
        ];
    }
}
