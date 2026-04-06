@php
    $faqs = collect($getState() ?? [])->filter()->values();
@endphp

@if ($faqs->isEmpty())
    <div class="rounded-xl border border-gray-200 bg-gray-50 py-10 text-center dark:border-gray-700 dark:bg-gray-800/50">
        <p class="text-sm text-gray-500 dark:text-gray-400">Chưa có câu hỏi nào.</p>
    </div>
@else
    <div class="space-y-4">
        @foreach ($faqs as $i => $faq)
            <div
                class="overflow-hidden rounded-xl border border-gray-200 dark:border-gray-700"
                x-data="{ open: false }"
            >
                {{-- Header / question --}}
                <button
                    type="button"
                    class="flex w-full items-center justify-between gap-4 bg-white px-5 py-4 text-left transition hover:bg-gray-50 dark:bg-gray-800 dark:hover:bg-gray-700/50"
                    x-on:click="open = !open"
                >
                    <div class="flex items-start gap-3">
                        <span class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-primary-100 text-xs font-bold text-primary-600 dark:bg-primary-900/30 dark:text-primary-400">
                            {{ $i + 1 }}
                        </span>
                        <div>
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">
                                🇻🇳 {{ $faq['question_vi'] ?? '—' }}
                            </p>
                            @if (!empty($faq['question_en']))
                                <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                                    🇬🇧 {{ $faq['question_en'] }}
                                </p>
                            @endif
                        </div>
                    </div>
                    <x-heroicon-m-chevron-down
                        class="h-5 w-5 shrink-0 text-gray-400 transition-transform duration-200"
                        x-bind:class="open ? 'rotate-180' : ''"
                    />
                </button>

                {{-- Answer --}}
                <div
                    x-show="open"
                    x-collapse
                    class="border-t border-gray-100 bg-gray-50 px-5 py-4 dark:border-gray-700 dark:bg-gray-900/40"
                >
                    <div class="space-y-3">
                        @if (!empty($faq['answer_vi']))
                            <div>
                                <p class="mb-1 text-xs font-semibold uppercase tracking-wide text-gray-400 dark:text-gray-500">
                                    🇻🇳 Tiếng Việt
                                </p>
                                <p class="text-sm text-gray-700 dark:text-gray-300">{{ $faq['answer_vi'] }}</p>
                            </div>
                        @endif
                        @if (!empty($faq['answer_en']))
                            <div>
                                <p class="mb-1 text-xs font-semibold uppercase tracking-wide text-gray-400 dark:text-gray-500">
                                    🇬🇧 English
                                </p>
                                <p class="text-sm text-gray-700 dark:text-gray-300">{{ $faq['answer_en'] }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endif
