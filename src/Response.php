<?php

declare(strict_types=1);

namespace Vits\Svilpo;

use Illuminate\Contracts\Database\Query\Builder;
use Inertia\Inertia;

class Response
{
    protected null|bool|int $paginate = false;
    protected \Closure|string $transform;
    protected array $data = [];

    public function __construct(
        protected $controller,
        protected ?Builder $query = null
    ) {}

    public function paginate(?int $perPage = null)
    {
        $this->paginate = $perPage;

        return $this;
    }

    public function transform(\Closure|string $transform)
    {
        $this->transform = $transform;

        return $this;
    }

    public function with(array $data = [])
    {
        $this->data = $data;

        return $this;
    }

    public function render(string $component)
    {
        $query = $this->query;

        if (false !== $this->paginate) {
            $items = $query->paginate($this->paginate);

            if ($items->currentPage() < 1 || $items->currentPage() > $items->lastPage()) {
                $key = get_class($this->controller).'--page';
                session([$key => $items->lastPage()]);

                return $this->controller->gotoIndex();
            }
        } else {
            $items = $query->get();
        }

        if ($transform = $this->transform ?? false) {
            if (is_string($transform)) {
                if (false !== $this->paginate) {
                    $items = $transform::paginated($items);
                } else {
                    $items = $transform::collection($items);
                }
            } else {
                $items = $transform($items);
            }
        }

        return Inertia::render(
            $component,
            [
                'items' => $items,
                ...$this->data,
            ],
        );
    }
}
