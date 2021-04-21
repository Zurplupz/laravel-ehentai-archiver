<?php

namespace App\Repositories;

use Illuminate\Database\Eloquent\Model;

/**
 * 
 */
abstract class BaseRepo
{
	protected $model;
	protected $modelClass;
	protected $cols;
	protected $select;

	protected function defineModel(Model $model)
	{
		$this->modelClass = get_class($model);

		$this->model = $model->query();

		$this->cols = \Schema::getColumnListing($model->getTable());

		$this->select = [];
	}

	protected function reset()
	{
		$q = new $this->modelClass();

		$this->defineModel($q);
	}

	public function select(array $selected) :self
	{
		$selected = array_filter($selected);

		foreach ($selected as $k => $v) {
			if (!in_array($v, $this->cols) || in_array($v, $this->select))
			{
				unset($selected[$k]);
				continue;
			}

			$this->select[] = $v;
		}

		if ($selected) {
			$this->model->addSelect($selected);
		}

		return $this;
	}

	public function limit(int $v) :self
	{
		$this->model->limit($v);

		return $this;
	}

	public function offset(int $v) :self
	{
		$this->model->offset($v);

		return $this;
	}

	public function get()
	{
		$result = $this->model->get();

		$this->reset();

		return $result;
	}

	public function first()
	{
		$result = $this->model->first();

		$this->reset();

		return $result;
	}

	public function find($id)
	{
		$result = $this->model->find($id);

		$this->reset();

		return $result;
	}
}