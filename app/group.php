<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class group extends Model
{
    public function gallery_group()
    {
    	return $this->hasMany('App\gallery_group');
    }
}
