<?php

namespace App\Filament\Pages;

use Illuminate\Contracts\Support\Htmlable;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use ShuvroRoy\FilamentSpatieLaravelHealth\Pages\HealthCheckResults as BaseHealthCheckResults;

class HealthCheckResults extends BaseHealthCheckResults
{
    use HasPageShield;
    protected static ?string $navigationIcon = 'heroicon-o-cpu-chip';
    protected static bool $shouldRegisterNavigation = false;
    public function getHeading(): string | Htmlable
    {
        return 'Health Check Results';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Core';
    }
}
