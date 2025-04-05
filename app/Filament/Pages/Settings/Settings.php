<?php
namespace App\Filament\Pages\Settings;

use Closure;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\FileUpload;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Outerweb\FilamentSettings\Filament\Pages\Settings as BaseSettings;

class Settings extends BaseSettings
{

    use HasPageShield;
    public function schema(): array|Closure
    {
        return [
            Tabs::make('Settings')
                ->schema([
                    Tabs\Tab::make('General')
                        ->schema([
                            TextInput::make('general.brand_name')
                                ->required(),
                            FileUpload::make('general.brand_logo')
                                ->image()
                                ->imageEditor(),
                            FileUpload::make('general.favicon')
                                ->image()
                                ->imageEditor(),
                            TextInput::make('general.email')
                                ->email(),
                            TextInput::make('general.phone')
                                ->tel(),
                            TextInput::make('general.address')
                                ->maxLength(255),

                        ]),
                    Tabs\Tab::make('Email')
                        ->schema([
                            TextInput::make('email.mail_driver')
                                ,
                            TextInput::make('email.mail_host')
                                ,
                            TextInput::make('email.mail_port')
                                ,
                            TextInput::make('email.mail_username')
                                ,
                            TextInput::make('email.mail_password')
                                ,
                            TextInput::make('email.mail_encryption')
                                ,
                            TextInput::make('email.mail_from_address')
                                ,
                            TextInput::make('email.mail_from_name')
                                ,
                        ]),
                    Tabs\Tab::make('SMS')
                        ->schema([
                            TextInput::make('sms.url')
                                ->label('API URL')
                                ,
                            TextInput::make('sms.api_key')
                                ->label('API Key')
                                ,
                            TextInput::make('sms.sender_id')
                                ->label('Sender ID')
                                ,
                        ]),

                        Tabs\Tab::make('Accounting')
                        ->schema([
                            Select::make('fiscal_year_end_month')
                                ->softRequired()
                                ->searchable()
                                ->options(array_combine(range(1, 12), array_map(static fn ($month) => now()->month($month)->monthName, range(1, 12))))
                                ->afterStateUpdated(static fn (Set $set) => $set('fiscal_year_end_day', null))
                                ->columnSpan(2)
                                ->label('Fiscal Year End Month')
                                ->live(),
                            Select::make('fiscal_year_end_day')
                                ->placeholder('Day')
                                ->softRequired()
                                ->columnSpan(1)
                                ->options(function (Get $get) {
                                    $month = $get('fiscal_year_end_month');

                                    $daysInMonth = now()->month($month)->daysInMonth;

                                    return array_combine(range(1, $daysInMonth), range(1, $daysInMonth));
                                })
                                ->live(),
                        ]),

                ]),
        ];
    }

    public static function getNavigationLabel(): string
    {
        return 'Organisation Settings';
    }

    public static function getNavigationGroup(): string
    {
        return 'Settings';
    }

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-home-modern';
    }
}
