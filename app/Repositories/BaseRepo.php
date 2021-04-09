<?php

namespace App\Repositories;

/**
 * 
 */
abstract class BaseRepo
{
	protected $model;

	protected function defineModel($model)
	{
		$this->model = $model;
	}
}