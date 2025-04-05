<?php
namespace App\Enums;
use Filament\Support\Contracts\HasLabel;

enum Position: string implements HasLabel
            {
                case Right = 'right';
                case Left = 'left';
              
                
                
                public function getLabel(): ?string
                {
                    return $this->name;
                }
            }