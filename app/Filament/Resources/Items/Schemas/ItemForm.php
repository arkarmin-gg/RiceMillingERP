<?php

namespace App\Filament\Resources\Items\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ItemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Item')->schema([
                    TextInput::make('name')
                        ->required()
                        ->maxLength(255),
                    Select::make('category')
                        ->options([
                            'PADDY' => 'Paddy',
                            'RICE' => 'Rice',
                            'BROKEN' => 'Broken',
                            'POINT_BROKEN' => 'Point Broken',
                            'BRAN' => 'Bran',
                            'POINT_BRAN' => 'Point Bran',
                            'HUSK' => 'Husk',
                            'WASTED' => 'Wasted',
                        ])
                        ->required(),
                    Select::make('unit')
                        ->options([
                            'BAG' => 'Bag',
                            'LB' => 'Lbs',
                            'KG' => 'Kg',
                        ])
                        ->required(),
                ])->columnSpanFull()
            ]);
    }
}
