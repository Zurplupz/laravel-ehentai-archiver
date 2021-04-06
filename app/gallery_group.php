<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class gallery_group extends Model
{
    public $timestamps = false;

    public function group()
    {
    	return $this->belongsTo('App\group');
    }
}
