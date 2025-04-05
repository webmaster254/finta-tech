<?php
namespace App\Enums;

use Filament\Support\Contracts\HasLabel;
use Filament\Support\Contracts\HasColor;
enum AssetStatus: string implements HasLabel, HasColor{
    case Active = 'active';
    case Inactive = 'inactive';
    case Damaged = 'damaged';
    case Sold = 'sold';
    case Written_off = 'writen_off';

    public function getLabel(): ?string
    {
        return $this->name;
    }


    public function getColor(): ?string
    {
        return match ($this) {
            self::Active => 'success',
            self::Inactive => 'danger',
            self::Damaged => 'warning',
            self::Sold => 'secondary',
            self::Written_off => 'primary',
        };
    }
}

