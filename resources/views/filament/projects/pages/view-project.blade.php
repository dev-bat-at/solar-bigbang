<x-filament-panels::page>
    @php
        $record = $this->record;

        $images = collect($record->images ?? [])
            ->filter()
            ->values()
            ->map(function ($image, $index) {
                $url = \Illuminate\Support\Facades\Storage::disk('root_public')->url($image);

                return [
                    'url' => \Illuminate\Support\Str::startsWith($url, ['http://', 'https://']) ? $url : url($url),
                    'original_name' => basename($image),
                    'display_name' => pathinfo(basename($image), PATHINFO_FILENAME),
                    'is_primary' => $index === 0,
                ];
            });

        $statusLabel = match ($record->status) {
            'approved' => 'Đã duyệt',
            'rejected' => 'Bị từ chối',
            default => 'Chờ duyệt',
        };
    @endphp

    <style>
        .project-legacy-page {
            --project-bg-panel: #ffffff;
            --project-bg-header: #f8fafc;
            --project-bg-input: #ffffff;
            --project-bg-soft: #f3f4f6;
            --project-bg-gallery: #ffffff;
            --project-border: #e5e7eb;
            --project-border-strong: #d1d5db;
            --project-text: #111827;
            --project-text-muted: #6b7280;
            --project-text-soft: #475569;
            --project-icon-bg: #f3f4f6;
            --project-icon-color: #6b7280;
            --project-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
        }

        .dark .project-legacy-page {
            --project-bg-panel: #1f1f23;
            --project-bg-header: #1f1f23;
            --project-bg-input: #2a2a2e;
            --project-bg-soft: #2a2a2e;
            --project-bg-gallery: #1f1f23;
            --project-border: #3a3a41;
            --project-border-strong: #4a4a52;
            --project-text: #f9fafb;
            --project-text-muted: #a1a1aa;
            --project-text-soft: #d4d4d8;
            --project-icon-bg: #2a2a2e;
            --project-icon-color: #a1a1aa;
            --project-shadow: 0 1px 2px rgba(0, 0, 0, 0.28);
        }

        .project-legacy-section {
            overflow: hidden;
            border: 1px solid var(--project-border);
            border-radius: 1rem;
            background: var(--project-bg-panel);
            box-shadow: var(--project-shadow);
        }

        .project-legacy-header {
            display: flex;
            align-items: flex-start;
            gap: 0.875rem;
            padding: 1rem 1.25rem;
            border-bottom: 1px solid var(--project-border);
            background: var(--project-bg-header);
        }

        .project-legacy-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 2rem;
            height: 2rem;
            border-radius: 999px;
            background: var(--project-icon-bg);
            color: var(--project-icon-color);
            flex-shrink: 0;
        }

        .project-legacy-icon svg {
            width: 1rem;
            height: 1rem;
            display: block;
        }

        .project-legacy-title {
            margin: 0;
            font-size: 1.2rem;
            line-height: 1.55rem;
            font-weight: 700;
            color: var(--project-text);
        }

        .project-legacy-subtitle {
            margin: 0.25rem 0 0;
            font-size: 0.82rem;
            line-height: 1.25rem;
            color: var(--project-text-muted);
        }

        .project-legacy-body {
            padding: 1.25rem;
        }

        .project-legacy-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 1.25rem 1.5rem;
        }

        .project-legacy-span-full {
            grid-column: 1 / -1;
        }

        .project-legacy-field {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .project-legacy-label {
            margin: 0;
            font-size: 0.88rem;
            line-height: 1.25rem;
            font-weight: 600;
            color: var(--project-text);
        }

        .project-legacy-value {
            min-height: 3rem;
            padding: 0.85rem 1rem;
            border: 1px solid var(--project-border-strong);
            border-radius: 0.85rem;
            background: var(--project-bg-input);
            font-size: 0.9rem;
            line-height: 1.35rem;
            color: var(--project-text);
            box-sizing: border-box;
        }

        .project-legacy-value-textarea {
            min-height: 5.5rem;
            white-space: pre-wrap;
        }

        .project-legacy-status {
            display: inline-flex;
            align-items: center;
            min-height: 3rem;
            padding: 0.85rem 1rem;
            border: 1px solid var(--project-border-strong);
            border-radius: 0.85rem;
            font-size: 0.9rem;
            line-height: 1.35rem;
            font-weight: 600;
            color: var(--project-text);
            background: var(--project-bg-input);
            box-sizing: border-box;
        }

        .project-legacy-gallery-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .project-legacy-gallery-title {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            margin: 0;
            font-size: 0.88rem;
            line-height: 1.2rem;
            font-weight: 700;
            color: var(--project-text);
        }

        .project-legacy-gallery-title svg {
            width: 1rem;
            height: 1rem;
            display: block;
        }

        .project-legacy-count {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 1.8rem;
            height: 1.8rem;
            padding: 0 0.6rem;
            border-radius: 999px;
            background: var(--project-bg-soft);
            font-size: 0.72rem;
            line-height: 1rem;
            font-weight: 600;
            color: var(--project-text-soft);
        }

        .project-legacy-gallery {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(170px, 1fr));
            gap: 1rem;
        }

        .project-legacy-shot {
            position: relative;
            overflow: hidden;
            border: 1px solid var(--project-border-strong);
            border-radius: 0.9rem;
            background: var(--project-bg-gallery);
            transition: border-color 0.2s ease, box-shadow 0.2s ease, transform 0.2s ease;
        }

        .project-legacy-shot:hover {
            transform: translateY(-2px);
            border-color: var(--project-text-muted);
            box-shadow: 0 12px 24px rgba(15, 23, 42, 0.06);
        }

        .project-legacy-shot-primary {
            border-color: #3b82f6;
            box-shadow: 0 0 0 2px rgba(191, 219, 254, 0.8);
        }

        .dark .project-legacy-shot-primary {
            box-shadow: 0 0 0 2px rgba(30, 64, 175, 0.45);
        }

        .project-legacy-badge {
            position: absolute;
            top: 0.5rem;
            left: 0.5rem;
            z-index: 2;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            padding: 0.35rem 0.55rem;
            border-radius: 0.6rem;
            background: linear-gradient(90deg, #3b82f6, #2563eb);
            color: #ffffff;
            font-size: 0.68rem;
            line-height: 1rem;
            font-weight: 700;
        }

        .project-legacy-badge svg {
            width: 0.8rem;
            height: 0.8rem;
            display: block;
        }

        .project-legacy-thumb-link {
            display: block;
            width: 100%;
            text-decoration: none;
            color: inherit;
            cursor: zoom-in;
        }

        .project-legacy-thumb {
            width: 100%;
            aspect-ratio: 1 / 1;
            object-fit: cover;
            display: block;
            background: var(--project-bg-soft);
        }

        .project-legacy-shot-footer {
            padding: 0.75rem;
            border-top: 1px solid var(--project-border);
        }

        .project-legacy-shot-name {
            margin: 0;
            font-size: 0.72rem;
            line-height: 1.1rem;
            font-weight: 500;
            color: var(--project-text-soft);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .project-legacy-empty {
            padding: 2.75rem 1rem;
            border: 1px dashed var(--project-border-strong);
            border-radius: 0.9rem;
            text-align: center;
            color: var(--project-text-muted);
            background: var(--project-bg-header);
        }

        .project-legacy-empty svg {
            width: 2rem;
            height: 2rem;
            display: block;
            margin: 0 auto 0.75rem;
        }

        @media (max-width: 768px) {
            .project-legacy-grid {
                grid-template-columns: 1fr;
            }

            .project-legacy-body {
                padding: 1rem;
            }

            .project-legacy-header {
                padding: 1rem;
            }

            .project-legacy-gallery {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }
    </style>

    <div
        class="project-legacy-page"
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
                if (typeof Fancybox === 'undefined') {
                    return
                }

                Fancybox.unbind('[data-fancybox]')
                Fancybox.bind('[data-fancybox]', {
                    zIndex: 999999,
                    hash: false,
                })
            },
        }"
        x-init="init()"
    >
        <section class="project-legacy-section">
            <div class="project-legacy-header">
                <span class="project-legacy-icon">
                    <x-heroicon-o-information-circle />
                </span>

                <div>
                    <h2 class="project-legacy-title">Thông tin định danh</h2>
                    <p class="project-legacy-subtitle">Cơ bản về dự án/công trình</p>
                </div>
            </div>

            <div class="project-legacy-body">
                <div class="project-legacy-grid">
                    <div class="project-legacy-field">
                        <p class="project-legacy-label">Đại lý thi công</p>
                        <div class="project-legacy-value">{{ $record->dealer?->name ?? '-' }}</div>
                    </div>

                    <div class="project-legacy-field">
                        <p class="project-legacy-label">Hệ</p>
                        <div class="project-legacy-value">{{ $record->systemType?->name ?? '-' }}</div>
                    </div>

                    <div class="project-legacy-field project-legacy-span-full">
                        <p class="project-legacy-label">Tên công trình</p>
                        <div class="project-legacy-value">{{ $record->title ?: '-' }}</div>
                    </div>

                    <div class="project-legacy-field">
                        <p class="project-legacy-label">Công suất</p>
                        <div class="project-legacy-value">{{ $record->capacity ?: '-' }}</div>
                    </div>

                    <div class="project-legacy-field">
                        <p class="project-legacy-label">Thời gian hoàn thành</p>
                        <div class="project-legacy-value">{{ optional($record->completion_date)?->format('d/m/Y') ?: '-' }}</div>
                    </div>

                    <div class="project-legacy-field project-legacy-span-full">
                        <p class="project-legacy-label">Địa điểm thi công (Địa chỉ)</p>
                        <div class="project-legacy-value">{{ $record->address ?: '-' }}</div>
                    </div>

                    <div class="project-legacy-field project-legacy-span-full">
                        <p class="project-legacy-label">Mô tả</p>
                        <div class="project-legacy-value project-legacy-value-textarea">{{ $record->description ?: 'Chưa có mô tả cho công trình này.' }}</div>
                    </div>
                </div>
            </div>
        </section>

        <section class="project-legacy-section">
            <div class="project-legacy-header">
                <span class="project-legacy-icon">
                    <x-heroicon-o-shield-check />
                </span>

                <div>
                    <h2 class="project-legacy-title">Kiểm duyệt</h2>
                    <p class="project-legacy-subtitle">Trạng thái phê duyệt công trình</p>
                </div>
            </div>

            <div class="project-legacy-body">
                <div class="project-legacy-grid">
                    <div class="project-legacy-field">
                        <p class="project-legacy-label">Trạng thái</p>
                        <div class="project-legacy-status">{{ $statusLabel }}</div>
                    </div>

                    <div class="project-legacy-field">
                        <p class="project-legacy-label">Ngày tạo</p>
                        <div class="project-legacy-value">{{ optional($record->created_at)?->format('d/m/Y H:i') ?: '-' }}</div>
                    </div>

                    <div class="project-legacy-field">
                        <p class="project-legacy-label">Cập nhật</p>
                        <div class="project-legacy-value">{{ optional($record->updated_at)?->format('d/m/Y H:i') ?: '-' }}</div>
                    </div>

                    <div class="project-legacy-field project-legacy-span-full">
                        <p class="project-legacy-label">Lý do từ chối</p>
                        <div class="project-legacy-value project-legacy-value-textarea">{{ $record->rejection_reason ?: 'Không có' }}</div>
                    </div>
                </div>
            </div>
        </section>

        <section class="project-legacy-section">
            <div class="project-legacy-header">
                <span class="project-legacy-icon">
                    <x-heroicon-o-photo />
                </span>

                <div>
                    <h2 class="project-legacy-title">Hình ảnh</h2>
                    <p class="project-legacy-subtitle">Hình ảnh thi công thực tế</p>
                </div>
            </div>

            <div class="project-legacy-body">
                <div class="project-legacy-gallery-head">
                    <h4 class="project-legacy-gallery-title">
                        <x-heroicon-m-photo />
                        Ảnh hiện có
                    </h4>

                    <span class="project-legacy-count">{{ $images->count() }}</span>
                </div>

                @if ($images->isEmpty())
                    <div class="project-legacy-empty">
                        <x-heroicon-o-photo />
                        <div>Chưa có ảnh nào</div>
                    </div>
                @else
                    <div class="project-legacy-gallery">
                        @foreach ($images as $image)
                            <article class="project-legacy-shot {{ $image['is_primary'] ? 'project-legacy-shot-primary' : '' }}">
                                @if ($image['is_primary'])
                                    <span class="project-legacy-badge">
                                        <x-heroicon-m-star />
                                        Ảnh chính
                                    </span>
                                @endif

                                <a
                                    href="{{ $image['url'] }}"
                                    data-src="{{ $image['url'] }}"
                                    data-fancybox="project-gallery"
                                    data-caption="{{ $image['original_name'] }}"
                                    class="project-legacy-thumb-link"
                                >
                                    <img
                                        src="{{ $image['url'] }}"
                                        alt="{{ $image['original_name'] }}"
                                        class="project-legacy-thumb"
                                        title="Click để phóng to"
                                    />
                                </a>

                                <div class="project-legacy-shot-footer">
                                    <p class="project-legacy-shot-name" title="{{ $image['original_name'] }}">{{ $image['display_name'] }}</p>
                                </div>
                            </article>
                        @endforeach
                    </div>
                @endif
            </div>
        </section>
    </div>
</x-filament-panels::page>
