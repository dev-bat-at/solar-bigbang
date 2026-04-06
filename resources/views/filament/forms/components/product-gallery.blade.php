@php
    $galleryId = 'product-form-gallery-' . \Illuminate\Support\Str::uuid();

    $imageItems = collect($images ?? [])
        ->filter()
        ->values()
        ->map(function ($item, $index) {
            if (is_array($item)) {
                $path = $item['path'] ?? '';
                $isPrimary = array_key_exists('is_primary', $item) ? ! empty($item['is_primary']) : $index === 0;
            } else {
                $path = $item;
                $isPrimary = $index === 0;
            }

            if (blank($path)) {
                return null;
            }

            $url = \Illuminate\Support\Facades\Storage::disk('root_public')->url($path);

            return [
                'url' => \Illuminate\Support\Str::startsWith($url, ['http://', 'https://']) ? $url : url($url),
                'original_name' => basename($path),
                'display_name' => pathinfo(basename($path), PATHINFO_FILENAME),
                'is_primary' => $isPrimary,
            ];
        })
        ->filter()
        ->values();
@endphp

<style>
    .product-form-gallery {
        --pfg-panel: #ffffff;
        --pfg-soft: #f8fafc;
        --pfg-muted: #6b7280;
        --pfg-text: #111827;
        --pfg-border: #e5e7eb;
        --pfg-border-strong: #d1d5db;
        --pfg-shadow: 0 1px 2px rgba(15, 23, 42, 0.05);
        --pfg-primary: #2563eb;
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .dark .product-form-gallery {
        --pfg-panel: #18181b;
        --pfg-soft: #27272a;
        --pfg-muted: #a1a1aa;
        --pfg-text: #fafafa;
        --pfg-border: #3f3f46;
        --pfg-border-strong: #52525b;
        --pfg-shadow: 0 1px 2px rgba(0, 0, 0, 0.28);
        --pfg-primary: #60a5fa;
    }

    .product-form-gallery * {
        box-sizing: border-box;
    }

    .product-form-gallery-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
    }

    .product-form-gallery-title {
        display: flex;
        align-items: center;
        gap: 0.65rem;
        margin: 0;
        color: var(--pfg-text);
        font-size: 0.95rem;
        line-height: 1.35rem;
        font-weight: 700;
    }

    .product-form-gallery-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 2rem;
        height: 2rem;
        border-radius: 0.75rem;
        background: var(--pfg-soft);
        color: var(--pfg-muted);
        flex-shrink: 0;
    }

    .product-form-gallery-icon svg,
    .product-form-gallery-badge svg,
    .product-form-gallery-empty svg {
        width: 1rem;
        height: 1rem;
        display: block;
    }

    .product-form-gallery-count {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 2rem;
        height: 2rem;
        padding: 0 0.65rem;
        border-radius: 999px;
        background: var(--pfg-soft);
        color: var(--pfg-muted);
        font-size: 0.78rem;
        line-height: 1rem;
        font-weight: 700;
    }

    .product-form-gallery-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
        gap: 1rem;
    }

    .product-form-gallery-item {
        position: relative;
        overflow: hidden;
        border: 1px solid var(--pfg-border-strong);
        border-radius: 1rem;
        background: var(--pfg-panel);
        box-shadow: var(--pfg-shadow);
        transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
    }

    .product-form-gallery-item:hover {
        transform: translateY(-2px);
        border-color: var(--pfg-primary);
        box-shadow: 0 12px 24px rgba(15, 23, 42, 0.08);
    }

    .product-form-gallery-item-primary {
        border-color: var(--pfg-primary);
        box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.12);
    }

    .product-form-gallery-badge {
        position: absolute;
        top: 0.75rem;
        left: 0.75rem;
        z-index: 2;
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.4rem 0.6rem;
        border-radius: 0.65rem;
        background: linear-gradient(90deg, #3b82f6, #2563eb);
        color: #ffffff;
        font-size: 0.72rem;
        line-height: 1rem;
        font-weight: 700;
    }

    .product-form-gallery-link {
        display: block;
        color: inherit;
        text-decoration: none;
        cursor: zoom-in;
    }

    .product-form-gallery-thumb {
        width: 100%;
        aspect-ratio: 1 / 1;
        object-fit: cover;
        display: block;
        background: var(--pfg-soft);
    }

    .product-form-gallery-footer {
        padding: 0.85rem 0.9rem;
        border-top: 1px solid var(--pfg-border);
    }

    .product-form-gallery-name {
        margin: 0;
        color: var(--pfg-muted);
        font-size: 0.78rem;
        line-height: 1.15rem;
        font-weight: 600;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .product-form-gallery-empty {
        padding: 2.5rem 1rem;
        border: 1px dashed var(--pfg-border-strong);
        border-radius: 1rem;
        background: var(--pfg-soft);
        text-align: center;
        color: var(--pfg-muted);
    }

    .product-form-gallery-empty svg {
        width: 2rem;
        height: 2rem;
        margin: 0 auto 0.75rem;
    }

    @media (max-width: 640px) {
        .product-form-gallery-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 480px) {
        .product-form-gallery-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div
    class="product-form-gallery"
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

            if (typeof Fancybox === 'undefined') {
                return
            }

            Fancybox.unbind(selector)
            Fancybox.bind(selector, {
                zIndex: 999999,
                hash: false,
            })
        },
    }"
    x-init="init()"
>
    <div class="product-form-gallery-head">
        <h4 class="product-form-gallery-title">
            <span class="product-form-gallery-icon">
                <x-heroicon-m-photo />
            </span>
            Ảnh hiện có
        </h4>

        <span class="product-form-gallery-count">{{ $imageItems->count() }}</span>
    </div>

    @if ($imageItems->isEmpty())
        <div class="product-form-gallery-empty">
            <x-heroicon-o-photo />
            <div>Chưa có ảnh nào để xem</div>
        </div>
    @else
        <div class="product-form-gallery-grid">
            @foreach ($imageItems as $image)
                <article class="product-form-gallery-item {{ $image['is_primary'] ? 'product-form-gallery-item-primary' : '' }}">
                    @if ($image['is_primary'])
                        <span class="product-form-gallery-badge">
                            <x-heroicon-m-star />
                            Ảnh chính
                        </span>
                    @endif

                    <a
                        href="{{ $image['url'] }}"
                        data-src="{{ $image['url'] }}"
                        data-fancybox="{{ $galleryId }}"
                        data-caption="{{ $image['original_name'] }}"
                        class="product-form-gallery-link"
                    >
                        <img
                            src="{{ $image['url'] }}"
                            alt="{{ $image['original_name'] }}"
                            class="product-form-gallery-thumb"
                            title="Click để phóng to"
                        />
                    </a>

                    <div class="product-form-gallery-footer">
                        <p class="product-form-gallery-name" title="{{ $image['original_name'] }}">{{ $image['display_name'] }}</p>
                    </div>
                </article>
            @endforeach
        </div>
    @endif
</div>
