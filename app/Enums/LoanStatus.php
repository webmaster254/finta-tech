<?php
namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum LoanStatus: string implements HasLabel,  HasColor
        {
            case Approved = 'approved';
            case Pending = 'pending';
            case Active = 'active';
            case Inactive = 'inactive';
            case Closed = 'closed';
            case Withdrawn = 'withdrawn';
            case Rejected = 'rejected';
            case Rescheduled = 'rescheduled';
            case Written_off = 'written_off';
            case Overpaid = 'overpaid';
            case Submitted = 'submitted';



            public function getLabel(): ?string
            {
                return $this->name;
            }
            public function getColor(): string | array | null
            {
                return match ($this) {
                    self::Inactive => 'gray',
                    self::Pending => 'warning',
                    self::Active => 'success',
                    self::Rejected => 'danger',
                    self::Withdrawn => 'secondary',
                    self::Written_off => 'danger',
                    self::Overpaid => 'warning',
                    self::Submitted => 'primary',
                    self::Closed => 'gray',
                    self::Approved => 'info',
                    self::Rescheduled => 'warning',
                };
            }
        }
