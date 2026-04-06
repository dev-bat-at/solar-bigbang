@php
    use App\Support\AdminTopbarAlerts;
    use Filament\Support\Icons\Heroicon;

    $items = collect(AdminTopbarAlerts::itemsFor(filament()->auth()->user()));
    $totalCount = $items->sum('unread_count');
    $hasFreshItems = $totalCount > 0;
@endphp

<div wire:poll.30s>
    <x-filament::dropdown
        placement="bottom-end"
        teleport
        width="sm"
    >
        <x-slot name="trigger">
            <div class="relative">
                <x-filament::icon-button
                    :badge="$totalCount ?: null"
                    :badge-color="$hasFreshItems ? 'danger' : 'gray'"
                    color="gray"
                    :icon="Heroicon::OutlinedBell"
                    icon-size="lg"
                    label="Thông báo quản trị"
                />

                @if ($hasFreshItems)
                    <span class="absolute right-2 top-2 flex h-2.5 w-2.5">
                        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-danger-500 opacity-75"></span>
                        <span class="relative inline-flex h-2.5 w-2.5 rounded-full bg-danger-500"></span>
                    </span>
                @endif
            </div>
        </x-slot>

        <x-filament::dropdown.list>
            @foreach ($items as $item)
                <x-filament::dropdown.list.item
                    :badge="$item['unread_count'] ?: null"
                    :badge-color="$item['color']"
                    :color="$item['unread_count'] > 0 ? $item['color'] : 'gray'"
                    :disabled="blank($item['url'])"
                    :href="$item['url']"
                    :icon="$item['icon']"
                    :tag="filled($item['url']) ? 'a' : 'button'"
                >
                    <div class="flex min-w-0 flex-col gap-0.5">
                        <div class="flex items-center gap-2">
                            <span>{{ $item['label'] }}</span>

                            @if ($item['unread_count'] > 0)
                                <x-filament::badge color="danger" size="xs">
                                    Mới {{ $item['unread_count'] }}
                                </x-filament::badge>
                            @endif
                        </div>

                        <span class="text-xs text-gray-500 dark:text-gray-400">
                            {{ $item['description'] }}

                            @if ($item['total_count'] > 0)
                                <span class="block">
                                    Tổng hiện có: {{ number_format($item['total_count'], 0, ',', '.') }}
                                </span>
                            @endif
                        </span>
                    </div>
                </x-filament::dropdown.list.item>
            @endforeach
        </x-filament::dropdown.list>
    </x-filament::dropdown>
</div>
