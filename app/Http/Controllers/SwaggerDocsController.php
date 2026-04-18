<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

class SwaggerDocsController extends Controller
{
    public function __invoke(): View
    {
        return view('swagger.docs', [
            'specUrl' => route('scramble.docs.openapi'),
            'exportUrl' => route('scramble.docs.export'),
            'scrambleUrl' => url('/docs/api'),
            'title' => config('app.name').' - Swagger API Docs',
        ]);
    }
}
