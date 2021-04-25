<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class credit_log extends Model
{
	protected $guarded = ['id','amount','difference','event'];
}
