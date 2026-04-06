@php
    $specs = collect($getState() ?? [])->filter()->values();
@endphp

@if ($specs->isEmpty())
    <div class="rounded-xl border border-gray-200 bg-gray-50 py-10 text-center dark:border-gray-700 dark:bg-gray-800/50">
        <p class="text-sm text-gray-500 dark:text-gray-400">Chưa có thông số nào.</p>
    </div>
@else
    <div class="overflow-hidden rounded-xl border border-gray-200 dark:border-gray-700">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-800">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 w-1/4">
                        Thông số (VI)
                    </th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 w-1/4">
                        Thông số (EN)
                    </th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 w-1/4">
                        Giá trị (VI)
                    </th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 w-1/4">
                        Giá trị (EN)
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 bg-white dark:divide-gray-700 dark:bg-gray-900">
                @foreach ($specs as $spec)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                        <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-white">
                            {{ $spec['label_vi'] ?? '—' }}
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                            {{ $spec['label_en'] ?? '—' }}
                        </td>
                        <td class="px-4 py-3 text-sm font-semibold text-primary-600 dark:text-primary-400">
                            {{ $spec['value_vi'] ?? ($spec['value'] ?? '—') }}
                        </td>
                        <td class="px-4 py-3 text-sm font-semibold text-primary-600 dark:text-primary-400">
                            {{ $spec['value_en'] ?? '—' }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
