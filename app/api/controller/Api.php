<?php
namespace app\api\controller;
use core\Controller;
use core\Model;
use core\Session;
use core\Module;
class Api extends Controller
{
    protected $db_config;
    protected $module='admin';
    protected $module_path='api';
 function __construct()
 {
     $this->db_config = include (APP_PATH . DS . $this->module . DS . 'config.php');
     $this->module_path=Module::get_method();
     
 }
 protected function Curl($url,$poststr='',$cookie='')
 {
     $curl = curl_init();
     curl_setopt($curl, CURLOPT_URL, $url);
     if (! empty($cookie))
         curl_setopt($curl, CURLOPT_HTTPHEADER, [
             "cookie:" . $cookie
         ]);
         curl_setopt($curl, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/67.0.3396.87 Safari/537.36");
         curl_setopt($curl, CURLOPT_HEADER, 0);
         curl_setopt($curl, CURLOPT_REFERER, 'https://www.baidu.com');
         if (strpos($url, 'https:') !== false) {
             curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
             curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
         }
         
         if ($poststr != "") {
             if (isset($poststr['file']))
                 curl_setopt($curl, CURLOPT_SAFE_UPLOAD, true);
                 curl_setopt($url, CURLOPT_POST, 1);
                 curl_setopt($url, CURLOPT_POSTFIELDS, $poststr);
         }
         curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
         curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
         $str = curl_exec($curl);
         curl_close($curl);
         return $str;
 }
 /**
  * 获取model
  *
  * @param string $name
  * @return Model
  */
 protected function getModel($name)
 {
     $name = ucfirst(trim($name));
     $key = md5($name);
     if (isset($this->model[$key]))
         return $this->model[$key];
         else {
             $model = "\\app\\admin\\model\\" . $name;
             return $this->model[$key] = new $model($this->db_config);
         }
 }
 protected function getConfig()
 {
     return $this->getModel('config')->get(1, 'id');
 }
 protected function getUploadImg($path=false)
 {
     if($path){
         $config=$this->getConfig();
         return $config[0]->uploadImg_domain.$config[0]->uploadImg;
     }
     return Session::get('uploadImg')?:$this->getConfig()[0]->uploadImg;
 }
}