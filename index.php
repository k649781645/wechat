<?php

//测试公众号二维码:http://mmbiz.qpic.cn/mmbiz_jpg/kQtmu10YXR4l4IdiaRfMCP5yhfibTt7K7HHWLWYo6onoOsumLmXpesib1B1Idy7EdPbxTEAxkRiagKtx3wpaBf47dQ/0

//微信配置信息
//URL http://weixin.chenghuajie.cn/index.php?c=home&a=wechat
//测试号
define('AppId','wx5a1e923e4afffb38');
define('AppSecret','d4624c36b6795d1d99dcf0547af5443d');
define('EncodingAESKey','PLLTc1f2N50dALWOeQm7UBJzgEl8hMdrLu4ih5IzGON');
define('Token','wx_token');

//星天科技
// define('AppId','wx3285bb6628046033');
// define('AppSecret','69ff903d1ae7398dfb13bece0d970e67');
// define('EncodingAESKey','PLLTc1f2N50dALWOeQm7UBJzgEl8hMdrLu4ih5IzGON');
// define('Token','wx_tokensss');

//数据库配置信息
$db_config = array(
    'hostname' => '127.0.0.1',
    'dbname'   => 'wx',
    'username' => 'root',
    'password' => 'root'
);

require_once 'function.php';
require_once 'weixin.php';
require_once 'mysql.php';
require_once 'start.php';

/**
 * 应用逻辑控制器
 */
class home
{

    public function index()
    {
        if(isset($_SESSION['username']))
        {
            echo 'welcome' . $_SESSION['username'];
        }
    }

    /**
     * 微信公共号入口（微信服务器都请求这个方法）
     */
    public function wechat()
    {
        $wechat = new wechat();
        if(!empty($_GET['echostr']))//接口有效性验证
        {
            $wechat->checkSignature();
        }
        else//消息接收
        {
            $wechat->responseMsg();
        }
    }

    public function test()
    {
        header("Content-type:text/html; charset=utf-8");
        $wechat = new wechat();
        $res = $wechat->getUserInfo('oYbwZv-YY-1XzwDi7vbu-RRCTpkks','mD2aGQm4nsK-3WXLSjF-oONy8n8hNIeDby8AQ-GRl0fk8yBy8xz6UvJLoI9a3Jo0RAmxrDF2uUOSG8l0GNKUPL2xIEFQecZoDWuyPQ6yojI');
        dump($res);
    }

    /**
     * 授权后的处理逻辑
     */
    function auth()
    {
        $code = isset($_GET['code']) ? $_GET['code'] : false;
        $wechat = new wechat();
        if( $code === false )
        {
            $wechat->getCode(url('home/auth'),false);
        }
        else
        {
            //通过code获取特殊access token(包含openid等信息)
            $res = $wechat->getAuthTokenByCode($code);
            //根据openId获取用户信息
            $openid        = isset($res->openid) ? $res->openid              : '';
            $access_token  = isset($res->access_token) ? $res->access_token  : '';
            $refresh_token = isset($res->refresh_token) ? $res->refresh_token: '';
            //获取更多用户信息
            $res           = json_decode($wechat->getUserInfo($openid,$access_token));
            $data = array(
                'openId'        => $openid,
                'access_token'  => $access_token,
                'refresh_token' => $refresh_token,
                'expires_time'  => time() + 7000,
                'unionid'       => isset($res->unionid) ? $res->unionid      : '',
                'nickname'      => isset($res->nickname) ? $res->nickname    : '',
                //sex值为1时是男性，值为2时是女性，值为0时是未知
                'sex'           => isset($res->sex) ? $res->sex              : 0,
                'headimgurl'    => isset($res->headimgurl) ? $res->headimgurl: '',
                'extend'        => json_encode(
                    array(
                        'country'  => ( isset($res->country) ? $res->country  : '' ),
                        'province' => ( isset($res->province) ? $res->province: '' ),
                        'city'     => ( isset($res->city) ? $res->city        : '')
                    ),JSON_UNESCAPED_UNICODE)
            );
            session_start();
            $_SESSION['username'] = $data['nickname'];
            global $db_config;
            $db = new mysql($db_config);
            //保存用户信息到数据库
            $db->table('user')->insert($data);
            header('Location: ' . url('home/index'));
        }
    }

}