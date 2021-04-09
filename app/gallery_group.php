<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class gallery_group extends Model
{
    public $timestamps = false;

    public function gallery()
    {
    	return $this->belongsTo('App\gallery');
    }

    public function group()
    {
    	return $this->belongsTo('App\group');
    }
}
