<?php


/*
 ____                                                         __                __     
/\  _`\                                                      /\ \__            /\ \    
\ \ \/\ \     __    _____    _ __     __     ___      __     \ \ ,_\     __    \_\ \   
 \ \ \ \ \  /'__`\ /\ '__`\ /\`'__\ /'__`\  /'___\  /'__`\    \ \ \/   /'__`\  /'_` \  
  \ \ \_\ \/\  __/ \ \ \L\ \\ \ \/ /\  __/ /\ \__/ /\ \L\.\_   \ \ \_ /\  __/ /\ \L\ \ 
   \ \____/\ \____\ \ \ ,__/ \ \_\ \ \____\\ \____\\ \__/.\_\   \ \__\\ \____\\ \___,_\
    \/___/  \/____/  \ \ \/   \/_/  \/____/ \/____/ \/__/\/_/    \/__/ \/____/ \/__,_ /
                      \ \_\                                                            
                       \/_/                                                            
*/

namespace App;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use App\ReturnsFlatenned;

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

	public static function query()
	{
		$q = parent::query();

		$class = new ReturnsFlatenned($q);
		
		return $class;
	}

	protected function defineNestedRelations(array $rels)
	{
		// name: name of object that will contain the relations
		// has: name of the relation/method that returns the relations

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
			// this relation is unknown
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

			// attach the granchildren to the grandpa
			$this->{$gc_name} = $gc_list;
		}

		// return grandpa
		return $this;
	}
}