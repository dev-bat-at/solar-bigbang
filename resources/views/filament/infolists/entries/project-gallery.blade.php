@php
    $images = collect($getState() ?? [])
        ->filter()
        ->map(function ($image) {
            $url = \Illuminate\Support\Facades\Storage::disk('root_public')->url($image);

            return \Illuminate\Support\Str::startsWith($url, ['http://', 'https://'])
                ? $url
                : url($url);
        })
        ->values();
@endphp

@if ($images->isNotEmpty())
    <div
        x-data="{
            images: {{ \Illuminate\Support\Js::from($images->all()) }},
            currentIndex: 0,
            next() {
                this.currentIndex = (this.currentIndex + 1) % this.images.length
            },
            prev() {
                this.currentIndex = (this.currentIndex - 1 + this.images.length) % this.images.length
            }
        }"
        class="space-y-4"
    >
        <div class="overflow-hidden rounded-2xl border bg-slate-100">
            <div class="relative aspect-[16/10] w-full bg-slate-900">
                <img
                    :src="images[currentIndex]"
                    :alt="`Ảnh công trình ${currentIndex + 1}`"
                    class="h-full w-full object-contain"
                >

                <button
                    type="button"
                    x-show="images.length > 1"
                    x-cloak
                    x-on:click="prev()"
                    class="absolute left-3 top-1/2 flex h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full bg-black/55 text-2xl text-white transition hover:bg-black/70"
                    aria-label="Ảnh trước"
                >
                    ‹
                </button>

                <button
                    type="button"
                    x-show="images.length > 1"
                    x-cloak
                    x-on:click="next()"
                    class="absolute right-3 top-1/2 flex h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full bg-black/55 text-2xl text-white transition hover:bg-black/70"
                    aria-label="Ảnh tiếp theo"
                >
                    ›
                </button>

                <div class="absolute bottom-3 right-3 rounded-full bg-black/55 px-3 py-1 text-xs font-medium text-white">
                    <span x-text="`${currentIndex + 1} / ${images.length}`"></span>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
            <template x-for="(image, index) in images" :key="index">
                <button
                    type="button"
                    x-on:click="currentIndex = index"
                    class="group relative overflow-hidden rounded-lg border transition focus:outline-none focus:ring-2 focus:ring-primary-500"
                    :aria-label="`Xem ảnh công trình ${index + 1}`"
                >
                    <img
                        :src="image"
                        :alt="`Ảnh công trình ${index + 1}`"
                        class="h-40 w-full object-cover"
                    >

                    <span
                        class="pointer-events-none absolute inset-0 ring-2 ring-inset transition group-hover:bg-black/10"
                        :class="currentIndex === index ? 'bg-primary-500/80 ring-primary-500' : 'bg-black/20 ring-transparent'"
                    ></span>
                </button>
            </template>
        </div>
    </div>
@else
    <p class="text-sm text-gray-500">Chưa có ảnh.</p>
@endif


