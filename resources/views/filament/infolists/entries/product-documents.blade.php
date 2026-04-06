@php
    $docs = collect($getState() ?? [])->filter()->values();
    $iconMap = [
        'pdf'  => 'heroicon-o-document-text',
        'doc'  => 'heroicon-o-document',
        'docx' => 'heroicon-o-document',
        'xls'  => 'heroicon-o-table-cells',
        'xlsx' => 'heroicon-o-table-cells',
    ];
@endphp

@if ($docs->isEmpty())
    <div class="rounded-xl border border-gray-200 bg-gray-50 py-10 text-center dark:border-gray-700 dark:bg-gray-800/50">
        <p class="text-sm text-gray-500 dark:text-gray-400">Chưa có tài liệu nào.</p>
    </div>
@else
    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($docs as $doc)
            @php
                $path = $doc['path'] ?? '';
                $nameVi = $doc['name_vi'] ?? basename($path);
                $nameEn = $doc['name_en'] ?? '';
                $ext  = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                $url  = \Illuminate\Support\Facades\Storage::disk('root_public')->url($path);
                $url  = \Illuminate\Support\Str::startsWith($url, ['http://', 'https://']) ? $url : url($url);

                $colorMap = [
                    'pdf'  => 'text-red-600 bg-red-50 dark:text-red-400 dark:bg-red-900/20',
                    'doc'  => 'text-blue-600 bg-blue-50 dark:text-blue-400 dark:bg-blue-900/20',
                    'docx' => 'text-blue-600 bg-blue-50 dark:text-blue-400 dark:bg-blue-900/20',
                    'xls'  => 'text-green-600 bg-green-50 dark:text-green-400 dark:bg-green-900/20',
                    'xlsx' => 'text-green-600 bg-green-50 dark:text-green-400 dark:bg-green-900/20',
                ];
                $color = $colorMap[$ext] ?? 'text-gray-600 bg-gray-50 dark:text-gray-400 dark:bg-gray-800';
            @endphp
            <a
                href="{{ $url }}"
                target="_blank"
                class="flex items-center gap-3 rounded-xl border border-gray-200 bg-white p-4 shadow-sm transition hover:shadow-md hover:border-primary-300 dark:border-gray-700 dark:bg-gray-800 dark:hover:border-primary-700"
            >
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg {{ $color }}">
                    <x-dynamic-component :component="$iconMap[$ext] ?? 'heroicon-o-document'" class="h-5 w-5" />
                </span>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-medium text-gray-900 dark:text-white">🇻🇳 {{ $nameVi }}</p>
                    @if(!empty($nameEn))
                    <p class="truncate text-xs text-gray-500 dark:text-gray-400 mt-0.5">🇬🇧 {{ $nameEn }}</p>
                    @endif
                    <p class="mt-1 text-xs uppercase text-gray-400 dark:text-gray-500">{{ strtoupper($ext) }}</p>
                </div>
                <x-heroicon-m-arrow-down-tray class="h-4 w-4 shrink-0 text-gray-400 dark:text-gray-500" />
            </a>
        @endforeach
    </div>
@endif
