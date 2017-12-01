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
     * 至少要保留512个字符空间
     * 微信正常返回类似：{"access_token":"ACCESS_TOKEN","expires_in":7200}
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
        }
        else
        {
            $access_token = isset($data->access_token) ? $data->access_token : false;
        }
        return $access_token;
    }

    /**
     * 刷新特殊access token
     * @parma [string] $refresh_token 通过access_token获取到的refresh_token参数
     */
    public function refreshAccessToken($refresh_token)
    {
        $url = "https://api.weixin.qq.com/sns/oauth2/refresh_token?appid={$this->_appid}&grant_type=refresh_token&refresh_token={$refresh_token}";
        $res = json_decode(http_get($url));
        return $res;
    }

    /**
     * 检查网页授权特殊access token是否有效
     * 注意:这里的特殊token和openid是一一对应的，其中一个不对都返回false
     * @param  [string] $_token 网页授权特殊token
     * @return [boolean] 有效返回true否则返回false 返回false时通常可以尝试刷新token
     */
    public function checkAuthTokenValid($token='',$openid='')
    {
        $url = "https://api.weixin.qq.com/sns/auth?access_token={$token}&openid={$openid}";
        $res = json_decode(http_get($url));
        $res = ( $res->errcode === 0 ) ? true : false;
        return $res;
    }

    /**
     * 获取code，用于换取其他信息
     * 就是请求微信服务器，微信服务器处理后然后重定向到设置的$redirect_url（在URL后面附加?code=CODE&state=STATE。）
     * 每次用户授权带上的code将不一样，code只能使用一次，5分钟未被使用自动过期
     * @param [string]  $redirect_url 授权后的重定向URL
     * @param [boolean] $scope 作用域 snsapi_base、snsapi_userinfo
     */
    public function getCode($redirect_url, $scope=true)
    {
        $redirect_uri = urlencode($redirect_url);//回调URL
        $state = rand(1,9999);//重定向后会带上state参数，开发者可以填写a-zA-Z0-9的参数值，最多128字节
        $scope = $base ? 'snsapi_base' : 'snsapi_userinfo';//应用授权作用域，snsapi_base （不弹出授权页面直接跳转，只能获取用户openid），snsapi_userinfo （弹出授权页面，可通过openid拿到昵称、性别、所在地。并且，即使在未关注的情况下，只要用户授权，也能获取其信息）
        $url = "https://open.weixin.qq.com/connect/oauth2/authorize?appid={$this->_appid}&redirect_uri={$redirect_uri}&response_type=code&scope={$scope}&state={$state}#wechat_redirect";
        header('Location:'.$url);//请求后微信会重定向到$redirect_uri并在这个URL后面附上code值
    }

    /**
     * 获取网页授权特殊access_token
     * 微信接口返回类似如下Json格式字符
     * { "access_token":"ACCESS_TOKEN",  "expires_in":7200,    "refresh_token":"REFRESH_TOKEN",   "openid":"OPENID",   "scope":"SCOPE" }
     * 返回一个对象
     */
    public function getAuthTokenByCode($code)
    {
        //通过code获取特殊access_token
        $url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid={$this->_appid}&secret={$this->_appsecret}&code={$code}&grant_type=authorization_code";
        $res = json_decode(http_get($url));
        if( isset($res->errcode) )
        {
            //code无效( 过期(42001)、错误(40029)、已使用(40163) )
            //可以做一个重定向到授权页的补救措施
            halt('Error:<br>method: '. __METHOD__ . '<br>errmsg: ' . $res->errmsg);
        }
        return $res;
    }

    /**
     * 获取用户基本信息
     * 不传入特殊access_token则使用基础access_token和接口去获取（需要用户关注公众号），传入则使用网页授权获取用户信息接口获取
     * 注意：传入access_token与不传返回用户信息字段不同
     * @param  [string] 用户openId (每个公众号每个用户唯一，不同公众号相同用户openId不同)
     * @param  [string] 特殊access_token (通过code换取的，不同于基础access_token)
     * @return [string] 包含用户基本信息的Json格式字符串
     */
    public function getUserInfo($openid,$access_token='')
    {
        if(empty($access_token))
        {
            $url = "https://api.weixin.qq.com/cgi-bin/user/info?access_token={$this->_access_token}&openid={$openid}&lang=zh_CN";
        }
        else
        {
            $url = "https://api.weixin.qq.com/sns/userinfo?access_token={$access_token}&openid={$openid}&lang=zh_CN";
        }
        $res = http_get($url);
        return $res;
    }


    /**
     * 消息响应
     */
    public function responseMsg()
    {
        $postStr = isset($GLOBALS["HTTP_RAW_POST_DATA"]) ? $GLOBALS["HTTP_RAW_POST_DATA"] :'';
        //TODO 验证消息是否确实来自微信服务器
        if(!empty($postStr))
        {
            $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
            $rx_type = trim($postObj->MsgType);
            //用户发送的消息类型判断
            switch ($rx_type)
            {
                case "text"://文本消息
                    $result = $this->receiveText($postObj);
                    break;
                case "image"://图片消息
                    $result = $this->receiveImage($postObj);
                    break;
                case "voice"://语音消息
                    $result = $this->receiveVoice($postObj);
                    break;
                case "video"://视频消息
                    $result = $this->receiveVideo($postObj);
                    break;
                case "location"://位置消息
                    $result = $this->receiveLocation($postObj);
                    break;
                case "link"://链接消息
                    $result = $this->receiveLink($postObj);
                    break;
                default:
                    $result = "unknow msg type: ".$rx_type;
                    break;
            }
            echo $result;
        }
    }

    /*
     * 格式化返回给用户的消息内容
     */
    private function transmitText($object, $content)
    {
        $textTpl = "
        <xml>
            <ToUserName><![CDATA[%s]]></ToUserName>
            <FromUserName><![CDATA[%s]]></FromUserName>
            <CreateTime>%s</CreateTime>
            <MsgType><![CDATA[text]]></MsgType>
            <Content><![CDATA[%s]]></Content>
        </xml>";
        $result = sprintf($textTpl, $object->FromUserName, $object->ToUserName, time(), $content);
        return $result;
    }

    /*
     * 接收文本消息
     */
    public function receiveText($object)
    {
        $content = "你发送的是文本，内容为：".$object->Content;
        $result = $this->transmitText($object, $content);
        return $result;
    }

    /*
     * 接收图片消息
     */
    private function receiveImage($object)
    {
        $content = "你发送的是图片，地址为：".$object->PicUrl;
        $result = $this->transmitText($object, $content);
        return $result;
    }

    /*
     * 接收视频消息
     */
    private function receiveVideo($object)
    {
        $content = "你发送的是视频，媒体ID为：".$object->MediaId;
        $result = $this->transmitText($object, $content);
        return $result;
    }

}