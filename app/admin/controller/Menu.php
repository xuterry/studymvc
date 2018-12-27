<?php
namespace app\admin\controller;

use core\Request;
use core\Session;

class Menu extends Index
{

    function __construct()
    {
        parent::__construct();
    }

    public function Index(Request $request)
    {
        $cart_id = $request->param('cart_id'); // 菜单id
        $title = $request->param('title'); // 菜单名称
        
        $pageto = $request->param('pageto');
        // 导出
        $pagesize = $request->param('pagesize');
        $pagesize = $pagesize ? $pagesize : '10';
        // 每页显示多少条数据
        $page = $request->param('page');
        
        $s_id1=$request->get('s_id1');
        $s_id2=$request->get('s_id2');
        
        // 页码
        if ($page) {
            $start = ($page - 1) * $pagesize;
        } else {
            $start = 0;
        }
        
        $condition = ' 1 = 1 ';
        
        if ($cart_id != '') {
            $condition .= " and id = '$cart_id' ";
        }
        if ($title != '') {
            $condition .= " and title = '$title'  ";
        }
        if(!empty($s_id2)){
            if($s_id2==-1)
                $condition.=' and level = 2';
            else
           $condition.=" and s_id = ".$s_id2;
        }
        elseif(!empty($s_id1)){
            if($s_id1==-1)
                $condition.=' and level = 1';
            else
                $condition.=" and s_id = ".$s_id1;
        }
        $rew = [];
        $res = '';
        
        $num = 0;
        $r=$this->getModel('Menu')->where($condition)->fetchOrder(['id'=>'asc'],'*');
        $sort=$menus=$menus2=[];
        
        if ($r) {
            foreach ($r as $k => $v) {
                if($v->level==1)
                    $v->show_id=$v->id;
                else{
                    if(isset($sort[$v->s_id]))
                    $v->show_id=$sort[$v->s_id].'_'.$v->id;
                    else{
                        $menus2[]=$v;
                        continue;
                    }
                        
                }
                $sort[$v->id]=$v->show_id;
                $menus[]=$v;         
            }
            foreach($menus2 as $v){
                if(isset($sort[$v->s_id])){
                $v->show_id=$sort[$v->s_id].'_'.$v->id;
                }else{
                    $v->show_id=$v->s_id.'_'.$v->id;
                }
                $sort[$v->id]=$v->show_id;
                $menus[]=$v;  
            }
            array_multisort($sort,SORT_ASC,SORT_NUMERIC,$menus);        
        }

        $url = "cart_id=" . urlencode($cart_id) . "&title=" . urlencode($title) . "&pagesize=" . urlencode($pagesize);
        parse_str($url,$url);
        $path=$this->module_url.'/menu';
        $list = array_slice($menus, $start, $pagesize);
        $list=\core\Paginator::make($list,$pagesize,$page,count($menus),false,$this->getUrlConfig($request->url));
        
        $this->assign("cart_id", $cart_id);
        $this->assign("title", $title);
        $this->assign("list", $list);
        $this->assign('pages_show', $list->render());
        $this->assign('pagesize', $pagesize);
        
        return $this->fetch('', [], [
                                        '__moduleurl__' => $this->module_url
        ]);
    }
   public function ajax(Request $request)
   {
       $s_id=$request->param('v');
       $r=$this->getModel('Menu')->where(['s_id'=>['=',$s_id]])->fetchOrder(['sort'=>'asc','id'=>'asc'],'*');
       $list = "";
       if ($r) { // 存在
           foreach ($r as $k => $v) {
               $list .= "<option value='$v->id'>$v->title</option>";
           }
       }
       exit($list);
   }
    public function add(Request $request)
    {
        $request->method() == 'post' && $this->do_add($request);
        
        // 查询一级菜单，根据排序和id排列
        $r=$this->getModel('Menu')->where(['s_id'=>['=','0']])->fetchOrder(['sort'=>'asc','id'=>'asc'],'*');
        $list = "";
        if ($r) { // 存在
            foreach ($r as $k => $v) {
                $list .= "<option value='$v->id'>$v->title</option>";
            }
        }
        $this->assign("list", $list);
        
        return $this->fetch('', [], [
                                        '__moduleurl__' => $this->module_url
        ]);
    }

    private function do_add($request)
    {
        $admin_id = Session::get('admin_id');
        
        // 接收数据
        $title = addslashes(trim($request->param('title'))); // 菜单名称
        $image = addslashes(trim($request->param('image'))); // 图标
        $image1 = addslashes(trim($request->param('image1'))); // 图标
        $url = addslashes(trim($request->param('url'))); // 路径
        $type = addslashes(trim($request->param('type'))); // 类型
        $sort = addslashes(trim($request->param('sort'))); // 排序
        $s_id = $request->param('val'); // 产品类别
        $level = $request->param('level') + 1; // 级别
        
        if ($title == '') {
            $this->error('菜单名称不能为空！', '');
        }
        
        if (is_numeric($sort)) {
            if ($sort <= 0) {
                $this->error('排序不能小于等于0！', '');
            }
        } else {
            $this->error('排序请填写数字！', '');
        }
        $rr=$this->getModel('Menu')->where(['title'=>['=',$title],'s_id'=>['=',$s_id]])->fetchAll('id');
        if ($rr) {
            $this->error('菜单名称".$title."已存在！', '');
        }
        if ($level != 1) {
            if ($url) {
                   $url=strpos($url,$this->module)===false?$this->module_url.$url:$url;
                    $rew = array_values(array_filter(explode('/', $url)));
                    $module = isset($rew[0])?$rew[1]:$this->module;
                    $controller=isset($rew[1])?$rew[1]:'index';
                    $action = isset($rew[2])?$rew[2]:'index';
            } else {
                $this->error('路径不能为空！', '');
            }
        } else {
            $module = '';
            $action = '';
        }
        
        $r=$this->getModel('Menu')->insert(['s_id'=>$s_id,'title'=>$title,'module'=>$module,'action'=>$action,'level'=>$level,'url'=>$url,'image'=>$image,'image1'=>$image1,'sort'=>$sort,'type'=>$type,'add_time'=>nowDate()]);
        if ($r == false) {
            $this->recordAdmin($admin_id, ' 添加菜单失败 ', 1);
            
            $this->error('未知原因，添加失败！', '');
        } else {
            $this->recordAdmin($admin_id, ' 添加菜单 ' . $title, 1);
            
            $this->success('添加成功！', $this->module_url . "/menu");
        }
    }

    public function del(Request $request)
    {
        $admin_id = Session::get('admin_id');
        // 接收信息
        $id = $request->param('id'); // id
        $num = 0;
        $status = 0;
        // 根据id,查询他的下级
        $r=$this->getModel('Menu')->where(['s_id'=>['=',$id],'recycle'=>['=','0']])->fetchAll('id');
        if ($r) { // 有下级
            $status = 1;
        }
        if ($status == 0) {
            $this->recordAdmin($admin_id, ' 删除菜单id为 ' . $id . ' 成功 ', 3);
            
            $update_rs=$this->getModel('Menu')->saveAll(['recycle'=>1],['id'=>['=',$id]]);
            
            $res = array(
                        'status' => '1','info' => '删除成功！'
            );
            echo json_encode($res);
            return;
        } else {
            $this->recordAdmin($admin_id, ' 删除菜单id为 ' . $id . ' 失败 ', 3);
            
            $res = array(
                        'status' => '0','info' => '删除失败！'
            );
            echo json_encode($res);
            return;
        }
    }

    public function modify(Request $request)
    {
        $request->method() == 'post' && $this->do_modify($request);
        
        // 接收信息
        $id = $request->param("id");
        
        // 根据id，查询菜单
        $r_1 = $this->getModel('Menu')->get($id, 'id');
        if ($r_1) {
            $space = "---";
            $s_id = $r_1[0]->s_id; // 上级id
            $title = $r_1[0]->title; // 菜单名称
                                     // $name = $r_1[0]->name; // 菜单标识
            $image = $r_1[0]->image; // 图片
            $image1 = $r_1[0]->image1; // 图片
            $url = $r_1[0]->url; // 路径
            $sort = $r_1[0]->sort; // 排序
            $type = $r_1[0]->type; // 排序
            $level = $r_1[0]->level; // 等级
            $list1=$list2='';
            if ($level == 1) {
                $list = "";
                $r=$this->getModel('Menu')->where(['s_id'=>['=','0']])->fetchOrder(['sort'=>'asc','id'=>'asc'],'*');
                if ($r) { // 存在
                    foreach ($r as $k => $v) {
                        $checked=$v->id==$id?'selected':'';
                        $list .= "<option value='$v->id' ".$checked.">$v->title</option>";
                    }
                }
                $cid = 0;
            } else if ($level == 2) {
                /**
                 * 二级 *
                 */
                $list1 = "";                
                $rr = $this->getModel('Menu')->get($s_id, 's_id ');
                if ($rr) {
                    foreach ($rr as $k => $v) {
                        $checked=$v->id==$id?'selected':'';        
                        $list1 .= "<option value='$v->id' ".$checked.">$v->title</option>";
                    }
                }
                /**
                 * 一级 *
                 */
                $r1 = $this->getModel('Menu')->get($s_id, 'id');
                if ($r1) {
                    $id1 = $r1[0]->id; // id
                    $title1 = $r1[0]->title; // 菜单名称
                    $list = "<option selected='true' value='$id1'>$title1</option>";
                    $r=$this->getModel('Menu')->where(['s_id'=>['=','0']])->fetchOrder(['sort'=>'asc','id'=>'asc'],'*');
                    if ($r) { // 存在
                        foreach ($r as $k => $v) {
                            if($v->id!=$s_id)
                            $list .= "<option value='$v->id' >$v->title</option>";
                        }
                    }
                }
                $cid = $s_id;
                
                $this->assign("list1", $list1);
            } else if ($level == 3) {
                /**
                 * 三级 *
                 */
                $list2 = "";
                $rrr = $this->getModel('Menu')->get($s_id, 's_id ');
                if ($rrr) {
                    foreach ($rrr as $k => $v) {
                        $checked=$v->id==$id?'selected':'';                       
                        $list2 .= "<option value='$v->id' ".$checked.">$v->title</option>";
                    }
                }
                /**
                 * 二级 *
                 */
                $r2 = $this->getModel('Menu')->get($s_id, 'id');
                if ($r2) {
                    $id2 = $r2[0]->id; // id
                    $s_id2 = $r2[0]->s_id; // 上级id
                    $title2 = $r2[0]->title; // 菜单名称
                    $list1 = "<option selected='true' value='$id2'>$title2</option>";
                    $r=$this->getModel('Menu')->where(['s_id'=>['=','0']])->fetchOrder(['sort'=>'asc','id'=>'asc'],'*');
                    if ($r) { // 存在
                        foreach ($r as $k => $v) {
                            if($v->id!=$id2)
                            $list1 .= "<option value='$v->id'>$v->title</option>";
                        }
                    }
                }
                /**
                 * 一级 *
                 */
                $r1 = $this->getModel('Menu')->get($s_id2, 'id');
                if ($r1) {
                    $id1 = $r1[0]->id; // id
                    $title1 = $r1[0]->title; // 菜单名称
                    $list = "<option selected='true' value='$id1'>$title1</option>";
                    $r=$this->getModel('Menu')->where(['s_id'=>['=','0']])->fetchOrder(['sort'=>'asc','id'=>'asc'],'*');
                    if ($r) { // 存在
                        foreach ($r as $k => $v) {
                            if($v->id!=$id1)
                            $list .= "<option value='$v->id'>$v->title</option>";
                        }
                    }
                }
                $cid = $s_id;
                
            } 
        }
        
        $r_1=$this->getModel('Menu')->where(['s_id'=>['=',$id]])->fetchAll('id');
        if ($r_1) {
            $status = 1;
        } else {
            $status = 0;
        }
        $this->assign('id', $id);
        $this->assign('title', $title);
        // $this->assign('name', $name );
        $this->assign('url', $url);
        $this->assign('sort', $sort);
        $this->assign('type', $type);
        $this->assign('level', $level);
        $this->assign('image', $image);
        $this->assign('image1', $image1);
        // $this->assign('type', $type );
        $this->assign('cid', $cid);
        $this->assign("level", $level - 1);
        $this->assign("list", $list);
        $this->assign("list1", $list1);
        $this->assign("list2", $list2);
        $this->assign("status", $status);
        return $this->fetch('', [], [
                                        '__moduleurl__' => $this->module_url
        ]);
    }

    private function do_modify($request)
    {
        $admin_id = Session::get('admin_id');
        
        // 接收数据
        $id = $request->param("id");
        $title = addslashes(trim($request->param('title'))); // 菜单名称
                                                             // $s_id = addslashes(trim($request->param('s_id'))); // 上级id
        $image = addslashes(trim($request->param('image'))); // 图标
        $oldpic = addslashes(trim($request->param('oldpic'))); // 产品图片
        $image1 = addslashes(trim($request->param('image1'))); // 图标
        $oldpic1 = addslashes(trim($request->param('oldpic1'))); // 产品图片
        
        $url = addslashes(trim($request->param('url'))); // 路径
        $type = addslashes(trim($request->param('type'))); // 类型
        $sort = addslashes(trim($request->param('sort'))); // 排序
        
        $s_id = $request->param('val'); // 产品类别
        $level = $request->param('level') + 1; // 级别
                                               
        // if(!empty($s_id)){
                                               // // 根据传过来的菜单id，查询菜单信息
                                               // $r_1=$this->getModel('Menu')->get($s_id,'id');
                                               // $name = $r_1[0]->name; // 菜单标识
                                               // }else{
                                               // $name = addslashes(trim($request->param('name'))); // 菜单标识
                                               // }
        if ($title == '') {
            $this->error('菜单名称不能为空！', '');
        } else {
            $r_1=$this->getModel('Menu')->where(['id'=>['<>',$id],'title'=>['=',$title],'s_id'=>['=',$s_id]])->fetchAll('id');
            if ($r_1) {
                $this->error('菜单名称".$title."已存在！', '');
            }
        }
        // if($name == ''){
        // $this->error('菜单标识不能为空！','');
        //
        // }else{
        // $sql = "select id,name,level from lkt_core_menu where id = '$id'";
        // $r_3 = $db->select($sql);
        // $yid = $r_3[0]->id;
        // $yname = $r_3[0]->name;
        // $ylevel = $r_3[0]->level;
        // if($name != $yname){
        // $sql = "update lkt_core_menu set name = '$name' where name = '$yname' and id = '$id' ";
        // $db->update($sql);
        // $sql = "update lkt_core_menu set name = '$name' where name = '$yname' and s_id = '$id' ";
        // $db->update($sql);
        //
        // $num = $level - $ylevel;
        // $sql = "update lkt_core_menu set level = level+'$num' where s_id = '$yid'";
        // $db->update($sql);
        // }
        // }
        
        if (is_numeric($sort)) {
            if ($sort <= 0) {
                $this->error('排序不能小于等于0！', '');
            }
        } else {
            $this->error('排序请填写数字！', '');
        }
        if ($level != 1) {
            if ($url) {
                $url=strpos($url,$this->module)===false?$this->module_url.$url:$url;
                $rew = array_values(array_filter(explode('/', $url)));
                $module = isset($rew[0])?$rew[1]:$this->module;
                $controller=isset($rew[1])?$rew[1]:'index';
                $action = isset($rew[2])?$rew[2]:'index';
                
            } else {
                $this->error('路径不能为空！', '');
            }
        } else {
            $url = '';
            $module = '';
            $action = '';
        }
        if ($image) {
            if ($image != $oldpic) {
                @unlink($oldpic);
            }
        } else {
            $image = $oldpic;
        }
        if ($image1) {
            if ($image1 != $oldpic1) {
                @unlink($oldpic1);
            }
        } else {
            $image1 = $oldpic1;
        }
        $r=$this->getModel('Menu')->saveAll(['title'=>$title,'module'=>$module,'action'=>$action,'s_id'=>$s_id,'level'=>$level,'image'=>$image,'image1'=>$image1,'url'=>$url,'type'=>$type,'sort'=>$sort],['id'=>['=',$id]]);
        if ($r == false) {
            $this->recordAdmin($admin_id, ' 修改菜单id为 ' . $id . ' 失败 ', 2);
            
            $this->error('未知原因，修改失败！', $this->module_url . "/menu");
        } else {
            $this->recordAdmin($admin_id, ' 修改菜单id为 ' . $id . ' 的信息', 2);
            
            $this->success('修改成功！', $this->module_url . "/menu");
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