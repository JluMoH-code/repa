<?php

namespace App\Filament\Pages;

use App\Actions\Settings\SettingsManager;
use BackedEnum;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;

class ShopSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.pages.shop-settings';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'Настройки магазина';

    protected static string|\UnitEnum|null $navigationGroup = 'Настройки';

    protected static ?int $navigationSort = 1;

    /** @var array<string, string|null> */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill(app(SettingsManager::class)->all());
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                TextInput::make('phone')
                    ->label('Телефон')
                    ->placeholder('+7 (999) 123-45-67')
                    ->required()
                    ->maxLength(30),
                TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->required()
                    ->maxLength(255),
                TextInput::make('address')
                    ->label('Адрес магазина')
                    ->required()
                    ->maxLength(255),
                TextInput::make('work_hours')
                    ->label('Часы работы')
                    ->required()
                    ->maxLength(255),
                Textarea::make('about_text')
                    ->label('О магазине')
                    ->rows(5)
                    ->columnSpanFull(),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $manager = app(SettingsManager::class);

        foreach ($this->form->getState() as $key => $value) {
            $manager->set($key, $value);
        }

        Notification::make()
            ->title('Настройки сохранены')
            ->success()
            ->send();
    }
}
