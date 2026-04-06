@php
    $galleryId = 'product-gallery-' . \Illuminate\Support\Str::uuid();

    $imageItems = collect($getState() ?? [])
        ->filter()
        ->values()
        ->map(function ($item, $index) {
            // Support both legacy (string) and new format ({path, is_primary})
            if (is_array($item)) {
                $path = $item['path'] ?? '';
                $isPrimary = !empty($item['is_primary']);
            } else {
                $path = $item;
                $isPrimary = $index === 0;
            }

            $url = \Illuminate\Support\Facades\Storage::disk('root_public')->url($path);

            return [
                'url'          => \Illuminate\Support\Str::startsWith($url, ['http://', 'https://']) ? $url : url($url),
                'original_name'=> basename($path),
                'display_name' => pathinfo(basename($path), PATHINFO_FILENAME),
                'is_primary'   => $isPrimary,
            ];
        });
@endphp

<div
    class="space-y-6"
    x-data="{
        init() {
            this.loadFancybox()
        },
        loadFancybox() {
            if (! document.getElementById('fancybox-css')) {
                const link = document.createElement('link')
                link.id = 'fancybox-css'
                link.rel = 'stylesheet'
                link.href = 'https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.css'
                document.head.appendChild(link)
            }

            if (typeof Fancybox === 'undefined') {
                const script = document.createElement('script')
                script.src = 'https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.umd.js'
                script.onload = () => this.initFancybox()
                document.head.appendChild(script)
            } else {
                this.initFancybox()
            }
        },
        initFancybox() {
            const selector = '[data-fancybox={{ $galleryId }}]'
            if (typeof Fancybox === 'undefined') return
            Fancybox.unbind(selector)
            Fancybox.bind(selector, { zIndex: 999999, hash: false })
        },
    }"
    x-init="init()"
>
    <div>
        <div class="mb-4 flex items-center justify-between">
            <h4 class="flex items-center gap-2 text-sm font-semibold text-gray-900 dark:text-white">
                <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-gray-100 dark:bg-gray-700">
                    <x-heroicon-m-photo class="h-4 w-4 text-gray-600 dark:text-gray-400" />
                </span>
                Ảnh sản phẩm
                <span class="inline-flex items-center justify-center rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600 dark:bg-gray-700 dark:text-gray-400">
                    {{ $imageItems->count() }}
                </span>
            </h4>
        </div>

        @if ($imageItems->isEmpty())
            <div class="rounded-xl border border-gray-200 bg-gray-50 py-12 text-center dark:border-gray-700 dark:bg-gray-800/50">
                <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-gray-100 dark:bg-gray-700">
                    <x-heroicon-o-photo class="h-8 w-8 text-gray-400" />
                </div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Chưa có ảnh nào</p>
                <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">Sản phẩm này hiện chưa có hình ảnh để xem.</p>
            </div>
        @else
            <div
                class="grid gap-4"
                style="display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));"
            >
                @foreach ($imageItems as $image)
                    <div
                        class="relative group overflow-hidden rounded-xl border-2 bg-white transition-all duration-200 {{ $image['is_primary'] ? 'border-primary-500 ring-2 ring-primary-200 shadow-lg shadow-primary-500/10 dark:ring-primary-800' : 'border-gray-200 hover:border-gray-300 dark:border-gray-700 dark:hover:border-gray-600' }} dark:bg-gray-800"
                    >
                        @if ($image['is_primary'])
                            <div class="absolute left-2 top-2 z-10">
                                <span class="inline-flex items-center gap-1 rounded-lg bg-gradient-to-r from-primary-500 to-primary-600 px-2 py-1 text-xs font-semibold text-white shadow-lg shadow-primary-500/30">
                                    <x-heroicon-m-star class="h-3 w-3" />
                                    Ảnh chính
                                </span>
                            </div>
                        @endif

                        <div class="relative aspect-square overflow-hidden bg-gray-50 dark:bg-gray-700">
                            <a
                                href="{{ $image['url'] }}"
                                data-src="{{ $image['url'] }}"
                                data-fancybox="{{ $galleryId }}"
                                data-caption="{{ $image['original_name'] }}"
                                class="block h-full w-full cursor-zoom-in"
                            >
                                <img
                                    src="{{ $image['url'] }}"
                                    alt="{{ $image['original_name'] }}"
                                    class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
                                    title="Click để phóng to"
                                />
                            </a>
                        </div>

                        <div class="space-y-2 border-t border-gray-100 p-3 dark:border-gray-700/50">
                            <p class="truncate text-xs font-medium text-gray-700 dark:text-gray-300" title="{{ $image['original_name'] }}">
                                {{ $image['display_name'] }}
                            </p>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
