<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\Gender;
use App\Enums\UserRole;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->schema([
                TextInput::make('name')
                    ->label('Имя')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),

                TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),

                TextInput::make('phone')
                    ->label('Телефон')
                    ->placeholder('+7 (999) 123-45-67')
                    ->maxLength(20)
                    ->helperText('Формат +7XXXXXXXXXX'),

                DatePicker::make('birth_date')
                    ->label('Дата рождения')
                    ->maxDate(now()),

                Select::make('gender')
                    ->label('Пол')
                    ->options(Gender::class)
                    ->native(false)
                    ->placeholder('Не указан'),

                Select::make('role')
                    ->label('Роль')
                    ->options(UserRole::class)
                    ->default(UserRole::Customer->value)
                    ->required()
                    ->native(false),

                Toggle::make('is_blocked')
                    ->label('Заблокирован')
                    ->helperText('Заблокированный пользователь не сможет войти')
                    ->default(false)
                    ->columnSpanFull(),

                TextInput::make('password')
                    ->label('Пароль')
                    ->password()
                    ->revealable()
                    ->required()
                    ->maxLength(255)
                    ->dehydrated(fn ($state) => filled($state))
                    ->visibleOn('create')
                    ->columnSpanFull(),
            ]);
    }
}
