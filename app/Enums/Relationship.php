<?php
namespace App\Enums;
use Filament\Support\Contracts\HasLabel;

enum Relationship: string implements HasLabel
            {
                case Father = 'Father';
                case Mother = 'Mother';
                case Spouse = 'Spouse';
                case Parent = 'Parent';
                case Child = 'Child';
                case Sibling = 'Sibling';
                case Friend = 'Friend';
                case Son = 'Son';
                case Daughter = 'Daughter';
                case Brother = 'Brother';
                case Sister = 'Sister';
                case Husband = 'Husband';
                case Wife = 'Wife';
                case Cousin = 'Cousin';
                case Father_in_law = 'Father in Law';
                case Mother_in_law = 'Mother in Law';
                case Niece = 'Niece';
                case Nephew = 'Nephew';
                case Business_partner = 'Business Partner';
                case Colleague = 'Colleague';
                case Relative = 'Relative';   
                case Neighbor = 'Neighbor';
                case Uncle = 'Uncle';
                case Aunt = 'Aunt';
                case Grandmother = 'Grandmother';
                case Grandfather = 'Grandfather';
                case Sister_in_law = 'Sister in Law';
                case Brother_in_law = 'Brother in Law';
                case Grand_son = 'Grand Son';
                case Grand_daughter = 'Grand Daughter';
                
                public function getLabel(): ?string
                {
                    return match($this) {
                        self::Father => 'Father',
                        self::Mother => 'Mother',
                        self::Spouse => 'Spouse',
                        self::Parent => 'Parent',
                        self::Child => 'Child',
                        self::Sibling => 'Sibling',
                        self::Friend => 'Friend',
                        self::Son => 'Son',
                        self::Daughter => 'Daughter',
                        self::Brother => 'Brother',
                        self::Sister => 'Sister',
                        self::Husband => 'Husband',
                        self::Wife => 'Wife',
                        self::Cousin => 'Cousin',
                        self::Father_in_law => 'Father in Law',
                        self::Mother_in_law => 'Mother in Law',
                        self::Niece => 'Niece',
                        self::Nephew => 'Nephew',
                        self::Business_partner => 'Business Partner',
                        self::Colleague => 'Colleague',
                        self::Relative => 'Relative',
                        self::Neighbor => 'Neighbor',
                        self::Uncle => 'Uncle',
                        self::Aunt => 'Aunt',
                        self::Grandmother => 'Grandmother',
                        self::Grandfather => 'Grandfather',
                        self::Sister_in_law => 'Sister in Law',
                        self::Brother_in_law => 'Brother in Law',
                        self::Grand_son => 'Grand Son',
                        self::Grand_daughter => 'Grand Daughter',
                    };
                }
            }