<?php

namespace GhostZero\TmiCluster;

use GhostZero\TmiCluster\Http\Controllers;
use Illuminate\Contracts\Routing\Registrar as Router;

class RouteRegistrar
{
    private Router $router;

    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    public function all(): void
    {
        $this->forHealth();
        $this->forDashboard();
        $this->forChannelManager();
    }

    private function forHealth(): void
    {
        $this->router->get('health', [Controllers\DashboardController::class, 'health']);
    }

    private function forDashboard(): void
    {
        $this->router->get('', [Controllers\DashboardController::class, 'index']);
        $this->router->post('statistics', [Controllers\DashboardController::class, 'statistics']);
        $this->router->get('statistics', [Controllers\DashboardController::class, 'statistics']);
        $this->router->get('metrics', [Controllers\MetricsController::class, 'handle']);
    }

    private function forChannelManager(): void
    {
        $this->router->group(['middleware' => 'auth'], function (Router $router) {
            $router->resource('invite', Controllers\InviteController::class)
                ->only(['index', 'store']);
        });
    }
}