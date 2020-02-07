<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Redis;
use GuzzleHttp\Client;
use App\Model\UserModel;

class TestController extends Controller
{
    public function info()
    {
        echo phpinfo();
    }
    public function test()
    {

        echo '<pre>';print_r($_SERVER);echo '</pre>';
    }


    /**
     * 用户注册
     */
    public function reg0(Request $request)
    {
        echo '<pre>';print_r($request->input());echo '</pre>';

        //验证用户名 验证email 验证手机号

        $pass1 = $request->input('pass1');
        $pass2 = $request->input('pass2');

        if($pass1 != $pass2){
            die("两次输入的密码不一致");
        }

        $password = password_hash($pass1,PASSWORD_BCRYPT);

        $data = [
            'email'         => $request->input('email'),
            'name'          => $request->input('name'),
            'password'      => $password,
            'mobile'        => $request->input('mobile'),
            'last_login'    => time(),
            'last_ip'       => $_SERVER['REMOTE_ADDR'],     //获取远程IP
        ];

        $uid = UserModel::insertGetId($data);
        var_dump($uid);
    }


    /**
     * 用户登录接口
     * @param Request $request
     * @return array
     */
    public function login0(Request $request)
    {

        $name = $request->input('name');
        $pass = $request->input('pass');

        $u = UserModel::where(['name'=>$name])->first();
        if($u){
            //验证密码
            if( password_verify($pass,$u->password) ){
                // 登录成功
                //echo '登录成功';
                //生成token
                $token = Str::random(32);

                $response = [
                    'errno' => 0,
                    'msg'   => 'ok',
                    'data'  => [
                        'token' => $token
                    ]
                ];

            }else{
                $response = [
                    'errno' => 400003,
                    'msg'   => '密码不正确'
                ];
            }
        }else{
            $response = [
                'errno' => 400004,
                'msg'   => '用户不存在'
            ];
        }

        return $response;

    }

    /**
     * 获取用户列表
     * 2020年1月2日16:32:07
     */
    public function userList()
    {

        $list = UserModel::all();
        echo '<pre>';print_r($list->toArray());echo '</pre>';

    }


    /**
     * APP注册
     * @return bool|string
     */
    public function reg()
    {
        //请求passport
        $url = 'http://laravel.1905.com/reg';
        $response = UserModel::curlPost($url,$_POST);
        return $response;
    }

    /**
     * APP 登录
     */
    public function login()
    {
        //请求passport
        $url = 'http://laravel.1905.com/login';
        $response = UserModel::curlPost($url,$_POST);
        return $response;
    }

    public function showData()
    {

        // 收到 token
        $uid = $_SERVER['HTTP_UID'];
        $token = $_SERVER['HTTP_TOKEN'];

        // 请求passport鉴权
        $url = 'http://laravel.1905.com/api/auth';         //鉴权接口
        $response = UserModel::curlPost($url,['uid'=>$uid,'token'=>$token]);

        $status = json_decode($response,true);

        //处理鉴权结果
        if($status['errno']==0)     //鉴权通过
        {
            $data = "sdlfkjsldfkjsdlf";
            $response = [
                'errno' => 0,
                'msg'   => 'ok',
                'data'  => $data
            ];
        }else{          //鉴权失败
            $response = [
                'errno' => 40003,
                'msg'   => '授权失败'
            ];
        }

        return $response;

    }
    public function postman1()
    {
        $data = [
            'user_name' => 'zhangsan',
            'email'     => 'zhangsan@qq.com',
            'amount'    => 10000
        ];

        echo json_encode($data);
//        //获取用户标识
//        $token = $_SERVER['HTTP_TOKEN'];
//        // 当前url
//        $request_uri = $_SERVER['REQUEST_URI'];
//
//        $url_hash = md5($token . $request_uri);
//
//
//        //echo 'url_hash: ' .  $url_hash;echo '</br>';
//        $key = 'count:url:'.$url_hash;
//        echo 'Key: '.$key;echo '</br>';
//
//        //检查 次数是否已经超过限制
//        $count = Redis::get($key);
//        echo "当前接口访问次数为：". $count;echo '</br>';
//
//        if($count >= 5){
//            $time = 10;     // 时间秒
//            echo "请勿频繁请求接口, $time 秒后重试";
//            Redis::expire($key,$time);
//            die;
//        }
//
//
//        // 访问数 +1
//        $count = Redis::incr($key);
//        echo 'count: '.$count;

    }


    public  function md5test()
    {
        $data = "马苏鹏";//发送的数据
        $key = '1905';//计算签名的key  发送端与接收端相同
        //计算签名规则  MD5($data . $key)
        $signature = md5($data . $key);
//        $signature = "msp155155";
        $url = "http://laravel.1905.com/test/get?data=".$data . '&signature='.$signature;
        echo $url;echo '<hr>';
        $response = file_get_contents($url);
        echo $response;
    }

    public function sign2()
    {
        $key = "1905";          // 签名使用key  发送端与接收端 使用同一个key 计算签名
        //待签名的数据
        $order_info = [
            "order_id"          => 'LN_' . mt_rand(111111,999999),
            "order_amount"      => mt_rand(111,999),
            "uid"               => 123,
            "add_time"          => time(),
        ];

        $data_json = json_encode($order_info);

        //计算签名
        $sign = md5($data_json.$key);

        // post 表单（form-data）发送数据
        $client = new Client();
        $url = 'http://laravel.1905.com/test/post';
        $response = $client->request("POST",$url,[
            "form_params"   => [
                "data"  => $data_json,
                "sign"  => $sign
            ]
        ]);

        //接收服务器端响应的数据
        $response_data = $response->getBody();
        echo $response_data;

    }



}
