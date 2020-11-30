<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UsermModel extends Model
{
    protected $table = 'xcx';
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $guarded=[];
}
