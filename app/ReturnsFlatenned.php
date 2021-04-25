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
use Illuminate\Database\Eloquent\Builder;
use App\FlattenableRelations as Flattenable;

/**
 * 
 */
class ReturnsFlatenned
{
    protected $builder;

    function __construct(Builder $builder)
    {
        $this->builder = $builder;
    }

    public function __call(string $name, array $arguments)
    {
        $this->builder = $this->builder->{$name}(...$arguments);

        return $this;
    }

	// get should return flattenablecollection

    /**
     * Execute the query as a "select" statement.
     *
     * @param  bool  $flatten
     * @param  array|string  $columns
     * @return \Illuminate\Support\Collection
     */
	public function get(bool $flatten=true, $columns = ['*']) :Collection
	{
		$result = $this->builder->get($columns);

		if ($flatten) {
			$result->each(function ($model) {
				$model->flattenRelationships();
			});
		}

		return $result;
	}

    /**
     * Get all of the models from the database.
     *
     * @param  bool  $flatten
     * @param  array|mixed  $columns
     * @return \Illuminate\Database\Eloquent\Collection|static[]
     */
    public static function all(bool $flatten=true, $columns = ['*'])
    {
        $result = $this->builder->all($columns);

        if ($flatten && $result instanceof Collection) {
			$result->each(function ($model) {
                if (!$model instanceof Flattenable) {
                    return;
                }

				$model->flattenRelationships();
			});
        }

        return $result;
    }

    /**
     * Execute the query and get the first result.
     *
     * @param  bool  $flatten
     * @param  array|string  $columns
     * @return \Illuminate\Database\Eloquent\Model|object|static|null
     */
    public function first(bool $flatten=true, $columns = ['*'])
    {
		$result = $this->builder->first($columns);

		if ($flatten && $result instanceof Flattenable) {
			$result->flattenRelationships();
		}

		return $result;
    }

    /**
     * Execute a query for a single record by ID.
     *
     * @param  int|string  $id
     * @param  bool  $flatten
     * @param  array  $columns
     * @return mixed|static
     */
    public function find($id, bool $flatten=true, $columns = ['*'])
    {
    	$result = $this->builder->find($id, $columns);

    	if ($flatten && $result instanceof Flattenable) {
    		$result->flattenRelationships();
    	}

    	return $result;
    }
}