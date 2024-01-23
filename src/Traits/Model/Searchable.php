<?php

declare(strict_types=1);

namespace Vits\Svilpo\Traits\Model;

trait Searchable
{
    public function scopeSearch($query, null|string $search)
    {
        if (! $this->searchable || ! $search) {
            return $query;
        }

        $searchable = $this->searchable;
        if (! is_array($searchable)) {
            $searchable = [$searchable];
        }

        $words = explode(' ', $search);

        foreach ($words as $word) {
            $word = str_replace('_', ' ', $word);

            $query = $query->where(function ($query) use ($word, $searchable) {
                foreach ($searchable as $field) {
                    $query->orWhere($field, 'like', '%'.$word.'%');
                }
            });
        }

        return $query;
    }
}
