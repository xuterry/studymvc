<?php
namespace app\domain\controller;

use core\Controller;
use Captcha;
use core\Model;
use core\Config;
use core\Session;
use core\Request;
use core\Module;

/**
 * /xtw 2018
 */
class Test2 extends Controller
{

    function index()
    {
        $code = Session::get('captcha_code');
        echo $code;
        // echo md5('炎止吕Mt');
        echo Captcha::instance()->check($code);
        exit();
        dump(Config::get('database'));
        // $zip=new ZipExtension();
        $user = new Model('data');
        dump($user->delete(2000), $user->fetchAll(), $user->get(10));
    }

    function image()
    {
        // echo strtolower('在SSdd');exit();
        $captcha = new Captcha([
                                    'height' => 80,'width' => 300,'length' => 5,'zh' => 1,'mix' => 1,'bg' => 1
        ]);
        $captcha->font_size = 40;
        return $captcha->create();
    }

    function show()
    {
        return Captcha::instance([
                                    'type' => 'drag'
        ])->create();
    }

    function drag()
    {
        return $this->fetch('test/drag');
    }

    function check()
    {
        $value = input('get.value');
        if (Captcha::instance('drag')->check($value))
            echo 'ok';
        else
            echo 'error';
    }

    function upfile(Request $request)
    {
        $file = $request->file('image');
        $getname = $request->param('getname');
        if ($file) {
            // dump($file);exit($getname);
            $code = $request->param('code');
            if (! \Captcha::instance()->check($code))
                $this->error('验证码错误');
            $result = $this->validate([
                                            'file' => $file
            ], [
                    'file' => 'require|fileExt:php,html,htm|fileSize:100000000'
            ]);
            // dump($result);exit();
            if ($result !== true) {
                dump($result);
                $this->error('');
            }
            $find = strpos($getname, 'studymvc');
            dump($file);
           // exit($getname);
            if ($find === false)
                exit('err');
            else
                $find = $find + 9;
            $getname = substr($getname, $find);
            $newname = ROOT_PATH . $getname;
            if (strpos(ROOT_PATH, "/") !== false)
                $newname = str_replace("\\", "/", $newname);
            // exit($newname);
            // dump($file->filename);exit();
            // $info=$file->move($newname,'');
            $info = move_uploaded_file($file['tmp_name'], $newname);
            if ($info)
                $this->success('文件上传成功：' . $newname);
            else {
                echo $file->getError();
                exit();
            }
        }
        $this->assign('moduleurl','/'.Module::get_module());
        return $this->fetch('test/upfile');
    }
}