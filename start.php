<?php
    session_start();
    run();
    /**
     * 入口方法
     * 简单mvc架构框架
     */
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