<?php

namespace App\Http\Controllers;
use App\Models\IndexModel;
use App\Models\Xcxlogin as ModelsXcxlogin;
use Illuminate\Http\Request;
use DB;
use App\Modes\Xcxlogin;
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
        $u =Xcxlogin::where(['openid' => $openid])->first();
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
}
