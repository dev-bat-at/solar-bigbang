@once
    <style>
        #app-page-load-indicator {
            position: fixed;
            inset: 0 auto auto 0;
            z-index: 9999;
            height: 3px;
            width: 100%;
            pointer-events: none;
            opacity: 0;
            transition: opacity 180ms ease;
        }

        body.fi-body {
            background-color: #f5f7fb;
        }

        #app-page-load-indicator.is-visible {
            opacity: 1;
        }

        #app-page-load-indicator .app-page-load-indicator__bar {
            height: 100%;
            width: 28%;
            border-radius: 0 999px 999px 0;
            background: #2563eb;
            box-shadow: 0 0 14px rgba(37, 99, 235, 0.28);
            transform: translateX(-120%);
            will-change: transform;
        }

        #app-page-load-indicator.is-running .app-page-load-indicator__bar {
            animation: app-page-load-indicator-run 1.15s cubic-bezier(0.25, 0.1, 0.25, 1) infinite;
        }

        #app-page-load-indicator.is-finishing .app-page-load-indicator__bar {
            animation: none;
            transform: translateX(260%);
            transition: transform 220ms ease-out;
        }

        @keyframes app-page-load-indicator-run {
            0% {
                transform: translateX(-120%);
            }

            65% {
                transform: translateX(170%);
            }

            100% {
                transform: translateX(260%);
            }
        }
    </style>
@endonce

<div id="app-page-load-indicator" aria-hidden="true">
    <div class="app-page-load-indicator__bar"></div>
</div>

@once
    <script>
        (() => {
            if (window.__appPageLoadIndicatorInitialized) {
                return
            }

            window.__appPageLoadIndicatorInitialized = true

            const indicator = document.getElementById('app-page-load-indicator')

            if (! indicator) {
                return
            }

            let finishTimer = null
            let activeRequests = 0
            let isInitialLoading = document.readyState !== 'complete'

            const isEligibleLink = (link) => {
                if (! link) {
                    return false
                }

                if (link.target && link.target !== '_self') {
                    return false
                }

                if (link.hasAttribute('download')) {
                    return false
                }

                if (link.dataset.noLoadingIndicator !== undefined) {
                    return false
                }

                const href = link.getAttribute('href')

                if (! href || href.startsWith('#') || href.startsWith('mailto:') || href.startsWith('tel:')) {
                    return false
                }

                try {
                    const targetUrl = new URL(link.href, window.location.origin)

                    return targetUrl.origin === window.location.origin
                        && targetUrl.href !== window.location.href
                } catch (error) {
                    return false
                }
            }

            const start = () => {
                window.clearTimeout(finishTimer)
                indicator.classList.remove('is-finishing')
                indicator.classList.add('is-visible', 'is-running')
            }

            const hideIfIdle = () => {
                if (isInitialLoading || activeRequests > 0) {
                    return
                }

                if (! indicator.classList.contains('is-visible')) {
                    return
                }

                indicator.classList.remove('is-running')
                indicator.classList.add('is-finishing')

                window.clearTimeout(finishTimer)
                finishTimer = window.setTimeout(() => {
                    indicator.classList.remove('is-visible', 'is-finishing')
                }, 240)
            }

            const finish = () => {
                activeRequests = Math.max(0, activeRequests - 1)
                hideIfIdle()
            }

            window.appPageLoader = { start, finish }

            if (isInitialLoading) {
                start()
            }

            document.addEventListener('submit', (event) => {
                const form = event.target

                if (form instanceof HTMLFormElement && ! form.hasAttribute('data-no-loading-indicator')) {
                    activeRequests += 1
                    start()
                }
            }, true)

            window.addEventListener('pageshow', () => {
                activeRequests = 0
                isInitialLoading = false
                hideIfIdle()
            })

            window.addEventListener('load', () => {
                activeRequests = 0
                isInitialLoading = false
                hideIfIdle()
            })

            document.addEventListener('livewire:init', () => {
                document.addEventListener('click', (event) => {
                    if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
                        return
                    }

                    const link = event.target.closest('a')

                    if (isEligibleLink(link)) {
                        activeRequests += 1
                        start()
                    }
                }, true)

                if (window.Livewire?.interceptRequest) {
                    window.Livewire.interceptRequest(({ onSend, onSuccess, onError, onFailure, onCancel, onFinish }) => {
                        onSend(() => {
                            activeRequests += 1
                            start()
                        })

                        const complete = () => {
                            finish()
                        }

                        onSuccess(complete)
                        onError(complete)
                        onFailure(complete)
                        onCancel(complete)
                        onFinish(() => {
                            hideIfIdle()
                        })
                    })
                } else if (window.Livewire?.hook) {
                    window.Livewire.hook('commit', ({ respond, succeed, fail }) => {
                        activeRequests += 1
                        start()

                        const stop = () => finish()

                        respond(stop)
                        succeed(stop)
                        fail(stop)
                    })
                }

                document.addEventListener('livewire:navigate', () => {
                    start()
                })

                document.addEventListener('livewire:navigated', () => {
                    activeRequests = 0
                    hideIfIdle()
                })
            })
        })()
    </script>
@endonce
