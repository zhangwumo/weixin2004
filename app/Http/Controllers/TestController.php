<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use GuzzleHttp\Client;
class TestController extends Controller
{
//推送事件
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
           // echo "";
           // die;
        //    把xml文本转换为php的对象或数组
           $data=simplexml_load_string($xml_str);
           //判断
           if($data->MsgType=="event"){
                if($data->Event=="subscribe"){
                    $content="关注成功";
                    $return=$this->nodeInfo($data,$content);
                    return $return;
                }
           }
           // dd($data);  
        }else{

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
     return $token;
    }

    public function nodeInfo($data,$content){
        $fromUserName = $data->ToUserName; //开发者微信号
        $toUserName = $data ->FromUserName;//发送方账号
        file_put_contents('www.log',$toUserName);
        $time=time();
        $msgType="text";
        $temlate="<xml>
                            <ToUserName><![CDATA[%s]]></ToUserName>
                            <FromUserName><![CDATA[%s]]></FromUserName>
                            <CreateTime>%s</CreateTime>
                            <MsgType><![CDATA[%s]]></MsgType>
                            <Content><![CDATA[%s]]></Content>
                        </xml>";

        echo sprintf($temlate,$toUserName,$fromUserName,$time,$msgType,$content);
    }

//     public function menu(){
//          $token = $this->getAccessToken();
//         $url= "https://api.weixin.qq.com/cgi-bin/menu/create?access_token=".$token;
//         $menu = [
//             "button"=> [
//                 [
//                     "type" =>"view",
//                    "name" =>"搜索",
//                     "url" => "https://www.baidu.com/"
//                 ]    
//             ]
//                 ];

// $Client = new Client();
// $response = $Client ->request('POST',$url,[
//     'verify'=>false,
//     'body'=>json_encode($menu,JSON_UNESCAPED_UNICODE)
// ]);
//     $data = $response->getBody();
//     echo $data;

//       }
  }
