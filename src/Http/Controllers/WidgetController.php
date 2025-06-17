<?php

declare(strict_types = 1);

namespace MlSolutions\NovaDashboard\Http\Controllers;

use MlSolutions\NovaDashboard\Card\View;
use Illuminate\Http\JsonResponse;
use Laravel\Nova\Http\Requests\NovaRequest;

class WidgetController
{
    public function __invoke(NovaRequest $request): JsonResponse
    {
        $widgetKey = $request->input('widget');

        return response()->json([
            'value' => View::findView($request)->resolveWidgetValue($request, $widgetKey),
        ]);
    }
}
