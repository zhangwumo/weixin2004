<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class User_info extends Model
{
    protected $table = 'user_info';
    protected $primaryKey = 'user_id';
    public $timestamps = false;
    protected $guarded=[];
}
