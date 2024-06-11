<?php

declare(strict_types=1);

namespace Vits\Svilpo\Traits\Controller;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Vits\Svilpo\Response;

trait IndexHelpers
{
    use InertiaHelpers;

    /**
     * @throws BindingResolutionException
     */
    public function indexQuery(Builder|Relation|string $query): Builder|Relation
    {
        if (\is_string($query)) {
            $query = \call_user_func($query.'::query');
        }

        $options = [...$this->getIndexQueryOptions()];

        if ($options['searchable'] && $search = request('search')) {
            $query = $query->search($search);
            $options['search'] = $search;
        }

        if ($order = request('order')) {
            if (! \in_array($order, $options['sortable'], true)) {
                $order = null;
            }
        }

        if (! $order && $options['sortable']) {
            $order = $options['sortable'][0];
        }

        if ($order) {
            $options['order'] = $order;
            $options['desc'] = (bool) request('desc');

            $model = $query->getModel();
            $method = 'orderBy'.Str::studly($order);
            if (method_exists($model, $method)) {
                $order = $query->getModel()->{$method}();
            }

            $query = $query->orderBy($order, request('desc') ? 'desc' : 'asc');
        }

        Inertia::share('index_query_options', $options);

        return $query;
    }

    public function indexResponse(Builder|Relation $query)
    {
        $response = new Response($this, $query);

        $key = static::class.'--page';
        $page = (int) request()->get('page', null);
        session([$key => $page]);

        foreach ($this->getSavedParamsNames() as $param) {
            session([
                static::class.'--'.$param => request()->get($param, null),
            ]);
        }

        return $response;
    }

    /**
     * Returns redirector back to indx page with saved parameters applied.
     *
     * @param array $params
     *
     * @return Redirector|RedirectResponse
     *
     * @throws BindingResolutionException
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     */
    public function backToIndex(
        ?int $page = null,
        $params = []
    ) {
        $key = static::class;

        if (null === $page) {
            $page = (int) session($key.'--page');

            if ($page < 2) {
                $page = null;
            }
        }

        $rememberedParams = [];
        foreach ($this->getSavedParamsNames() as $param) {
            if ($value = session($key.'--'.$param)) {
                $rememberedParams[$param] = $value;
            }
        }

        return redirect($this->getIndexUrl([
            ...$rememberedParams,
            'page' => $page,
            ...$params,
        ]));
    }

    protected function getIndexQueryOptions()
    {
        $options = [
            'sortable' => [],
            'searchable' => false,
            'filterable' => [],
        ];

        if (property_exists($this, 'indexQueryOptions')) {
            $options = [
                ...$options,
                ...$this->indexQueryOptions,
            ];
        }

        if (! \is_bool($options['searchable'])) {
            $options['searchable'] = (bool) $options['searchable'];
        }

        if (\is_string($options['sortable'])) {
            $options['sortable'] = [$options['sortable']];
        }

        if (\is_string($options['filterable'])) {
            $options['filterable'] = [$options['filterable']];
        }

        return $options;
    }

    /**
     * Returns names of query parameters to be saved and restored.
     *
     * @return array
     */
    protected function getSavedParamsNames()
    {
        $names = [];

        if (
            property_exists($this, 'rememberIndexParams')
            && \is_array($this->rememberIndexParams)
        ) {
            $names = [
                ...$names,
                ...$this->rememberIndexParams,
            ];
        }

        $options = $this->getIndexQueryOptions();
        if ($options['searchable']) {
            $names[] = 'search';
        }

        if ($options['sortable']) {
            $names = [
                ...$names,
                'order',
                'desc',
            ];
        }

        return $names;
    }
}
