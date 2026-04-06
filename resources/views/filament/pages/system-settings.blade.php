<x-filament-panels::page>
    <form wire:submit="submit">
        {{ $this->form }}

        <div class="mt-4 flex justify-end">
            <x-filament::button type="submit" size="lg">
                Lưu cấu hình
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
