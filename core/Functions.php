<?php
use core\Request;
use core\Response;
use core\Module;
use core\Config;

function input($key = '', $default = null, $filter = '')
{
    if (0 === strpos($key, '?')) {
        $key = substr($key, 1);
        $has = true;
    }
    if (strpos($key, '.')>0) {
        // 指定参数来源
        list($method, $key) = explode('.', $key, 2);
        if (!in_array($method, ['get', 'post', 'put', 'patch', 'delete', 'route', 'param', 'request', 'session', 'cookie', 'server', 'env', 'path', 'file'])) {
            $key    = $method . '.' . $key;
            $method = 'param';
        }
    } else {
        // 默认为自动判断
        $method = 'param';
    }
    if (isset($has)) {
        return request()->has($key,$method);
    } else {
        return request()->$method($key);
    }
}
if (!function_exists('dump')) {
/**
 * 输出多个变量
 * @param mix $var
 */
    function dump($var)
    {
        $args=func_get_args();
        ob_start();
        foreach($args as $var)
        var_dump($var);
        $output = preg_replace('/\]\=\>\n(\s+)/m', '] => ', ob_get_clean());
        if(!extension_loaded('xdebug'))
        $output= htmlspecialchars($output,ENT_SUBSTITUTE);
        echo    '<pre>' .$output.'</pre>';       
        return true;       
    }
}

if (!function_exists('request')) {
    /**
     * 获取当前Request对象实例
     * @return Request
     */
    function request()
    {
        return Request::init();
    }
}
if (!function_exists('json')) {
    /**
     * 获取\core\response\Json对象实例
     * @param mixed   $data 返回的数据
     * @param integer $code 状态码
     * @param array   $header 头部
     * @param array   $options 参数
     * @return \core\response\Json
     */
    function json($data = [], $code = 200, $header = [], $options = [])
    {
        $respnse=new Response($data, 'json', $code, $header, $options);
        $respnse->send();
    }
}

if(!function_exists('url')){
    function url($url=''){
        $infos=Module::getname();
        return '/'.$infos['module'].'/'.$infos['controller'].'/'.$url.Config::get('suffix');
    }
}
