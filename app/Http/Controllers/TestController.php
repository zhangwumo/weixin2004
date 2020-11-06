<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
class TestController extends Controller
{



public function wxEvent()
    {
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];

        $token = env('WX_TOKEN');
        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode( $tmpArr );
        $tmpStr = sha1( $tmpStr );
        //验证通过
        if( $tmpStr == $signature ){
            // 接收数据
            $xml_str=file_get_contents("php://input");
         //记录日志
            file_put_contents('wx_event.log',$xml_str);
//            Log::info($xml_str);
            echo "";
            die;
        //    把xml文本转换为php的对象或数组
//            $data=simplexml_load_string($xml_str,'SimpleXMLElement',LIBXML_NOCDATA);
//            dd($data);
        }else{
            echo "";
        }
    }

    public function getAccessToken(){
        $key = 'wx:access_token';

        //检查是否有token
        $token = Redis::get($key);
            if($token){
                echo "有缓存";echo '<br>';
                echo $token;
            }else{
                echo "无缓存";

        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".env('WX_APPID')."&secret=".env('WX_APPSEC')."";
        $response = file_get_contents($url);
        //echo $response;
        $data = json_decode($response,true);
        $token=$data['access_token'];
        //保存redis

        Redis::set($key,$token);
        Redis::expire($key,3600);


            }
    echo "access_token:".$token;




    }


}