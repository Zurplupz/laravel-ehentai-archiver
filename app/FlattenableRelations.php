<?php

namespace App;
use Illuminate\Database\Eloquent\Model;

/**
 * 
 */
abstract class FlattenableRelations extends Model
{
	protected $nested_relations;
	protected $models_namespace;

	function __construct(string $models_namespace='App')
	{
		parent::__construct();
	}

	protected function defineNestedRelations(array $rels)
	{
		$rels = array_filter($rels, function ($v, $k) {
			$array = !empty($v['name']) && !empty($v['has']); 

			return is_string($k) && (is_string($v) || $array);
		}, ARRAY_FILTER_USE_BOTH);

		if (!$rels) return;

		foreach ($rels as $children_name => $v) {
			if (is_string($v)) {
				$this->nested_relations[$children_name] = [
					'name' => $v, 'has' => $v
				];

				continue;
			}

			$this->nested_relations[$children_name] = $v;
		}
	}

	public function flattenRelationships()
	{
		// array of collections
		$relation_groups = $this->getRelations();

		if (empty($relation_groups) || empty($this->nested_relations)) {
			return;
		}

		foreach ($relation_groups as $children_name => $children) {
			if (empty($this->nested_relations[$children_name])) {
				continue;
			}

			$gc_list = [ ];

			$grandchild = $this->nested_relations[$children_name]['has'];
			$gc_name = $this->nested_relations[$children_name]['name'];

			$children->each(function ($child) use ($grandchild, &$gc_list)
			{
				if (empty($child->{$grandchild})) {
					return;
				}

				// todo: get primary key and columns dynamically
				$pk = $child->{$grandchild}->id;

				$gc_list[$pk] = $child->{$grandchild}->name;
			});

			$this->{$gc_name} = $gc_list;
		}

		return $this;
	}
}