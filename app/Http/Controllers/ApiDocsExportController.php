<?php

namespace App\Http\Controllers;

use App\Services\ApiDocsExportService;
use Dedoc\Scramble\Generator;
use Dedoc\Scramble\Scramble;
use Illuminate\Http\Response;

class ApiDocsExportController extends Controller
{
    public function __invoke(Generator $generator, ApiDocsExportService $apiDocsExportService)
    {
        $config = Scramble::getGeneratorConfig(Scramble::DEFAULT_API);
        $spec = $generator($config);

        if ($spec instanceof \Dedoc\Scramble\Support\Generator\OpenApi) {
            $spec = $spec->toArray();
        }

        $payload = $apiDocsExportService->build($spec);

        return response()->json($payload, 200, [
            'Content-Disposition' => 'attachment; filename="solar-bigbang-frontend-ai-api.json"',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
