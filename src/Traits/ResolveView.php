<?php

declare(strict_types = 1);

namespace MlSolutions\NovaDashboard\Traits;

use Closure;
use MlSolutions\NovaDashboard\Card\NovaDashboard;
use MlSolutions\NovaDashboard\Card\View;
use MlSolutions\NovaDashboard\Card\Widget;
use MlSolutions\NovaDashboard\Http\Controllers\WidgetController;
use Laravel\Nova\Http\Controllers\CardController;
use Laravel\Nova\Http\Controllers\DashboardController;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Nova;
use RuntimeException;

trait ResolveView
{
    public static function findView(NovaRequest $request, ?Closure $resolver = null): ?View
    {
        $viewKey = $request->input('view');
        $controller = $request->route()->getController();

        $cards = match (true) {
            /**
             * When there is a "resource" param on the URL, we are able to infer the Resource from it
             */
            !is_null($request->route('resource')) => collect($request->newResource()->cards()),

            /**
             * If the dashboard is placed on a Nova Resource we need to find which resource was it
             * And retrieve its available cards
             */
            $controller instanceof CardController && $resolver => collect($resolver()->cards()),

            /**
             * When it is a Nova Dashboard, retrieve the available dashboard cards
             */
            $controller instanceof WidgetController,
                $controller instanceof DashboardController => collect(Nova::dashboards())
                ->flatMap(fn ($dashboard) => $dashboard->cards()),

            /**
             * ¯\_(ツ)_/¯
             */
            default => throw new RuntimeException('Unable to find dashboard card.'),
        };

        return $cards
            ->whereInstanceOf(NovaDashboard::class)
            ->flatMap(fn (NovaDashboard $dashboard) => $dashboard->meta['views'])
            ->firstWhere(fn (View $view) => !$viewKey || $view->key() === $viewKey);
    }


    public function findWidgetByKey(string $key): ?Widget
    {
        return $this
            ->widgets()
            ->firstWhere(fn (Widget $widget) => $widget->key() === $key);
    }

    public function resolveWidgetValue(NovaRequest $request, string $key): mixed
    {
        $widget = $this->findWidgetByKey($key);
        $widget?->configure($request);

        return $widget?->resolveValue($request, $this);
    }
}