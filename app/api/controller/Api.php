<?php
namespace app\api\controller;

use core\Controller;
use core\Model;
use core\Session;
use core\Module;

class Api extends Controller
{

    protected $db_config;

    protected $model_path = 'admin';

    protected $module_path = 'api';

    function __construct()
    {
        $this->db_config = include (APP_PATH . DS . $this->model_path . DS . 'config.php');
        $this->module_path = Module::get_module();
    }
 /**
  * 获取数据
  * @param string $url
  * @param string $poststr
  * @param string $cookie
  * @return mixed
  */
    protected function Curl($url, $poststr = '', $cookie = '')
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
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $poststr);
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
            $model = "\\app\\".$this->model_path."\\model\\" . $name;
            return $this->model[$key] = new $model($this->db_config);
        }
    }
  /**
   * 获取配置
   * @return \PDOStatement|boolean|\core\Collection|string
   */
    protected function getConfig()
    {
        return $this->getModel('config')->get(1, 'id');
    }
    /**
     * 插入消息
     */
    protected function insertMsg($userid='',$title='',$detail='',$url='',$type=0,$role=0)
    {     
        return $this->getModel('message')
        ->insert(['title'=>$title,'detail'=>$detail,'url'=>$url,'type'=>$type,'role'=>$role,'add_date'=>time(),'userid'=>$userid]);
    }
    /**
     * 获取id
     * @param string $openid
     * @return string
     */
    protected function getUserId($openid)
    {
        $r=$this->getModel('User')->where(['wx_id'=>['=',$openid]])->fetchAll('user_id',1);
        !empty($r)&&$user_id = $r[0]->user_id;
        return $user_id?:'';
    }
    /**
     * 获取上传路径
     * @param string $path
     * @return string|unknown
     */
    protected function getUploadImg($path = false)
    {
        if ($path) {
            $config = $this->getConfig();
            return $config[0]->uploadImg_domain . $config[0]->uploadImg;
        }
        return Session::get('uploadImg') ?: $this->getConfig()[0]->uploadImg;
    }
  /**
   * 上传图片
   * @param string $file
   * @param int $size
   * @param string $type
   * @param string $path
   * @return string|boolean
   */
    protected function uploadImg($file, $size = 1024*512, $type = "jpg,jpeg,pgn,gif", $path = '')
    {
        empty($path) && $path = $this->getUploadImg();
        $msg = '';
        $error = $file['error'];
        switch ($error) {
            case 0:
                $msg = '';
                break;
            case 1:
                $msg = '超出了php.ini中文件大小';
                break;
            case 2:
                $msg = '超出了MAX_FILE_SIZE的文件大小';
                break;
            case 3:
                $msg = '文件被部分上传';
                break;
            case 4:
                $msg = '没有文件上传';
                break;
            case 5:
                $msg = '文件大小为0';
                break;
            default:
                $msg = '上传失败';
                break;
        }
        if (empty($msg)) {
            if ($this->validate([
                                    $file
            ], "requires|fileType:".$type."|fileSize:".$size, $msg)) {
                list ($name, $filetype) = explode('.', $file['name']);
                if(strpos($type,$filetype)===false)
                    $filetype='jpg';
                $imgURL_name = time() . mt_rand(1, 1000) . '.' . $filetype;
                move_uploaded_file($file['tmp_name'], check_file(PUBLIC_PATH . $path .DS. $imgURL_name));
                return $imgURL_name;
            } else
               return false;
        }
        return false;
    }
}