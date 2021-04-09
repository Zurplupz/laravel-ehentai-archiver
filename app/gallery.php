<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class gallery extends Model
{
	public function gallery_group()
	{
		return $this->hasMany('App\gallery_group');
	}

	public function gallery_tagging()
	{
		return $this->hasMany('App\gallery_tagging');
	}
}
