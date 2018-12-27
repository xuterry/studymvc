<?php
namespace app\admin\controller;
use core\Session;
use core\Cookie;
use core\Controller;
use core\Request;
use core\Module;
use core\Db;
class Login extends Controller
{
    protected $module;
    protected $module_rul;
    function __construct()
    {
        $this->module=Module::get_module();
        $this->module_url='/'.$this->module;
        //采用smarty模板
       $this->config['type']='smarty';
        
    }
    function index(Request $req)
    {
        $m = $req->params("m");
        if($m == 'verify_num'){
            $this->verify_num();
            exit;
        }
        if (! empty(Session::get('login_err'))) {
            Session::clear();
            if (Session::get('login_err') > 5)
                exit('login err');
           // echo 'error' . Session::get('login_err');
        }
        
        $referer = $req->referer;
        if (empty($referer) || strpos($referer, $req->url) > 0)
            $referer = $this->module_url.'/index';
        Session::has('admin_id')&&$this->redirect($referer);
                
        $admin_pw = md5($req->post('pwd'));
        $admin_login=addslashes(trim(strtolower($req->post('login'))));
       // empty($admin_pw) && $admin_pw = input("get.pw");
        //exit($admin_login);
        if (! empty($admin_pw)&&!empty($admin_login)) {
           // $sql = "select id,name,password,admin_type,permission,status from lkt_admin where " . "name = '$name' and password = '$password'";
            $db_config=include(APP_PATH.DS.$this->module.DS.'config.php');
            $db_config['result_type']=2;
            $conn=Db::connect($db_config);
            $result=$conn->name('admin')->where(['name'=>['=',$admin_login],'password'=>['=',$admin_pw]])->select();
           
            if ($result) {
                //dump($result);exit();
               Session::delete('login_err');
                
                $admin_id = $result[0]['name'];
                $admin_type = $result[0]['admin_type'];
                $admin_permission = unserialize($result[0]['permission']);
                $status = $result[0]['status'];
                if($status == 1){
                    exit($admin_id.'状态错误');
                }
                // 生成session_id
                $access_token = session_id();
                //修改token
                $ip = $req->ip();
                $aid = $result[0]['id'];
                
                $conn->name('admin')->where('id','=',$aid)->update(['token'=>$access_token,'ip'=>$ip]);
                $conn->name('record')->insert(['user_id'=>$aid,'event'=>'登录成功']);
                $this->insertAdminRescord($conn, $admin_id, '登录成功', 0);
                
                Session::set('admin_id', $admin_id);
                Cookie::set('admin_id', $admin_id, 3600 * 24);
                Cookie::set('admin_pw', $admin_pw, 3600 * 24);
                Session::set('login_time',time());
                $_SESSION['auth']=1;
                // dump($ref);exit();
              // Session::set('login_err', 0);

                if (! input("get.pass"))
                    $this->redirect($referer);
                 //   $this->success('登录成功', $referer);
            } else {
               // $sql="insert into lkt_record (user_id,event) values ('$admin_login','登录密码错误') ";
                $conn->name('record')->insert(['user_id'=>$admin_login,'event'=>'登录失败']);
                $conn->close();
                Session::has('login_err') ? Session::set('login_err', (Session::get('login_err') + 1)) : Session::set('login_err', 1);
                $this->error('登录失败,还有'.(5-Session::get('login_err')).'次机会',$this->module_url.'/login');
            }
        }else{
        $this->assign('module_url', $this->module_url);
        return $this->fetch('',[],['__moduleurl__'=>$this->module_url]);
    }
    
    }
    
    public function logout()
    {
        $aid=Session::get('admin_id');
        if(!empty($aid)){
        Cookie::delete('admin_id');
        Cookie::delete('admin_pw');
        $db_config=include(APP_PATH.DS.$this->module.DS.'config.php');
        $conn=Db::connect($db_config);
        //dump($conn->getFields('lkt_admin_record'));exit();
        $conn->name('record')->insert(['user_id'=>$aid,'event'=>'安全退出']);
        $this->insertAdminRescord($conn, $aid, '安全退出', 0);
        $conn->close();
        }
        Session::clear('',true);     
        $this->redirect($this->module_url.'/login');
               
    }
    protected function insertAdminRescord($conn,$admin_name,$event,$type)
    {
        $event = $admin_name . $event;
      //  $sql = "insert into lkt_admin_record(admin_name,event,type,add_date) values ('$admin_name','$event','$type',CURRENT_TIMESTAMP)";
     //   $conn->execute($sql);
      $conn->name('admin_record')->insert(['admin_name'=>$admin_name,'event'=>$event,'type'=>$type]);
        return true;
    }
    public function verify_num()
    {
        $db_config=include(APP_PATH.DS.$this->module.DS.'config.php');
        $conn=Db::connect($db_config);
        $verify_num = strtolower(input("get.user_num"));
        $time = date("Y-m-d H:i:s");
        //$sql = "select user_id,email from lkt_customer where user_id = '$verify_num' and status =0 and end_date > '$time'";
        $rs=$conn->name('customer')->field(['user_id','email'])->where(['user_id'=>['=',$verify_num],'status'=>['=',0],'end_date'=>['>',$time]])->select();
        if(empty($rs)){
            echo json_encode(array('status'=>0,'err'=>'信息错误!'));
            $conn->close();
            exit;
        }else{
            echo json_encode(array('status'=>1,'succ'=>md5($rs[0]->email)));
            $conn->close();
            exit;
        }
        exit;
    }
}

