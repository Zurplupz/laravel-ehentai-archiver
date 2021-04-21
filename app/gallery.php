<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Flattenable;

class gallery extends FlattenableRelations
{
	protected $hidden = ['gallery_group','gallery_tagging','archiver_key'];
	protected $fillable = ['title','favorited','rating'];

	function __construct()
	{
		parent::__construct();

		$table = \Schema::getColumnListing('galleries');

		$this->guarded = array_filter($table, function ($v) {
			return !in_array($v, $this->fillable);
		});

		$this->defineNestedRelations([
			'gallery_group' 	=>	[
				'name' => 'groups',
				'has' => 'group'
			], 

			'gallery_tagging' 	=>	[
				'name' => 'tags',
				'has' => 'tag'
			]
		]);
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
