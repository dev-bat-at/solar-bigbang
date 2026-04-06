<div class="ms-3" x-data>
    <button
        type="button"
        class="inline-flex items-center gap-2 rounded-xl border border-rose-400/20 bg-rose-500/10 px-3 py-2 text-sm font-semibold text-rose-200 transition hover:border-rose-300/40 hover:bg-rose-500/15"
        x-on:click="
            fetch('{{ route('filament.admin.test-error-snackbar') }}', {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            })
            .then(async (response) => {
                const payload = await response.json().catch(() => ({
                    title: 'Lỗi kiểm thử',
                    message: 'Không đọc được nội dung phản hồi từ máy chủ.',
                }))

                new FilamentNotification()
                    .title(payload.title ?? 'Lỗi kiểm thử')
                    .body(payload.message ?? 'Đây là thông báo lỗi kiểm thử.')
                    .danger()
                    .send()
            })
        "
    >
        <span class="inline-flex h-2.5 w-2.5 rounded-full bg-rose-400"></span>
        Test lỗi
    </button>
</div>

