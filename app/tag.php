<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class tag extends Model
{
    public function gallery_tagging()
    {
    	return $this->hasMany('App\gallery_tagging');
    }
}
