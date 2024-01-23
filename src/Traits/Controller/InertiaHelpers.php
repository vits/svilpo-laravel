<?php

declare(strict_types=1);

namespace Vits\Svilpo\Traits\Controller;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\Route;

trait InertiaHelpers
{
    /**
     * Returns routes fro Inertia.
     *
     * @return array
     */
    public function sharedRoutes()
    {
        return [];
    }

    /**
     * Returns extra data for Inertia responses.
     *
     * @return array
     */
    public function sharedData()
    {
        return [];
    }

    /**
     * Returns extra data for Inertia respones only for create and update methods.
     *
     * @return array
     */
    public function sharedFormData()
    {
        return [];
    }

    /**
     * Returns URL for index route of current controller.
     *
     * @param array $params
     *
     * @return string
     *
     * @throws BindingResolutionException
     */
    public function getIndexUrl($params = [])
    {
        return route(
            $this->getIndexRouteName(),
            [
                ...$this->indexRouteParams(),
                ...$params,
            ]
        );
    }

    /**
     * Returns index route name for current controller.
     *
     * @return string
     */
    protected function getIndexRouteName()
    {
        return Route::getRoutes()
            ->getByAction(self::class.'@index')
            ->action['as'];
    }

    /**
     * Returns parameters required for index route.
     *
     * @return array
     */
    protected function indexRouteParams()
    {
        return [];
    }
}
