<?php
namespace App\Enums;
use Filament\Support\Contracts\HasLabel;

enum EducationLevel: string implements HasLabel
            {
                case PRIMARY = 'Primary';
                case SECONDARY = 'Secondary';
                case TERTIARY = 'Tertiary';
                
                public function getLabel(): ?string
                {
                    return $this->name;
                }
            }