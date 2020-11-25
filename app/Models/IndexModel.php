<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IndexModel extends Model
{
    protected $table = 'ecs_goods';
    protected $primaryKey = 'goods_id';
    public $timestamps = false;
    protected $guarded=[];
}
