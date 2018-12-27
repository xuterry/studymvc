<?php
namespace app\admin\controller;

use core\Request;
use core\Session;

class Member extends Index
{

    function __construct()
    {
        parent::__construct();
    }

    public function Index(Request $request)
    {
        
         $admin_name = Session::get('admin_id'); // 管理员账号
         $r = $this->getModel('admin')->get($admin_name,'name');
         
         //$sql = "select id from lkt_admin where name = '$admin_name'";
        // $r = $db->select($sql);
        $id = $r[0]->id;
        // 查询管理员信息
        $rr=$this->getModel('Admin')->where(['sid'=>['=',$id],'recycle'=>['=','0']])->fetchAll('*');
        if ($rr) {
            array_unshift($rr,$r[0]);
            foreach ($rr as $k1 => $v1) {
                $list_3[$k1] = '';
                $sid = $v1->sid;
                $role = $v1->role;
                $r_role=$this->getModel('Role')->where(['id'=>['=',$role]])->fetchAll('name,permission');
                $v1->role_name = $r_role[0]->name;
                $v1->permission = $r_role[0]->permission;
                $permission = unserialize($v1->permission);
                $arr_1 = [];
                $arr_2 = [];
                $arr_3 = [];
                $r_admin_name=$this->getModel('Admin')->where(['id'=>['=',$sid]])->fetchAll('name');
                if ($r_admin_name) {
                    $v1->admin_name = $r_admin_name[0]->name;
                } else {
                    $v1->admin_name = '';
                }
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
                        $r_1=$this->getModel('CoreMenu')->where(['s_id'=>['=','0'],'name'=>['=',$v[0]]])->fetchOrder(['sort'=>'asc','id'=>'asc'],'id,title');
                        if ($r_1) {
                            $list_1 .= $r_1[0]->title . '('; // 一级菜单名称拼接
                            $id_1 = $r_1[0]->id;
                            foreach ($arr_2 as $ke => $va) {
                                // 根据上级id、权限信息，查询菜单名称
                                $r_2=$this->getModel('CoreMenu')->where(['s_id'=>['=',$id_1],'name'=>['=',$va[0]],'module'=>['=',$va[1]],'action'=>['=',$va[2]]])->fetchOrder(['sort'=>'asc','id'=>'asc'],'id,title');
                                if ($r_2) {
                                    $list_2 .= $r_2[0]->title . ','; // 二级菜单名称拼接
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
        $this->assign("list", $rr);
        $this->assign('adminid',$id);
        return $this->fetch('', [], [
                                        '__moduleurl__' => $this->module_url
        ]);
    }

    public function Recycle(Request $request)
    {
        $getid=$request->get('id');
        $do=$request->get('do');
        if($do=='recovery'){
            if($this->getModel('admin')->save(['recycle'=>0],$getid,'id'))
                exit('1');
            exit('-1');
        }
        if($do=='del'){
            if($this->getModel('admin')->delete($getid,'id'))
                exit('1');
                exit('-1');
        }
        $admin_name = Session::get('admin_id'); // 管理员账号
        $r = $this->getModel('admin')->get($admin_name,'name');
        
        //$sql = "select id from lkt_admin where name = '$admin_name'";
        // $r = $db->select($sql);
        $id = $r[0]->id;
        // 查询管理员信息
        $rr=$this->getModel('Admin')->where(['sid'=>['=',$id],'recycle'=>['=','1']])->fetchAll('*');
        if ($rr) {
            foreach ($rr as $k1 => $v1) {
                $list_3[$k1] = '';
                $sid = $v1->sid;
                $role = $v1->role;
                $r_role=$this->getModel('Role')->where(['id'=>['=',$role]])->fetchAll('name,permission');
                $v1->role_name = $r_role[0]->name;
                $v1->permission = $r_role[0]->permission;
                $permission = unserialize($v1->permission);
                $arr_1 = [];
                $arr_2 = [];
                $arr_3 = [];
                $r_admin_name=$this->getModel('Admin')->where(['id'=>['=',$sid]])->fetchAll('name');
                if ($r_admin_name) {
                    $v1->admin_name = $r_admin_name[0]->name;
                } else {
                    $v1->admin_name = '';
                }
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
                        $r_1=$this->getModel('CoreMenu')->where(['s_id'=>['=','0'],'name'=>['=',$v[0]]])->fetchOrder(['sort'=>'asc','id'=>'asc'],'id,title');
                        if ($r_1) {
                            $list_1 .= $r_1[0]->title . '('; // 一级菜单名称拼接
                            $id_1 = $r_1[0]->id;
                            foreach ($arr_2 as $ke => $va) {
                                // 根据上级id、权限信息，查询菜单名称
                                $r_2=$this->getModel('CoreMenu')->where(['s_id'=>['=',$id_1],'name'=>['=',$va[0]],'module'=>['=',$va[1]],'action'=>['=',$va[2]]])->fetchOrder(['sort'=>'asc','id'=>'asc'],'id,title');
                                if ($r_2) {
                                    $list_2 .= $r_2[0]->title . ','; // 二级菜单名称拼接
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
        $this->assign("list", $rr);
        $this->assign('adminid',$id);
        return $this->fetch('', [], [
            '__moduleurl__' => $this->module_url
        ]);
    }
    
    
    public function add(Request $request)
    {
        $request->method() == 'post' && $this->do_add($request);
        
        $admin_name = Session::get('admin_id'); // 管理员账号
        $rew = "<option value='0'>请选择</option>";
        // 查询角色
        $r_1 = $this->getModel('Role')->fetchAll();
        
        if ($r_1) {
            foreach ($r_1 as $k => $v) {
                $rew .= "<option value='$v->id'>$v->name</option>";
            }
        }
        $this->assign("list", $rew);
        return $this->fetch('', [], [
                                        '__moduleurl__' => $this->module_url
        ]);
    }

    private function do_add($request)
    {
        
        // 接收数据
        $admin_id = Session::get('admin_id');
        
        $name = addslashes(trim($request->param('name'))); // 管理员账号
        $password = MD5(addslashes(trim($request->param('password')))); // 密码
        $password1 = MD5(addslashes(trim($request->param('password1')))); // 确认密码
        $role = addslashes(trim($request->param('role'))); // 角色
        
        $r_1=$this->getModel('Admin')->where(['name'=>['=',$admin_id]])->fetchAll('id');
        $sid = $r_1[0]->id;
        // 检查是否重复
        $sr = $this->getModel('admin')->getCount(['name'=>['=',$name]]);
        if ($sr && count($sr) > 0) {
            $this->error('用户名已经存在！', '');
        } else {
            if ($password == $password1) {
                if ($role == 0) {
                    $this->error('请选择角色！', '');
                } else {
                    // $sql = "select permission from lkt_role where id = '$role'";
                    // $r_role = $db->select($sql);
                    // $permission = $r_role[0]->permission;
                    // "insert into lkt_admin(sid,name,password,permission,role,status,add_date,recycle) values('$sid','$name','$password','$permission','$role',2,CURRENT_TIMESTAMP,0)";
                    $r=$this->getModel('Admin')->insert(['sid'=>$sid,'name'=>$name,'password'=>$password,'role'=>$role,'status'=>2,'add_date'=>nowDate(),'recycle'=>0]);
                    if ($r == false) {
                        $this->recordAdmin($admin_id, '添加管理员失败', 1);
                        
                        $this->error('未知原因，添加失败！', '');
                    } else {
                        $this->recordAdmin($admin_id, '添加管理员' . $name, 1);
                        
                        $this->success('添加成功！', $this->module_url . "/member");
                    }
                }
            } else {
                $this->error('确认密码不正确！', '');
            }
        }
        return;
    }

    public function ajax(Request $request)
    {
        $userid = addslashes(trim($request->param('id')));
        $type = $request->param('type');
        $r=$this->getModel('Admin')->where(['name'=>['=',$userid]])->fetchAll('permission');
        echo json_encode(unserialize($r[0]->permission));
        return;
    }

    public function del(Request $request)
    {
        $admin_id = Session::get('admin_id');
        
        // 接收信息
        $id = $request->param('id'); // id
        $id = rtrim($id, ','); // 去掉最后一个逗号
        $id = explode(',', $id); // 变成数组
        foreach ($id as $k => $v) {
            $r=$this->getModel('Admin')->where(['id'=>['=',$v]])->fetchAll('name');
            $admin_name = $r[0]->name;
            if($admin_id==$admin_name)
                exit('-1');
            $update_rs=$this->getModel('Admin')->saveAll(['recycle'=>1,'status'=>1],['id'=>['=',$v]]);
            
            $this->recordAdmin($admin_id, ' 删除管理员 ' . $admin_name, 3);
        }
        exit('1');
        $res = array(
                    'status' => '1','info' => '删除成功！'
        );     
        echo json_encode($res);
    }

    public function member_record(Request $request)
    {
        $admin_id = Session::get('admin_id');
        
        $pageto = $request->param('pageto'); // 导出
        $pagesize = $request->param('pagesize'); // 每页显示多少条数据
        $page = $request->param('page'); // 页码
        
        $admin_name = $request->param('admin_name'); // 管理员账号
        $startdate = $request->param("startdate");
        $enddate = $request->param("enddate");
        
        $pageto = $request->param('pageto');
        // 导出
        $pagesize = $request->param('pagesize');
        $pagesize = $pagesize ? $pagesize : '10';
        // 每页显示多少条数据
        $page = $request->param('page');
        
        // 页码
        if ($page) {
            $start = ($page - 1) * 10;
        } else {
            $start = 0;
        }
        
        $condition = ' 1=1';
        if ($startdate != '') {
            $condition .= " and add_date >= '$startdate 00:00:00' ";
        }
        if ($enddate != '') {
            $condition .= " and add_date <= '$enddate 23:59:59' ";
        }
        if ($admin_name != '') {
            $condition .= " and admin_name = '$admin_name' ";
        }
       $pages_show='';     
        if ($pageto == 'ne') {
            $this->recordAdmin($admin_id, '导出管理员记录表第' . $page . '数据', 4);
        } else if ($pageto == 'all') {
            $this->recordAdmin($admin_id, '导出管理员记录表全部数据', 4);
        }
       if($pageto=='all')
           $r=$this->getModel('AdminRecord')->where($condition)->fetchOrder(['add_date'=>'desc']);
           else 
           {
               $r=$this->getModel('AdminRecord')->where($condition)->order(['add_date'=>'desc'])->paginator($pagesize,$this->getUrlConfig($request->url));             
                 $pages_show = $r->render();
           }
        $this->assign("list", $r);
        $this->assign("admin_name", $admin_name);
        $this->assign("startdate", $startdate);
        $this->assign("enddate", $enddate);
        $this->assign('pageto', $pageto);
        $this->assign('pages_show', $pages_show);
        $this->assign('pagesize', $pagesize);
        !empty($pageto)&&$this->pagetoExcel();
        return $this->fetch('', [], [
                                        '__moduleurl__' => $this->module_url
        ]);
    }

    public function member_record_del(Request $request)
    {
        $admin_id = Session::get('admin_id');
        $A=$this->getModel('AdminRecord');
        // 接收信息
        $id = $request->param('id'); // id数组
        $type = $request->param('type'); // id
        if ($type == 'onekey') {
            $sql = "TRUNCATE TABLE lkt_admin_record";
            $A->query($sql);
            $this->recordAdmin($admin_id, '一键清空管理员记录表', 3);
        } else {
            $id = rtrim($id, ','); // 去掉最后一个逗号
            $id = explode(',', $id); // 变成数组
            foreach ($id as $k => $v) {
                $del_rs=$A->delete($v,'id');
            }
            $this->recordAdmin($admin_id, '批量删除管理员记录表', 3);
        }
        
        $res = array(
                    'status' => '1','info' => '删除成功！'
        );
        echo json_encode($res);
        return;
    }

    public function modify(Request $request)
    {
        $request->method() == 'post' && $this->do_modify($request);
        
        // 接收信息
        $admin_name = Session::get('admin_id'); // 管理员账号
        $id = $request->param("id");
        
        // 根据id查询管理员信息
        $r = $this->getModel('Admin')->get($id, 'id');
        $name = $r[0]->name; // 管理员名称
        $admin_type = $r[0]->admin_type;
        $role = $r[0]->role; // 角色id
                             
        // 根据角色id,查询角色信息
        $r_1 = $this->getModel('Role')->get($role, 'id');
        $r_id = $r_1[0]->id; // 角色id
        $r_name = $r_1[0]->name; // 角色名称
        
        $rew = "<option value='$r_id'>$r_name</option>";
        // 查询角色
        $r_2 = $this->getModel('Role')->fetchAll();
        if ($r_2) {
            foreach ($r_2 as $r_k => $r_v) {
                $rew .= "<option value='$r_v->id'>$r_v->name</option>";
            }
        }
        
        $this->assign('id', $id);
        $this->assign('name', $name);
        $this->assign('admin_type', $admin_type);
        $this->assign('list', $rew);
        
        return $this->fetch('', [], [
                                        '__moduleurl__' => $this->module_url
        ]);
    }

    private function do_modify($request)
    {
        $admin = Session::get('admin_id');
        
        // 接收数据
        $id = $request->param("id");
        $name = addslashes(trim($request->param('name')));
        $y_password = md5(addslashes(trim($request->param('y_password'))));
        $password = md5(addslashes(trim($request->param('password'))));
        $role = addslashes(trim($request->param('role'))); // 角色
                                                           
        // $sql = "select permission from lkt_role where id = '$role'";
                                                           // $r_role = $db->select($sql);
                                                           // $permission = $r_role[0]->permission;
        
        if (addslashes(trim($request->param('y_password'))) != '') {
            $rr=$this->getModel('Admin')->where(['name'=>['=',$name],'password'=>['=',$y_password]])->fetchAll('id');
            if (empty($rr)) {
                $this->error('密码不正确！', '');
            }
            if ($password != '') {
                $r=$this->getModel('Admin')->saveAll(['name'=>$name,'password'=>$password,'permission'=>$permission,'role'=>$role],['id'=>['=',$id]]);
            }
        } else {
            // 更新数据表
            $r=$this->getModel('Admin')->saveAll(['name'=>$name,'permission'=>$permission,'role'=>$role],['id'=>['=',$id]]);
        }
        if ($r == false) {
            $this->recordAdmin($admin, '修改管理员id为 ' . $id . ' 失败', 2);
            
            $this->error('未知原因，修改失败！', $this->module_url . "/member");
        } else {
            $this->recordAdmin($admin, '修改管理员id为 ' . $id . ' 的信息', 2);
            
            $this->success('修改成功！', $this->module_url . "/member");
        }
        return;
    }

    public function status(Request $request)
    {
        $admin_id = Session::get('admin_id');
        
        $id = addslashes(trim($request->param('id')));
        
        $r=$this->getModel('Admin')->where(['id'=>['=',$id]])->fetchAll('name,status');
        if ($r) {
            $admin_name = $r[0]->name;
            $status = $r[0]->status;
            if ($status == 1) {
                $update_rs=$this->getModel('Admin')->saveAll(['status'=>2],['id'=>['=',$id]]);
                $update_rs=$this->getModel('Admin')->saveAll(['status'=>2],['sid'=>['=',$id]]);
                
                $this->recordAdmin($admin_id, '启用管理员' . $admin_name, 5);
                
                //$this->success('启用成功！', $this->module_url . "/member");
                exit('1');
            } else if ($status == 2) {
                $update_rs=$this->getModel('Admin')->saveAll(['status'=>1],['id'=>['=',$id]]);
                $update_rs=$this->getModel('Admin')->saveAll(['status'=>1],['sid'=>['=',$id]]);
                $this->recordAdmin($admin_id, '禁用管理员' . $admin_name, 5);
                
                //$this->success('禁用成功！', $this->module_url . "/member");
                exit('1');
            }
        }
        exit('-1');
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