<?php
/**
 * 公共函数
 */

 /**
  * curl http get 请求
  */
 function http_get($url)
 {
    $oCurl = curl_init();
    if(stripos($url,"https://")!==FALSE)
    {
        curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($oCurl, CURLOPT_SSLVERSION, 1);
    }
    curl_setopt($oCurl, CURLOPT_URL, $url);
    curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1 );
    curl_setopt($oCurl, CURLOPT_VERBOSE, 1);
    $sContent = curl_exec($oCurl);
    curl_close($oCurl);

    return $sContent;
 }


/**
 * POST 请求
 * @param string $url
 * @param array $param
 * @param boolean $post_file 是否文件上传
 * @return string content
 */
function http_post($url,$param,$post_file=false)
{
    $oCurl = curl_init();

    if(stripos($url,"https://")!==FALSE)
    {
        curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($oCurl, CURLOPT_SSLVERSION, 1); //CURL_SSLVERSION_TLSv1
    }
    if(PHP_VERSION_ID >= 50500 && class_exists('\CURLFile'))
    {
        $is_curlFile = true;
    }else
    {
        $is_curlFile = false;
        if (defined('CURLOPT_SAFE_UPLOAD'))
        {
            curl_setopt($oCurl, CURLOPT_SAFE_UPLOAD, false);
        }
    }

    if($post_file)
    {
        if($is_curlFile)
        {
            foreach ($param as $key => $val)
            {
                if(isset($val["tmp_name"]))
                {
                    $param[$key] = new \CURLFile(realpath($val["tmp_name"]),$val["type"],$val["name"]);
                }else if(substr($val, 0, 1) == '@')
                {
                    $param[$key] = new \CURLFile(realpath(substr($val,1)));
                }
            }
        }
        $strPOST = $param;
    }else
    {
        $strPOST = json_encode($param);
    }

    curl_setopt($oCurl, CURLOPT_URL, $url);
    curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1 );
    curl_setopt($oCurl, CURLOPT_POST,true);
    curl_setopt($oCurl, CURLOPT_POSTFIELDS,$strPOST);
    curl_setopt($oCurl, CURLOPT_VERBOSE, 1);

    $sContent = curl_exec($oCurl);

    curl_close($oCurl);

    return $sContent;
}

/**
 * 浏览器友好的变量输出
 * @param mixed $var 变量
 * @param boolean $echo 是否输出 默认为True 如果为false 则返回输出字符串
 * @param string $label 标签 默认为空
 * @param boolean $strict 是否严谨 默认为true
 * @return void|string
 */
function dump($var, $echo=true, $label=null, $strict=true)
{
    $label = ($label === null) ? '' : rtrim($label) . ' ';
    if (!$strict)
    {
        if (ini_get('html_errors'))
        {
            $output = print_r($var, true);
            $output = '<pre>' . $label . htmlspecialchars($output, ENT_QUOTES) . '</pre>';
        }else
        {
            $output = $label . print_r($var, true);
        }
    } else
    {
        ob_start();
        var_dump($var);
        $output = ob_get_clean();
        if (!extension_loaded('xdebug'))
        {
            $output = preg_replace('/\]\=\>\n(\s+)/m', '] => ', $output);
            $output = '<pre>' . $label . htmlspecialchars($output, ENT_QUOTES) . '</pre>';
        }
    }
    if ($echo)
    {
        echo($output);
        return null;
    }else
    {
         return $output;
    }
}

/**
 * 简单php缓存函数
 * @param  [string] $key 缓存键名
 * @param  [string|array] $value 缓存值
 * @param  [int] $express 缓存有效时间（单位秒）
 * @return
 */
function cache($key='',$value='',$expire=0)
{
    $path = './cache/';//缓存目录
    $filename = $path.md5($key).'.php';//缓存文件路径
    switch(func_num_args())
    {
        case 1:
            //获取指定键名的缓存
            if(file_exists($filename))
            {
                return trim(substr(file_get_contents($filename), 15));
            }else
            {
                return false;
            }
            break;
        case 2:
            //设置缓存（设置成功返回true否则返回false）
            switch(gettype($value))
            {
                case 'string':
                case 'integer':
                case 'double':
                    $content = $value;
                    break;
                case 'array':
                    $content = json_encode($value);
                    break;
                default:
                    return false;
            }
            $fp = fopen($filename,'w');
            $res = fwrite($fp, "<?php exit();?>" . $content);//fwrite()返回写入的字符数，出现错误时则返回false
            fclose($fp);
            $res = ($res !== false) ? true : false;
            return $res;
            break;
        default:
            exit('cache param error');
    }
}

/**
 * 获取客户端Ip
 */
function getClientIp()
{
    if (getenv('HTTP_CLIENT_IP'))
    {
        $ip = getenv('HTTP_CLIENT_IP');
    }
    elseif (getenv('HTTP_X_FORWARDED_FOR'))
    {
        $ip = getenv('HTTP_X_FORWARDED_FOR');
    }
    elseif (getenv('HTTP_X_FORWARDED'))
    {
        $ip = getenv('HTTP_X_FORWARDED');
    }
    elseif (getenv('HTTP_FORWARDED_FOR'))
    {
        $ip = getenv('HTTP_FORWARDED_FOR');
    }
    elseif (getenv('HTTP_FORWARDED'))
    {
        $ip = getenv('HTTP_FORWARDED');
    }
    else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    return $ip;
}

/**
 * 将数组转换成对象
 * @param [array] $arr 需要转换为对象的数组
 * @return 传入数组则返回转换后的对象，否则返回布尔值false
 */
function arrayToObject($arr)
{
    if(is_array($arr))
    {
        $obj = json_encode($arr);
        $obj = json_decode($obj);
    }else {
        $obj = false;
    }
    return $obj;
}

/**
 * 让火狐友好输出json数据
 * 不能有其他额外字符等输出，否则显示会异常
 */
function echoJson($json='')
{
    if(is_string($json))
    {
        if(!is_null(json_decode($json)))
        {
            header('Content-type: application/json');
            echo $json;
        }else {
            echo $json;
            //echo 'Not valid json formatted data';
        }
    }
    else
    {
        echo 'Error: Parameter not a string';
    }
}

/**
 * 生成URL链接 【待优化】
 * @param [string] $url 调用的控制器和方法组成的字符（例如：需要访问home控制器的index则为home/index）
 * @param [array]  $param URL额外的参数（键值对形式的数组格式）
 * @return 直接输出生成后的URL字符转
 */
function url($url,$param='')
{
    $host = $_SERVER['HTTP_HOST'];
    $port = $_SERVER['SERVER_PORT'];
    $url  = explode('/',$url);

    if( count($url) >= 2 )
    {
        $url = 'c='.$url[0].'&a='.$url[1];
    }
    else
    {
        $url = 'c=&a=';
    }

    if( $port == '80' )
    {
        $url = 'http://'.$host.'/?'.$url;
    }
    else
    {
        $url = 'http://'.$host.':'.$port.'/?'.$url;
    }
    return $url;
}