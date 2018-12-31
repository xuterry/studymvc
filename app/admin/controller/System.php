<?php
namespace app\admin\controller;
use core\Request;
use app\admin\model\Config;
use app\admin\model\Notice;

class System extends Index
{
    function __construct()
    {
        parent::__construct();
    }
    public function Index(Request $request)
    {
        if($request->method()=='post'){
            $this->do_Index($request);
            exit;
        }
       $config=new Config($this->db_config);
        $id = intval($request->param("id"));
        $r = $config->get(1,'id');
        $logo = $r[0]->logo; // 公司logo
        $company = $r[0]->company; // 公司名称
        $appid = $r[0]->appid; // 小程序id
        $appsecret = $r[0]->appsecret; // 小程序密钥
        $domain = $r[0]->domain; // 小程序域名
        $uploadImg_domain = $r[0]->uploadImg_domain; // 图片上传域名
        $uploadImg = $r[0]->uploadImg; // 图片上传位置
        $upload_file = $r[0]->upload_file; // 软件上传位置
        $ip = $r[0]->ip; // ip地址
        
        if($uploadImg == ''){
            $uploadImg = "/images";
        }
        empty($upload_file)&&$upload_file='/upfile';
        
        $this->assign('logo', isset($logo) ? $logo : '');
        $this->assign('company', isset($company) ? $company : '');
        $this->assign("appid", isset($appid) ? $appid : '');
        $this->assign('appsecret', isset($appsecret) ? $appsecret : '');
        $this->assign('domain', isset($domain) ? $domain : '');
        $this->assign('uploadImg_domain', isset($uploadImg_domain) ? $uploadImg_domain : '');
        $this->assign('uploadImg', isset($uploadImg) ? $uploadImg : '');
        $this->assign('upload_file', isset($upload_file) ? $upload_file : '');
        $this->assign('ip', isset($ip) ? $ip : '');
        return $this->fetch('',[],['__moduleurl__'=>$this->module_url]);
    }

    
    private function do_Index($request)
    {
          
        //取得参数
        $image= addslashes($request->param('image')); // 公司logo
        $oldpic= addslashes($request->param('oldpic')); // 原图片
        $company= addslashes($request->param('company')); // 公司名称
        $appid= addslashes($request->param('appid')); // 小程序id
        $appsecret = addslashes(trim($request->param('appsecret'))); // 小程序密钥
        $domain = addslashes(trim($request->param('domain'))); // 小程序域名
        $uploadImg_domain = addslashes(trim($request->param('uploadImg_domain'))); // 图片上传域名
        $uploadImg = addslashes(trim($request->param('uploadImg'))); // 图片上传位置
        $upload_file = addslashes(trim($request->param('upload_file'))); // 软件上传位置
        $ip = addslashes(trim($request->param('ip'))); // ip地址

        if($company == ''){
            $this->error('公司名称不能为空！',$this->module_url."/system");
        }
        if($appid == ''){
            $this->error('小程序id不能为空！',$this->module_url."/system");
        }
        if($appsecret == ''){
            $this->error('小程序密钥不能为空！',$this->module_url."/system");
        }
        if($domain == ''){
            $this->error('小程序域名不能为空！',$this->module_url."/system");
        }
        if($uploadImg_domain == ''){
            $this->error('图片上传域名不能为空！',$this->module_url."/system");
        }
        if($uploadImg == ''){
            $this->error('图片上传位置不能为空！',$this->module_url."/system");
        }else{
            $path=check_file(PUBLIC_PATH.DS.$uploadImg);
            if(!is_dir($path)){ // 如果文件不存在
                mkdir($path); // 创建文件
            }
        }
        if($upload_file == ''){
            $this->error('软件上传位置不能为空！',$this->module_url."/system");
        }else{
            $path=check_file(PUBLIC_PATH.DS.$upload_file);
            if(!is_dir($path)){ // 如果文件不存在
                mkdir($path); // 创建文件
            }
        }
        if(substr($uploadImg,-1) != '/'){
            $uploadImg .= '/';
        }
        
        if($image){
            $image = preg_replace('/.*\//','',$image); // 获取图片名称
            if($image != $oldpic){
                //@unlink(check_file(PUBLIC_PATH.DS.$uploadImg.DS.$oldpic));
            }
        }else{
            $image = $oldpic;
        }

        // 更新
        $config=new Config($this->db_config);
        $data =$this->parseSql("logo = '$image',company = '$company', appid = '$appid',appsecret = '$appsecret', domain = '$domain',uploadImg_domain = '$uploadImg_domain', uploadImg = '$uploadImg',upload_file = '$upload_file', ip = '$ip'");
        $r= $config->save($data,1,'id');

        if($r ==false) {
            $this->error('未知原因，修改失败！',$this->module_url."/system");
            
        } else {
            $this->success('修改成功！',$this->module_url."/system");
        }       
        exit;
    }

    
    public function pay(Request $request)
    {
        $request->method()=='post'&&$this->do_pay($request);
       
        $config=new Config($this->db_config);
        $id = intval($request->param("id")); 
        $r =$config->get(1,'id');
        $mch_cert = $r[0]->mch_cert; // 软件上传位置
        $uploadImg_domain = $r[0]->uploadImg_domain; // 图片上传域名
        $uploadImg = $r[0]->uploadImg; // 图片上传位置
        if(strpos($uploadImg,'../') === false){ // 判断字符串是否存在 ../
            $img = $uploadImg_domain . $uploadImg; // 图片路径
        }else{ // 不存在
            $img = $uploadImg_domain . substr($uploadImg,2); // 图片路径
        }      
        $mch_id = $r[0]->mch_id; // 商户id
        $mch_key = $r[0]->mch_key; // ip地址
        
        if($uploadImg == ''){
            $uploadImg = "/images";
        }
        $domain=$r[0]->domain;
        $this->assign('uploadImg_domain', isset($uploadImg_domain) ? $uploadImg_domain : '');
        $this->assign('img', isset($img) ? $img : '');
        $this->assign("mch_cert", isset($mch_cert) ? $mch_cert : '');
        $this->assign('mch_key', isset($mch_key) ? $mch_key : '');
        $this->assign('domain', isset($domain) ? $domain : '');
        $this->assign('uploadImg', isset($uploadImg) ? $uploadImg : '');
        $this->assign('mch_id', isset($mch_id) ? $mch_id : '');
        return $this->fetch('',[],['__moduleurl__'=>$this->module_url]);
    }

    
    private function do_pay($request)
    {

        //取得参数
        // $mch_cert= addslashes($request->param('mch_cert')); 
        $mch_key= addslashes($request->param('mch_key')); 
        $mch_id = addslashes(trim($request->param('mch_id'))); // 商户id

        if($mch_key == ''){
            $this->error('信息未填写完整,请完善后在提交！',$this->module_url."/system/pay");
        }
        if(is_numeric($mch_id) == false){
            $this->error('商户id请输入数字！',$this->module_url."/system/pay");
        }

        $file = $request->file('upload_cert');

        $mch_cert_url ='';
        $config=new Config($this->db_config);
        $r=$config->get(1,'id');
        $upfile = $r[0]->upload_file; // 软件上传位置
        $error='';
        if($this->validate([$file],"requires|fileType:zip",$error)){
            $upload_file = $upfile.'/cert/'; // 文件上传位置
            $type = pathinfo($file['name'],PATHINFO_EXTENSION);
            $edition_url_name = 'apiclient_key_'.time().'.zip';
            $zip_file = check_file(PUBLIC_PATH.$upload_file.$edition_url_name);
            check_path($zip_file);
            move_uploaded_file($file['tmp_name'],$zip_file);
            //存放证书到两个地址
            $uzip_res = unzip(check_file($zip_file),check_file(PUBLIC_PATH.$upload_file),true);

            $upload_file2 = $upfile.'/WxPayPubHelper/cacert/'; // 文件上传位置
            $uzip_res = unzip(check_file($zip_file),check_file(PUBLIC_PATH.$upload_file2),true);

            $uploadImg_domain = $r[0]->uploadImg_domain; // 图片上传域名
            
            if($uzip_res){
               $mch_cert = '/upfile/cert';
            }
        }else 
            $this->error($error,$this->module_url.'/system/pay');
        // 更新
        $data =$this->parseSql( "mch_cert ='$mch_cert', mch_id = '$mch_id', mch_key = '$mch_key'");
       $r= $config->save($data,1,'id');

        if($r ==false) {
            $this->error('未知原因，修改失败！',$this->module_url."/system/pay");
            
        } else {

        //异步通知配置文件
        $r_db = $config->field('appid,appsecret,mch_key,mch_id')->get(1,'id');
        $db_config = [];

        foreach ($r_db[0] as $key => $value) {
            $db_config[$key] = $value;
        }
        $conf = file_get_contents(check_file(PUBLIC_PATH.$mch_cert. '/WxPay.tpl'));
        foreach ($db_config as $name => $value) {
            $conf = str_replace("[{$name}]", $value, $conf);
        }
        file_put_contents(check_file(PUBLIC_PATH.$upfile.'/WxPayPubHelper/WxPay.pub.config.php'), $conf);
        // exit;
        $this->success('修改成功！',$this->module_url."/system/pay");
        }      
        exit();
    }

    
    public function template_message(Request $request)
    {       
        $request->method()=='post'&&$this->do_template_message($request);
        $id = intval($request->param("id")); 
        $Notice=new Notice($this->db_config);
        $r=$Notice->get(1,'id');
        $notice = $r[0]; // notice模板
        $this->assign('notice', isset($notice) ? $notice : '');
        return $this->fetch('',[],['__moduleurl__'=>$this->module_url]);

    }

    
    private function do_template_message($request)
    {
              
        $notice= $request->param('notice'); 
        $Notice=new Notice($this->db_config);
        $num = $Notice->getCount('','id');
        if($num < 1){
         $r = $Notice->insert($notice);
        }else{
         $r = $Notice->save($notice,1,'id');
        }
        if($r ==false) {
            $this->error('未知原因，修改失败！',$this->module_url."/system/template_message");

            
        } else {
            $this->success('修改成功！',$this->module_url."/system/template_message");
        }
        exit();

    }

    
    public function uploadImg(Request $request)
    {
        
        $request->method()=='post'&&$this->do_uploadImg($request);
        return $this->fetch('',[],['__moduleurl__'=>$this->module_url]);
    }

    
    private function do_uploadImg($request)
    {
          
        // 查询配置表信息
        $config=new Config($this->db_config);
        $r=$config->get(1,'id');
        $uploadImg = $r[0]->uploadImg;  // 图片上传位置
        
        if(empty($uploadImg)){
            $uploadImg = "/images";
        }
        if(!is_dir($uploadImg)){ // 如果文件不存在
            mkdir($uploadImg); // 创建文件
        }
           $msg='';
           $error= $request->file('imgFile')['error'];
            switch($error){
            case 0: $msg = ''; break;
            case 1: $msg = '超出了php.ini中文件大小'; break;
            case 2: $msg = '超出了MAX_FILE_SIZE的文件大小'; break;
            case 3: $msg = '文件被部分上传'; break;
            case 4: $msg = '没有文件上传'; break;
            case 5: $msg = '文件大小为0'; break;
            default: $msg = '上传失败'; break;
        }
        if(empty($msg)){
            $file=$request->file('imgFile');
        if($this->validate([$file],"requires|fileType:jpg,jpeg,gif,png|fileSize:500000",$msg)){
        list($name,$type)=explode('.', $file['name']);
        $imgURL_name=time().mt_rand(1,1000).'.'.$type;
        move_uploaded_file($file['tmp_name'],PUBLIC_PATH.$uploadImg.$imgURL_name);
        $image = $uploadImg . $imgURL_name;
        }else 
            $error=-1;
        }
        echo json_encode(array("error"=>$error,"url"=>$image,'message'=>$msg));
        exit();
    }

    
    function changePassword(Request $request)
    {
        $this->redirect($this->module_url.'/index/changePassword');
    }
         function maskContent(Request $request)
    {
       $this->redirect($this->module_url.'/index/maskContent');
    }
}