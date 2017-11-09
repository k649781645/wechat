<?php

require_once './function.php';
require_once 'mp.php';

//测试公众号二维码:http://mmbiz.qpic.cn/mmbiz_jpg/kQtmu10YXR4l4IdiaRfMCP5yhfibTt7K7HHWLWYo6onoOsumLmXpesib1B1Idy7EdPbxTEAxkRiagKtx3wpaBf47dQ/0

//微信配置信息
define('AppId','wx5a1e923e4afffb38');
define('AppSecret','d4624c36b6795d1d99dcf0547af5443d');
define('EncodingAESKey','PLLTc1f2N50dALWOeQm7UBJzgEl8hMdrLu4ih5IzGON');
define('Token','wx_token');


//微型PHP开发框架

$c = isset($_GET['c']) ? $_GET['c'] : 'home';
$a = isset($_GET['a']) ? $_GET['a'] : 'index';

if( class_exists($c) && method_exists($c, $a) )
{
    $o = new $c(); $o->$a();
}
else
{
    exit('error');
}


$wechat = new wechat();
//$wechat->checkSignature();//微信公众号安全验证

//$token = $wechat->getAccessToken();

$wechat->getCode();//发起oauth2授权请求

//$a = $wechat->getUserInfoByAuth(cache('openId'));
//echoJson($a);
//echo $url;


/**
 * 授权后的处理逻辑
 * 保存opneId、特殊access_token等信息
 */
function auth()
{
    $code = isset($_GET['code']) ? $_GET['code'] : false;
    if( $code === false )
    {
        exit('{"errmsg":"miss code"}');
        //$this->getCode();
    }
    else
    {
        //请求用户信息等。。。
        $res = json_decode($this->getAuthTokenByCode($code));
        //$openid = isset($res->openid) ? $res->openid : '';//没设置openid应该抛出错误（这里只是避免了程序出错）

        //cache('auth_token',$res->access_token);
        //$res = $this->getUserInfoByAuth($openid);
        dump($res);
        //cache('openId',$openid);
        //echo $access_token;
    }
}