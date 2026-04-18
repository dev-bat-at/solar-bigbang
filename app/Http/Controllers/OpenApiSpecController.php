<?php

namespace App\Http\Controllers;

use Dedoc\Scramble\Generator;
use Dedoc\Scramble\Scramble;

class OpenApiSpecController extends Controller
{
    public function __invoke(Generator $generator)
    {
        $config = Scramble::getGeneratorConfig(Scramble::DEFAULT_API);
        $spec = $generator($config);

        if ($spec instanceof \Dedoc\Scramble\Support\Generator\OpenApi) {
            $spec = $spec->toArray();
        }

        return response()->json(
            $spec,
            200,
            [],
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );
    }
}
