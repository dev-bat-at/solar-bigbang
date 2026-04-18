<!doctype html>
<html lang="en" data-theme="{{ $config->get('ui.theme', 'light') }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="color-scheme" content="{{ $config->get('ui.theme', 'light') }}">
    <title>{{ $config->get('ui.title') ?? config('app.name') . ' - API Docs' }}</title>

    <script src="https://unpkg.com/@stoplight/elements@8.4.2/web-components.min.js"></script>
    <link rel="stylesheet" href="https://unpkg.com/@stoplight/elements@8.4.2/styles.min.css">

    <script>
        const originalFetch = window.fetch;

        // intercept TryIt requests and add the XSRF-TOKEN header,
        // which is necessary for Sanctum cookie-based authentication to work correctly
        window.__scrambleDocsPersistResponse = null;

        window.fetch = async (url, options) => {
            const CSRF_TOKEN_COOKIE_KEY = "XSRF-TOKEN";
            const CSRF_TOKEN_HEADER_KEY = "X-XSRF-TOKEN";
            const API_KEY_STORAGE = "scramble.docs.api_key";
            const BEARER_STORAGE = "scramble.docs.bearer_token";
            const getOperationKey = () => {
                const hash = (window.location.hash || '').trim();

                if (hash) {
                    return hash;
                }

                return window.location.pathname || 'global';
            };
            const getCookieValue = (key) => {
                const cookie = document.cookie.split(';').find((cookie) => cookie.trim().startsWith(key));
                return cookie?.split("=")[1];
            };
            const getStorageValue = (key) => {
                try {
                    return window.localStorage.getItem(key) || '';
                } catch (error) {
                    return '';
                }
            };
            const normalizeBearerValue = (value) => {
                const trimmed = (value || '').trim();

                if (! trimmed) {
                    return '';
                }

                return /^Bearer\s+/i.test(trimmed) ? trimmed : `Bearer ${trimmed}`;
            };

            const updateFetchHeaders = (
                headers,
                headerKey,
                headerValue,
            ) => {
                if (headers instanceof Headers) {
                    headers.set(headerKey, headerValue);
                } else if (Array.isArray(headers)) {
                    headers.push([headerKey, headerValue]);
                } else if (headers) {
                    headers[headerKey] = headerValue;
                }
            };
            const csrfToken = getCookieValue(CSRF_TOKEN_COOKIE_KEY);
            const apiKey = getStorageValue(API_KEY_STORAGE).trim();
            const bearerToken = normalizeBearerValue(getStorageValue(BEARER_STORAGE));
            const { headers = new Headers() } = options || {};
            const operationKey = getOperationKey();

            if (csrfToken) {
                updateFetchHeaders(headers, CSRF_TOKEN_HEADER_KEY, decodeURIComponent(csrfToken));
            }

            if (apiKey) {
                updateFetchHeaders(headers, 'X-API-KEY', apiKey);
            }

            if (bearerToken) {
                updateFetchHeaders(headers, 'Authorization', bearerToken);
            }

            const response = await originalFetch(url, {
                ...options,
                headers,
            });

            if (typeof window.__scrambleDocsPersistResponse === 'function') {
                try {
                    window.__scrambleDocsPersistResponse(operationKey, url, options, response.clone());
                } catch (error) {
                    // Ignore persistence errors and return the original response.
                }
            }

            return response;
        };
    </script>

    <style>
        html, body { margin:0; height:100%; }
        body { background-color: var(--color-canvas); }
        .docs-export-toolbar {
            position: fixed;
            top: 1rem;
            right: 1rem;
            z-index: 20;
            display: flex;
            gap: 0.75rem;
            align-items: center;
            flex-wrap: wrap;
            justify-content: flex-end;
        }
        .docs-auth-input {
            min-width: 16rem;
            min-height: 2.5rem;
            padding: 0.65rem 0.9rem;
            border-radius: 999px;
            border: 1px solid rgba(148, 163, 184, 0.35);
            background: rgba(255, 255, 255, 0.94);
            color: #0f172a;
            font-size: 0.9rem;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08);
            backdrop-filter: blur(10px);
        }
        .docs-auth-input::placeholder {
            color: #64748b;
        }
        .docs-export-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 2.5rem;
            padding: 0.65rem 1rem;
            border-radius: 999px;
            border: 1px solid rgba(148, 163, 184, 0.3);
            background: rgba(15, 23, 42, 0.92);
            color: #f8fafc;
            font-size: 0.9rem;
            font-weight: 600;
            text-decoration: none;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.18);
            backdrop-filter: blur(10px);
        }
        .docs-export-button:hover {
            background: rgba(30, 41, 59, 0.96);
        }
        .docs-inline-response {
            margin: 0.75rem 0 1rem;
            border-radius: 0.75rem;
            border: 1px solid rgba(148, 163, 184, 0.28);
            background: rgba(255, 255, 255, 0.96);
            box-shadow: 0 10px 28px rgba(15, 23, 42, 0.1);
            overflow: hidden;
        }
        .docs-inline-response[hidden] {
            display: none;
        }
        .docs-inline-response-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            border-bottom: 1px solid rgba(148, 163, 184, 0.18);
        }
        .docs-inline-response-title {
            font-size: 0.9rem;
            font-weight: 700;
            color: #0f172a;
        }
        .docs-inline-response-meta {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex-wrap: wrap;
            font-size: 0.75rem;
            color: #475569;
        }
        .docs-inline-response-badge {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            padding: 0.2rem 0.55rem;
            background: rgba(15, 23, 42, 0.08);
            color: #0f172a;
            font-weight: 700;
        }
        .docs-inline-response-clear {
            border: 0;
            background: rgba(15, 23, 42, 0.08);
            color: #0f172a;
            min-height: 2rem;
            padding: 0.45rem 0.8rem;
            border-radius: 999px;
            cursor: pointer;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .docs-inline-response-body {
            margin: 0;
            padding: 1rem;
            overflow: auto;
            font-size: 0.8rem;
            line-height: 1.55;
            color: #0f172a;
            background: rgba(248, 250, 252, 0.95);
            white-space: pre-wrap;
            word-break: break-word;
            flex: 1;
        }
        .docs-export-hint {
            padding: 0.65rem 0.9rem;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.92);
            color: #334155;
            font-size: 0.8rem;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.1);
            backdrop-filter: blur(10px);
        }
        [data-theme="dark"] .docs-export-hint {
            background: rgba(15, 23, 42, 0.92);
            color: #cbd5e1;
        }
        [data-theme="dark"] .docs-auth-input {
            background: rgba(15, 23, 42, 0.92);
            color: #f8fafc;
            border-color: rgba(148, 163, 184, 0.22);
        }
        [data-theme="dark"] .docs-auth-input::placeholder {
            color: #94a3b8;
        }
        [data-theme="dark"] .docs-inline-response {
            background: rgba(15, 23, 42, 0.96);
            border-color: rgba(148, 163, 184, 0.18);
        }
        [data-theme="dark"] .docs-inline-response-header {
            border-bottom-color: rgba(148, 163, 184, 0.12);
        }
        [data-theme="dark"] .docs-inline-response-title,
        [data-theme="dark"] .docs-inline-response-clear,
        [data-theme="dark"] .docs-inline-response-badge {
            color: #f8fafc;
        }
        [data-theme="dark"] .docs-inline-response-meta {
            color: #cbd5e1;
        }
        [data-theme="dark"] .docs-inline-response-clear,
        [data-theme="dark"] .docs-inline-response-badge {
            background: rgba(148, 163, 184, 0.16);
        }
        [data-theme="dark"] .docs-inline-response-body {
            color: #e2e8f0;
            background: rgba(2, 6, 23, 0.52);
        }
        @media (max-width: 960px) {
            .docs-export-toolbar {
                left: 1rem;
                right: 1rem;
                top: auto;
                bottom: 1rem;
                justify-content: stretch;
            }
            .docs-export-button,
            .docs-export-hint {
                width: 100%;
            }
        }
        /* issues about the dark theme of stoplight/mosaic-code-viewer using web component:
         * https://github.com/stoplightio/elements/issues/2188#issuecomment-1485461965
         */
        [data-theme="dark"] .token.property {
            color: rgb(128, 203, 196) !important;
        }
        [data-theme="dark"] .token.operator {
            color: rgb(255, 123, 114) !important;
        }
        [data-theme="dark"] .token.number {
            color: rgb(247, 140, 108) !important;
        }
        [data-theme="dark"] .token.string {
            color: rgb(165, 214, 255) !important;
        }
        [data-theme="dark"] .token.boolean {
            color: rgb(121, 192, 255) !important;
        }
        [data-theme="dark"] .token.punctuation {
            color: #dbdbdb !important;
        }
    </style>
</head>
<body style="height: 100vh; overflow-y: hidden">
<div class="docs-export-toolbar">
    <input id="docs-api-key" class="docs-auth-input" type="text" placeholder="X-API-KEY">
    <input id="docs-bearer-token" class="docs-auth-input" type="text" placeholder="Authorization: Bearer access_token">
    <span class="docs-export-hint">Tải JSON tối ưu cho AI frontend: base URL, headers, params, request, response, sample</span>
    <a href="{{ route('scramble.docs.swagger') }}" class="docs-export-button">
        Mở Swagger UI
    </a>
    <a href="{{ route('scramble.docs.export') }}" class="docs-export-button" download>
        Tải JSON Cho AI Frontend
    </a>
</div>
<elements-api
    id="docs"
    tryItCredentialsPolicy="{{ $config->get('ui.try_it_credentials_policy', 'include') }}"
    router="hash"
    @if($config->get('ui.hide_try_it')) hideTryIt="true" @endif
    @if($config->get('ui.hide_schemas')) hideSchemas="true" @endif
    @if($config->get('ui.logo')) logo="{{ $config->get('ui.logo') }}" @endif
    @if($config->get('ui.layout')) layout="{{ $config->get('ui.layout') }}" @endif
/>
<script>
    (async () => {
        const docs = document.getElementById('docs');
        const apiKeyInput = document.getElementById('docs-api-key');
        const bearerTokenInput = document.getElementById('docs-bearer-token');
        const API_KEY_STORAGE = 'scramble.docs.api_key';
        const BEARER_STORAGE = 'scramble.docs.bearer_token';
        const TRY_IT_STORAGE_PREFIX = 'scramble.docs.tryit.';
        const RESPONSE_HASH_STORAGE_PREFIX = 'scramble.docs.response.hash.';
        const RESPONSE_SIGNATURE_STORAGE_PREFIX = 'scramble.docs.response.signature.';
        let hydrationInProgress = false;
        let responseRenderQueued = false;
        let responseRenderInProgress = false;

        const normalizeBearerValue = (value) => {
            const trimmed = (value || '').trim();

            if (! trimmed) {
                return '';
            }

            return /^Bearer\s+/i.test(trimmed) ? trimmed : `Bearer ${trimmed}`;
        };

        apiKeyInput.value = localStorage.getItem(API_KEY_STORAGE) || '';
        bearerTokenInput.value = localStorage.getItem(BEARER_STORAGE) || '';

        apiKeyInput.addEventListener('input', () => {
            localStorage.setItem(API_KEY_STORAGE, apiKeyInput.value);
        });

        bearerTokenInput.addEventListener('input', () => {
            localStorage.setItem(BEARER_STORAGE, bearerTokenInput.value);
        });

        const getCurrentOperationKey = () => {
            const hash = (window.location.hash || '').trim();

            if (hash) {
                return hash;
            }

            return window.location.pathname || 'global';
        };

        const getCurrentOperationContainer = () => {
            const root = getDocsRoot();
            const hash = (window.location.hash || '').trim();

            if (!root) {
                return null;
            }

            if (hash) {
                const escapedHash = typeof CSS !== 'undefined' && typeof CSS.escape === 'function'
                    ? `#${CSS.escape(hash.replace(/^#/, ''))}`
                    : hash;

                try {
                    const directMatch = root.querySelector(escapedHash);

                    if (directMatch) {
                        return getOperationScope(directMatch) || directMatch.parentElement;
                    }
                } catch (error) {
                    // Ignore invalid selectors and continue with fallbacks.
                }
            }

            return root.querySelector('[data-testid="two-column-operation"], [data-testid="operation-card"], [data-testid="http-operation"], section, article');
        };

        const getResponseHashStorageKey = (operationKey = getCurrentOperationKey()) => `${RESPONSE_HASH_STORAGE_PREFIX}${operationKey}`;

        const getResponseSignatureStorageKey = (signature) => {
            if (!signature) {
                return null;
            }

            return `${RESPONSE_SIGNATURE_STORAGE_PREFIX}${signature}`;
        };

        const getRequestSignatureFromUrl = (url, method = 'GET') => {
            try {
                const resolved = new URL(url, window.location.origin);

                if (!/\/api(?:\/|$)/.test(resolved.pathname)) {
                    return null;
                }

                return `${String(method || 'GET').toUpperCase()} ${resolved.pathname}`;
            } catch (error) {
                return null;
            }
        };

        const isApiRequest = (url) => {
            try {
                const resolved = new URL(url, window.location.origin);

                return /\/api(?:\/|$)/.test(resolved.pathname);
            } catch (error) {
                return typeof url === 'string' && url.includes('/api');
            }
        };

        const formatResponseMeta = (payload) => {
            const chips = [];

            if (payload.method) {
                chips.push(`<span class="docs-inline-response-badge">${payload.method}</span>`);
            }

            if (payload.status) {
                chips.push(`<span class="docs-inline-response-badge">HTTP ${payload.status}</span>`);
            }

            if (payload.saved_at) {
                chips.push(`<span>Lưu lúc: ${payload.saved_at}</span>`);
            }

            if (payload.url) {
                chips.push(`<span>${payload.url}</span>`);
            }

            return chips.join('');
        };

        const clearInlineResponses = (exceptHost = null) => {
            const root = getDocsRoot();

            if (!root) {
                return;
            }

            root.querySelectorAll('.docs-inline-response').forEach((element) => {
                if (exceptHost && element === exceptHost) {
                    return;
                }

                element.hidden = true;
            });
        };

        const findResponseAnchor = (container) => {
            if (!container) {
                return null;
            }

            const elements = Array.from(container.querySelectorAll('*'));
            const responseElement = elements.find((element) => {
                const text = (element.textContent || '').trim();

                return (
                    text === 'Response'
                    || text === 'Response Preview'
                    || /^Response\s*$/.test(text)
                    || /^Response\s+Preview$/i.test(text)
                );
            });

            if (responseElement) {
                return responseElement;
            }

            return elements.find((element) => {
                const text = (element.textContent || '').trim();

                return text === 'Response Example' || text === 'Response example';
            }) || null;
        };

        const getOperationRequestSignature = (container = getCurrentOperationContainer()) => {
            if (!container) {
                return null;
            }

            const text = (container.textContent || '').replace(/\s+/g, ' ').trim();
            const methodMatch = text.match(/\b(GET|POST|PUT|PATCH|DELETE|OPTIONS|HEAD)\b/i);
            const pathMatch = text.match(/\/api(?:\/[A-Za-z0-9._~!$&'()*+,;=:@%-]+|\{[^}]+\})*/);

            if (!methodMatch || !pathMatch) {
                return null;
            }

            return `${methodMatch[1].toUpperCase()} ${pathMatch[0]}`;
        };

        const getStoredResponseRaw = (operationKey = getCurrentOperationKey(), container = getCurrentOperationContainer()) => {
            const candidateKeys = [];
            const signature = getOperationRequestSignature(container);
            const signatureKey = getResponseSignatureStorageKey(signature);
            const hashKey = getResponseHashStorageKey(operationKey);

            if (signatureKey) {
                candidateKeys.push(signatureKey);
            }

            candidateKeys.push(hashKey);

            for (const storageKey of candidateKeys) {
                const value = localStorage.getItem(storageKey);

                if (value !== null) {
                    return value;
                }
            }

            return null;
        };

        const removeStoredResponse = (operationKey = getCurrentOperationKey(), container = getCurrentOperationContainer()) => {
            localStorage.removeItem(getResponseHashStorageKey(operationKey));

            const signatureKey = getResponseSignatureStorageKey(getOperationRequestSignature(container));

            if (signatureKey) {
                localStorage.removeItem(signatureKey);
            }
        };

        const ensureInlineResponseHost = (container) => {
            if (!container) {
                return null;
            }

            let host = container.querySelector('.docs-inline-response');

            if (host) {
                return host;
            }

            const anchor = findResponseAnchor(container);

            if (!anchor) {
                return null;
            }

            host = document.createElement('section');
            host.className = 'docs-inline-response';
            host.hidden = true;

            const insertionTarget = anchor.closest('div') || anchor;

            insertionTarget.insertAdjacentElement('afterend', host);

            return host;
        };

        const renderSavedResponse = () => {
            if (responseRenderInProgress) {
                return;
            }

            responseRenderInProgress = true;

            const operationContainer = getCurrentOperationContainer();
            const raw = getStoredResponseRaw(getCurrentOperationKey(), operationContainer);
            const host = ensureInlineResponseHost(operationContainer);

            clearInlineResponses(host);

            if (!raw) {
                if (host) {
                    host.hidden = true;
                    host.dataset.responsePayload = '';
                }

                responseRenderInProgress = false;
                return;
            }

            if (!host) {
                responseRenderInProgress = false;
                return;
            }

            try {
                const payload = JSON.parse(raw);
                const payloadSignature = JSON.stringify(payload);

                if (host.dataset.responsePayload === payloadSignature && !host.hidden) {
                    responseRenderInProgress = false;
                    return;
                }

                host.innerHTML = `
                    <div class="docs-inline-response-header">
                        <div>
                            <div class="docs-inline-response-title">Response Đã Lưu</div>
                            <div class="docs-inline-response-meta">${formatResponseMeta(payload)}</div>
                        </div>
                        <button type="button" class="docs-inline-response-clear">Xóa response</button>
                    </div>
                    <pre class="docs-inline-response-body"></pre>
                `;

                const body = host.querySelector('.docs-inline-response-body');
                const clearButton = host.querySelector('.docs-inline-response-clear');

                if (body) {
                    body.textContent = payload.body || '';
                }

                if (clearButton) {
                    clearButton.addEventListener('click', () => {
                        removeStoredResponse(getCurrentOperationKey(), getCurrentOperationContainer());
                        scheduleRenderSavedResponse();
                    });
                }

                host.dataset.responsePayload = payloadSignature;
                host.hidden = false;
            } catch (error) {
                host.dataset.responsePayload = '';
                host.hidden = true;
            }

            responseRenderInProgress = false;
        };

        const scheduleRenderSavedResponse = () => {
            if (responseRenderQueued) {
                return;
            }

            responseRenderQueued = true;

            window.requestAnimationFrame(() => {
                responseRenderQueued = false;
                renderSavedResponse();
            });
        };

        const persistResponse = async (operationKey, url, options, response) => {
            if (!isApiRequest(url)) {
                return;
            }

            const cloned = response.clone();
            const contentType = cloned.headers.get('content-type') || '';
            let bodyText = '';

            try {
                if (contentType.includes('application/json')) {
                    const json = await cloned.json();
                    bodyText = JSON.stringify(json, null, 2);
                } else {
                    bodyText = await cloned.text();
                }
            } catch (error) {
                bodyText = '[Không thể đọc response body]';
            }

            const payload = {
                method: (options?.method || 'GET').toUpperCase(),
                status: response.status,
                url: (() => {
                    try {
                        return new URL(url, window.location.origin).toString();
                    } catch (error) {
                        return url?.toString?.() || '';
                    }
                })(),
                body: bodyText,
                saved_at: new Date().toLocaleString(),
            };

            const serializedPayload = JSON.stringify(payload);
            const signature = getRequestSignatureFromUrl(url, options?.method || 'GET');

            localStorage.setItem(getResponseHashStorageKey(operationKey), serializedPayload);

            if (signature) {
                const signatureKey = getResponseSignatureStorageKey(signature);

                if (signatureKey) {
                    localStorage.setItem(signatureKey, serializedPayload);
                }
            }

            if (operationKey === getCurrentOperationKey()) {
                scheduleRenderSavedResponse();
            }
        };

        const getDocsRoot = () => docs.shadowRoot || docs;

        const getOperationScope = (element) => {
            const containers = [
                '[data-testid="two-column-operation"]',
                '[data-testid="operation-card"]',
                '[data-testid="http-operation"]',
                'section',
                'article',
            ];

            for (const selector of containers) {
                const scope = element.closest(selector);

                if (scope) {
                    return scope;
                }
            }

            return null;
        };

        const getOperationIdentity = (element) => {
            const scope = getOperationScope(element);
            const candidates = [
                scope?.getAttribute('id'),
                scope?.getAttribute('data-testid'),
                scope?.querySelector?.('[id]')?.getAttribute('id'),
                scope?.querySelector?.('h1, h2, h3, h4')?.textContent,
                window.location.hash,
                window.location.pathname,
            ];

            return candidates
                .map((value) => (value || '').toString().trim())
                .find((value) => value.length > 0) || 'global';
        };

        const getFieldIdentity = (element) => {
            const scope = getOperationScope(element);
            const allControls = scope
                ? Array.from(scope.querySelectorAll('input, textarea, select'))
                : [];
            const similarControls = allControls.filter((control) => {
                const sameTag = control.tagName === element.tagName;
                const sameType = (control.getAttribute('type') || '') === (element.getAttribute('type') || '');
                const sameName = (control.getAttribute('name') || '') === (element.getAttribute('name') || '');
                const samePlaceholder = (control.getAttribute('placeholder') || '') === (element.getAttribute('placeholder') || '');

                return sameTag && sameType && sameName && samePlaceholder;
            });

            const index = Math.max(0, similarControls.indexOf(element));
            const rawIdentity = [
                element.getAttribute('name'),
                element.getAttribute('id'),
                element.getAttribute('data-testid'),
                element.getAttribute('aria-label'),
                element.getAttribute('placeholder'),
                element.closest('label')?.textContent,
                element.tagName.toLowerCase(),
                element.getAttribute('type'),
                String(index),
            ]
                .filter((value) => value && value.toString().trim().length > 0)
                .join('|');

            return rawIdentity || `${element.tagName.toLowerCase()}|${index}`;
        };

        const getStorageKey = (element) => {
            const operation = getOperationIdentity(element);
            const field = getFieldIdentity(element);

            return `${TRY_IT_STORAGE_PREFIX}${operation}::${field}`;
        };

        const readControlValue = (element) => {
            if (element instanceof HTMLInputElement) {
                if (element.type === 'checkbox' || element.type === 'radio') {
                    return element.checked;
                }

                if (element.type === 'file') {
                    return null;
                }
            }

            return element.value;
        };

        const writeControlValue = (element, value) => {
            if (value === null || value === undefined) {
                return;
            }

            if (element instanceof HTMLInputElement) {
                if (element.type === 'file') {
                    return;
                }

                if (element.type === 'checkbox' || element.type === 'radio') {
                    element.checked = value === true || value === 'true' || value === 1 || value === '1';
                } else {
                    element.value = value;
                }
            } else {
                element.value = value;
            }

            element.dispatchEvent(new Event('input', { bubbles: true, composed: true }));
            element.dispatchEvent(new Event('change', { bubbles: true, composed: true }));
        };

        const persistControl = (element) => {
            if (!element || hydrationInProgress) {
                return;
            }

            if (!(element instanceof HTMLInputElement || element instanceof HTMLTextAreaElement || element instanceof HTMLSelectElement)) {
                return;
            }

            const value = readControlValue(element);

            if (value === null) {
                return;
            }

            localStorage.setItem(getStorageKey(element), JSON.stringify(value));
        };

        const hydrateControls = () => {
            const root = getDocsRoot();

            if (!root) {
                return;
            }

            hydrationInProgress = true;

            root.querySelectorAll('input, textarea, select').forEach((element) => {
                if (!(element instanceof HTMLInputElement || element instanceof HTMLTextAreaElement || element instanceof HTMLSelectElement)) {
                    return;
                }

                if (element.type === 'file') {
                    return;
                }

                const rawValue = localStorage.getItem(getStorageKey(element));

                if (rawValue === null) {
                    return;
                }

                try {
                    writeControlValue(element, JSON.parse(rawValue));
                } catch (error) {
                    writeControlValue(element, rawValue);
                }
            });

            hydrationInProgress = false;
        };

        const bindPersistence = () => {
            const root = getDocsRoot();

            if (!root || docs.dataset.tryItPersistenceBound === 'true') {
                return;
            }

            const handler = (event) => {
                const path = typeof event.composedPath === 'function' ? event.composedPath() : [event.target];
                const target = path.find((node) =>
                    node instanceof HTMLInputElement
                    || node instanceof HTMLTextAreaElement
                    || node instanceof HTMLSelectElement
                );

                if (target) {
                    persistControl(target);
                }
            };

            root.addEventListener('input', handler, true);
            root.addEventListener('change', handler, true);
            docs.dataset.tryItPersistenceBound = 'true';
        };

        const setupPersistence = () => {
            const root = getDocsRoot();

            if (!root) {
                window.requestAnimationFrame(setupPersistence);
                return;
            }

            bindPersistence();
            hydrateControls();

            const observer = new MutationObserver(() => {
                bindPersistence();
                hydrateControls();
                scheduleRenderSavedResponse();
            });

            observer.observe(root, {
                childList: true,
                subtree: true,
            });

            window.addEventListener('hashchange', () => {
                window.setTimeout(hydrateControls, 50);
                window.setTimeout(scheduleRenderSavedResponse, 50);
            });
        };

        docs.apiDescriptionDocument = @json($spec);
        window.requestAnimationFrame(setupPersistence);
        window.requestAnimationFrame(scheduleRenderSavedResponse);

        window.__scrambleDocsPersistResponse = (operationKey, url, options, response) => {
            persistResponse(operationKey, url, options, response).catch(() => {});
        };
    })();
</script>

@if($config->get('ui.theme', 'light') === 'system')
    <script>
        var mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');

        function updateTheme(e) {
            if (e.matches) {
                window.document.documentElement.setAttribute('data-theme', 'dark');
                window.document.getElementsByName('color-scheme')[0].setAttribute('content', 'dark');
            } else {
                window.document.documentElement.setAttribute('data-theme', 'light');
                window.document.getElementsByName('color-scheme')[0].setAttribute('content', 'light');
            }
        }

        mediaQuery.addEventListener('change', updateTheme);
        updateTheme(mediaQuery);
    </script>
@endif
</body>
</html>
