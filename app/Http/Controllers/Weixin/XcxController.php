<?php

namespace App\Http\Controllers\Weixin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use App\Models\Xcxlogin;
use App\Models\IndexModel;
class XcxController extends Controller
{
    public function login(Request $request){
        
        //接收code
        $code = $request->get('code');
        //使用code
        $url = 'https://api.weixin.qq.com/sns/jscode2session?appid='.env('WX_XCX_APPID').'&secret='.env('WX_XCX_SECRET').'&js_code='.$code.'&grant_type=authorization_code';
        
        $data = json_decode(file_get_contents($url),true);
        echo '<pre>';print_r($data);echo '</pre>';

        //自定义登录状态
        if(isset($data['errcode']))//有错误
        {
            $response = [
                'errno' => 50001,
                'msg' =>'登录失败'
            ];
        }else{//成功
            //入库
            $openid = $data['openid'];
            Xcxlogin::insert(["openid"=>$openid]);

            $token = sha1($data['openid'].$data['session_key'].mt_rand(0,99999));
            //保存token
            $redis_key = 'xcx_token'.$token;
            Redis::set($redis_key,time());
            //设置过期时间
            Redis::expire($redis_key,7200);

            $response = [
                'errno' => 0,
                'msg' =>'ok',
                'data' => [
                    'token'=>$token
                ]
            ];
        }
        return $response;

    }
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
