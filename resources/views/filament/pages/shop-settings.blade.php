<x-filament-panels::page>
    <form wire:submit="save" class="fi-section rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <div class="space-y-6">
            {{ $this->form }}

            <x-filament::button type="submit">
                Сохранить настройки
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
