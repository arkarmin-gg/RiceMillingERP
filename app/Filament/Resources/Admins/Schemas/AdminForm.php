<?php

namespace App\Filament\Resources\Admins\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AdminForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Profile')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                FileUpload::make('profile_image_url')
                                    ->image()
                                    ->disk('s3')
                                    ->directory('profile-images')
                                    ->visibility('private')
                                    ->imageEditor()
                                    ->maxSize(2048),
                                TextInput::make('full_name')
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('email')
                                    ->email()
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(ignoreRecord: true),
                                TextInput::make('phone')
                                    ->tel()
                                    ->maxLength(20),

                                TextInput::make('password')
                                    ->password()
                                    ->revealable()
                                    ->required(fn($record): bool => $record === null)
                                    ->minLength(8)
                                    ->maxLength(255)
                                    ->dehydrated(fn(?string $state): bool => filled($state)),
                                DatePicker::make('date_of_birth')
                                    ->native(false),
                                Select::make('gender')
                                    ->options([
                                        'male' => 'Male',
                                        'female' => 'Female',
                                        'other' => 'Other',
                                    ])
                                    ->nullable(),
                                Select::make('role_id')
                                    ->relationship('role', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required(),
                                Toggle::make('is_banned')
                                    ->label('Banned')
                                    ->default(false),
                            ])->columnSpanFull(),
                    ])->columnSpanFull(),
            ]);
    }
}
