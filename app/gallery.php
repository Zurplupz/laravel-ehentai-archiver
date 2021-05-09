<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Flattenable;

class gallery extends Model
{
	protected $hidden = ['gallery_group','gallery_tagging','archiver_key'];

	protected $fillable = [
		'title','archived','archive_path','rating','favorited','rating'
	];

	function __construct()
	{
		parent::__construct();

		$table = \Schema::getColumnListing('galleries');

		$this->guarded = array_filter($table, function ($v) {
			return !in_array($v, $this->fillable);
		});
	}

	public function gallery_group()
	{
		return $this->hasMany('App\gallery_group');
	}

	public function gallery_tagging()
	{
		return $this->hasMany('App\gallery_tagging');
	}
}
