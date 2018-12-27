<?php
namespace app\admin\controller;

use core\Request;

class Bargain extends Index
{

    function __construct()
    {
        parent::__construct();
    }

    public function Index(Request $request)
    {
        $product_class = addslashes(trim($request->param('cid'))); // 分类名称
        $product_title = addslashes(trim($request->param('product_title'))); // 标题
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
        
        $condition = ' 1=1 ';
        if ($product_class != '') {
            $condition .= " and a.product_class like '%$product_class%' ";
        }
        
        if ($product_title != '') {
            $condition .= " and a.product_title like '%$product_title%' ";
        }
        $r=$this->getModel('ProductList')->alias('a')->join('configure c','a.id=c.pid','LEFT')
        ->where($condition."and a.status = '1'")
        ->fetchOrder(['sort'=>'asc'],'a.id,a.product_title,a.sort,a.add_date,c.id as sx_id,c.name,c.color,c.img,c.size,c.bargain_price,c.status,a.product_class,a.brand_id');
        $list = [];
        foreach ($r as $key => $value) {
            $class = $value->product_class;
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
        foreach ($list as $key01 => $value01) {
            if (! empty($value01->brand_id)) {
                $r01=$this->getModel('BrandClass')->where(['brand_id'=>['=',$value01->brand_id]])->fetchAll('brand_name');
                $list[$key01]->brand_name = $r01[0]->brand_name;
            }
        }
        $this->assign("product_title", $product_title);
        $this->assign("class", $res);
        $this->assign("list", $list);
        return $this->fetch('', [], [
                                        '__moduleurl__' => $this->module_url
        ]);
    }
    private function do_configs($request)
    {
        
        // 接收信息
        $plug_ins_id = intval($request->param('plug_ins_id'));
        $can_num = addslashes(trim($request->param('can_num'))); // 能砍的次数
        $help_num = addslashes(trim($request->param('help_num'))); // 每天最多帮别人砍的次数
        $parameter = addslashes($request->param('parameter')); // 每次砍价的参数
        $invalid_time = addslashes(trim($request->param('invalid_time'))); // 逾期失效时间
        if (is_numeric($can_num) == '') {
            $this->error('能砍的次数请输入数字!', '');
        }
        if ($can_num < 2) {
            $this->error('能砍的次数要大于2!', '');
        }
        if (is_numeric($help_num) == '') {
            $this->error('每天最多帮别人砍的次数请输入数字!', '');
        }
        if ($help_num < 1) {
            $this->error('每天最多帮别人砍的次数要大于0!', '');
        }
        if (is_numeric($parameter) == '') {
            $this->error('每次砍价的参数请输入数字!', '');
        }
        if ($parameter <= 0) {
            $this->error('每次砍价的参数不能小于等于0!', '');
        }
        if (is_numeric($invalid_time) == '') {
            $this->error('逾期失效时间请输入数字!', '');
        }
        if ($invalid_time < 0) {
            $this->error('逾期失效时间不能为负数!', '');
        }
        
        $r = $this->getModel('BargainConfig')
            ->where([
                        'plug_ins_id' => [
                                            '=',$plug_ins_id
                        ]
        ])
            ->fetchAll('*');
        if ($r) {
            $r_1 = $this->getModel('BargainConfig')->saveAll([
                                                                'can_num' => $can_num,'help_num' => $help_num,'parameter' => $parameter,'invalid_time' => $invalid_time,'add_time' => nowDate()
            ], [
                    'plug_ins_id' => [
                                        '=',$plug_ins_id
                    ]
            ]);
            if ($r_1 == false) {
                $this->error('未知原因，砍价免单设置修改失败！', $this->module_url . "/plug_ins");
            } else {
                $this->success('砍价免单设置修改成功！', $this->module_url . "/plug_ins");
            }
        } else {
            $r_1 = $this->getModel('BargainConfig')->insert([
                                                                'plug_ins_id' => $plug_ins_id,'can_num' => $can_num,'help_num' => $help_num,'parameter' => $parameter,'invalid_time' => $invalid_time,'add_time' => nowDate()
            ]);
            if ($r_1 == false) {
                $this->error('未知原因，砍价免单设置添加失败！', $this->module_url . "/plug_ins");
            } else {
                $this->success('砍价免单设置添加成功！', $this->module_url . "/plug_ins");
            }
        }
        return;
    }

    public function configs(Request $request)
    {
       $request->method()=='post'&&$this->do_configs($request);

        $plug_ins_id = intval($request->param("id")); // 插件id
        
        $r = $this->getModel('BargainConfig')->get($plug_ins_id, 'plug_ins_id ');
        if ($r) {
            $can_num = $r[0]->can_num; // 能砍的次数
            $help_num = $r[0]->help_num; // 每天最多帮别人砍的次数
            $parameter = $r[0]->parameter; // 每次砍价的参数
            $invalid_time = $r[0]->invalid_time; // 逾期失效时间
        } else {
            $can_num = 7;
            $help_num = 3;
            $parameter = 0.3;
            $invalid_time = 7;
        }
        $this->assign('plug_ins_id', $plug_ins_id);
        $this->assign('can_num', $can_num);
        $this->assign('help_num', $help_num);
        $this->assign('parameter', $parameter);
        $this->assign('invalid_time', $invalid_time);
        return $this->fetch('', [], [
                                        '__moduleurl__' => $this->module_url
        ]);
    }
    public function lists(Request $request)
    {
        $user_id = addslashes(trim($request->param('user_id'))); // 用户id  
        $condition = '1 = 1';
        if ($user_id != '') {
            $condition .= " and user_id like '$user_id'";
        }
        $r_1 = $this->getModel('BargainConfig')->get('1', 'id');
        $invalid_time = $r_1[0]->invalid_time; // 逾期时间
        $time = date("Y-m-d H:i:s");
        
        // 根据条件查询砍价记录表
        $r=$this->getModel('BargainRecord')->where($condition)->fetchAll('*');
        if ($r) {
            foreach ($r as $k => $v) {
                $bargain_id = $v->id; // 砍价记录id
                $s_id = $v->s_id; // 属性id
                $money = $v->money; // 砍掉的金额
                                    // 根据属性id、产品id,查询商品列表(商品id、商品名称、商品排序),属性表(属性名称、颜色、图片、规格、砍价金额)
                $rr=$this->getModel('ProductList')->alias('a')->join('configure c','a.id=c.pid','LEFT')->where(['c.id'=>['=',$s_id]])->fetchOrder(['sort'=>'asc'],'a.id,a.product_title,a.sort,c.name,c.color,c.img,c.size,c.bargain_price');
                $pid = $rr[0]->id;
                $product_title = $rr[0]->product_title; // 商品名称
                $bargain_price = $rr[0]->bargain_price; // 商品砍价金额
                $v->bargain_price = $rr[0]->bargain_price; // 商品砍价金额
                $v->product_name = $rr[0]->product_title . '[' . $rr[0]->name . '-' . $rr[0]->color . '-' . $rr[0]->size . ']'; // 拼接商品全名
                $v->end_time = date("Y-m-d H:i:s", strtotime("+$invalid_time day", strtotime($v->add_time))); // 逾期失效时间
                                                                                                            // 当当前时间大于等于失效时间 并且 (状态为砍价成功 或者为 砍价中)时,把砍价状态改为(逾期失效)
                if ($time >= $v->end_time && ($v->status = 1 || $v->status = 0)) {
                    $update_rs=$this->getModel('BargainRecord')->saveAll(['status'=>2],['id'=>['=',$bargain_id]]);
                }
                // 当砍价金额等于砍掉的金额时,修改砍价状态(砍价成功)
                if ($rr[0]->bargain_price == $money) {
                    $update_rs=$this->getModel('BargainRecord')->saveAll(['status'=>1],['id'=>['=',$bargain_id]]);
                }
                // 当用户填写了收货信息 并且 状态为砍价成功 并且 时间还没过期, 把状态改为生成订单,在订单表和订单详情里添加一条数据
                if ($v->name != '' && $v->tel != '' && $v->sheng != '' && $v->city != '' && $v->quyu != '' && $v->address != '' && $time < $v->end_time && $v->status = 1) {
                    // 修改状态为生成订单
                    $update_rs=$this->getModel('BargainRecord')->saveAll(['status'=>3],['id'=>['=',$bargain_id]]);
                    $sNo = $this->order_number(); // 生成订单号
                                                   
                    // 写入配置
                    $size = $rr[0]->name . ' ' . $rr[0]->color . ' ' . $rr[0]->size;
                    if ($rr[0]->name == $rr[0]->color && $rr[0]->color == $rr[0]->size) {
                        $size = $rr[0]->name;
                    }
                    // 在订单详情里添加一条数据
                    $insert_rs=$this->getModel('OrderDetails')
                    ->insert(['user_id'=>$user_id,'p_id'=>$pid,'p_name'=>$product_title,'p_price'=>$bargain_price,'num'=>1,'unit'=>'件','r_sNo'=>$sNo,'add_time'=>nowDate(),'r_status'=>0,'size'=>$size]);
                    // 在订单表里添加一条数据
                    $insert_rs=$this->getModel('Order')
                    ->insert(['user_id'=>$user_id,'name'=>$v->name,'mobile'=>$v->tel,'num'=>1,'z_price'=>$bargain_price,'sNo'=>$sNo,'sheng'=>$v->sheng,'shi'=>$v->city,'xian'=>$v->quyu,'address'=>$v->address,'pay'=>xxx,'add_time'=>nowDate(),'status'=>0,'coupon_id'=>0,'allow'=>0,'coupon_activity_name'=>'']);
                }
            }
        }
        
        $this->assign("user_id", $user_id);
        $this->assign("list", $r);
        return $this->fetch('', [], [
                                        '__moduleurl__' => $this->module_url
        ]);
    }
    private function order_number()
    {
        return date('Ymd', time()) . time() . rand(10, 99); // 18位
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