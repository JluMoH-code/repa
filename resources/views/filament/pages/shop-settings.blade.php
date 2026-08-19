<x-filament-panels::page>
    <x-filament::section
        icon="heroicon-o-cog-6-tooth"
        icon-color="primary"
        heading="Основные контакты"
        description="Телефон, почта и адрес — отображаются в шапке и подвале витрины."
    >
        <form wire:submit="save" class="space-y-6">
            {{ $this->form }}

            <div class="flex justify-end">
                <x-filament::button type="submit">
                    Сохранить настройки
                </x-filament::button>
            </div>
        </form>
    </x-filament::section>
</x-filament-panels::page>
