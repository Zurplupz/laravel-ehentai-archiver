<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class GalleryResource extends JsonResource
{
    protected $nested_relations;

    function __construct($resource)
    {
        parent::__construct($resource);

        $this->nested_relations = [
            'gallery_group'     =>  'group', 
            'gallery_tagging'   =>  'tag'
        ];
    }

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        if ($this->resource instanceof Collection) {
            $this->resource->map(function ($model) {
                return $this->flattenRelations($model);
            });

        } elseif ($this->resource instanceof Model) {
            $this->resource = $this->flattenRelations($this->resource);
        
        }

        return parent::toArray($request);
    }

    protected function flattenRelations(Model $model)
    {
        $relations = $model->getRelations();

        if (empty($relations)) {
            return $model;
        }

        // foreach relation
        foreach ($relations as $k => $rel) {
            if (empty($this->nested_relations[$k])) 
            {
                $model->{$k} = $rel->toArray();
                continue;
            }

            $name = $this->nested_relations[$k];

            $model->{$name} = $this->flattenNested($rel);
        }

        return $model;
    }

    protected function flattenNested($v)
    {
        if (!is_array($v) && !$v instanceof Model && !$v instanceof Collection) 
        {
            return $v;
        }

        $v = is_array($v) ? collect($v): collect($v->toArray());

        return $v->flatten();
    }
}
