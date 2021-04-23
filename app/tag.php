<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class tag extends Model
{
	protected $hidden = ['id','created_at','updated_at'];

    public function gallery_tagging()
    {
    	return $this->hasMany('App\gallery_tagging');
    }
}
