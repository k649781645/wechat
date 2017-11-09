<?php

$db_config = array(
    'hostname' => '127.0.0.1',
    'dbname'   => 'wx',
    'username' => 'root',
    'password' => ''
);



run();

function run($param='')
{
    $class  = isset($_GET['c']) ? $_GET['c']: 'home';
    $method = isset($_GET['a']) ? $_GET['a']: 'index';
    $param  = isset($params) ? (is_array($params)  ? $params : array()) : array();
    //检查类是否存在
    if( class_exists( $class ) )
    {
        $class = new $class();
    }
    else
    {
        exit( 'Error: <b style="color:red;">' . $class . ' </b>class not exist.');
    }
    //检查方法是否存在
    if( method_exists($class, $method) )
    {
        call_user_func_array(array($class, $method), $param);
    }
    else
    {
        exit( 'Error: <b style="color:red;">' . $method . ' </b>method not exist in <b style="color:red;">' . $class .' </b>class.' );
    }
}

class home
{
    public function index()
    {
        global $db_config;
        var_dump($db_config);
    }
}