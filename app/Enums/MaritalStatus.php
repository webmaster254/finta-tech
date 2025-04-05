<?php
namespace App\Enums;
use Filament\Support\Contracts\HasLabel;

enum MaritalStatus: string implements HasLabel
    {
        case Single = 'single';
        case Married = 'married';
        case Separated = 'separated';
        case Divorced = 'divorced';
        case Widow = 'widow';
        case Widowed = 'widowed';
        
        public function getLabel(): ?string
        {
            return $this->name;
        }
    }