<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Enums\Status;
use App\Models\Currency;
use App\Models\Investor;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Enums\InvestorStatus;
use App\Events\InvestmentMade;
use App\Policies\InvestorPolicy;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Awcodes\Curator\Models\Media;
use App\Events\InvestmentReversed;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Fieldset;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Split;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\ImageColumn;
use Illuminate\Database\Eloquent\Builder;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ImageEntry;
use App\Filament\Resources\InvestorResource\Pages;
use Awcodes\Curator\Components\Forms\CuratorPicker;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Awcodes\Curator\Components\Tables\CuratorColumn;
use Awcodes\Curator\PathGenerators\DatePathGenerator;
use App\Filament\Resources\InvestorResource\RelationManagers;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use App\Filament\Resources\InvestorResource\RelationManagers\InvestmentRelationManager;

class InvestorResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = Investor::class;


    protected static ?string $navigationIcon = 'heroicon-o-user';
    protected static ?string $navigationLabel = 'View Investors';
    protected static ?string $navigationGroup = 'Back office';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Fieldset::make('Personal Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Full Name')
                            ->required(),
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->required(),
                        Forms\Components\TextInput::make('phone')
                            ->tel()
                            ->required(),
                        Forms\Components\TextInput::make('id_number')
                            ->required()
                            ->minLength(4)
                            ->label('ID Number'),
                        Forms\Components\Textarea::make('address')
                            ->columnSpanFull(),
                        CuratorPicker::make('avatar')
                            ->label('photo')
                            ->constrained(true)
                            ->pathGenerator(DatePathGenerator::class),
                    ]),

                    Fieldset::make('Investment Information')
                        ->schema([
                            Forms\Components\TextInput::make('investment_amount')
                                ->numeric()
                                ->required(),
                            Forms\Components\DatePicker::make('investment_date')
                                ->required()
                                ->date()
                                ->native(false),
                            Forms\Components\TextInput::make('investment_duration')
                                ->numeric()
                                ->required(),
                            Forms\Components\Select::make('investment_duration_type')
                                ->options([
                                    'year' => 'Year',
                                ])
                                ->default('year'),
                            Forms\Components\TextInput::make('investment_return')
                                ->required()
                                ->numeric()
                                ->suffix(' %'),
                                ]),

                Fieldset::make('Bank Information')
                    ->schema([
                        Forms\Components\TextInput::make('bank_account')
                            ->numeric()
                            ->minLength(4)
                            ->required(),
                        Forms\Components\TextInput::make('bank_name')
                            ->required(),
                        Forms\Components\TextInput::make('bank_branch')
                            ->required(),
                    ]),



                Forms\Components\Select::make('status')
                    ->required()
                    ->label('Status')
                    ->preload()
                    ->options(InvestorStatus::class)
                    ->default('pending'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('avatar')
                    ->label('Photo')
                    ->size(40)
                    ->circular()
                    ->getStateUsing(function($record) {
                        $media = Media::where('id', $record->avatar)->first();

                            if ($media) {
                                return $media->path;
                            } else {
                                return 'https://ui-avatars.com/api/?background=random&name=' . urlencode($record->name);
                            }
                    }),
                Tables\Columns\TextColumn::make('name')
                    ->label('Full Name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone')
                    ->searchable()
                    ->label('Phone Number'),
                Tables\Columns\TextColumn::make('investment_amount')
                    ->numeric()
                    ->sortable()
                    ->money(Currency::where('is_default', 1)->first()->symbol),
                Tables\Columns\TextColumn::make('investment_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('investment_return')
                    ->label('Interest Rate')
                    ->searchable()
                    ->suffix(' %'),
                Tables\Columns\TextColumn::make('status')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color(function (Investor $record) {
                        return $record->status === 'pending' ? 'warning' : ($record->status === 'approved' || $record->status === 'active' ? 'success' : 'danger');
                    }),
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
                Tables\Actions\Action::make('Approve')
                    ->visible(function (Investor $record) {
                        $policy = new InvestorPolicy();
                        return $record->status === 'pending' && $policy->canApprove(Auth::user(), $record);
                    })
                    ->requiresConfirmation()
                    ->button()
                    ->color('success')
                    ->action(function (Investor $record) {
                        $record->update([
                            'status' => 'approved',
                            'approved_by_id' => Auth::user()->id,
                        ]);
                    }),

                Tables\Actions\Action::make('Invest')
                    ->visible(function (Investor $record) {
                        $policy = new InvestorPolicy();
                        return $record->status === 'approved' && $policy->canDisapprove(Auth::user(), $record);
                    })
                    ->requiresConfirmation()
                    ->button()
                    ->color('success')
                    ->icon('heroicon-o-currency-dollar')
                    ->action(function (Investor $record) {

                        event(new InvestmentMade($record));
                        Notification::make()
                            ->title('Investment made successfully')
                            ->success()
                            ->body('The Investment has been made successfully.')
                            ->send();
                    }),
                Tables\Actions\Action::make('Disapprove')
                    ->visible(function (Investor $record) {
                        $policy = new InvestorPolicy();
                        return $record->status === 'approved' && $policy->canDisapprove(Auth::user(), $record);
                    })
                    ->requiresConfirmation()
                    ->button()
                    ->color('primary')
                    ->action(function (Investor $record) {
                        $record->update([
                            'status' => 'pending',
                            'rejected_by_id' => Auth::user()->id,
                        ]);
                    }),
                Tables\Actions\Action::make('Undo Investment')
                ->visible(function (Investor $record) {
                    $policy = new InvestorPolicy();
                    return $record->status === 'active' && $policy->canDisapprove(Auth::user(), $record);
                })
                    ->requiresConfirmation()
                    ->button()
                    ->color('danger')
                    ->icon('heroicon-o-x-circle')
                    ->action(function (Investor $record) {
                        event(new InvestmentReversed($record));
                        Notification::make()
                            ->title('Investment reversed successfully')
                            ->success()
                            ->body('The Investment has been reversed successfully.')
                            ->send();
                    }),
                ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
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

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
             ->schema([
                Split::make([
                    Section::make('Personal Information')

                        ->schema([
                            ImageEntry::make('avatar')
                                ->height(60)
                                ->label('Photo')
                                ->circular()
                                ->getStateUsing(function($record) {
                                    $media = Media::where('id', $record->avatar)->first();

                                        if ($media) {
                                            return $media->path;
                                        } else {
                                            return 'https://ui-avatars.com/api/?background=random&name=' . urlencode($record->name);
                                        }
                                }),
                            TextEntry::make('name')
                               ->color('info')
                               ,
                            TextEntry::make('email')
                                ->color('info'),
                            TextEntry::make('phone')
                                ->color('info'),
                            TextEntry::make('id_number')
                                ->label('ID Number')
                                ->color('info'),
                            TextEntry::make('address')
                                ->color('info'),
                        ])->columns(3),
                    ]),

                        Split::make([


                    Section::make('Investment Information')

                        ->schema([
                            TextEntry::make('investment_amount')
                                ->label('Amount')
                                ->color('info')
                                ->money(Currency::where('is_default', 1)->first()->symbol),
                            TextEntry::make('investment_date')
                               ->color('info'),
                            TextEntry::make('investment_duration')
                               ->color('info')
                               ->label('Duration'),
                            TextEntry::make('investment_duration_type')
                               ->label('Duration Type')
                               ->color('info'),
                            TextEntry::make('investment_return')
                                ->label('Interest Rate')
                                ->suffix(' %')
                                ->color('info'),
                            TextEntry::make('status')
                                ->label('Status')
                                ->badge()
                                ->color(function (Investor $record) {
                                    return $record->status === 'pending' ? 'warning' : ($record->status === 'approved' || $record->status === 'active' ? 'success' : 'danger');
                                }),
                            TextEntry::make('approved_by.fullname')
                                ->label('Approved By')
                                ->color('info'),
                        ])->columns(3),
                    ]),

                    Section::make('Bank Information')

                        ->schema([
                            TextEntry::make('bank_name')
                               ->color('info'),
                            TextEntry::make('bank_account')
                                ->color('info'),
                            TextEntry::make('bank_branch')
                                 ->color('info'),
                        ])->columns(3),



            ]);

    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getRelations(): array
    {
        return [
            InvestmentRelationManager::class,
        ];
    }

    public static function getPermissionPrefixes(): array
    {
        return [
            'view',
            'view_any',
            'create',
            'update',
            'delete',
            'delete_any',
            'approve',
            'disapprove',
            'invest'
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInvestors::route('/'),
            'create' => Pages\CreateInvestor::route('/create'),
            'edit' => Pages\EditInvestor::route('/{record}/edit'),
            'view' => Pages\ViewInvestor::route('/{record}'),
        ];
    }
}
