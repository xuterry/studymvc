<?php
namespace app\admin\controller;
use core\Request;
use core\Session; 

class Finance extends Index
{

    function __construct()
    {
        parent::__construct();
    }

    public function Index(Request $request)
    {
        $admin_id = Session::get('admin_id');
        
        $name = addslashes(trim($request->param('name'))); // 用户名
        $Bank_card_number = addslashes(trim($request->param('Bank_card_number'))); // 卡号
        $Cardholder = addslashes(trim($request->param('Cardholder'))); // 持卡人姓名
        
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
        
        $condition = ' a.status = 0 ';
         
        if ($name != '') {
            $condition .= " and a.name = '$name' ";
        }
        if ($Bank_card_number != '') {
            $condition .= " and b.Bank_card_number like '%$Bank_card_number%' ";
        }
        if ($Cardholder != '') {
            $condition .= " and b.Cardholder = '$Cardholder' ";
        }
        $list=[];
        $pages_show=''; 
        if ($pageto == 'all') { // 导出全部
            $this->recordAdmin($admin_id, ' 导出提现待审核列表 ', 4);
            $r1 = $this->getModel('withdraw')->alias('t')
            ->where('status','=',0)->group('user_id')->fetchOrder(['t'=>'desc'],'user_id,max(add_date) as t');
        } else if ($pageto == 'ne') { // 导出本页
            $this->recordAdmin($admin_id, ' 导出提现待审核列表第 ' . $page . '页' . $pagesize . '条数据', 4);
        } 
        if ($pageto != 'all'){
        $r1 = $this->getModel('withdraw')->alias('t')->field('user_id,max(add_date) as t')
        ->where('status','=',0)->group('user_id')->order('t','desc')
        ->paginator($pagesize,$this->getUrlConfig($request->url));
            $pages_show = $r1->render();      
        }
            foreach ($r1 as $k => $v) {
                $user_id = $v->user_id;
                $rr=$this->getModel('Withdraw')->alias('a')->join('user_bank_card b','a.Bank_id=b.id','left')->join('user c','a.user_id=c.user_id','right')->where($condition)->fetchOrder(['a.add_date'=>'desc'],'a.id,a.user_id,a.name,a.add_date,a.money,a.s_charge,a.mobile,a.status,b.Cardholder,b.Bank_name,b.Bank_card_number,b.mobile,c.source',"1");
                if ($rr) {
                    $list[] = $rr[0];
                }
            }            
        $this->assign("name", $name);
        $this->assign("Bank_card_number", $Bank_card_number);
        $this->assign("Cardholder", $Cardholder);
        $this->assign("list", $list);
        $this->assign('pageto', $pageto);
        $this->assign('pages_show', $pages_show);
        $this->assign('pagesize', $pagesize);
        if ($pageto != '') {
            $this->pagetoExcel();
        }
        return $this->fetch('', [], [
                                        '__moduleurl__' => $this->module_url
        ]);
    }

    private function do_configs($request)
    {
        // 接收信息
        $min_cz = addslashes(trim($request->param('min_cz'))); // 最小充值金额
        $min_amount = addslashes(trim($request->param('min_amount'))); // 最小提现金额
        $max_amount = addslashes($request->param('max_amount')); // 最大提现金额
        $service_charge = addslashes($request->param('service_charge')); // 手续费
        $unit = addslashes($request->param('unit')); // 单位
        $multiple = trim($request->param('multiple')); // 提现倍数
        $transfer_multiple = trim($request->param('transfer_multiple')); // 转账倍数
        $cz_multiple = trim($request->param('cz_multiple')); // 充值倍数
        
        if (is_numeric($min_cz) == false) {
            $this->error('最小充值金额请输入数字！', '');
        } else {
            if ($min_cz <= 0) {
                $this->error('最小充值金额不能小于等于0！', '');
            }
        }
        
        if (is_numeric($min_amount) == false) {
            $this->error('最小提现金额请输入数字！', '');
        } else {
            if ($min_amount <= 0) {
                $this->error('最小提现金额不能小于等于0！', '');
            }
        }
        if (is_numeric($max_amount) == false) {
            $this->error('最大提现金额请输入数字！', '');
        } else {
            if ($max_amount <= 0) {
                $this->error('最大提现金额不能小于等于0！', '');
            }
        }
        if ($min_amount <= $service_charge) {
            $this->error('最小提现金额不能小于等于手续费！', '');
        } else if ($min_amount >= $max_amount) {
            $this->error('最小提现金额不能大于等于最大提现金额！', '');
        } else {
            $r = $this->getModel('FinanceConfig')
                ->where([
                            'id' => [
                                        '=','1'
                            ]
            ])
                ->fetchAll('*');
            if ($r) {
                $r_1 = $this->getModel('FinanceConfig')->saveAll([
                                                                    'min_cz' => $min_cz,'min_amount' => $min_amount,'max_amount' => $max_amount,'multiple' => $multiple,'transfer_multiple' => $transfer_multiple,'service_charge' => $service_charge,'unit' => $unit,'cz_multiple' => $cz_multiple,'modify_date' => nowDate()
                ], [
                        'id' => [
                                    '=','1'
                        ]
                ]);
                if ($r_1 == false) {
                    $this->error('未知原因，参数修改失败！', $this->module_url . "/finance/configs");
                } else {
                    $this->success('参数修改成功！', $this->module_url . "/finance/configs");
                }
            } else {
                $r_1 = $this->getModel('FinanceConfig')->insert([
                                                                    'min_cz' => $min_cz,'min_amount' => $min_amount,'max_amount' => $max_amount,'service_charge' => $service_charge,'unit' => $unit,'modify_date' => nowDate(),'multiple' => $multiple,'transfer_multiple' => $transfer_multiple,'cz_multiple' => $cz_multiple
                ]);
                if ($r_1 == false) {
                    $this->error('未知原因，参数添加失败！', $this->module_url . "/finance/configs");
                } else {
                    $this->success('参数添加成功！', $this->module_url . "/finance/configs");
                }
            }
        }
        
        return;
    }

    public function configs(Request $request)
    {
        $request->method()=='post'&&$this->do_configs($request);      
        $r = $this->getModel('FinanceConfig')->get('1', 'id ');
        if ($r) {
            $min_cz = $r[0]->min_cz;
            $min_amount = $r[0]->min_amount;
            $max_amount = $r[0]->max_amount;
            $service_charge = $r[0]->service_charge;
            $unit = $r[0]->unit;
            $multiple = $r[0]->multiple;
            $transfer_multiple = $r[0]->transfer_multiple;
            $cz_multiple = $r[0]->cz_multiple;
        } else {
            $min_cz = 50;
            $min_amount = 50;
            $max_amount = 100;
            $service_charge = '0.05';
            $unit = '元';
            $multiple = 0;
            $transfer_multiple = 0;
            $cz_multiple = 100;
        }
        $this->assign('min_cz', $min_cz);
        $this->assign('cz_multiple', $cz_multiple);
        $this->assign('min_amount', $min_amount);
        $this->assign('max_amount', $max_amount);
        $this->assign('service_charge', $service_charge);
        $this->assign('multiple', $multiple);
        $this->assign('transfer_multiple', $transfer_multiple);
        $this->assign('unit', $unit);
        return $this->fetch('', [], [
                                        '__moduleurl__' => $this->module_url
        ]);
    }

    public function del(Request $request)
    {
        $admin_id = Session::get('admin_id');
        
        // 接收信息
        $id = intval($request->param('id')); // 提现id
        $m = intval($request->param('m')); // 参数
        $user_id = $request->param('user_id'); // 用户id
        $money = $request->param('money'); // 实际提款金额
        $s_charge = $request->param('s_charge'); // 手续费
        $zmoney = $money + $s_charge;
        $r = $this->getModel('User')->get($user_id, 'user_id');
        empty($r)&&exit;
        $user_id = $r[0]->user_id; // 用户id
        $ymoney = $r[0]->money; // 原有金额
                                // 根据提现id，修改状态信息
        if ($m == 1) {
            $event = $user_id . "提现了" . $zmoney;
            // 在操作列表里添加一条数据
            $r=$this->getModel('Record')->insert(['user_id'=>$user_id,'money'=>$zmoney,'oldmoney'=>$ymoney,'event'=>$event,'type'=>21]);
            
            // 根据id,修改提现列表中数据的状态
            $update_rs=$this->getModel('Withdraw')->saveAll(['status'=>1],['id'=>['=',$id]]);
            
            $this->recordAdmin($admin_id, ' 通过id为 ' . $id . ' 的提现信息', 6);
            echo 1;
            return;
        } else {
            // 根据微信昵称,修改会员列表里的金额
            $r=$this->getModel('User')->where("user_id = '".$user_id."'")->inc('money',$zmoney)->update();
            
            $event = $user_id . "提现" . $zmoney . "找拒绝";
            // 在操作列表里添加一条数据
            $r=$this->getModel('Record')->insert(['user_id'=>$user_id,'money'=>$zmoney,'oldmoney'=>$ymoney,'event'=>$event,'type'=>22]);
            
            $update_rs=$this->getModel('Withdraw')->saveAll(['status'=>2],['id'=>['=',$id]]);
            
            $this->recordAdmin($admin_id, ' 拒绝id为 ' . $id . ' 的提现信息', 6);
            
            echo 1;
            return;
        }
    }

    public function jifen(Request $request)
    {
        $admin_id = Session::get('admin_id');
        
        $user_name = addslashes(trim($request->param('user_name'))); // 用户名
        $mobile = addslashes(trim($request->param('mobile'))); // 手机号码
        $type = $request->param('otype');
        $starttime = $request->param('startdate'); // 开始时间
        $group_end_time = $request->param('enddate'); // 结束时间
        
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
        
        $condition = ' 1=1 ';
        if ($user_name) {
            $condition .= " and b.user_name = '$user_name' ";
        }
        if ($mobile) {
            $condition .= " and b.mobile = '$mobile' ";
        }
        if ($type == '') {
            $type = 'all';
        } else {
            if ($type != 'all') {
                $condition .= " and a.type = '$type' ";
            }
        }
        
        $list = array();

            if ($pageto == 'all') { // 导出全部
                $r1=$this->getModel('SignRecord')->alias('a')->join('user b','a.user_id=b.user_id','left')
                ->where($condition)->group('user_id')
                ->order(['t'=>'desc'])->field('a.user_id,max(sign_time) as t')->select();           
                $this->recordAdmin($admin_id, ' 导出积分列表 ', 4);
            } else if ($pageto == 'ne') { // 导出本页
                $this->recordAdmin($admin_id, ' 导出积分列表第 ' . $page . '页' . $pagesize . '条数据', 4);
            } else { // 不导出
            }
            if($pageto!='all'){
                $r1=$this->getModel('SignRecord')->alias('a')->join('user b','a.user_id=b.user_id','left')->where($condition)->group('a.user_id')->order(['t'=>'desc'])
                ->field('a.user_id,max(sign_time) as t')
                ->paginator($pagesize,$this->getUrlConfig($request->url));
                $pages_show=$r1->render();
            }
            foreach ($r1 as $k => $v) {
                $user_id = $v->user_id;
                $rr=$this->getModel('SignRecord')->alias('a')->join('user b','a.user_id=b.user_id','left')->where($condition." and a.user_id = '".$user_id."'")->fetchOrder(['a.sign_time'=>'desc'],'a.*,b.user_name,b.mobile,b.source',"1");
                if ($rr) {
                    foreach ($rr as $key => $value) {
                        $user_id = $value->user_id;
                        $r021 = $this->getModel('userDistribution')->alias('a')->join('distribution_grade b','a.level=b.id')
                        ->fetchWhere("user_id ='".$user_id."'",'b.name');
                        if (! empty($r021)) {
                            $value->typename = $r021[0]->name;
                        } else {
                            $value->typename = '';
                        }
                    }
                    $list[] = $value;
                }
            
        }
        
        $this->assign('pageto', $pageto);
        $this->assign("user_name", $user_name);
        $this->assign("mobile", $mobile);
        $this->assign("type", $type);
        $this->assign("list", $list);
        // $this->assign("starttime",$starttime);
        // $this->assign("group_end_time",$group_end_time);
        
        $this->assign('pages_show', $pages_show);
        $this->assign('pagesize', $pagesize);
        !empty($pageto)&&$this->pagetoExcel('excel_jifen');
        return $this->fetch('', [], [
            '__moduleurl__' => $this->module_url
        ]);
    }

    public function jifen_see(Request $request)
    {
        $admin_id = Session::get('admin_id');
        
        $user_id = $request->param('user_id'); // 用户id
                                               // 导出
        $pagesize = $request->param('pagesize');
        $pagesize = $pagesize ? $pagesize : '10';
        // 每页显示多少条数据
        $page = $request->param('page');
                
        $r=$this->getModel('SignRecord')->alias('a')->join('user b','a.user_id=b.user_id','left')
        ->where(['a.user_id'=>['=',$user_id]])->order(['a.sign_time'=>'desc'])
        ->field('a.*,b.user_name,b.mobile,b.source')->paginator($pagesize,$this->getUrlConfig($request->url));
        $pages_show=$r->render();
        if($r){
            foreach ($r as $key => $value) {
                $user_id = $value->user_id;
                $r2 = $this->getModel('userDistribution')->alias('a')->join('distribution_grade b','a.level=b.id')
                ->fetchWhere("user_id='".$user_id."'",'b.name');
                if (! empty($r2)) {
                    $value->typename = $r2[0]->name;
                } else {
                    $value->typename = '';
                }
            }
        }
        
        $this->assign("list", $r);
        $this->assign('pages_show', $pages_show);
        return $this->fetch('', [], [
            '__moduleurl__' => $this->module_url
        ]);
    }

    public function lists1(Request $request)
    {
        $admin_id = Session::get('admin_id');
        
        $name = addslashes(trim($request->param('name'))); // 用户名
        $Bank_card_number = addslashes(trim($request->param('Bank_card_number'))); // 卡号
        $Cardholder = addslashes(trim($request->param('Cardholder'))); // 持卡人姓名
        
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
        
        $condition = 'a.status = 2';
        if ($name) {
            $condition .= " and a.name = '$name' ";
        }
        if ($Bank_card_number != '') {
            $condition .= " and b.Bank_card_number like '%$Bank_card_number%' ";
        }
        if ($Cardholder != '') {
            $condition .= " and b.Cardholder = '$Cardholder' ";
        }
        $list=[];
        $pages_show='';
        if ($pageto == 'all') { // 导出全部
            $this->recordAdmin($admin_id, ' 导出提现待审核列表 ', 4);
            $r1 = $this->getModel('withdraw')->alias('t')
            ->where('status','=',2)->group('user_id')->fetchOrder(['t'=>'desc'],'user_id,max(add_date) as t');
        } else if ($pageto == 'ne') { // 导出本页
            $this->recordAdmin($admin_id, ' 导出提现待审核列表第 ' . $page . '页' . $pagesize . '条数据', 4);
        }
        if ($pageto != 'all'){
        $r1 = $this->getModel('withdraw')->alias('t')->field('user_id,max(add_date) as t')
        ->where('status','=',2)->group('user_id')->order('t','desc')
        ->paginator($pagesize,$this->getUrlConfig($request->url));
        $pages_show = $r1->render();
        }
        foreach ($r1 as $k => $v) {
            $user_id = $v->user_id;
            $rr=$this->getModel('Withdraw')->alias('a')->join('user_bank_card b','a.Bank_id=b.id','left')->join('user c','a.user_id=c.user_id','right')->where($condition)->fetchOrder(['a.add_date'=>'desc'],'a.id,a.user_id,a.name,a.add_date,a.money,a.s_charge,a.mobile,a.status,b.Cardholder,b.Bank_name,b.Bank_card_number,b.mobile,c.source',"1");
            if ($rr) {
                $list[] = $rr[0];
            }
        }
        $this->assign("name", $name);
        $this->assign("Bank_card_number", $Bank_card_number);
        $this->assign("Cardholder", $Cardholder);
        $this->assign("list", $list);
        $this->assign('pageto', $pageto);
        $this->assign('pages_show', $pages_show);
        $this->assign('pagesize', $pagesize);
        if ($pageto != '') {
            $this->pagetoExcel();
        }
        return $this->fetch('', [], [
                                        '__moduleurl__' => $this->module_url
        ]);
    }

    public function lists(Request $request)
    {
        $admin_id = Session::get('admin_id');      
        $name = addslashes(trim($request->param('name'))); // 用户名
        $Bank_card_number = addslashes(trim($request->param('Bank_card_number'))); // 卡号
        $Cardholder = addslashes(trim($request->param('Cardholder'))); // 持卡人姓名
        
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
        
        $condition = 'a.status = 1';
        if ($name) {
            $condition .= " and a.name = '$name' ";
        }
        if ($Bank_card_number != '') {
            $condition .= " and b.Bank_card_number like '%$Bank_card_number%' ";
        }
        if ($Cardholder != '') {
            $condition .= " and b.Cardholder = '$Cardholder' ";
        }
        $list=[];
        $pages_show='';
        if ($pageto == 'all') { // 导出全部
            $this->recordAdmin($admin_id, ' 导出提现待审核列表 ', 4);
            $r1 = $this->getModel('withdraw')->alias('t')
            ->where('status','=',1)->group('user_id')->fetchOrder(['t'=>'desc'],'user_id,max(add_date) as t');
        } else if ($pageto == 'ne') { // 导出本页
            $this->recordAdmin($admin_id, ' 导出提现待审核列表第 ' . $page . '页' . $pagesize . '条数据', 4);
        }
        $r1 = $this->getModel('withdraw')->alias('t')->field('user_id,max(add_date) as t')
        ->where('status','=',1)->group('user_id')->order('t','desc')
        ->paginator($pagesize,$this->getUrlConfig($request->url));
        $pages_show = $r1->render();
        foreach ($r1 as $k => $v) {
            $user_id = $v->user_id;
            $rr=$this->getModel('Withdraw')->alias('a')->join('user_bank_card b','a.Bank_id=b.id','left')->join('user c','a.user_id=c.user_id','right')->where($condition)->fetchOrder(['a.add_date'=>'desc'],'a.id,a.user_id,a.name,a.add_date,a.money,a.s_charge,a.mobile,a.status,b.Cardholder,b.Bank_name,b.Bank_card_number,b.mobile,c.source',"1");
            if ($rr) {
                $list[] = $rr[0];
            }
        }
        $this->assign("name", $name);
        $this->assign("Bank_card_number", $Bank_card_number);
        $this->assign("Cardholder", $Cardholder);
        $this->assign("list", $list);
        $this->assign('pageto', $pageto);
        $this->assign('pages_show', $pages_show);
        $this->assign('pagesize', $pagesize);
        if ($pageto != '') {
            $this->pagetoExcel();
        }
        return $this->fetch('', [], [
                                        '__moduleurl__' => $this->module_url
        ]);
    }

    public function recharge(Request $request)
    {
        $admin_id = Session::get('admin_id');
        
        $zhanghao = addslashes(trim($request->param('zhanghao'))); // 账号
        $mobile = addslashes(trim($request->param('mobile'))); // 手机号码
        $user_name = addslashes(trim($request->param('user_name'))); // 用户昵称
        
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
        
        $condition = '(a.type = 1 or a.type = 14)';
        if ($zhanghao != '') {
            $condition .= " and b.zhanghao = '$zhanghao' ";
        }
        if ($mobile != '') {
            $condition .= " and b.mobile = '$mobile' ";
        }
        if ($user_name != '') {
            $condition .= " and b.user_name = '$user_name' ";
        }

      
        if ($pageto == 'all') { // 导出
            $list=$this->getModel('Record')->alias('a')->join('user b','a.user_id=b.user_id','LEFT')->where($condition)->fetchOrder(['a.add_date'=>'desc'],'a.*,b.user_name,b.mobile,b.source',"$start,$pagesize");
            
            $this->recordAdmin($admin_id, ' 导出充值列表 ', 4);
        } else if ($pageto == 'ne') { // 导出本页
            $list=$this->getModel('Record')->alias('a')->join('user b','a.user_id=b.user_id','LEFT')
            ->where($condition)->order(['a.add_date'=>'desc'])
            ->field('a.*,b.user_name,b.mobile,b.source')->paginator($pagesize,$this->getUrlConfig($request->url));
                       
            $this->recordAdmin($admin_id, ' 导出充值列表第 ' . $page . '页' . $pagesize . '条数据', 4);
        } else { // 不导出
                 // 根据用户id,查询充值记录和用户昵称
            $list=$this->getModel('Record')->alias('a')->join('user b','a.user_id=b.user_id','LEFT')->where($condition)
            ->order(['a.add_date'=>'desc'])
            ->field('a.*,b.user_name,b.mobile,b.source')
            ->paginator($pagesize,$this->getUrlConfig($request->url));
        }
        
        $pages_show = $pageto!='all'?$list->render():'';
        
        $this->assign("zhanghao", $zhanghao);
        $this->assign("mobile", $mobile);
        $this->assign("user_name", $user_name);
        $this->assign("list", $list);
        $this->assign('pageto', $pageto);
        $this->assign('pages_show', $pages_show);
        $this->assign('pagesize', $pagesize);
        if ($pageto =='all'||$pageto=='ne') {
              $this->pagetoExcel('r_excel');
        }
        return $this->fetch('', [], [
                                        '__moduleurl__' => $this->module_url
        ]);
    }

    public function see(Request $request)
    {
        $user_id = $request->param('user_id'); // 用户id      
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
        $r=$this->getModel('Withdraw')->alias('a')->join('user_bank_card b','a.Bank_id=b.id','left')
        ->join('user c','a.user_id=c.user_id','right')->where(['a.user_id'=>['=',$user_id]])->
        order(['a.add_date'=>'desc'])
        ->field('a.id,a.name,a.add_date,a.money,a.s_charge,a.mobile,a.status,b.Cardholder,b.Bank_name,b.Bank_card_number,b.mobile,c.source')
        ->paginator($pagesize,$this->getUrlConfig($request->url));
        
        $pages_show = $r->render();
        
        $this->assign("list", $r);
        $this->assign("pages_show", $pages_show);
        
        return $this->fetch('', [], [
                                        '__moduleurl__' => $this->module_url
        ]);
    }

    public function yue(Request $request)
    {
        $admin_id = Session::get('admin_id');
        
        $name = addslashes(trim($request->param('name'))); // 用户名
        $type = $request->param('otype');
        $starttime = $request->param('startdate'); // 开始时间
        $group_end_time = $request->param('enddate'); // 结束时间
        
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
        
        $condition = 'a.type !=0 and a.type !=6 and a.type !=7 and a.type !=8 and a.type !=9 and a.type !=10 and a.type !=15 and a.type !=16 and a.type !=17 and a.type !=18 ';
        if ($name) {
            $condition .= " and a.user_id = '$name' ";
        }
        if ($type && $type != 'all') {
            $condition .= " and a.type = '$type' ";
        }
        if ($starttime) {
            $condition .= " and a.add_date >= '$starttime' ";
        }
        if ($group_end_time) {
            $condition .= " and a.add_date <= '$group_end_time' ";
        }
        $list = array();
        
            if ($pageto == 'all') { // 导出全部
                $this->recordAdmin($admin_id, ' 导出余额列表 ', 4);
            } else if ($pageto == 'ne') { // 导出本页
                $this->recordAdmin($admin_id, ' 导出余额列表第 ' . $page . '页' . $pagesize . '条数据', 4);
            } else { // 不导出
            }
            if ($pageto == 'all')
                $r1=$this->getModel('Record')->alias('a')->join('user b','a.user_id=b.user_id','left')->where($condition)->group('a.user_id')
                ->fetchOrder(['t'=>'desc'],'a.user_id,max(a.add_date) as t');
            else{
            $r1=$this->getModel('Record')->alias('a')->join('user b','a.user_id=b.user_id','left')->where($condition)->group('a.user_id')
            ->order(['t'=>'desc'])
            ->field('a.user_id,max(a.add_date) as t')->paginator($pagesize,$this->getUrlConfig($request->url));
            $pages_show=$r1->render();
            }
            foreach ($r1 as $k => $v) {
                $user_id = $v->user_id;
                $rr=$this->getModel('Record')->alias('a')->join('user b','a.user_id=b.user_id','left')->where($condition." and a.user_id = '".$user_id."'")->fetchOrder(['a.add_date'=>'desc'],'a.*,b.user_name,b.mobile,b.source',"1");
                if ($rr) {
                    $list[] = $rr[0];
                }
            }
        empty($pages_show)&&$pages_show='';
                
        $this->assign("name", $name);
        $this->assign("type", $type);
        $this->assign("list", $list);
        $this->assign('pageto', $pageto);
        $this->assign("starttime", $starttime);
        $this->assign("group_end_time", $group_end_time);
        
        $this->assign('pages_show', $pages_show);
        $this->assign('pagesize', $pagesize);
        if ($pageto =='all'||$pageto=='ne') {
            $this->pagetoExcel('excel_yue');
        }
        return $this->fetch('', [], [
                                        '__moduleurl__' => $this->module_url
        ]);
    }

    public function yue_see(Request $request)
    {
        $user_id = $request->param('user_id'); // 用户id       
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
        $condition = 'a.type !=0 and a.type !=6 and a.type !=7 and a.type !=8 and a.type !=9 and a.type !=10 and a.type !=15 and a.type !=16 and a.type !=17 and a.type !=18 ';
        
        $r=$this->getModel('Record')->alias('a')->join('user b','a.user_id=b.user_id','left')
        ->where($condition)
         ->where(['a.user_id'=>['=',$user_id]])
        ->order(['a.add_date'=>'desc'])->field('a.*,b.user_name,b.mobile,b.source')->paginator($pagesize,$this->getUrlConfig($request->url));
        
        $url = $this->module_url . "/finance/yue_see?user_id=" . urlencode($user_id) . "&pagesize=" . urlencode($pagesize);
        
        $this->assign("list", $r);
        $this->assign('pages_show', $r->render());
        return $this->fetch('', [], [
                                        '__moduleurl__' => $this->module_url
        ]);
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