<?php
namespace app\admin\controller;

use core\Request;
use core\Session;

class Role extends Index
{

    function __construct()
    {
        parent::__construct();
    }

    public function Index(Request $request)
    {
        $pageto = $request->param('pageto');
        // 导出
        $pagesize = $request->param('pagesize');
        $pagesize = $pagesize ? $pagesize : '10';
        // 每页显示多少条数据
        $page = $request->param('page');
        
        // 页码
        if ($page) {
            $start = ($page - 1) * $pagesize;
        } else {
            $start = 0;
        }
        
        // 查询管理员信息
        $rr=$this->getModel('Role')->order(['add_date'=>'desc'])->paginator($pagesize,$this->getUrlConfig($request->url));
        $pages_show=$rr->render();
        if ($rr) {
            foreach ($rr as $k1 => $v1) {
                $list_3[$k1] = '';
                $permission = unserialize($v1->permission);
                $arr_1 = [];
                $arr_2 = [];
                $arr_3 = [];
                if ($permission) {
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
                        $list_1 = '';
                        $list_2 = '';
                        // 查询模块表(模块名称、模块标识、模块描述)
                        $r=$this->getModel('CoreMenu')->where(['recycle'=>['=','0'],'s_id'=>['=','0'],'name'=>['=',$v[0]]])->fetchOrder(['sort'=>'asc','id'=>'asc'],'id,title');
                        if ($r) {
                            $list_1 .= $r[0]->title . '('; // 一级菜单名称拼接
                            $id_1 = $r[0]->id;
                            foreach ($arr_2 as $ke => $va) {
                                // 根据上级id、权限信息，查询菜单名称
                                $r_1=$this->getModel('CoreMenu')->where(['recycle'=>['=','0'],'s_id'=>['=',$id_1],'name'=>['=',$va[0]],'module'=>['=',$va[1]],'action'=>['=',$va[2]]])->fetchOrder(['sort'=>'asc','id'=>'asc'],'id,title');
                                if ($r_1) {
                                    $list_2 .= $r_1[0]->title . ','; // 二级菜单名称拼接
                                }
                            }
                            $list_1 .= rtrim($list_2, ','); // 一级菜单名称拼接
                            $list_1 .= ')'; // 一级菜单名称拼接
                            $list_3[$k1] .= $list_1 . ',';
                        }
                    }
                }
                $v1->permission = rtrim($list_3[$k1], ',');
            }
        }
        ;
        
        $this->assign("list", $rr);
        $this->assign('pages_show', $pages_show);
        
        return $this->fetch('', [], [
                                        '__moduleurl__' => $this->module_url
        ]);
    }

    public function add(Request $request)
    {
        $request->method() == 'post' && $this->do_add($request);
        
        // 查询模块表(模块名称、模块标识、模块描述)
        $r=$this->getModel('CoreMenu')->where(['s_id'=>['=','0']])->fetchOrder(['sort'=>'asc','id'=>'asc'],'*');
        if ($r) {
            foreach ($r as $k => $v) {
                $id_1 = $v->id;
                $v->res=[];
                // 根据上级id,查询下级信息
                $r_1=$this->getModel('CoreMenu')->where(['s_id'=>['=',$id_1]])->fetchOrder(['sort'=>'asc','id'=>'asc'],'*');
                if ($r_1) {
                    foreach ($r_1 as $ke => $va) {
                        $id_2 = $va->id;
                        $va->res=[];
                        // 根据上级id,查询下级信息
                        $r_2=$this->getModel('CoreMenu')->where(['s_id'=>['=',$id_2]])->fetchOrder(['sort'=>'asc','id'=>'asc'],'*');
                        if ($r_2) {
                            foreach ($r_2 as $key => $val) {
                                $id_3 = $val->id;
                                $val->res=[];
                                // 根据上级id,查询下级信息
                                $r_3=$this->getModel('CoreMenu')->where(['s_id'=>['=',$id_3]])->fetchOrder(['sort'=>'asc','id'=>'asc'],'*');
                                $val->res = $r_3;
                            }
                            $va->res = $r_2;
                        }
                    }
                    $v->res = $r_1;
                }
            }
        }
        $this->assign("list", $r);
        
        return $this->fetch('', [], [
                                        '__moduleurl__' => $this->module_url
        ]);
    }

    private function do_add($request)
    {
        $admin_id = Session::get('admin_id');
        
        // 接收数据
        $name = addslashes(trim($request->param('name'))); // 角色
        $permissions = $request->param('permission'); // 权限
        if ($name == '') {
            $this->error('角色不能为空！', '');
        } else {
            $r=$this->getModel('Role')->where(['name'=>['=',$name]])->fetchAll('id');
            if ($r) {
                $this->error('角色已经存在！', '');
            }
        }
        
        /* 避免选择中间一级，最上面一级没被选中 */
        $res = [];
        foreach ($permissions as $a => $b) {
            $rew = substr($b, strpos($b, '/') + 1); // 获取第一个/后面的内容
            $res[] = substr($rew, 0, strpos($rew, '/')); // 获取第一个/ 前面的内容(得到name值)
        }
        $list = [];
        $list1 = array_unique($res); // 去重复
        foreach ($list1 as $c => $d) { // 循环去空值
            if ($d != '') {
                $list[] = $d;
            }
        }
        foreach ($list as $e => $f) {
            $permissions[] = '1/' . $f; // 拼接最上面一级权限
        }
        $permissions = array_unique($permissions); // 去重复
        $permissions = serialize($permissions); // 转序列化
                                                // 添加一条数据
        $r=$this->getModel('Role')->insert(['name'=>$name,'permission'=>$permissions,'add_date'=>nowDate()]);
        if ($r == false) {
            $this->recordAdmin($admin_id, ' 添加角色失败 ', 1);
            
            $this->error('未知原因，添加失败！', '');
        } else {
            $this->recordAdmin($admin_id, ' 添加角色 ' . $name, 1);
            
            $this->success('添加成功！', $this->module_url . "/role");
        }
        
        return;
    }

    public function del(Request $request)
    {
        $admin_id = Session::get('admin_id');
        
        // 接收信息
        $id = intval($request->param('id')); // id
        
        $r=$this->getModel('Role')->where(['id'=>['=',$id]])->fetchAll('name');
        $admin_name = $r[0]->name;
        // 根据id删除信息
        $res=$this->getModel('Role')->delete($id,'id');
        echo $res;
        exit();
        
        $this->recordAdmin($admin_id, ' 删除角色 ' . $admin_name, 3);
        
        $this->success('删除成功！', $this->module_url . "/role");
        return;
    }

    public function modify(Request $request)
    {
        $request->method() == 'post' && $this->do_modify($request);
        
        // 接收信息
        $id = $request->param("id"); // 角色id
                                     
        // 根据角色id查询角色信息
        $rr = $this->getModel('Role')->get($id, 'id');
        $name = $rr[0]->name;
        $permission = unserialize($rr[0]->permission);
        $arr_1 = [];
        $arr_2 = [];
        $arr_3 = [];
        if ($permission) {
            foreach ($permission as $a => $b) {
                $res = substr($b, 0, strpos($b, '/')); // 获取第一个斜线之前的内容
                $rew = substr($b, strpos($b, '/') + 1); // 获取第一个斜线后面的内容
                if ($res == 1) {
                    $arr_1[] = explode('/', $rew); // 根据斜线转数组(第一级)
                } else if ($res == 2) {
                    $arr_2[] = explode('/', $rew); // 根据斜线转数组(第二级)
                } else if ($res == 3) {
                    $arr_3[] = explode('/', $rew); // 根据斜线转数组(第三级)
                }
            }
        }
        
        // 查询菜单表(模块名称、模块标识、模块描述)
        $r=$this->getModel('CoreMenu')->where(['s_id'=>['=','0']])->fetchOrder(['sort'=>'asc','id'=>'asc'],'*');
        if ($r) {
            foreach ($r as $k => $v) {
                $v->res=[];
                $r[$k]->status = 0; // 定义没选中
                $id_1 = $v->id;
                $name_1 = $v->name;
                if ($arr_1 != '') {
                    foreach ($arr_1 as $k1 => $v1) {
                        if ($name_1 == $v1[0]) {
                            $r[$k]->status = 1; // 选中
                        }
                    }
                }
                $r_1=$this->getModel('CoreMenu')->where(['s_id'=>['=',$id_1]])->fetchOrder(['sort'=>'asc','id'=>'asc'],'*');
                if ($r_1) {
                    foreach ($r_1 as $ke => $va) {
                        $va->res=[];
                        $r_1[$ke]->status = 0; // 定义没选中
                        $id_2 = $va->id;
                        $name_2 = $va->name;
                        $module_2 = $va->module;
                        $action_2 = $va->action;
                        foreach ($arr_2 as $k2 => $v2) {
                            if ($name_2 == $v2[0] && $module_2 == $v2[1] && $action_2 == $v2[2]) {
                                $r_1[$ke]->status = 1; // 选中
                            }
                        }
                        $r_2=$this->getModel('CoreMenu')->where(['s_id'=>['=',$id_2]])->fetchOrder(['sort'=>'asc','id'=>'asc'],'*');
                        if ($r_2) {
                            foreach ($r_2 as $key => $val) {
                                $val->res=[];
                                $r_2[$key]->status = 0; // 定义没选中
                                $id_3 = $val->id;
                                $name_3 = $val->name;
                                $module_3 = $val->module;
                                $action_3 = $val->action;
                                foreach ($arr_3 as $k3 => $v3) {
                                    if ($name_3 == $v3[0] && $module_3 == $v3[1] && $action_3 == $v3[2]) {
                                        $r_2[$key]->status = 1; // 选中
                                    }
                                }
                                $r_3=$this->getModel('CoreMenu')->where(['s_id'=>['=',$id_3]])->fetchOrder(['sort'=>'asc','id'=>'asc'],'*');
                                if ($r_3) {
                                    foreach ($r_3 as $key1 => $val1) {
                                        $r_3[$key1]->status = 0; // 定义没选中
                                        $id_4 = $val1->id;
                                        $name_4 = $val1->name;
                                        $module_4 = $val1->module;
                                        $action_4 = $val1->action;
                                        foreach ($arr_3 as $k4 => $v4) {
                                            if ($name_4 == $v4[0] && $module_4 == $v4[1] && $action_4 == $v4[2]) {
                                                $r_3[$key1]->status = 1; // 选中
                                            }
                                        }
                                    }
                                    $val->res = $r_3;
                                }
                            }
                            $va->res = $r_2;
                        }
                    }
                    $v->res = $r_1;
                }
            }
        }
        $this->assign('id', $id);
        $this->assign('name', $name);
        $this->assign('permission', $permission);
        $this->assign("list", $r);
        
        return $this->fetch('', [], [
                                        '__moduleurl__' => $this->module_url
        ]);
    }

    private function do_modify($request)
    {
        $admin_id = Session::get('admin_id');
        
        // 接收数据
        $id = $request->param("id");
        $name = addslashes(trim($request->param('name')));
        $permissions = $request->param('permission'); // 权限
        if ($name == '') {
            $this->error('角色不能为空！', '');
        } else {
            $r=$this->getModel('Role')->where(['id'=>['<>',$id],'name'=>['=',$name]])->fetchAll('id');
            if ($r) {
                $this->error('角色已经存在！', '');
            }
        }
        /* 避免选择中间一级，最上面一级没被选中 */
        $res = [];
        foreach ($permissions as $a => $b) {
            $rew = substr($b, strpos($b, '/') + 1); // 获取第一个/后面的内容
            $res[] = substr($rew, 0, strpos($rew, '/')); // 获取第一个/ 前面的内容(得到name值)
        }
        
        $list = [];
        $list1 = array_unique($res); // 去重复
        foreach ($list1 as $c => $d) { // 循环去空值
            if ($d != '') {
                $list[] = $d;
            }
        }
        foreach ($list as $e => $f) {
            $permissions[] = '1/' . $f; // 拼接最上面一级权限
        }
        $permissions = array_unique($permissions); // 去重复
        $permissions = serialize($permissions); // 转序列化
                                                // 更新数据表
        $r=$this->getModel('Role')->saveAll(['name'=>$name,'permission'=>$permissions],['id'=>['=',$id]]);
        if ($r == false) {
            $this->recordAdmin($admin_id, ' 修改角色id为 ' . $id . ' 失败 ', 2);
            
            $this->error('未知原因，修改失败！', $this->module_url . "/role");
        } else {
            $this->recordAdmin($admin_id, ' 修改角色id为 ' . $id . ' 的信息', 2);
            
            $this->success('修改成功！', $this->module_url . "/role");
        }
        return;
    }

    function changePassword(Request $request)
    {
        $this->redirect($this->module_url . '/index/changePassword');
    }

    function maskContent(Request $request)
    {
        $this->redirect($this->module_url . '/index/maskContent');
    }
}