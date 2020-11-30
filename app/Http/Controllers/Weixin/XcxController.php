<?php

namespace App\Http\Controllers\Weixin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use App\Models\Xcxlogin;
use App\Models\IndexModel;
class XcxController extends Controller
{

    public function detail(Request $request){
        $goods_id=Request()->get("goods_id");
        $detail=IndexModel::select("goods_img","goods_name","goods_price","goods_imgs","goods_id")->where("goods_id",$goods_id)->first()->toArray();

        $array = [
            "goods_name"=>$detail['goods_name'],
            "goods_price"=>$detail['goods_price'],
            "goods_img"=>explode(",",$detail['goods_imgs']),
            "goods_id"=>$detail['goods_id']
        ];
        return $array;
    }
    public function addfav(Request $request){
        $goods_id = $request->get('goods_id');
//        $token = $request->get('token');

        //加入收藏redis有序集合
        $uid = 2345;
        $key = 'xcx:add-fav'.$uid;   //用户收藏商品的有序集合
        Redis::zadd($key,time(),$goods_id);  //将商品id加入有序集合并给排序值
        $response = [
            'errno'=>0,
            'msg'=>'ok'
        ];

        return $response;
    }
}
