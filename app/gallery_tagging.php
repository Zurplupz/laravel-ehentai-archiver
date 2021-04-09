<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class gallery_tagging extends Model
{
    public $timestamps = false;

    public function gallery()
    {
    	return $this->belongsTo('App\gallery');
    }

    public function tag()
    {
    	return $this->belongsTo('App\gallery');
    }
}
