<script>
    document.addEventListener('livewire:init', () => {
        if (window.__solarCrudErrorSnackbarInitialized) {
            return
        }

        window.__solarCrudErrorSnackbarInitialized = true

        const defaultNotification = {
            title: 'Thao tác không thành công',
            message: 'Hệ thống gặp lỗi trong quá trình xử lý. Vui lòng thử lại.',
        }

        const shouldIgnoreNotificationsComponent = (request) => {
            try {
                const payload = request?.payload

                if (! payload) {
                    return false
                }

                const parsedPayload = JSON.parse(payload)

                if (parsedPayload.components.length !== 1) {
                    return false
                }

                return parsedPayload.components.some((component) => {
                    return JSON.parse(component.snapshot).data?.isFilamentNotificationsComponent
                })
            } catch (error) {
                return false
            }
        }

        const parseErrorPayload = async (response) => {
            if (! response) {
                return defaultNotification
            }

            try {
                const contentType = response.headers.get('content-type') ?? ''

                if (contentType.includes('application/json')) {
                    const json = await response.clone().json()

                    return {
                        title: json.title ?? defaultNotification.title,
                        message: json.message ?? defaultNotification.message,
                    }
                }

                const text = await response.clone().text()
                const titleMatch = text.match(/<title>(.*?)<\/title>/i)

                return {
                    title: titleMatch?.[1] ?? defaultNotification.title,
                    message: defaultNotification.message,
                }
            } catch (error) {
                return defaultNotification
            }
        }

        const sendNotification = (payload) => {
            new FilamentNotification()
                .title(payload.title ?? defaultNotification.title)
                .body(payload.message ?? defaultNotification.message)
                .danger()
                .send()
        }

        Livewire.interceptRequest(({ request, onError, onFailure }) => {
            onError(async ({ response, preventDefault }) => {
                if (shouldIgnoreNotificationsComponent(request)) {
                    return
                }

                preventDefault()

                const payload = await parseErrorPayload(response)
                sendNotification(payload)
            })

            onFailure(() => {
                sendNotification(defaultNotification)
            })
        })
    })
</script>
