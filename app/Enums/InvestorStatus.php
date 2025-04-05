<?php
namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum InvestorStatus: string implements HasLabel,  HasColor
        {
            case Active = 'active';
            case Pending = 'pending';
            case Approved = 'approved';
            case Closed = 'closed';

            public function getLabel(): ?string
            {
                return $this->name;
            }
            public function getColor(): string | array | null
            {
                return match ($this) {
                    self::Approved => 'info',
                    self::Pending => 'warning',
                    self::Active => 'success',
                    self::Closed => 'danger',
                };
            }
        }
