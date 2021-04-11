<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class gallery extends Model
{
	protected $fillable = ['title','favorited','rating'];

	function __construct()
	{
		parent::__construct();

		$table = \Schema::getColumnListing('galleries');

		$this->guarded = array_filter($table, function ($v) {
			return !in_array($v, $this->fillable);
		});
	}

	protected $hidden = ['gallery_group','gallery_tagging'];

	public function gallery_group()
	{
		return $this->hasMany('App\gallery_group');
	}

	public function gallery_tagging()
	{
		return $this->hasMany('App\gallery_tagging');
	}
}
