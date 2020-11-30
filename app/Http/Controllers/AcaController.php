<?php

namespace App\Http\Controllers;
use App\Models\IndexModel;
use App\Models\Xcxlogin as ModelsXcxlogin;
use Illuminate\Http\Request;
use DB;
use App\Models\Xcxlogin;
use Illuminate\Support\Facades\Redis;
use App\Models\UsermModel;


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

    
    public function homeLogin(Request $request)
{
    //接收code
    $code = $request->get('code');
    $userInfo=json_decode(file_get_contents('php://input'),true);
    dd($userInfo);

    //使用code
    $url = 'https://api.weixin.qq.com/sns/jscode2session?appid=' . env('WX_XCX_APPID') . '&secret=' . env('WX_XCX_SECRET') . '&js_code=' . $code . '&grant_type=authorization_code';
    $data = json_decode(file_get_contents($url), true);
    //自定义登录状态
    if (isset($data['errcode']))     //有错误
    {
        $response = [
            'errno' => 50001,
            'msg' => '登录失败',
        ];

    } else {              //成功
        $openid = $data['openid'];          //用户OpenID
        //判断新用户 老用户
        $u = Xcxlogin::where(['openid' => $openid])->first();
        if ($u) {
            // TODO 老用户
            $uid = $u->id;
            //更新用户信息

        } else {
            // TODO 新用户
            $u_info = [
                'openid' => $openid,
                'add_time' => time(),  
                'type' => 3        //小程序
            ];

            $uid = Xcxlogin::insertGetId($u_info);
        }

        //生成token
        $token = sha1($data['openid'] . $data['session_key'] . mt_rand(0, 999999));
        //保存token
        $redis_login_hash = 'xcx_token:' . $token;

        $login_info = [
            'uid' => $uid,
            'user_name' => "",
            'login_time' => date('Y-m-d H:i:s'),
            'login_ip' => $request->getClientIp(),
            'token' => $token,
            'openid'    => $openid
        ];

        //保存登录信息
        Redis::hMset($redis_login_hash, $login_info);
        // 设置过期时间
        Redis::expire($redis_login_hash, 7200);

        $response = [
            'errno' => 0,
            'msg' => 'ok',
            'data' => [
                'token' => $token
            ]
        ];
    }

    return $response;

} 


public function userLogin(Request $request)
{
    //接收code
    //$code = $request->get('code');
    $token = $request->get('token');

    //获取用户信息
    $userinfo = json_decode(file_get_contents("php://input"), true);
//        dd($userinfo);
    $redis_login_hash = 'h:xcx:login:' . $token;
    //获取openid
    $openid = Redis::hget($redis_login_hash,'openid');
//         dd($openid);
     //获取uid
    $uid = Redis::hget($redis_login_hash,'uid');
    //        $uid = UserxModel::where('openid' ,$openid)->get('id')->toArray();
//        $uid = $uid[0]["id"];
//        dd($uid);



    if(empty($umy=UsermModel::where('openid' ,$openid)->first())){
        $u_info=[
            "uid"=>$uid,
            'openid' => $openid,
            'nickname' => $userinfo['u']['nickName'],
            'sex' =>  $userinfo['u']['gender'],
            'language' => $userinfo['u']['language'],
            'city'=> $userinfo['u']['city'],
            'province' =>  $userinfo['u']['province'],
            'country'  => $userinfo['u']['country'],
            'headimgurl'=>$userinfo['u']['avatarUrl'],
            'add_time'=>time(),
            'type'=> 3
        ];

        UsermModel::insertGetId($u_info);
    }elseif($umy->update_time == 0){     // 未更新过资料
        //因为用户已经在首页登录过 所以只需更新用户信息表
        $u_infos= [
            'nickname' => $userinfo['u']['nickName'],
            'sex' => $userinfo['u']['gender'],
            'language' => $userinfo['u']['language'],
            'city' => $userinfo['u']['city'],
            'province' => $userinfo['u']['province'],
            'country' => $userinfo['u']['country'],
            'headimgurl' => $userinfo['u']['avatarUrl'],
            'update_time'   => time()
        ];
        UsermModel::where('openid' ,$openid)->update($u_infos);
    }

    $response = [
        'errno' => 0,
        'msg' => 'ok',
    ];


}
}
