<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;

class OverdueArrears extends Page
{
    use HasPageShield;
    protected static ?string $navigationIcon = null;
    protected static ?string $navigationGroup = 'Loans Management';
    protected static ?int $navigationSort = 2;

    protected static string $view = 'filament.pages.overdue-arrears';
}
