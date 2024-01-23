<?php

declare(strict_types=1);

namespace Vits\Svilpo\Traits\Resource;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;

trait ResourceBuilder
{
    /**
     * Convert paginated items collection to JSON resource.
     *
     * @return LengthAwarePaginator
     */
    public static function paginated(LengthAwarePaginator $items)
    {
        $items->setCollection(
            collect(self::collection($items))
        );

        return $items;
    }

    /**
     * Builds resource data array based on provided options.
     *
     * @param array $options
     *
     * @return mixed
     */
    protected function buildArray(Request $request, $options = [])
    {
        if (null === $this->resource) {
            return [];
        }

        $data = parent::toArray($request);
        if (\array_key_exists('only', $options)) {
            $data = Arr::only($data, $options['only']);
        }

        if (\array_key_exists('except', $options)) {
            $data = Arr::except($data, $options['except']);
        }

        if (\array_key_exists('extra', $options)) {
            $data = [...$data, ...$options['extra']];
        }

        if (\array_key_exists('with', $options)) {
            foreach ($options['with'] as $relation => $resource) {
                if ($this->relationLoaded($relation)) {
                    if ($this->{$relation}() instanceof HasMany) {
                        $data[$relation] = $resource::collection($this->{$relation});
                    } else {
                        $data[$relation] = $resource::make($this->{$relation});
                    }
                }
            }
        }

        return $data;
    }
}
