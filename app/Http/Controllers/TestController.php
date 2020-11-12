<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use GuzzleHttp\Client;
use App\Models\User_info;
use Log;
class TestController extends Controller
{
//推送事件
public function wxEvent()
    {
        // file_put_contents('1.txt','1');die;  request()->get("");
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];

        $token = env('WX_TOKEN');
        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode( $tmpArr );
        $tmpStr = sha1( $tmpStr );  
        //验证通过
        if( $tmpStr == $signature ) {
            //1、接收数据
           $xml_data = file_get_contents("php://input");
           //记录日志
           file_put_contents('wx_event.log',$xml_data);

           //2、把xml文本转换成为php的对象或数组
           $data = simplexml_load_string($xml_data,'SimpleXMLElement',LIBXML_NOCDATA);

             if($data->MsgType=="event"){
                if($data->Event=="subscribe"){
                  $access_token = $this->getAccessToken();
                   $openid = $data->FromUserName;
          $url = "https://api.weixin.qq.com/cgi-bin/user/info?access_token=".$access_token."&openid=".$openid."&lang=zh_CN";
                   $user = file_get_contents($url);
                   $res = json_decode($user,true);

                    if(isset($res['errcode'])){
                       file_put_contents('wx_event.log',$res['errcode']);
                   }else{
                       $user_id = User_info::where('openid',$openid)->first();
                        if($user_id){
                           $user_id->subscribe=1;
                           $user_id->save();
                           $contentt = "感谢再次关注";
                       }else{

                            $res = [
                               'subscribe'=>$res['subscribe'],
                               'openid'=>$res['openid'],
                               'nickname'=>$res['nickname'],
                               'sex'=>$res['sex'],
                               'city'=>$res['city'],
                               'country'=>$res['country'],
                               'province'=>$res['province'],
                               'language'=>$res['language'],
                               'headimgurl'=>$res['headimgurl'],
                               'subscribe_time'=>$res['subscribe_time'],
                               'subscribe_scene'=>$res['subscribe_scene']

                           ];
                           User_info::insert($res);
                           $contentt = "欢迎老铁关注";
                }
             }
       }

            //取消关注
               if($data->Event=='unsubscribe'){
                   $user_id->subscribe=0;
                   $user_id->save();
               }
               echo $this->nodeInfo($data,$contentt);

                   }
       }
    }
    public function getAccessToken(){

        $key = 'WX:access_token';
    
        //检查是否有token
        $token = Redis::get($key);
        if($token){
            echo "有缓存";echo'</br>';
            
        }else{
           
        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".env('WX_APPID')."&secret=".env('WX_APPSEC');
    
    
            //使用guzzle发送get请求
            $client = new Client();  //实例化客户端
            $response = $client->request('GET',$url,['verify'=>false]);     //发送请求并接受响应
    
            $json_str = $response->getBody();          //服务器的响应数据
    
    
    
    
     
    
        $data = json_decode($json_str,true);
        $token = $data['access_token'];
    
    
        //保存到redis 中  时间为3600
    
        Redis::set($key,$token);
        Redis::expire($key,3600);
    
        }
        
        return $token;
     }

    public function nodeInfo($data,$content){
        $fromUserName = $data->ToUserName; //开发者微信号
        $toUserName = $data->FromUserName;//发送方账号
      
        $temlate="<xml>
                       <ToUserName><![CDATA[".$toUserName."]]></ToUserName>
                       <FromUserName><![CDATA[".$fromUserName."]]></FromUserName>
                       <CreateTime>".time()."</CreateTime>
                       <MsgType><![CDATA[text]]></MsgType>
                       <Content><![CDATA[".$content."]]></Content>
                  </xml>";
        echo $temlate;

    }
// file_put_contents ('3.txt','1');
// file_put_contents ('1.txt',print_r(sprintf($temlate,$toUserName,$fromUserName,$time,$msgType,$content),1));
// file_put_contents ('2.txt',sprintf($temlate,$toUserName,$fromUserName,$time,$msgType,$content));
// die;
       

    public function menu(){
         $token = $this->getAccessToken();
        $url= "https://api.weixin.qq.com/cgi-bin/menu/create?access_token=".$token;
        $menu = [
            "button"=> [
                [
                    "type" =>"view",
                   "name" =>"天气",
                    "url" => "https://www.baidu.com/"
                ],
                [
                    "name"=>"娱乐",   
                    "sub_button"=>[
                        [
                         "type"=>"view",
                         "name"=>"视频",
                         "url"=>"https://www.baidu.com/"   
                        ],
                        [
                            "type"=>"view",
                            "name"=>"音乐",
                            "url"=>"https://www.baidu.com/"   
                        ]
                    ]             
                        ],

                        [
                            "name"=>"学习",
                            "sub_button"=>[
                                [
                                    "type"=>"view",
                                    "name"=>"语文",
                                    "url"=>"https://www.baidu.com/"
                                ],
                                [
                                    "type"=>"view",
                                    "name"=>"数学",
                                    "url"=>"https://www.baidu.com/"
                                ]
                            ]
                        ]
                ]
                ];

$Client = new Client();
$response = $Client ->request('POST',$url,[
    'verify'=>false,
    'body'=>json_encode($menu,JSON_UNESCAPED_UNICODE)
]);
    $data = $response->getBody();
    echo $data;

      }



//       //关注回复
//       public function responseMsg($array,$Contentt){
//                   $ToUserName = $array->FromUserName;
//                   $FromUserName = $array->ToUserName;
//                   $CreateTime = time();
//                   $MsgType = "text";
  
//                   $text = "<xml>
//                     <ToUserName><![CDATA[%s]]></ToUserName>
//                     <FromUserName><![CDATA[%s]]></FromUserName>
//                     <CreateTime>%s</CreateTime>
//                     <MsgType><![CDATA[%s]]></MsgType>
//                     <Content><![CDATA[%s]]></Content>
//                   </xml>";
//                   echo sprintf($text,$ToUserName,$FromUserName,$CreateTime,$MsgType,$Content);
  
  
  
  
//   }
  }
