<?php

declare(strict_types = 1);

namespace MlSolutions\NovaDashboard\Http\Controllers;

use MlSolutions\NovaDashboard\Card\View;
use MlSolutions\NovaDashboard\Downloads\DownloadWriter;
use Laravel\Nova\Http\Requests\NovaRequest;
use Symfony\Component\HttpFoundation\Response;

class DownloadController
{
    public function __invoke(NovaRequest $request, DownloadWriter $writer): Response
    {
        $view = View::findView($request);
        $format = (string) $request->input('format', 'csv');

        abort_if(!$view, 404, 'Unable to resolve the requested view.');

        return $writer->toResponse(
            $view->downloadResult($format, $request),
            $format,
            data_get($view->jsonSerialize(), 'download.filename', 'report'),
        );
    }
}
