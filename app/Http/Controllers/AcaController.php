<?php

namespace App\Http\Controllers;
use App\Models\IndexModel;
use Illuminate\Http\Request;
use DB;
class AcaController extends Controller
{
    public function goodslist(){
       
      $g = IndexModel::select('goods_id','goods_name','goods_price','goods_img')->limit(10)->get()->toArray();
     
      $response = [

        'errno' => 0,
        'msg'   =>'ok',
        'data'  => [
            'list' => $g
        ]
      ];
      return $response;
    }
}
