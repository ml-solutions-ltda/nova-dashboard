<?php

declare(strict_types = 1);

use MlSolutions\NovaDashboard\Http\Controllers\DownloadController;
use MlSolutions\NovaDashboard\Http\Controllers\WidgetController;
use Illuminate\Support\Facades\Route;

Route::post('/widget/update/{resource?}', WidgetController::class);
Route::post('/download/{resource?}', DownloadController::class);
