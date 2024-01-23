<?php

declare(strict_types=1);

namespace Vits\Svilpo\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Middleware;

class SvilpoMiddleware extends Middleware
{
    /**
     * Defines the props that are shared by default.
     */
    public function share(Request $request): array
    {
        $data = [];
        $routes = [];

        if ($controller = Route::current()->controller) {
            if (method_exists($controller, 'getIndexUrl')) {
                $routes['default'] = $controller->getIndexUrl();
            }

            if (method_exists($controller, 'sharedRoutes')) {
                $routes = [
                    ...$routes,
                    ...$controller->sharedRoutes(),
                ];
            }

            if (method_exists($controller, 'sharedData')) {
                $data = $controller->sharedData();
            }

            $method = Route::current()->getActionMethod();
            if (('create' === $method || 'edit' === $method)
            && method_exists($controller, 'sharedFormData')) {
                $data = [
                    ...$data,
                    ...$controller->sharedFormData(),
                ];
            }
        }

        return [
            ...parent::share($request),
            ...$data,
            'auth' => $this->shareAuthData(),
            'routes' => $routes,
            'flash' => [
                'success' => session('success'),
                'error' => session('error'),
            ],
        ];
    }

    public function shareAuthData(): array
    {
        $user = Auth::user();

        return [
            'user' => $user ? [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'permissions' => $this->shareUserPermissions(),
            ] : null,
            'logout' => route('logout'),
        ];
    }

    public function shareUserPermissions(): array
    {
        return [];
    }
}
