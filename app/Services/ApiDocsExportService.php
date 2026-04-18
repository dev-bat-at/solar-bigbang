<?php

namespace App\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class ApiDocsExportService
{
    /**
     * @param  array<string, mixed>  $spec
     * @return array<string, mixed>
     */
    public function build(array $spec): array
    {
        $servers = collect($spec['servers'] ?? [])
            ->pluck('url')
            ->filter(fn ($url) => is_string($url) && $url !== '')
            ->values()
            ->all();

        $components = $spec['components'] ?? [];
        $defaultSecurity = is_array($spec['security'] ?? null) ? $spec['security'] : [];
        $defaultHeaders = $this->normalizeSecurityRequirements($defaultSecurity, $components);
        $baseUrl = $servers[0] ?? null;

        $apis = collect($spec['paths'] ?? [])
            ->flatMap(function ($pathItem, $path) use ($baseUrl, $servers, $components, $defaultHeaders, $defaultSecurity) {
                if (! is_array($pathItem)) {
                    return [];
                }

                return collect($pathItem)
                    ->filter(fn ($operation, $method) => in_array(strtolower((string) $method), ['get', 'post', 'put', 'patch', 'delete', 'options', 'head'], true))
                    ->map(function ($operation, $method) use ($path, $baseUrl, $servers, $components, $defaultHeaders, $defaultSecurity) {
                        $operation = is_array($operation) ? $operation : [];
                        $security = array_key_exists('security', $operation) ? ($operation['security'] ?? []) : $defaultSecurity;
                        $operationParameters = is_array($operation['parameters'] ?? null) ? $operation['parameters'] : [];
                        $headers = $this->resolveHeaders($operationParameters, $security, $components);
                        $pathParameters = $this->resolveParameters($operationParameters, 'path', $components);
                        $queryParameters = $this->resolveParameters($operationParameters, 'query', $components);
                        $request = $this->resolveRequestBody($operation['requestBody'] ?? null, $components);
                        $responses = $this->resolveResponses($operation['responses'] ?? [], $components);

                        $successResponse = $this->pickSuccessResponse($responses);
                        $errorResponses = $this->pickErrorResponses($responses);

                        return [
                            'id' => $operation['operationId'] ?? Str::slug(strtoupper((string) $method).' '.$path),
                            'group' => Arr::first($operation['tags'] ?? []),
                            'name' => $operation['summary'] ?? null,
                            'description' => $operation['description'] ?? null,
                            'method' => strtoupper((string) $method),
                            'base_url' => $baseUrl,
                            'path' => $path,
                            'url' => $this->buildPrimaryUrl($servers, $path),
                            'headers' => $headers,
                            'uses_default_headers' => $this->hasSameHeaderNames($defaultHeaders, $headers),
                            'params' => [
                                'path' => $pathParameters,
                                'query' => $queryParameters,
                            ],
                            'request' => $request,
                            'responses' => $responses,
                            'success_response' => $successResponse,
                            'error_responses' => $errorResponses,
                            'demo' => [
                                'request' => [
                                    'method' => strtoupper((string) $method),
                                    'url' => $this->buildPrimaryUrl($servers, $path),
                                    'headers' => $this->headersToObjectTemplate($headers),
                                    'query' => $this->parametersToObjectTemplate($queryParameters),
                                    'body' => $request['sample'] ?? null,
                                ],
                                'response' => [
                                    'success' => [
                                        'status' => $successResponse['status'] ?? 200,
                                        'description' => $successResponse['description'] ?? 'Success',
                                        'sample' => $successResponse['sample'] ?? null,
                                    ],
                                    'validation_error' => isset($errorResponses[422]) ? [
                                        'status' => 422,
                                        'description' => $errorResponses[422]['description'] ?? 'Validation Error',
                                        'sample' => $errorResponses[422]['sample'] ?? null,
                                    ] : null,
                                ],
                            ],
                            'frontend' => [
                                'fetch_url' => $this->buildPrimaryUrl($servers, $path),
                                'suggested_query_object' => $this->parametersToObjectTemplate($queryParameters),
                                'suggested_headers' => $this->headersToObjectTemplate($headers),
                                'suggested_body' => $request['sample'] ?? null,
                            ],
                        ];
                    })
                    ->values()
                    ->all();
            })
            ->values()
            ->all();

        return [
            'generated_at' => Carbon::now()->toIso8601String(),
            'purpose' => 'frontend_ai_codegen',
            'app' => [
                'name' => Arr::get($spec, 'info.title'),
                'version' => Arr::get($spec, 'info.version'),
                'description' => Arr::get($spec, 'info.description'),
            ],
            'base_url' => $baseUrl,
            'servers' => $servers,
            'default_headers' => $defaultHeaders,
            'apis' => $apis,
        ];
    }

    /**
     * @param  array<int, string>  $servers
     */
    protected function buildPrimaryUrl(array $servers, string $path): string
    {
        if ($servers === []) {
            return $path;
        }

        return $this->joinUrl($servers[0], $path);
    }

    protected function joinUrl(string $server, string $path): string
    {
        return rtrim($server, '/').'/'.ltrim($path, '/');
    }

    /**
     * @param  array<int, mixed>  $parameters
     * @param  array<string, mixed>  $components
     * @return array<int, array<string, mixed>>
     */
    protected function resolveParameters(array $parameters, string $location, array $components): array
    {
        return collect($parameters)
            ->map(fn ($parameter) => $this->resolveReference($parameter, $components))
            ->filter(fn ($parameter) => is_array($parameter) && ($parameter['in'] ?? null) === $location)
            ->map(function (array $parameter) use ($components) {
                $schema = $this->resolveSchema($parameter['schema'] ?? null, $components);

                return [
                    'name' => $parameter['name'] ?? null,
                    'type' => $schema['type'] ?? null,
                    'required' => (bool) ($parameter['required'] ?? false),
                    'description' => $parameter['description'] ?? null,
                    'enum' => $schema['enum'] ?? null,
                    'example' => $schema['example'] ?? null,
                    'schema' => $schema,
                    'sample' => $this->buildSampleFromSchema($schema, $components),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param  array<int, mixed>  $parameters
     * @param  array<int, mixed>  $security
     * @param  array<string, mixed>  $components
     * @return array<int, array<string, mixed>>
     */
    protected function resolveHeaders(array $parameters, array $security, array $components): array
    {
        $parameterHeaders = $this->resolveParameters($parameters, 'header', $components);
        $securityHeaders = $this->normalizeSecurityRequirements($security, $components);

        return collect([...$parameterHeaders, ...$securityHeaders])
            ->unique(fn (array $header): string => Str::lower((string) ($header['name'] ?? '')))
            ->values()
            ->all();
    }

    /**
     * @param  array<int, mixed>  $security
     * @param  array<string, mixed>  $components
     * @return array<int, array<string, mixed>>
     */
    protected function normalizeSecurityRequirements(array $security, array $components): array
    {
        $schemes = $components['securitySchemes'] ?? [];

        return collect($security)
            ->filter(fn ($item) => is_array($item))
            ->flatMap(function (array $requirement) use ($schemes, $components) {
                return collect($requirement)
                    ->map(function ($scopes, $schemeName) use ($schemes, $components) {
                        $scheme = $this->resolveReference($schemes[$schemeName] ?? null, $components);

                        if (! is_array($scheme)) {
                            return null;
                        }

                        $type = $scheme['type'] ?? null;

                        if ($type === 'apiKey' && ($scheme['in'] ?? null) === 'header') {
                            return [
                                'name' => $scheme['name'] ?? $schemeName,
                                'type' => 'string',
                                'required' => true,
                                'description' => $scheme['description'] ?? null,
                                'example' => 'your-api-key',
                                'schema' => ['type' => 'string'],
                                'sample' => 'your-api-key',
                            ];
                        }

                        if ($type === 'http' && ($scheme['scheme'] ?? null) === 'bearer') {
                            return [
                                'name' => 'Authorization',
                                'type' => 'string',
                                'required' => true,
                                'description' => $scheme['description'] ?? 'Bearer access token.',
                                'example' => 'Bearer {access_token}',
                                'schema' => [
                                    'type' => 'string',
                                    'example' => 'Bearer {access_token}',
                                ],
                                'sample' => 'Bearer {access_token}',
                            ];
                        }

                        return null;
                    })
                    ->filter()
                    ->values()
                    ->all();
            })
            ->unique(fn (array $header): string => Str::lower((string) ($header['name'] ?? '')))
            ->values()
            ->all();
    }

    /**
     * @param  mixed  $requestBody
     * @param  array<string, mixed>  $components
     * @return array<string, mixed>|null
     */
    protected function resolveRequestBody(mixed $requestBody, array $components): ?array
    {
        $requestBody = $this->resolveReference($requestBody, $components);

        if (! is_array($requestBody)) {
            return null;
        }

        $content = $this->normalizeContent($requestBody['content'] ?? [], $components);
        $preferredContentType = $this->pickPreferredContentType($content);
        $preferred = $preferredContentType ? ($content[$preferredContentType] ?? null) : null;

        return [
            'required' => (bool) ($requestBody['required'] ?? false),
            'description' => $requestBody['description'] ?? null,
            'content_type' => $preferredContentType,
            'schema' => $preferred['schema'] ?? null,
            'required_fields' => $preferred['required_fields'] ?? [],
            'sample' => $preferred['sample'] ?? null,
            'content' => $content,
        ];
    }

    /**
     * @param  array<string, mixed>  $responses
     * @param  array<string, mixed>  $components
     * @return array<string, mixed>
     */
    protected function resolveResponses(array $responses, array $components): array
    {
        return collect($responses)
            ->mapWithKeys(function ($response, $status) use ($components) {
                $response = $this->resolveReference($response, $components);

                if (! is_array($response)) {
                    return [$status => null];
                }

                $content = $this->normalizeContent($response['content'] ?? [], $components);
                $preferredContentType = $this->pickPreferredContentType($content);
                $preferred = $preferredContentType ? ($content[$preferredContentType] ?? null) : null;

                return [$status => [
                    'description' => $response['description'] ?? null,
                    'headers' => $this->normalizeResponseHeaders($response['headers'] ?? [], $components),
                    'content_type' => $preferredContentType,
                    'schema' => $preferred['schema'] ?? null,
                    'required_fields' => $preferred['required_fields'] ?? [],
                    'sample' => $preferred['sample'] ?? null,
                    'content' => $content,
                ]];
            })
            ->all();
    }

    /**
     * @param  array<string, mixed>  $headers
     * @param  array<string, mixed>  $components
     * @return array<string, mixed>
     */
    protected function normalizeResponseHeaders(array $headers, array $components): array
    {
        return collect($headers)
            ->mapWithKeys(function ($header, $name) use ($components) {
                $header = $this->resolveReference($header, $components);

                if (! is_array($header)) {
                    return [$name => null];
                }

                $schema = $this->resolveSchema($header['schema'] ?? null, $components);

                return [$name => [
                    'required' => (bool) ($header['required'] ?? false),
                    'description' => $header['description'] ?? null,
                    'schema' => $schema,
                    'sample' => $this->buildSampleFromSchema($schema, $components),
                ]];
            })
            ->all();
    }

    /**
     * @param  array<string, mixed>  $content
     * @param  array<string, mixed>  $components
     * @return array<string, mixed>
     */
    protected function normalizeContent(array $content, array $components): array
    {
        return collect($content)
            ->mapWithKeys(function ($mediaType, $contentType) use ($components) {
                if (! is_array($mediaType)) {
                    return [$contentType => null];
                }

                $schema = $this->resolveSchema($mediaType['schema'] ?? null, $components);

                return [$contentType => [
                    'schema' => $schema,
                    'required_fields' => $this->extractRequiredFields($schema, $components),
                    'example' => $mediaType['example'] ?? null,
                    'examples' => $this->normalizeExamples($mediaType['examples'] ?? null),
                    'sample' => $this->buildSampleFromSchema($schema, $components),
                ]];
            })
            ->all();
    }

    /**
     * @param  mixed  $value
     * @param  array<string, mixed>  $components
     * @return mixed
     */
    protected function resolveReference(mixed $value, array $components): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        if (! isset($value['$ref']) || ! is_string($value['$ref'])) {
            return $value;
        }

        $reference = Str::after($value['$ref'], '#/');
        $resolved = data_get($components, str_replace('/', '.', Str::after($reference, 'components/')));

        return is_array($resolved) ? $resolved : $value;
    }

    /**
     * @param  mixed  $schema
     * @param  array<string, mixed>  $components
     * @return array<string, mixed>|null
     */
    protected function resolveSchema(mixed $schema, array $components): ?array
    {
        $schema = $this->resolveReference($schema, $components);

        return is_array($schema) ? $schema : null;
    }

    /**
     * @param  array<string, mixed>|null  $schema
     * @param  array<string, mixed>  $components
     * @return array<int, string>
     */
    protected function extractRequiredFields(?array $schema, array $components, string $prefix = ''): array
    {
        if (! $schema) {
            return [];
        }

        $schema = $this->resolveSchema($schema, $components);

        if (! $schema) {
            return [];
        }

        $required = collect($schema['required'] ?? [])
            ->filter(fn ($item) => is_string($item))
            ->map(fn (string $field): string => $prefix === '' ? $field : "{$prefix}.{$field}")
            ->values();

        $nested = collect($schema['properties'] ?? [])
            ->flatMap(function ($propertySchema, $propertyName) use ($components, $prefix) {
                if (! is_string($propertyName)) {
                    return [];
                }

                $propertySchema = $this->resolveSchema($propertySchema, $components);

                if (! $propertySchema) {
                    return [];
                }

                return $this->extractRequiredFields(
                    $propertySchema,
                    $components,
                    $prefix === '' ? $propertyName : "{$prefix}.{$propertyName}",
                );
            });

        $composed = collect(['allOf', 'oneOf', 'anyOf'])
            ->flatMap(function (string $key) use ($schema, $components, $prefix) {
                return collect($schema[$key] ?? [])
                    ->flatMap(fn ($item) => $this->extractRequiredFields($this->resolveSchema($item, $components), $components, $prefix));
            });

        $items = [];

        if (isset($schema['items'])) {
            $items = $this->extractRequiredFields(
                $this->resolveSchema($schema['items'], $components),
                $components,
                $prefix === '' ? '[]' : "{$prefix}[]",
            );
        }

        return $required
            ->merge($nested)
            ->merge($composed)
            ->merge($items)
            ->filter(fn ($field) => is_string($field) && $field !== '')
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>|null  $schema
     * @param  array<string, mixed>  $components
     * @return mixed
     */
    protected function buildSampleFromSchema(?array $schema, array $components, int $depth = 0): mixed
    {
        if (! $schema || $depth > 4) {
            return null;
        }

        $schema = $this->resolveSchema($schema, $components);

        if (! $schema) {
            return null;
        }

        if (array_key_exists('example', $schema)) {
            return $schema['example'];
        }

        if (isset($schema['default'])) {
            return $schema['default'];
        }

        if (! empty($schema['enum']) && is_array($schema['enum'])) {
            return $schema['enum'][0] ?? null;
        }

        foreach (['allOf', 'oneOf', 'anyOf'] as $composedKey) {
            if (! empty($schema[$composedKey]) && is_array($schema[$composedKey])) {
                foreach ($schema[$composedKey] as $candidate) {
                    $sample = $this->buildSampleFromSchema($this->resolveSchema($candidate, $components), $components, $depth + 1);

                    if ($sample !== null) {
                        return $sample;
                    }
                }
            }
        }

        $type = $schema['type'] ?? null;

        if (($type === 'object' || isset($schema['properties'])) && is_array($schema['properties'] ?? null)) {
            $sample = [];

            foreach ($schema['properties'] as $property => $propertySchema) {
                if (! is_string($property)) {
                    continue;
                }

                $sample[$property] = $this->buildSampleFromSchema(
                    $this->resolveSchema($propertySchema, $components),
                    $components,
                    $depth + 1,
                );
            }

            return $sample;
        }

        if ($type === 'array') {
            return [
                $this->buildSampleFromSchema(
                    $this->resolveSchema($schema['items'] ?? null, $components),
                    $components,
                    $depth + 1,
                ),
            ];
        }

        return match ($type) {
            'integer' => 0,
            'number' => 0,
            'boolean' => true,
            'string' => $this->sampleStringByFormat($schema['format'] ?? null),
            default => null,
        };
    }

    protected function sampleStringByFormat(?string $format): string
    {
        return match ($format) {
            'date-time' => '2026-01-01T00:00:00Z',
            'date' => '2026-01-01',
            'email' => 'user@example.com',
            'uri', 'url' => 'https://example.com',
            'uuid' => '00000000-0000-0000-0000-000000000000',
            default => 'string',
        };
    }

    /**
     * @param  array<string, mixed>  $responses
     * @return array<string, mixed>|null
     */
    protected function pickSuccessResponse(array $responses): ?array
    {
        foreach ($responses as $status => $response) {
            if (! is_array($response)) {
                continue;
            }

            if (preg_match('/^2\d\d$/', (string) $status) === 1) {
                return ['status' => (string) $status, ...$response];
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $responses
     * @return array<string, mixed>
     */
    protected function pickErrorResponses(array $responses): array
    {
        return collect($responses)
            ->filter(fn ($response, $status) => preg_match('/^[45]\d\d$/', (string) $status) === 1)
            ->all();
    }

    protected function normalizeExamples(mixed $examples): array
    {
        if (! is_array($examples)) {
            return [];
        }

        return collect($examples)
            ->map(function ($example) {
                if (is_array($example) && array_key_exists('value', $example)) {
                    return $example['value'];
                }

                return $example;
            })
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $content
     */
    protected function pickPreferredContentType(array $content): ?string
    {
        foreach (['application/json', 'multipart/form-data', 'application/x-www-form-urlencoded'] as $preferredType) {
            if (array_key_exists($preferredType, $content)) {
                return $preferredType;
            }
        }

        return array_key_first($content);
    }

    /**
     * @param  array<int, array<string, mixed>>  $parameters
     * @return array<string, mixed>
     */
    protected function parametersToObjectTemplate(array $parameters): array
    {
        $template = [];

        foreach ($parameters as $parameter) {
            $name = $parameter['name'] ?? null;

            if (! is_string($name) || $name === '') {
                continue;
            }

            $template[$name] = $parameter['sample'] ?? null;
        }

        return $template;
    }

    /**
     * @param  array<int, array<string, mixed>>  $headers
     * @return array<string, mixed>
     */
    protected function headersToObjectTemplate(array $headers): array
    {
        $template = [];

        foreach ($headers as $header) {
            $name = $header['name'] ?? null;

            if (! is_string($name) || $name === '') {
                continue;
            }

            $template[$name] = $header['sample'] ?? ($header['example'] ?? null);
        }

        return $template;
    }

    /**
     * @param  array<int, array<string, mixed>>  $defaultHeaders
     * @param  array<int, array<string, mixed>>  $headers
     */
    protected function hasSameHeaderNames(array $defaultHeaders, array $headers): bool
    {
        $normalize = fn (array $items): array => collect($items)
            ->pluck('name')
            ->filter(fn ($item) => is_string($item))
            ->map(fn (string $name): string => Str::lower($name))
            ->sort()
            ->values()
            ->all();

        return $normalize($defaultHeaders) === $normalize($headers);
    }
}
