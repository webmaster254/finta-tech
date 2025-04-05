<?php
namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum Status: string implements HasLabel,  HasColor
        {
            case Active = 'active';
            case Pending = 'pending';
            case Inactive = 'inactive';
            case Closed = 'closed';
            case Deceased = 'deceased';
            case Suspended = 'suspended';

            public function getLabel(): ?string
            {
                return $this->name;
            }
            public function getColor(): string | array | null
            {
                return match ($this) {
                    self::Inactive => 'gray',
                    self::Pending => 'info',
                    self::Active => 'success',
                    self::Suspended => 'danger',
                    self::Deceased => 'gray',
                    self::Closed => 'danger',
                };
            }
        }
