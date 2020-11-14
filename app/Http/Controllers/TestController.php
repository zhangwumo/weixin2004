<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use GuzzleHttp\Client;
use App\Models\User_info;
use App\Models\Media;
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
            if($data->EventKey == 'zwm'){
                $key = $data->FromIserName;
                $times = date("Y-m-d", time());
                $date =Redis::zrange($key, 0,-1);//从0开始
                if($date){
                    $date = $date[0];//下标为0
                }   
                if($date == $times){
                    $comtent = "你已经签到过了 快滚吧";
                }else{
                    $zcard =Redis::zcard($key);//zcard 获取它的总数量
                    if($zcard >=1){ //他的值只会保留一个 只会保留当天签到
                        Redis::zremrangebyrank($key , 0, 0);
                    }
                    $keys = $this->array_xml($xml_data);
                    $keys = $keys['FromUserName'];
                    $zincrby = Redis::zincrby($key,1,$keys);
                    $zadd = Redis::zadd($key,$zincrby,$times);
                    $score = Redis::incrby($keys . "_score",100);
                }
                $content ="恭喜你签到了第". $zincrby . "天" . "那你积累获得了".$score."积分";
                
                    
                }
            










            if($data->Event!="subscribe" && $data->Event!= "unsubscribe"){
            $this->typeContent($data); //先调用这个方法 判断是什么类型
    
            }
        
        

    


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

    if($data->MsgType=="text"){
        $city = urlencode(str_replace("天气:","",$data->Content));
        $key = "e2ca2bb61958e6478028e72b8a7a8b60";
        $url = "http://apis.juhe.cn/simpleWeather/query?city=".$city."&key=".$key;
        $tianqi = file_get_contents($url);
        //file_put_contents('tianqi.txt',$tianqi);
        $res = json_decode($tianqi,true);
        $content="";
        if($res['error_code']==0){
            $today = $res['result']['realtime'];
            $content .= "查询天气的城市:".$res['result']['city']."\n";
            $content .= "天气详细情况".$today['info']."\n";
            $content .= "温度".$today['temperature']."\n";
            $content .= "湿度".$today['humidity']."\n";
            $content .= "风向".$today['direct']."\n";
            $content .= "风力".$today['power']."\n";
            $content .= "空气质量指数".$today['aqi']."\n";

            //获取一个星期的天气
            $future = $res['result']['future'];
            foreach($future as $k=>$v){
                $content .= "日期:".date("Y-m-d",strtotime($v['date'])).$v['temperature'].",";
                $content .= "天气:".$v['weather']."\n";
            }
        }else{
            $content = "你查寻的天气失败，请输入正确的格式:天气、城市";
        }
        //file_put_contents("tianqi.txt",$content);

        echo $this->nodeInfo($data,$content);

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
      //关注回复
        $temlate="<xml>
                    <ToUserName><![CDATA[".$toUserName."]]></ToUserName>
                    <FromUserName><![CDATA[".$fromUserName."]]></FromUserName>
                    <CreateTime>".time()."</CreateTime>
                    <MsgType><![CDATA[text]]></MsgType>
                    <Content><![CDATA[".$content."]]></Content>
                </xml>";
        echo $temlate;

    }
// file_put_contents ('3.txt','1');
// file_put_contents ('1.txt',print_r(sprintf($temlate,$toUserName,$fromUserName,$time,$msgType,$content),1));
// file_put_contents ('2.txt',sprintf($temlate,$toUserName,$fromUserName,$time,$msgType,$content));
// die;

    //菜单
    public function menu(){
        $token = $this->getAccessToken();
        $url= "https://api.weixin.qq.com/cgi-bin/menu/create?access_token=".$token;
        $menu = [
            "button"=> [
                [
                    "type" =>"view",
                    "name" =>"搜索",
                    "url" => "https://www.baidu.com/"
                ],
                [
                    "name"=>"娱乐",
                    "sub_button"=>[
                        [
                        "type"=>"click",
                        "name"=>"签到",
                        "key"=>"zwm",
                        ],
                        [
                            "type"=>"view",
                            "name"=>"拼多多",
                            "url"=>"https://www.pinduoduo.com/"
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
    


    //图片，视频，语言，
    public function typeContent($data){
        $res = Media::where("media_id",$data->MediaId)->first();
        $token = $this->getAccessToken(); //获取token、
        if(empty($res)){
            $url = "https://api.weixin.qq.com/cgi-bin/media/get?access_token=".$token."&media_id=".$data->MediaId;
            $url = file_get_contents($url);
            $obj=[
                "time"=>time(), //类型公用的 类型不一样向 $data里面查数据
                "msg_type"=>$data->MsgType,
                "openid"=>$data->FromUserName,
                "msg_id"=>$data->MsgId
            ];
            //图片
            if($data->MsgType=="image"){
                $file_type = '.jpg';
                $data["url"] = $data->PicUrl;
                $data["media_id"] =$data->MediaId;
                
            }
            //视频
            if($data->MsgType=="video"){
                $file_type='.mp4';
                $data["media_id"]=$data->MediaId;
            }
            //文本
            if($data->MsgType=="text"){
                $file_type='.txt';
                $data["content"]=$data->Content;
            }
            //音频
            if($data->MsgType=="voice"){
                $file_type ='.amr';
                $data["media_id"]=$data->MediaId;
            }
            if(!empty($file_type)){
                file_put_contents("dwaw".$file_type,$url);
            }
            Media::insert($obj);exit;

        }else{
            return $res;
        }
        return true;
    }

    }