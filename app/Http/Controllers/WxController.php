<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class WxController extends Controller
{
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

public function date()
  {
    //echo '<pre>';print_r($_POST);echo '<pre>';
    $xml_data =file_get_contents("php://input");

    //将xml 转化为 对象或数组
    $xml_obj= simplexml_load_string($xml_data);
   // echo '<pre>';print_r($xml_obj);echo '<pre>';
    echo $xml_obj->ToUserName;

}

}
