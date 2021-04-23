<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class gallery_tagging extends Model
{
    public $timestamps = false;
	protected $hidden = ['id','gallery_id','tag_id'];

    public function gallery()
    {
    	return $this->belongsTo('App\gallery');
    }

    public function tag()
    {
    	return $this->belongsTo('App\tag');
    }
}
