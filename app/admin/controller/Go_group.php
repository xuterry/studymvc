<?php
namespace app\admin\controller;

use core\Request;
use core\Session;
use core\Cookie;
use core\Cache;

class Go_group extends Index
{

    function __construct()
    {
        parent::__construct();
    }

    private function do_addgroup($request)
    {

        $set = $request->get;
        $set['overtime'] = $set['timehour'].':'.$set['timeminite'];
        if(isset($set['starttime'])) $set['starttime'] = strtotime($set['starttime']);
        
        if(isset($set['radio']) && $set['radio'] == 1){
            
            $set['endtime'] = strtotime('+1year');
            
        }else if($set['radio'] == 2 && isset($set['endtime'])){
            
            $set['endtime'] = strtotime($set['endtime']);
            
        }     
        Cache::set('set',$set);
    }

    public function addgroup(Request $request)
    {
       $request->method()=='post'&&$this->do_addgroup($request);

        return $this->fetch('', [], [
                                        '__moduleurl__' => $this->module_url
        ]);
    }

    public function addproduct(Request $request)
    {
        $product_class = $request->param('cid'); // 分类名称
        $product_title = $request->param('pro_name'); // 标题                                                   
        // var_dump($_COOKIE['proids']);
        $rr=$this->getModel('ProductClass')->where(['sid'=>['=','0']])->fetchAll('cid,pname');
        $res = '';
        foreach ($rr as $key => $value) {
            $c = '-' . $value->cid . '-';
            // 判断所属类别 添加默认标签
            if ($product_class == $c) {
                $res .= '<option selected="selected" value="' . $c . '">' . $value->pname . '</option>';
            } else {
                $res .= '<option  value="' . $c . '">' . $value->pname . '</option>';
            }
            // 循环第一层
            $r_e=$this->getModel('ProductClass')->where(['sid'=>['=',$value->cid]])->fetchAll('cid,pname');
            if ($r_e) {
                $hx = '-----';
                foreach ($r_e as $ke => $ve) {
                    $cone = $c . $ve->cid . '-';
                    // 判断所属类别 添加默认标签
                    if ($product_class == $cone) {
                        $res .= '<option selected="selected" value="' . $cone . '">' . $hx . $ve->pname . '</option>';
                    } else {
                        $res .= '<option  value="' . $cone . '">' . $hx . $ve->pname . '</option>';
                    }
                    // 循环第二层
                    $r_t=$this->getModel('ProductClass')->where(['sid'=>['=',$ve->cid]])->fetchAll('cid,pname');
                    if ($r_t) {
                        $hxe = $hx . '-----';
                        foreach ($r_t as $k => $v) {
                            $ctow = $cone . $v->cid . '-';
                            // 判断所属类别 添加默认标签
                            if ($product_class == $ctow) {
                                $res .= '<option selected="selected" value="' . $ctow . '">' . $hxe . $v->pname . '</option>';
                            } else {
                                $res .= '<option  value="' . $ctow . '">' . $hxe . $v->pname . '</option>';
                            }
                        }
                    }
                }
            }
        }
        $arr = [];
        $condition = ' 1=1 ';
        if ($product_class != '') {
            $condition .= " and a.product_class like '%$product_class%' ";
        }
        
        if ($product_title != '') {
            $condition .= " and a.product_title like '%$product_title%' ";
        }
        $r=$this->getModel('ProductList')->alias('a')->where($condition)->field('a.id,a.product_title,a.imgurl,product_class')
        ->order(['status'=>'asc','add_date'=>'desc','a.sort'=>'desc'])->select();
        $list = [];
        $status_num = 0;
        foreach ($r as $key => $value) {
            $pid = $value->id;
            $class = $value->product_class;
            // $num = $value -> num;
            $typestr = trim($class, '-');
            $typeArr = explode('-', $typestr);
            // 取数组最后一个元素 并查询分类名称
            $cid = end($typeArr);
            $r_p=$this->getModel('ProductClass')->where(['cid'=>['=',$cid]])->fetchAll('pname');
            if ($r_p) {
                $pname = $r_p['0']->pname;
            } else {
                $pname = '顶级';
            }
            foreach ($value as $k => $v) {
                $arr[$k] = $v;
            }
            $arr['pname'] = $pname;
            
            $list[$key] = (object) $arr;
        }
        // 查询系统参数
          $img=$this->getUploadImg(); 
        foreach ($list as $ke => $ve) {
            $list[$ke]->image = $img . $ve->imgurl;
        }  
        $this->do_addgroup($request); // 把活动设置储存在缓存里      
        $str = Cookie::get('proids')? : '';
        if (strlen($str) > 1) {
            $str = substr($str, 1, - 1);
            $idarr = explode(',', $str);
            foreach ($list as $ke => $val) {
                $val->checked = 0;
                foreach ($idarr as $v) {
                    if ($val->id == $v) {
                        $val->checked = 1;
                    }
                }
                $list[$ke] = $val;
            }
        }      
        $this->assign("arr", $list);
        $this->assign("class", $res);
        $this->assign("title", $product_title);
        $this->assign('set',Cache::get('set')?:'');
        return $this->fetch('', [], [
                                        '__moduleurl__' => $this->module_url
        ]);
    }

    public function configs(Request $request)
    {
       $request->method()=='post'&&$this->do_configs($request);
        $arr = array(
                    '1' => '自动退款','2' => '手动退款'
        );
        $res=$this->getModel('GroupConfig')->fetchAll();
        if (! empty($res))
            $res = $res[0]->refunmoney;
        $this->assign("arr", $arr);
        $this->assign("type", $res);
        return $this->fetch('', [], [
                                        '__moduleurl__' => $this->module_url
        ]);
    }

    public function del(Request $request)
    {
        // 查询插件表
        $id=trim($request->get('id'));
        $res=0;
        $use=$request->get('use');
        if (isset($use) && $use == 1) {
            $res=($this->getModel('groupBuy')->delete($id,'status')&&$this->getModel('groupProduct')->delete($id,'group_id'));
        } else if (isset($use) && $use == 2) {
            $res=$this->getModel('groupBuy')->save(['is_show'=>1],$id,'status');
        } else if (isset($use) && $use == 3) {
            $res=$this->getModel('groupBuy')->save(['is_show'=>0],$id,'status');
        }
        $status=$res?1:-1;
        exit(json_encode(array('status' => $status)));
        
        return $this->fetch('', [], [
                                        '__moduleurl__' => $this->module_url
        ]);
    }

    public function grouppro(Request $request)
    {    
        // 接收信息
        $id = addslashes(trim($request->param('id'))); // 插件id
     //   $sql = "select m.*,l.product_title as pro_name from (select p.id,p.product_id,p.group_id,c.img as image,p.group_price,p.member_price,c.price as market_price,c.name as attr_name,c.color,c.size as guige,p.classname from lkt_group_product as p left join lkt_configure as c on p.attr_id=c.id where p.group_id='$id' order by p.classname) as m left join lkt_product_list as l on m.product_id=l.id";       
        $res=$this->getModel('GroupProduct')
        ->alias('p')->join("configure c","p.attr_id=c.id",'left')
        ->join("product_list l","p.product_id=l.id",'left')->fetchOrder("p.classname","p.*,l.product_title as pro_name,c.img as image,c.price as market_price,c.name as attr_name,c.color,c.size as guige");
        $len = count($res);
        //  dump($this->getModel('GroupProduct')->getLastSql(),$res);
        $img=$this->getUploadImg();
        foreach ($res as $k => $v) {
            $res[$k]->image = $img . $v->image;
        }
        $status = trim($request->param('status')) ? 1 : 0;
        $this->assign("status", $status);
        $this->assign("list", $res);
        $this->assign("len", $len);
        return $this->fetch('', [], [
                                        '__moduleurl__' => $this->module_url
        ]);
    }
    public function index(Request $request)
    {
        $status = trim($request->param('status'));
        $and = ' 1 = 1';
        $time = time();
        if ($status == 1) {
            $and .= " and starttime < '".$time."' and endtime > '".$time."' and is_show='0'";
        } else if ($status == 2) {
            $and .= " and starttime < '".$time."' and endtime > '".$time."' and is_show='1'";
        } else if ($status == 3) {
            $and .= " and endtime < '".$time."'";
        }
        // 查询插件表
        $condition = '';
        $res=$this->getModel('GroupBuy')->where($and)->fetchAll('*');
        foreach ($res as $k => $v) {
            $res[$k]->time = date('Y-m-d H:i:s', $v->starttime) . ' 至 ' . date('Y-m-d H:i:s', $v->endtime);
            $arr = explode(':', $v->time_over);
            $res[$k]->time_over = $arr[0] . '小时' . $arr[1] . '分钟';
            
            if (time() < $v->starttime) {
                $res[$k]->code = 1;
            } else if (time() > $v->starttime && time() < $v->endtime) {
                $res[$k]->code = 2;
            } else if (time() > $v->endtime) {
                $res[$k]->code = 3;
            }
        }     
        $showres = $this->getModel('GroupBuy')->getCount(['is_show'=>['=',1]],'*');
        
        $this->assign("is_show", $showres);
        $this->assign("list", $res);
        
        if (isset($request['use']) && $request['use'] == 1) {
            $this->delgroup();
        } else if (isset($request['use']) && $request['use'] == 2) {
            $this->startgroup();
        } else if (isset($request['use']) && $request['use'] == 3) {
            $this->stopgroup();
        }
        $this->assign("status", $status);
        
        return $this->fetch('', [], [
                                        '__moduleurl__' => $this->module_url
        ]);
    }
    public function modify(Request $request)
    {
        $id = intval(trim($request->param('id')));
        $set = addslashes(trim($request->param('set')));
        if ($set == 'msg') {
            $this->setgroupmsg($request);
        } else if ($set == 'msgsubmit') {
            $this->msgsubmit($request);
        } else if ($set == 'gpro') {
            $this->modifypro($request);
        } else if ($set == 'delpro') {
            $this->delpro();
        }
        $status = trim($request->param('status')) ? 1 : 0;
        $this->assign("status", $status);
        return $this->fetch('', [], [
                                        '__moduleurl__' => $this->module_url
        ]);
    }
    public function searchpro(Request $request)
    {
        $product_class = $request->param('proc'); // 分类名称
        $product_title = $request->param('proname'); // 标题
                                                     // var_dump($request);die;
        $proids = isset($_COOKIE['proids']) ? $_COOKIE['proids'] : '';
        // var_dump($proids);die;
        $rr=$this->getModel('ProductClass')->where(['sid'=>['=','0']])->fetchAll('cid,pname');
        $res = '';
        foreach ($rr as $key => $value) {
            $c = '-' . $value->cid . '-';
            // 判断所属类别 添加默认标签
            if ($product_class == $c) {
                $res .= '<option selected="selected" value="' . $c . '">' . $value->pname . '</option>';
            } else {
                $res .= '<option  value="' . $c . '">' . $value->pname . '</option>';
            }
            // 循环第一层
            $r_e=$this->getModel('ProductClass')->where(['sid'=>['=',$value->cid]])->fetchAll('cid,pname');
            if ($r_e) {
                $hx = '-----';
                foreach ($r_e as $ke => $ve) {
                    $cone = $c . $ve->cid . '-';
                    // 判断所属类别 添加默认标签
                    if ($product_class == $cone) {
                        $res .= '<option selected="selected" value="' . $cone . '">' . $hx . $ve->pname . '</option>';
                    } else {
                        $res .= '<option  value="' . $cone . '">' . $hx . $ve->pname . '</option>';
                    }
                    // 循环第二层
                    $r_t=$this->getModel('ProductClass')->where(['sid'=>['=',$ve->cid]])->fetchAll('cid,pname');
                    if ($r_t) {
                        $hxe = $hx . '-----';
                        foreach ($r_t as $k => $v) {
                            $ctow = $cone . $v->cid . '-';
                            // 判断所属类别 添加默认标签
                            if ($product_class == $ctow) {
                                $res .= '<option selected="selected" value="' . $ctow . '">' . $hxe . $v->pname . '</option>';
                            } else {
                                $res .= '<option  value="' . $ctow . '">' . $hxe . $v->pname . '</option>';
                            }
                        }
                    }
                }
            }
        }
        $arr = [];
        $condition = ' 1=1 ';
        if ($product_class != '') {
            $condition .= " and a.product_class like '%$product_class%' ";
        }
        
        if ($product_title != '') {
            $condition .= " and a.product_title like '%$product_title%' ";
        }
        $r=$this->getModel('ProductList')->alias('a')->where($condition)->field('a.id,a.product_title,a.imgurl,product_class')->order(['desc'=>'desc','a.sort'=>'desc'])->select();
        $list = [];
        $status_num = 0;
        foreach ($r as $key => $value) {
            $pid = $value->id;
            $class = $value->product_class;
            // $num = $value -> num;
            $typestr = trim($class, '-');
            $typeArr = explode('-', $typestr);
            // 取数组最后一个元素 并查询分类名称
            $cid = end($typeArr);
            $r_p=$this->getModel('ProductClass')->where(['cid'=>['=',$cid]])->fetchAll('pname');
            if ($r_p) {
                $pname = $r_p['0']->pname;
            } else {
                $pname = '顶级';
            }
            foreach ($value as $k => $v) {
                $arr[$k] = $v;
            }
            $arr['pname'] = $pname;
            
            $list[$key] = (object) $arr;
        }
        
        // 查询系统参数
        $img=$this->getUploadImg();
        foreach ($list as $ke => $ve) {
            $list[$ke]->image = $img . $ve->imgurl;
        }     
        // print_r($list);die;
        
        $this->assign("arr", $list);
        $this->assign("class", $res);
        
        echo json_encode(array(
                                'code' => 1
        ));
        return $this->fetch('', [], [
                                        '__moduleurl__' => $this->module_url
        ]);
    }

    private function do_configs($request)
    {
        $retype = intval(trim($request->param('retype')));
        $res = $this->getModel('GroupConfig')->saveAll(['refunmoney'=>$retype],"1=1");
        if ($res >= 0) {
            echo json_encode(array(
                                    'code' => 1
            ));
            exit();
        }
    }

    private function do_setpro($request)
    {
        $attr = $request->param('attr');
        
        $gprice = (array) json_decode($request->param('gprice'));
        
        $mprice = (array) json_decode($request->param('mprice'));
        
        $prod = Session::get('prod');
        
        $game = Cache::get('set');
        
        $num = mt_rand(100, 100000);
        
        $resbuy = $this->getModel('GroupBuy')->insert([
                                                            'groupname' => $game['groupname'],'man_num' => $game['peoplenum'],'time_over' => $game['overtime'],'starttime' => $game['starttime']
            ,'endtime' => $game['endtime'],'groupnum' => $game['groupnum'],'productnum' => $game['productnum'],'status' => $num,'overtype' => $game['radio']
        ]);
        
        $newprod = array();
        
        foreach ($prod as $k => $v) {
            
            $v = (array) $v;
            
            $ppid = $v['id'];
            
            // 先给值 后修改 覆盖
            
            $v['gprice'] = $v['price'];
            
            $v['mprice'] = $v['price'];
            
            // 拼团价格修改
            
            if (! empty($gprice)) {
                
                foreach ($gprice as $key => $value) {
                    
                    if ($ppid == $key) {
                        
                        $v['gprice'] = $value;
                        
                        $v['mprice'] = $value; // 团长价格改变
                    }
                }
            }        
            // 团长价格修改
            
            if (! empty($mprice)) {
                
                foreach ($mprice as $key => $value) {
                    
                    if ($ppid == $key) {
                        
                        $v['mprice'] = $value;
                    }
                }
            }
            
            $newprod[($v['id'])] = (object) $v;
        }
        
        // var_dump($newprod);die;
        foreach ($newprod as $k => $v) {
            
            $respro = $this->getModel('GroupProduct')->insert([
                                                                    'attr_id' => $v->id,'group_id' => $num,'product_id' => $v->pid,'group_price' => $v->gprice,'member_price' => $v->mprice
            ]);
        }      
        if ($resbuy > 0 && $respro > 0) {
            
            echo json_encode(array(
                                    'code' => 1
            ));
            exit();
        }
    }

    public function setpro(Request $request)
    {
       $request->method()=='post'&&$this->do_setpro($request);

        if (isset($request['from']) && $request['from'] == 'pro') {
            // var_dump($request['from']);die;          
            $str = Cookie::get('proids');        
            $str = substr($str, 1, - 1);          
            Session::set('susu', $str);           
            echo json_encode(array(
                                    'code' => 1
            ));
            exit();
        } else if (isset($request['from']) && $request['from'] == 'attr') {          
            $str = Session::get('susu');          
            // $game = Session::get('zhou');
            // var_dump($game);
            $arr = array();
            $idarr = explode(',', $str);
            // 查询系统参数
            
           $img=$this->getUploadImg();
            
            //$sql = 'select c.*,l.product_title from lkt_configure as c left join lkt_product_list as l on c.pid=l.id where c.pid in (' . $str . ') order by c.pid';           
            $res=$this->getModel('Configure')->alias('c')->join("product_list l","c.pid=l.id",'left')
            ->where("c.pid in(".$str.")")->fetchOrder("c.pid","c.*,l.product_title");
            // var_dump($res);die;
            foreach ($res as $key => $value) {
                
                // if($value -> pid == $k){
                // $value -> classname = $v;
                // }
                $value->image = $img . $value->img;
                $arr[] = $value;
            }
            
            // $arr_id = array();
            // foreach ($arr as $k => $v) {
            // $arr_id[] = $v -> id;
            // }
            Session::set('prod', $arr);
            $this->assign("set",Cache::get('set'));
            $this->assign("arr", $res);
            
            return $this->fetch('', [], [
                                            '__moduleurl__' => $this->module_url
            ]);
        }
    }
    private function setgroupmsg($request) {
        
        
        $id = addslashes(trim($request->param('id')));
        
        $res=$this->getModel('GroupBuy')->where(['status'=>['=',$id]])->fetchAll('*');
        
        $res = $res[0];
        list($hour,$minute) = explode(':', $res -> time_over);
        $res -> hour = $hour;
        $res -> minute = $minute;
        $res -> starttime = date('Y-m-d H:i:s',$res -> starttime);
        $res -> endtime = date('Y-m-d H:i:s',$res -> endtime);
        
        $this->assign("list",$res);
    }
    
    private function msgsubmit($request) {
        
        
        $id = addslashes(trim($request->param('id')));
        $groupname = addslashes(trim($request->param('groupname')));
        $peoplenum = addslashes(trim($request->param('peoplenum')));
        $timehour = addslashes(trim($request->param('timehour')));
        $timeminite = addslashes(trim($request->param('timeminite')));
        $starttime = addslashes(trim($request->param('starttime')));
        $overtime = addslashes(trim($request->param('overtime')));
        $groupnum = addslashes(trim($request->param('groupnum')));
        $productnum = addslashes(trim($request->param('productnum')));
        $otype = addslashes(trim($request->param('otype')));
        if($overtime == '0') $overtime = date('Y-m-d H:i:s',strtotime('+1year'));
        $grouptime = $timehour.':'.$timeminite;
        $starttime = strtotime($starttime);
        $overtime = strtotime($overtime);
        
        $res=$this->getModel('GroupBuy')->saveAll(['groupname'=>$groupname,'man_num'=>$peoplenum,'time_over'=>$grouptime,'starttime'=>$starttime,'endtime'=>$overtime,'groupnum'=>$groupnum,'productnum'=>$productnum,'overtype'=>$otype],['id'=>['=',$id]]);
        
        if($res >= 0){
            echo json_encode(array('code' => 1));exit;
        }
    }
    
    private function modifypro($request) {
        
        
        $gprice = (array)json_decode($request->param('gprice'));
        $mprice = (array)json_decode($request->param('mprice'));
        
        if(!empty($gprice)){
            foreach ($gprice as $k => $v) {
                $gres=$this->getModel('GroupProduct')->saveAll(['group_price'=>$v],['id'=>['=',$k]]);
            }
            if($gres >= 0){
                $gcode = 1;
            }
        }else{
            $gcode = 1;
        }
        if(!empty($mprice)){
            foreach ($mprice as $k => $v) {
                $mres=$this->getModel('GroupProduct')->saveAll(['member_price'=>$v],['id'=>['=',$k]]);
            }
            if($mres >= 0){
                $mcode = 1;
            }
        }else{
            $mcode = 1;
        }
        echo json_encode(array('gcode' => $gcode,'mcode' => $mcode));exit;
    }
    
    private function delpro($request) {
                
        $id = intval($request->param('id'));
        
        $res=$this->getModel('GroupProduct')->delete($id,'id');
        
        if($res > 0){
            echo json_encode(array('code' => 1));exit;
        }
        
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