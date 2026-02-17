<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                Section::make('Account Details')
                    ->schema([
                        ImageEntry::make('profile_image_url')
                            ->disk('s3')
                            ->circular()
                            ->hiddenLabel(),
                        TextEntry::make('full_name')
                            ->label('Full Name :')
                            ->inlineLabel()
                            ->weight('medium'),
                        TextEntry::make('email')
                            ->label('Email address :')
                            ->inlineLabel(),
                        TextEntry::make('phone')
                            ->label('Mobile number :')
                            ->inlineLabel(),
                        TextEntry::make('user_type')
                            ->label('User type :')
                            ->inlineLabel(),
                        IconEntry::make('is_banned')
                            ->label('Banned :')
                            ->boolean()
                            ->inlineLabel(),
                        TextEntry::make('created_at')
                            ->label('Created at :')
                            ->date('M j, Y')
                            ->inlineLabel(),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
