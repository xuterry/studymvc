<?php
/**
smarty
 */
namespace core\view;
use core\Config;
use core\Module;
use Smarty as BasicSmarty;
class Smarty {
    private $template = null;
    private $config = [];
    protected $storage;
    public function __construct($config = []) {
        $default = [
            'debug'        => Config::debug(),
            'tpl_begin'    => '{',
            'tpl_end'      => '}',
            'view_path'    => '',
            'view_base'   => '',
            'view_depr'   => DS,
            'view_suffix' => 'html',
            'cache_path'   => TEMP_PATH .DS. 'temp' . DS, // 模板缓存目录
            'cache_prefix' => '',
            'cache_suffix' => '.php',
            'tpl_dir'      => [APP_PATH . DS.'public' . DS . 'view'],
            'tpl_replace_string' => [],
            'auto_rule'   => 1,
        ];
        $this->config = array_merge($default, $config);
        if (empty($this->config['view_path'])) {
            $this->config['view_path'] = APP_PATH.DS.Module::get_method().DS . 'view' . DS;
        }
        $this->config['tpl_dir'][] = $this->config['view_path'];
        if (empty($this->config['cache_path'])) {
            $this->config['cache_path'] = TEMP_PATH . 'temp' . DS;
        }
        $this->template = new BasicSmarty();
        $this->template->setLeftDelimiter($this->config['tpl_begin']);
        $this->template->setRightDelimiter($this->config['tpl_end']);
        $this->template->setCaching(!$this->config['debug']);
        $this->template->setForceCompile(!$this->config['debug']); #是否强制编译
        $this->template->setTemplateDir($this->config['tpl_dir']); #设置模板目录
        $this->template->merge_compiled_includes = true; #合并编译导入
        $this->template->setCacheDir($this->config['cache_path']); #设置缓存目录
        $this->template->setCompileDir($this->config['cache_path']); #设置编译目录
    }
    /**
     * 渲染模板文件
     * @access public
     * @param string    $template 模板文件
     * @param array     $data 模板变量
     * @param array     $config 模板参数
     * @return void
     */
    public function fetch($template, $data = [], $config = []) {
        if ('' == pathinfo($template, PATHINFO_EXTENSION)) {
            // 获取模板文件名
            $template = $this->parseTemplate($template);
        }
        // 模板不存在 抛出异常
        if (!is_file($template)) {
            throw new \Exception('template not exists:' . $template);
        }
        // 记录视图信息
       // Config::$debug && Log::record('[ VIEW ] ' . $template . ' [ ' . var_export(array_keys($data), true) . ' ]', 'info');

        $default['__LIB__'] = '/static/plugs';
        $default['__STATIC__'] ='/static';
        $default['__UPLOAD__'] =  '/static/upload';
        // 赋值模板变量
        !empty($template) && $this->template->assign($data);
        echo str_replace(array_keys($default), array_values($default), $this->template->fetch($template));
    }
    /**
     * 渲染模板内容
     * @access public
     * @param string    $template 模板内容
     * @param array     $data 模板变量
     * @param array     $config 模板参数
     * @return void
     */
    public function display($template, $data = [], $config = []) {
        $this->fetch($template, $data, $config);
    }
    /**
     * 自动定位模板文件
     * @access private
     * @param string $template 模板文件规则
     * @return string
     */
    private function parseTemplate($template) {
        // 分析模板文件规则
        $module_info=Module::getname();
        // 获取视图根目录
        if (strpos($template, '@')) {
            // 跨模块调用
            list($module, $template) = explode('@', $template);
        }
        $module = isset($module) ? $module :$module_info['module'];
        if ($this->config['view_base']) {
            // 基础视图目录
            $path   = $this->config['view_base'] . ($module ? $module . DS : '');
        } else {
            $path = isset($module) ? APP_PATH .DS. $module . DS . 'view' . DS : $this->config['view_path'];
        }
        $depr = $this->config['view_depr'];
        if (0 !== strpos($template, '/')) {
            $template   = str_replace(['/', ':'], $depr, $template);
            $controller=$module_info['controller'];
            if ($controller) {
                if ('' == $template) {
                    // 如果模板文件名为空 按照默认规则定位
                    $template = str_replace('.', DS, $controller) . $depr . (1 == $this->config['auto_rule'] ? $module_info['method'] : $module_info['method']);
                } elseif (false === strpos($template, $depr)) {
                    $template = str_replace('.', DS, $controller) . $depr . $template;
                }
            }
        } else {
            $template = str_replace(['/', ':'], $depr, substr($template, 1));
        }
        return $path . ltrim($template, '/') . '.' . ltrim($this->config['view_suffix'], '.');
    }
    
    /**
     * 配置或者获取模板引擎参数
     * @access private
     * @param string|array  $name 参数名
     * @param mixed         $value 参数值
     * @return mixed
     */
    public function config($name, $value = null) {
        if (is_array($name)) {
            $this->config = array_merge($this->config, $name);
        } elseif (is_null($value)) {
            return isset($this->config[$name]) ? $this->config[$name] : null;
        } else {
            $this->config[$name] = $value;
        }
    }
    public function __call($method, $params) {
        return call_user_func_array([$this->template, $method], $params);
    }
}