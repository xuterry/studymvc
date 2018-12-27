<?php
namespace app\admin\controller;

use core\Controller;
use core\Session;
use core\Cookie;
use core\Request;
use app\admin\model\Menu;
use core\Module;
use core\Db;
use core\Model;

class Index extends Controller
{

    protected $module;

    protected $module_url;

    protected $db_config;

    protected $version = '1.0';

    protected $model = [];

    function __construct()
    {
        $this->module = Module::get_module();
        $this->module_url = '/' . $this->module;
        $this->db_config = include (APP_PATH . DS . $this->module . DS . 'config.php');
        ! $this->checklogin() && $this->redirect($this->module_url . '/login');
        // 采用smarty模板
        $this->config['type'] = 'smarty';
        // 获取模块名称
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
    protected function trimContent($content,$domain='')
    {
        empty($domain)&&$domain=$this->getConfig()[0]->uploadImg_domain;
        $content=str_replace(["\\"],[""],$content);
        return preg_replace_callback("/<img(.+)src=('|\")(.+)('|\")(.*)>/isU",function($m) use($domain){
            if(strpos($m[3],"http")!==false)
                return $m[0];
                return "<img".$m[1]."src=".$m[2].$domain.$m[3].$m[4].$m[5].">";
        },$content);
    }
    protected function checklogin()
    {
        if (Session::has('admin_id'))
            return true;
        else {
            $get_admin = Cookie::get('admin_id');
            $get_pw = Cookie::get('admin_pw');
            $conn = Db::connect($this->db_config);
            $rs = $conn->name('admin')
                ->where([
                            'name' => [
                                        '=',$get_admin
                            ],'password' => [
                                                '=',$get_pw
                            ]
            ])
                ->select();
            // dump($rs);exit(md5('123456'));
            if (isset($rs['name'])) {
                Session::set('admin_id', $get_admin);
                $_SESSION['auth'] = 1;
                $conn->close();
                return true;
            }
            $conn->close();
        }
        return false;
    }

    public function Index(Request $request)
    {
        $type = intval($request->param('type'));
        $admin_name = Session::get('admin_id');
        $login_time = Session::get('login_time');
        $this->db_config['table'] = 'admin';
        $admin = new Model($this->db_config);
        $r = $admin->where('name', '=', $admin_name)->fetchAll();
        if ($r[0]->sid == 0) {
            $menu = new Menu($this->db_config, $this->module);
            $r_1 = $menu->where([
                                    'type' => [
                                                '=',$type
                                    ],'s_id' => [
                                                    '=',0
                                    ]
            ])
                ->order('sort,id')
                ->fetchAll();
            if ($r_1) {
                foreach ($r_1 as $k => $v) {
                    $id_1 = $v->id;
                    $r_2 = $menu->where([
                                            's_id' => [
                                                        '=',$id_1
                                            ]
                    ])
                        ->order('sort,id')
                        ->fetchAll();
                    if ($r_2) {
                        foreach ($r_2 as $ke => $va) {
                            $id_2 = $va->id;
                            $r_3 = $menu->where([
                                                    's_id' => [
                                                                '=',$id_2
                                                    ]
                            ])
                                ->order('sort,id')
                                ->fetchAll();
                            if ($r_3) {
                                $va->res = $r_3;
                            }
                        }
                        $v->res = $r_2;
                    }
                }
                $list = $r_1;
            }
        } else {
            $role = $r[0]->role;
            $this->db_config['table'] = 'role';
            $Role = new Model($this->db_config);
            $rr = $Role->where('id', '=', $role)->fetchAll();
            if ($rr) {
                if ($rr[0]->permission != '') {
                    $permission = unserialize($rr[0]->permission);
                    $arr_1 = [];
                    $arr_2 = [];
                    $arr_3 = [];
                    $list = [];
                    foreach ($permission as $a => $b) {
                        $res = substr($b, 0, strpos($b, '/')); // 截取第一个'/'之前的内容
                        $rew = substr($b, strpos($b, '/') + 1); // 截取第一个'/'之后的内容
                        
                        if ($res == 1) {
                            $arr_1[] = explode('/', $rew); // 第一级数组
                        } else if ($res == 2) {
                            $arr_2[] = explode('/', $rew); // 第二级数组
                        } else if ($res == 3) {
                            $arr_3[] = explode('/', $rew); // 第三级数组
                        }
                    }
                    foreach ($arr_1 as $k => $v) {
                        // 查询模块表(模块名称、模块标识、模块描述)
                        $r_0 = $menu->where([
                                                'name' => [
                                                            '=',$v[0]
                                                ],'s_id' => [
                                                                '=',0
                                                ]
                        ])
                            ->order('sort,id')
                            ->fetchAll();
                        if ($r_0) {
                            $id_1 = $r_0[0]->id;
                            foreach ($arr_2 as $ke => $va) {
                                $r_1 = $menu->where([
                                                        'name' => [
                                                                    '=',$va[0]
                                                        ],'s_id' => [
                                                                        '=',$id_1
                                                        ],'module' => [
                                                                        '=',$va[1]
                                                        ]
                                ])
                                    ->order('sort,id')
                                    ->fetchAll();
                                
                                if ($r_1) {
                                    $id_2 = $r_1[0]->id;
                                    foreach ($arr_3 as $key => $val) {
                                        $r_2 = $menu->where([
                                                                'name' => [
                                                                            '=',$val[0]
                                                                ],'s_id' => [
                                                                                '=',$id_2
                                                                ],'module' => [
                                                                                '=',$val[1]
                                                                ]
                                        ])
                                            ->order('sort,id')
                                            ->fetchAll();
                                        if ($r_2) {
                                            $r_1[0]->res[] = $r_2[0];
                                        }
                                    }
                                    $r_0[0]->res[] = $r_1[0];
                                }
                            }
                            $list[] = $r_0[0];
                        }
                    }
                }
            }
        }
        $config = new \app\admin\model\Config($this->db_config, $this->module);
        $rr = $config->get(1, 'id');
        $domain = $rr[0]->domain;
        Session::set('uploadImg', $rr[0]->uploadImg);
        Session::set('uploadfile', $rr[0]->upload_file);
        $this->assign('version', $this->version);
        $this->assign('list', $list);
        $this->assign('admin_id', $admin_name);
        $this->assign('type', $type);
        $this->assign('login_time', $login_time);
        $this->assign('domain', $domain);
        return $this->fetch('', [], [
                                        '__moduleurl__' => $this->module_url
        ]);
    }

    public function changePassword(Request $request)
    {
        // \core\Response::instance(['d'=>'d'], 'json', 200, ['Access-Control-Allow-Origin: *'],false)->send();
        $this->db_config['table'] = 'admin';
        $admin = new Model($this->db_config);
        // 接收信息
        $admin_name = Session::get('admin_id'); // 管理员账号
        $y_password = md5(addslashes(trim($request->param('oldPW')))); // 原密码
        $password = md5(addslashes(trim($request->param('newPW')))); // 新密码
                                                                     
        // 根据id查询管理员信息
        $r = $admin->where([
                                'name' => [
                                            '=',$admin_name
                                ],'password' => [
                                                    '=',$y_password
                                ]
        ])->fetchAll();
        if (! $r) {
            $res = array(
                        'status' => '1','info' => '密码不正确！'
            );
            echo json_encode($res);
            exit();
        }
        if (! empty($password) && $password != $y_password) {
            $r01 = $admin->where([
                                    'name' => [
                                                '=',$admin_name
                                    ]
            ])->saveAll([
                            'password' => $password
            ]);
            if ($r01 == - 1) {
                $this->recordAdmin($admin_name, '修改管理员密码为 ' . $password . ' 失败', 2);
                $res = array(
                            'status' => '2','info' => '未知原因，修改失败!'
                );
                echo json_encode($res);
                exit();
            } else {
                
                $this->recordAdmin($admin_name, '修改管理员密码为 ' . $password . ' 成功', 2);
                $res = array(
                            'status' => '3','info' => '修改成功！'
                );
                echo json_encode($res);
                exit();
            }
        }
        exit(json_encode([
                                'status' => - 1
        ]));
    }

    public function maskContent(Request $request)
    {
        $admin_name = Session::get('admin_id'); // 管理员账号
        $nickname = addslashes(trim($request->param('nickname'))); // 昵称
        $birthday = addslashes(trim($request->param('birthday'))); // 生日
        $sex = addslashes(trim($request->param('sex'))); // 性别（1.男 2. 女）
        $tel = addslashes(trim($request->param('tel'))); // 手机号码
                                                         
        // 根据id查询管理员信息
        $this->db_config['table'] = 'admin';
        $admin = new Model($this->db_config);
        $r = $admin->where('name', '=', $admin_name)->fetchAll();
        if (! $r) {
            $res = array(
                        'status' => '1','info' => '没有该用户'
            );
            echo json_encode($res);
            exit();
        }
        if (! empty($nickname) || ! empty($birthday) || ! empty($sex) || ! empty($tel)) {
            $r01 = $admin->where('name', '=', $admin_name)->saveAll([
                                                                        'nickname' => $nickname,'birthday' => $birthday,'sex' => $sex,'tel' => $tel
            ]);
            if ($r01 == false) {
                $this->recordAdmin($admin_name, '修改管理员昵称为 ' . $nickname . '，生日为' . $birthday . '，性别' . $sex . '，手机号码为' . $tel . ' 失败', 2);
                $res = array(
                            'status' => '2','info' => '未知原因，修改失败!','re' => $r
                );
                echo json_encode($res);
                exit();
            } else {
                $this->recordAdmin($admin_name, '修改管理员昵称为 ' . $nickname . '，生日为' . $birthday . '，性别' . $sex . '，手机号码为' . $tel . '  成功', 2);
                $res = array(
                            'status' => '3','info' => '修改成功！','re' => $r
                );
                echo json_encode($res);
                exit();
            }
        } else {
            $res = array(
                        're' => $r
            );
            echo json_encode($res);
            exit();
        }
    }

    protected function parseSql($str, $type = 'update')
    {
        $return = [];
        if ($type == 'update') {
            $arrays = explode(",", $str);
            foreach ($arrays as $v) {
                list ($key, $value) = explode("=", $v);
                $return[trim($key)] = trim(str_replace("'", '', $value));
            }
        }
        if ($type == 'insert') {
            list ($key, $value) = explode("values", $str);
            $keys = explode(",", $key);
            $values = explode(",", $value);
            foreach ($keys as $k => $v) {
                $return[str_replace([
                                        '(',')',"`"
                ], [
                        ''
                ], trim($v))] = str_replace([
                                                '(',')',"'","`"
                ], [
                        ''
                ], trim($values[$k]));
            }
        }
        return $return;
    }
    protected function pagetoExcel($tplfile='excel')
    {
        $r = time();
        $str = $this->fetch($tplfile);
        \core\Response::instance($str, 'excel', 200, [
            "Content-Disposition" => "attachment;filename=orders-" . $r . ".xls",'content-type' => "application/msexcel;charset=utf-8"
        ])->send();
        exit();
    }
    protected function getConfig()
    {
        return $this->getModel('config')->get(1, 'id');
    }
    protected function getUploadImg()
    {
        return Session::get('uploadImg')?:$this->getConfig()[0]->uploadImg;
    }
    protected function getUrlConfig($url)
    {
        $returns=parse_url($url);
        !isset($returns['query'])&&$returns['query']='';
        parse_str($returns['query'],$returns['query']);
        return $returns;
    }
    protected function recordAdmin($admin_name, $event, $type)
    {
        $event = $admin_name . $event;
        $record = $this->getModel('AdminRecord');
        return $record->insert([
                                    'admin_name' => $admin_name,'event' => $event,'type' => $type
        ]);
        return true;
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
    function __destruct()
    {
        unset($this->model);
    }
}