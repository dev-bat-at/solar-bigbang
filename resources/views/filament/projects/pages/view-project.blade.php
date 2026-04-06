<x-filament-panels::page>
    @php
        $record = $this->record;

        $imageUrls = collect($record->images ?? [])
            ->filter()
            ->map(function ($image) {
                $url = \Illuminate\Support\Facades\Storage::disk('root_public')->url($image);

                return \Illuminate\Support\Str::startsWith($url, ['http://', 'https://'])
                    ? $url
                    : url($url);
            })
            ->values();

        $statusLabel = match ($record->status) {
            'approved' => 'Đã duyệt',
            'rejected' => 'Từ chối',
            default => 'Chờ duyệt',
        };

        $statusClasses = match ($record->status) {
            'approved' => 'bg-emerald-500/15 text-emerald-300 ring-emerald-400/30',
            'rejected' => 'bg-rose-500/15 text-rose-300 ring-rose-400/30',
            default => 'bg-amber-500/15 text-amber-300 ring-amber-400/30',
        };
    @endphp

    <div
        x-data="{
            images: {{ \Illuminate\Support\Js::from($imageUrls->all()) }},
            currentIndex: 0,
            open: false,
            openGallery(index) {
                this.currentIndex = index
                this.open = true
                document.body.classList.add('overflow-hidden')
            },
            closeGallery() {
                this.open = false
                document.body.classList.remove('overflow-hidden')
            },
            nextImage() {
                if (! this.images.length) return
                this.currentIndex = (this.currentIndex + 1) % this.images.length
            },
            prevImage() {
                if (! this.images.length) return
                this.currentIndex = (this.currentIndex - 1 + this.images.length) % this.images.length
            }
        }"
        x-on:keydown.window.escape="if (open) closeGallery()"
        x-on:keydown.window.arrow-right.prevent="if (open) nextImage()"
        x-on:keydown.window.arrow-left.prevent="if (open) prevImage()"
        class="min-h-[calc(100vh-12rem)] rounded-[30px] bg-[#09090b] p-4 text-white sm:p-6 xl:p-7"
    >
        <div class="grid gap-5 xl:grid-cols-[1.05fr_1fr]">
            <section class="overflow-hidden rounded-[22px] border border-white/10 bg-white/[0.06] shadow-[0_24px_80px_rgba(0,0,0,0.28)]">
                <div class="border-b border-white/10 px-6 py-5">
                    <h2 class="text-xl font-semibold tracking-tight text-white">Thông tin công trình</h2>
                </div>

                <div class="grid gap-8 px-6 py-7 md:grid-cols-2 xl:grid-cols-3">
                    <div class="space-y-2">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-white/45">Tên công trình</p>
                        <p class="text-lg font-semibold text-white">{{ $record->title }}</p>
                    </div>

                    <div class="space-y-2">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-white/45">Đại lý thi công</p>
                        <p class="text-lg font-semibold text-white">{{ $record->dealer?->name ?? '-' }}</p>
                    </div>

                    <div class="space-y-2">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-white/45">Hệ sản phẩm</p>
                        <p class="text-lg font-semibold text-white">{{ $record->systemType?->name ?? '-' }}</p>
                    </div>

                    <div class="space-y-2 md:col-span-2 xl:col-span-3">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-white/45">Địa chỉ</p>
                        <p class="text-base font-medium leading-7 text-white/90">{{ $record->address ?: '-' }}</p>
                    </div>

                    <div class="space-y-2 md:col-span-2 xl:col-span-3">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-white/45">Mô tả</p>
                        <p class="max-w-4xl text-base leading-7 text-white/75">{{ $record->description ?: 'Chưa có mô tả cho công trình này.' }}</p>
                    </div>
                </div>
            </section>

            <section class="overflow-hidden rounded-[22px] border border-white/10 bg-white/[0.06] shadow-[0_24px_80px_rgba(0,0,0,0.28)]">
                <div class="border-b border-white/10 px-6 py-5">
                    <h2 class="text-xl font-semibold tracking-tight text-white">Trạng thái và tiến độ</h2>
                </div>

                <div class="grid gap-8 px-6 py-7 md:grid-cols-2 xl:grid-cols-3">
                    <div class="space-y-2">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-white/45">Trạng thái</p>
                        <span class="inline-flex rounded-full px-3 py-1 text-sm font-semibold ring-1 {{ $statusClasses }}">
                            {{ $statusLabel }}
                        </span>
                    </div>

                    <div class="space-y-2">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-white/45">Công suất</p>
                        <p class="text-lg font-semibold text-white">{{ $record->capacity ?: '-' }}</p>
                    </div>

                    <div class="space-y-2">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-white/45">Hoàn thành</p>
                        <p class="text-lg font-semibold text-white">{{ optional($record->completion_date)?->format('d/m/Y') ?: '-' }}</p>
                    </div>

                    <div class="space-y-2">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-white/45">Ngày tạo</p>
                        <p class="text-base font-medium text-white/90">{{ optional($record->created_at)?->format('d/m/Y H:i') ?: '-' }}</p>
                    </div>

                    <div class="space-y-2">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-white/45">Cập nhật</p>
                        <p class="text-base font-medium text-white/90">{{ optional($record->updated_at)?->format('d/m/Y H:i') ?: '-' }}</p>
                    </div>

                    <div class="space-y-2">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-white/45">Lý do từ chối</p>
                        <p class="text-base font-medium leading-7 text-white/90">{{ $record->rejection_reason ?: '-' }}</p>
                    </div>
                </div>
            </section>
        </div>

        <section class="mt-5 overflow-hidden rounded-[22px] border border-white/10 bg-white/[0.06] shadow-[0_24px_80px_rgba(0,0,0,0.28)]">
            <div class="border-b border-white/10 px-6 py-5">
                <h2 class="text-xl font-semibold tracking-tight text-white">Hình ảnh công trình</h2>
            </div>

            <div class="px-6 py-6">
                @if ($imageUrls->isNotEmpty())
                    <div class="grid grid-cols-2 gap-4 md:grid-cols-3 xl:grid-cols-4">
                        @foreach ($imageUrls as $index => $imageUrl)
                            <button
                                type="button"
                                x-on:click="openGallery({{ $index }})"
                                class="group relative aspect-[0.9] overflow-hidden rounded-[18px] border border-white/10 bg-white/5 text-left transition duration-200 hover:-translate-y-1 hover:border-white/20 hover:shadow-[0_20px_35px_rgba(0,0,0,0.35)] focus:outline-none focus:ring-2 focus:ring-white/40"
                            >
                                <img
                                    src="{{ $imageUrl }}"
                                    alt="Ảnh công trình {{ $index + 1 }}"
                                    class="h-full w-full object-cover transition duration-300 group-hover:scale-[1.03]"
                                >

                                <div class="absolute inset-x-0 bottom-0 h-24 bg-gradient-to-t from-black/55 via-black/10 to-transparent"></div>
                            </button>
                        @endforeach
                    </div>
                @else
                    <div class="flex min-h-56 items-center justify-center rounded-[18px] border border-dashed border-white/15 bg-white/[0.03] px-6 text-center">
                        <div class="space-y-2">
                            <p class="text-base font-semibold text-white/85">Chưa có hình ảnh công trình</p>
                            <p class="text-sm text-white/45">Thêm ảnh ở mục chỉnh sửa để hiển thị tại đây.</p>
                        </div>
                    </div>
                @endif
            </div>
        </section>

        <div
            x-cloak
            x-show="open"
            x-transition.opacity.duration.200ms
            class="fixed inset-0 z-[80] bg-black/85 backdrop-blur-sm"
        >
            <div class="absolute inset-0" x-on:click="closeGallery()"></div>

            <button
                type="button"
                x-on:click="closeGallery()"
                class="absolute right-6 top-6 z-[82] flex h-14 w-14 items-center justify-center rounded-full bg-white/10 text-4xl text-white transition hover:bg-white/20"
                aria-label="Đóng gallery"
            >
                ×
            </button>

            <button
                type="button"
                x-show="images.length > 1"
                x-on:click.stop="prevImage()"
                class="absolute left-5 top-1/2 z-[82] flex h-14 w-14 -translate-y-1/2 items-center justify-center rounded-full bg-white/12 text-5xl leading-none text-white transition hover:bg-white/20"
                aria-label="Ảnh trước"
            >
                ‹
            </button>

            <button
                type="button"
                x-show="images.length > 1"
                x-on:click.stop="nextImage()"
                class="absolute right-5 top-1/2 z-[82] flex h-14 w-14 -translate-y-1/2 items-center justify-center rounded-full bg-white/12 text-5xl leading-none text-white transition hover:bg-white/20"
                aria-label="Ảnh tiếp theo"
            >
                ›
            </button>

            <div class="relative z-[81] flex h-full items-center justify-center px-6 py-16">
                <div class="flex max-h-full max-w-5xl flex-col items-center justify-center gap-5">
                    <div class="overflow-hidden rounded-[22px] bg-white/5 shadow-[0_30px_80px_rgba(0,0,0,0.45)]">
                        <img
                            :src="images[currentIndex]"
                            :alt="`Ảnh công trình ${currentIndex + 1}`"
                            class="max-h-[72vh] max-w-[min(88vw,900px)] object-contain"
                        >
                    </div>

                    <div
                        x-show="images.length > 1"
                        class="rounded-full bg-white/12 px-4 py-1.5 text-sm font-semibold tracking-wide text-white"
                        x-text="`${currentIndex + 1} / ${images.length}`"
                    ></div>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
