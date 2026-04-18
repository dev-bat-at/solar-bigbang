<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }}</title>

    <link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist@5/swagger-ui.css">

    <style>
        html, body {
            margin: 0;
            min-height: 100%;
            background: #f8fafc;
            color: #0f172a;
            font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }
        .swagger-shell {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .swagger-toolbar {
            position: sticky;
            top: 0;
            z-index: 20;
            display: flex;
            gap: 0.75rem;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            padding: 1rem 1.25rem;
            background: rgba(15, 23, 42, 0.96);
            box-shadow: 0 12px 30px rgba(15, 23, 42, 0.18);
            backdrop-filter: blur(12px);
        }
        .swagger-toolbar-copy {
            display: flex;
            flex-direction: column;
            gap: 0.2rem;
        }
        .swagger-toolbar-title {
            font-size: 1rem;
            font-weight: 700;
            color: #f8fafc;
        }
        .swagger-toolbar-subtitle {
            font-size: 0.82rem;
            color: #cbd5e1;
        }
        .swagger-toolbar-actions {
            display: flex;
            gap: 0.75rem;
            align-items: center;
            flex-wrap: wrap;
        }
        .swagger-toolbar-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 2.5rem;
            padding: 0.65rem 1rem;
            border-radius: 999px;
            border: 1px solid rgba(148, 163, 184, 0.24);
            background: rgba(255, 255, 255, 0.08);
            color: #f8fafc;
            text-decoration: none;
            font-size: 0.88rem;
            font-weight: 600;
        }
        .swagger-toolbar-link:hover {
            background: rgba(255, 255, 255, 0.14);
        }
        #swagger-ui {
            flex: 1;
        }
        .swagger-ui .topbar {
            display: none;
        }
        .swagger-ui .info {
            margin: 0;
            padding-top: 1.5rem;
        }
        .swagger-ui .scheme-container {
            position: sticky;
            top: 4.75rem;
            z-index: 10;
            background: #fff;
        }
        @media (max-width: 960px) {
            .swagger-toolbar {
                align-items: flex-start;
            }
            .swagger-toolbar-actions {
                width: 100%;
            }
            .swagger-toolbar-link {
                flex: 1 1 12rem;
            }
            .swagger-ui .scheme-container {
                top: 7.5rem;
            }
        }
    </style>
</head>
<body>
<div class="swagger-shell">
    <div class="swagger-toolbar">
        <div class="swagger-toolbar-copy">
            <div class="swagger-toolbar-title">Swagger API Docs</div>
            <div class="swagger-toolbar-subtitle">Dùng nút Authorize để nhập `X-API-KEY` và Bearer token. Swagger sẽ tự nhớ auth đã nhập.</div>
        </div>

        <div class="swagger-toolbar-actions">
            <a class="swagger-toolbar-link" href="{{ $scrambleUrl }}">Mở Docs Cũ</a>
            <a class="swagger-toolbar-link" href="{{ $exportUrl }}" download>Tải JSON Cho AI Frontend</a>
            <a class="swagger-toolbar-link" href="{{ $specUrl }}" target="_blank" rel="noopener">Mở Raw OpenAPI</a>
        </div>
    </div>

    <div id="swagger-ui"></div>
</div>

<script src="https://unpkg.com/swagger-ui-dist@5/swagger-ui-bundle.js"></script>
<script src="https://unpkg.com/swagger-ui-dist@5/swagger-ui-standalone-preset.js"></script>
<script>
    window.addEventListener('load', () => {
        const API_KEY_STORAGE_KEYS = [
            'swagger.docs.api_key',
            'scramble.docs.api_key',
        ];
        const BEARER_STORAGE_KEYS = [
            'swagger.docs.bearer_token',
            'scramble.docs.bearer_token',
        ];

        const readStorageValue = (keys) => {
            for (const key of keys) {
                const value = window.localStorage.getItem(key);

                if (value && value.trim()) {
                    return value.trim();
                }
            }

            return '';
        };

        const writeStorageValue = (keys, value) => {
            keys.forEach((key) => window.localStorage.setItem(key, value));
        };

        const clearStorageValue = (keys) => {
            keys.forEach((key) => window.localStorage.removeItem(key));
        };

        const normalizeBearerToken = (value) => (value || '').replace(/^Bearer\s+/i, '').trim();
        const getStoredApiKey = () => readStorageValue(API_KEY_STORAGE_KEYS);
        const getStoredBearerToken = () => normalizeBearerToken(readStorageValue(BEARER_STORAGE_KEYS));

        const persistApiKeyFromHeaders = (headers = {}) => {
            const apiKey = headers['X-API-KEY'] || headers['x-api-key'];

            if (typeof apiKey === 'string' && apiKey.trim()) {
                writeStorageValue(API_KEY_STORAGE_KEYS, apiKey.trim());
            }
        };

        const persistBearerFromHeaders = (headers = {}) => {
            const authorization = headers.Authorization || headers.authorization;
            const token = normalizeBearerToken(authorization);

            if (token) {
                writeStorageValue(BEARER_STORAGE_KEYS, token);
            }
        };

        const getResponseUrl = (response) => {
            const candidates = [
                response?.url,
                response?.obj?.url,
                response?.request?.url,
                response?.req?.url,
            ];

            return candidates.find((value) => typeof value === 'string' && value.length > 0) || '';
        };

        const isLoginEndpoint = (response) => {
            const url = getResponseUrl(response);

            if (!url) {
                return false;
            }

            try {
                const parsed = new URL(url, window.location.origin);

                return /\/api\/auth(?:\/dealer)?\/login$/.test(parsed.pathname);
            } catch (error) {
                return /\/api\/auth(?:\/dealer)?\/login$/.test(url);
            }
        };

        const extractResponsePayload = (response) => {
            const candidates = [
                response?.body,
                response?.obj?.body,
                response?.data,
                response?.text,
            ];

            for (const candidate of candidates) {
                if (!candidate) {
                    continue;
                }

                if (typeof candidate === 'object') {
                    return candidate;
                }

                if (typeof candidate === 'string') {
                    try {
                        return JSON.parse(candidate);
                    } catch (error) {
                        continue;
                    }
                }
            }

            return null;
        };

        const authorizeSwaggerUi = (uiInstance) => {
            if (!uiInstance) {
                return;
            }

            const apiKey = getStoredApiKey();
            const bearerToken = getStoredBearerToken();
            const authorizations = {};

            if (apiKey) {
                authorizations.ApiKeyAuth = {
                    name: 'ApiKeyAuth',
                    schema: {
                        type: 'apiKey',
                        in: 'header',
                        name: 'X-API-KEY',
                    },
                    value: apiKey,
                };
            }

            if (bearerToken) {
                authorizations.BearerAuth = {
                    name: 'BearerAuth',
                    schema: {
                        type: 'http',
                        scheme: 'bearer',
                        bearerFormat: 'Bearer',
                    },
                    value: bearerToken,
                };
            }

            if (Object.keys(authorizations).length > 0) {
                uiInstance.authActions.authorize(authorizations);
            }
        };

        let ui;

        ui = SwaggerUIBundle({
            url: @json($specUrl),
            dom_id: '#swagger-ui',
            deepLinking: true,
            docExpansion: 'list',
            defaultModelsExpandDepth: 1,
            defaultModelExpandDepth: 1,
            displayRequestDuration: true,
            persistAuthorization: true,
            tryItOutEnabled: true,
            filter: true,
            requestInterceptor: (request) => {
                request.headers = request.headers || {};

                persistApiKeyFromHeaders(request.headers);
                persistBearerFromHeaders(request.headers);

                const apiKey = getStoredApiKey();
                const bearerToken = getStoredBearerToken();

                if (apiKey && !request.headers['X-API-KEY'] && !request.headers['x-api-key']) {
                    request.headers['X-API-KEY'] = apiKey;
                }

                if (bearerToken) {
                    request.headers.Authorization = `Bearer ${bearerToken}`;
                }

                return request;
            },
            responseInterceptor: (response) => {
                if (isLoginEndpoint(response)) {
                    const payload = extractResponsePayload(response);
                    const accessToken = normalizeBearerToken(payload?.data?.access_token);

                    if (accessToken) {
                        writeStorageValue(BEARER_STORAGE_KEYS, accessToken);
                        authorizeSwaggerUi(ui);
                    } else {
                        clearStorageValue(BEARER_STORAGE_KEYS);
                    }
                }

                return response;
            },
            onComplete: () => {
                authorizeSwaggerUi(ui);
            },
            presets: [
                SwaggerUIBundle.presets.apis,
                SwaggerUIStandalonePreset,
            ],
            layout: 'BaseLayout',
        });

        window.ui = ui;
    });
</script>
</body>
</html>
