<?php
namespace App\Enums;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Contracts\HasColor;
enum AssetTypeEnum : string implements HasLabel,  HasColor{
    // Define your enum values here
    case Current = 'current';
    case Fixed = 'fixed';
    case Intangible = 'intangible';
    case Investment = 'investment';
    case Other = 'other';
    public function getLabel(): ?string
    {
        return $this->name;
    }
    public function getColor(): ?string
    {
        return match ($this) {
            self::Current => 'success',
            self::Fixed => 'danger',
            self::Intangible => 'warning',
            self::Investment => 'secondary',
            self::Other => 'primary',
        };
    }
}

