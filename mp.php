<?php
/**
 * 微信公众平台开发类
 */
class wechat
{
    private $_appid;
    private $_appsecret;
    private $_EncodingAESKey;
    private $_token;
    private $_access_token;//基础接口调用凭据

    /**
     * 通过构造函数赋值配置信息
     */
    public function __construct(){
        $this->_appid          = AppId;
        $this->_appsecret      = AppSecret;
        $this->_EncodingAESKey = EncodingAESKey;
        $this->_token          = Token;
        $this->_access_token   = $this->getAccessToken();
    }

    /**
     * 验证签名,即验证消息的确来自微信服务器（接入微信平台时验证）
     * 实现步骤:
     * 1）将token、timestamp、nonce三个参数进行字典序排序
     * 2）将三个参数字符串拼接成一个字符串进行sha1加密
     * 3）开发者获得加密后的字符串与signature对比，标识该请求来源于微信
     * 4）若确认此次GET请求来自微信服务器，请原样返回echostr参数内容，则接入生效，成为开发者成功，否则接入失败
     */
    public function checkSignature()
    {
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];
        $tmpArr = array($this->_token, $timestamp, $nonce);
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode( $tmpArr );
        $tmpStr = sha1( $tmpStr );
        if( $tmpStr === $signature )
        {
            exit($_GET["echostr"]);//验证通过后原样返回echostr给微信服务器
        }
    }

    /**
     * 获取基础access_token（接口调用凭证）
     * 已经实现了缓存access_token（没有有效的缓存才向微信服务器请求）
     */
    public function getAccessToken()
    {
        $cache = cache($this->_appsecret);
        $data = json_decode($cache);
        if( isset($data->expire_time) ? ($data->expire_time < time()) : true )
        {
            $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$this->_appid}&secret={$this->_appsecret}";
            $res = json_decode(http_get($url));
            $access_token = $res->access_token;
            $data = array(
                'access_token' => $res->access_token,
                'expire_time'  => time() + 7000
            );
            cache($this->_appsecret,$data);
        }else
        {
            $access_token = isset($data->access_token) ? $data->access_token : false;
        }
        return $access_token;
    }

    /**
     * 获取code，用于换取其他信息
     * 就是请求微信服务器，微信服务器处理后然后重定向到设置的$redirect_url（在URL后面附加?code=CODE&state=STATE。）
     * 每次用户授权带上的code将不一样，code只能使用一次，5分钟未被使用自动过期
     * @param [string] $redirect_url 授权后的重定向URL
     */
    public function getCode($redirect_url='')
    {
        $redirect_uri = urlencode('http://weixin.chenghuajie.cn');//回调URL
        $state = rand(1,9999);//重定向后会带上state参数，开发者可以填写a-zA-Z0-9的参数值，最多128字节
        $scope = 'snsapi_userinfo';//应用授权作用域，snsapi_base （不弹出授权页面直接跳转，只能获取用户openid），snsapi_userinfo （弹出授权页面，可通过openid拿到昵称、性别、所在地。并且，即使在未关注的情况下，只要用户授权，也能获取其信息）
        $url = "https://open.weixin.qq.com/connect/oauth2/authorize?appid={$this->_appid}&redirect_uri={$redirect_uri}&response_type=code&scope={$scope}&state={$state}#wechat_redirect";
        header('Location:'.$url);//请求后微信会重定向到$redirect_uri并在这个URL后面附上code值
    }

    /**
     * 获取网页授权特殊access_token
     * 返回 { "access_token":"ACCESS_TOKEN",  "expires_in":7200,    "refresh_token":"REFRESH_TOKEN",   "openid":"OPENID",   "scope":"SCOPE" } 这样的Json格式字符串
     */
    public function getAuthTokenByCode($code)
    {
        //通过code获取特殊access_token
        $url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid={$this->_appid}&secret={$this->_appsecret}&code={$code}&grant_type=authorization_code";
        $res = json_decode(http_get($url));
        if( isset($res->errcode) ? ( ($res->errcode == 42003 | $res->errcode == 40029 | $res->errcode == 40163) ? true : false ) : false )
        {
            //code无效( 过期(42003)、错误(40029)、已使用(40163) )
            //header('Location:http://weixin.chenghuajie.cn');//可以做一个重定向到授权页的补救措施
            exit('{"errcode":40029,"errmsg":"invalid code"}');
        }
        return json_encode($res);
    }

    /**
     * 获取用户基本信息（需要关注）【说明：测试号不适用,会要求关注】
     */
    public function getUserInfoByToken($openid)
    {
        $url = "https://api.weixin.qq.com/cgi-bin/user/info?access_token={$this->_access_token}&openid={$openid}&lang=zh_CN";
        $res = http_get($url);
        return $res;
    }

    /**
     * 获取用户基本信息（无需关注[测试号不适用]）
     * @param  [string] 用户openId (每个公众号每个用户唯一，不同公众号相同用户openId不同)
     * @param  [string] 特殊access_token (通过code换取的，不同于基础access_token)
     * @return [string] 包含用户基本信息的Json格式字符串
     */
    public function getUserInfoByAuth($openid,$auth_token='')
    {
        $auth_token = cache('auth_token');
        $url = "https://api.weixin.qq.com/sns/userinfo?access_token={$auth_token}&openid={$openid}&lang=zh_CN";
        $res = http_get($url);
        return $res;
    }

}