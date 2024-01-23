<?php

declare(strict_types=1);

namespace Vits\Svilpo\Traits\Model;

use Illuminate\Database\Eloquent\Relations\HasMany;

trait SavesRelationships
{
    protected $_unsaved_relationships = [];

    public static function bootSavesRelationships()
    {
        static::saved(function ($model) {
            if (! $model->saveRelationships) {
                return;
            }

            foreach ($model->saveRelationships as $relationship) {
                $method = null;

                if (is_array($relationship)) {
                    if (count($relationship) > 1) {
                        $method = $relationship[1];
                    }
                    $relationship = $relationship[0];
                }

                if (! array_key_exists($relationship, $model->_unsaved_relationships)) {
                    continue;
                }

                $data = $model->_unsaved_relationships[$relationship];
                if ($method) {
                    $model->{$method}($data);
                } else {
                    if ($model->{$relationship}() instanceof HasMany) {
                        $model->_relationshipSyncHasMany($relationship, $data);
                    }
                }
            }
        });
    }

    public function setAttribute($name, $data)
    {
        if (! in_array($name, $this->_relationshipAttributes())) {
            return parent::setAttribute($name, $data);
        }

        $this->_unsaved_relationships[$name] = $data;

        return $this;
    }

    private function _relationshipAttributes()
    {
        if (! $this->saveRelationships) {
            return [];
        }

        return array_map(function ($attr) {
            return is_array($attr) ? $attr[0] : $attr;
        }, $this->saveRelationships);
    }

    private function _relationshipSyncHasMany($relationship, $data)
    {
        $existing = $this->{$relationship};
        $new = collect($data)->where('_delete', false);

        foreach ($existing->pluck('id')->diff($new->pluck('id')) as $id) {
            $delete = $existing->find($id);
            $delete->delete();
        }

        $updatedIds = $existing->pluck('id')->intersect($new->pluck(['id']))->toArray();

        foreach ($new as $item) {
            if (array_key_exists('id', $item) && in_array($item['id'], $updatedIds)) {
                $updated = $existing->find($item['id']);
                $updated->update($item);
            } else {
                $this->{$relationship}()->create($item);
            }
        }
    }
}
