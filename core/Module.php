<?php
namespace core;

/**
 * /xtw 2018
 */
class Module
{

    /**
     * 获取模型，控制器等信息
     * 
     * @param string $name            
     * @return boolean|unknown|boolean|array[]|string[]|unknown[]|unknown[][]|string[][]
     */
    public static function getname($name = '')
    {
        $req = new Request();
        $url = $req->url();
        $route = Route::get_route();
        $infos = ($infos = $route ? self::parserouter($url, $route, $req->method()) : self::parseurl($url)) ? null : self::parseurl($url);
        if (is_null($infos))
            return false;
        return empty($name) ? $infos : $infos[$name];
    }
/**
 * 获取模型
 * @return boolean|\core\unknown|array|\core\string[]
 */
    public static function get_module()
    {
        return self::getname('module');
    }

    public static function get_controller()
    {
        return self::getname('controller');
    }

    public static function get_method()
    {
        return self::getname('method');
    }

    public static function get_params()
    {
        return self::getname('params');
    }
/**
 * 根据url，解析返回路由相关信息
 * @param string $url
 * @return string|array
 */
    public static function parseurl($url)
    {
        $parse = parse_url($url);
        $params = [];
        $paths = array_filter(explode("/", $parse['path']));
        if (isset($paths[0])) {
            $module = $paths[0];
            unset($paths[0]);
        } else
            $module = 'index';
        if (isset($paths[1])) {
            $controller = $paths[1];
            unset($paths[1]);
        } else
            $controller = 'index';
        if (isset($paths[2])) {
            $method = $paths[2];
            unset($paths[2]);
        } else
            $method = 'index';
        if (! empty($paths)) {
            $i = 0;
            foreach ($paths as $val) {
                $i ++;
                $i % 2 == 0 ? $params[$k] = $val : $k = $val;
            }
        }
        $getparam = isset($parse['query']) ? explode("&", $parse['query']) : [];
        if (! empty($getparam)) {
            foreach ($getparam as $val)
                $list = explode("=", $val);
            $params[isset($list[0]) ? $list[0] : 0] = isset($list[1]) ? $list[1] : '';
        }
        return [
                    'module' => $module,'controller' => $controller,'method' => $method,'params' => $params
        ];
    }
/**
 * 解析url,路由规则,返回相关信息
 * @param  $url
 * @param  $route
 * @param  $method
 * @return array|boolean
 */
    public static function parserouter($url, $route, $method)
    {
        if (is_array($route)) {
            foreach ($route as $getmethod => $val) {
                if ($getmethod == $method) {
                    foreach ($val as $v) {
                        $parttern = "/" . str_replace("/", "(\/)*", $v[0]) . "/";
                        if (preg_match($parttern, $url, $match)) {

                            unset($match[0]);
                            foreach ($match as $k0 => $v0) {
                                if (empty($v0) || strpos($v0, ".") === 0 || $v0 == '/')
                                    unset($match[$k0]);
                            }
                            $params = array_combine($v[2], $match);
                            if (strpos($v[1], "/") === 0) {
                                $paths = explode("/", substr($v[1], 1, - 1));
                                $module = $paths[0];
                                $controller = isset($paths[1]) ? $paths[1] : 'index';
                                $method = isset($paths[2]) ? $paths[2] : 'index';
                            } else {
                                $paths = array_filter(explode('/', $v[1]));
                                $paths = array_reverse($paths);
                                $method = $paths[0];
                                $controller = isset($paths[1]) ? $paths[1] : 'index';
                                $module = isset($paths[2]) ? $paths[2] : 'index';
                            }
                            return [
                                        'module' => $module,'controller' => $controller,'method' => $method,'params' => $params
                            ];
                            break;
                        }
                    }
                }
            }
        }
        return false;
    }
}