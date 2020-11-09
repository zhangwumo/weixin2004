<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use GuzzleHttp\Client;
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
           // $data=simplexml_load_string($xml_str,'SimpleX36MLElement',LIBXML_NOCDATA);
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
public function data()
  {
    echo '<pre>';print_r($_GET);echo '<pre>';
  }

public function date()
  {
    //echo '<pre>';print_r($_POST);echo '<pre>';
    $xml_data =file_get_contents("php://input");

    //将xml 转化为 对象或数组
    $xml_obj= simplexml_load_string($xml_data);
   // echo '<pre>';print_r($xml_obj);echo '<pre>';
    echo $xml_obj->ToUserName;

}



public function guzzle(){
//        echo __METHOD__;
        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".env('WX_APPID')."&secret=".env('WX_APPSEC');
        echo $url;die;

        //使用guzzle发送get请求
        $client = new Client();   //实例化客户端
        $response = $client->request('GET',$url,['verify'=>false]);    //发起请求并接受响应
        $json_str = $response->getBody();   //服务器的响应数据
        echo $json_str;
    }

 public function guzzle2(){
    $access_token = $this->getAccessToken();
    $type = "image";
    $url = "https://api.weixin.qq.com/cgi-bin/media/upload?access_token=".$access_token."&type=".$type;
    //使用guzzle发送get请求
    $client = new Client();   //实例化客户端
    $response = $client->request('POST',$url,[
        'verify' => false,
        'multipart' => [
            [
                'name'     => 'media',   //上传文件的路径
                'contents'     => fopen('5.jpg','r'),   //上传文件的路径
            ],

        ]
    ]);    //发起请求并接受响应
    $data = $response->getBody();
    echo $data;
}





}