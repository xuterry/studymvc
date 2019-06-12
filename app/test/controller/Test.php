<?php
namespace app\test\controller;
use core\Controller;
use core\Response;
use core\Request;
use http\Exchange;
use core\Module;
use phpseclib\Crypt\RSA;
/**
/xtw
2018
*/
class Test extends Controller
{
     function index($a=''){
        // return $this->fetch('');
         $symbol='BTC_CQ';
         $period='1min';
         $size=200;
        // $ex=new Exchange(['type'=>'Binance']);
       // $ex=new Exchange(['type'=>'Coineal']);
        //  $ex=new Exchange();
        $ex=new Exchange(['type'=>'Hbdm']);
        // dump($ex->get_symbol_open());exit(); 
         //dump($ex->place_order('188','0.00000662','TRXBTC','sell-limit'));exit();
        //   dump($ex->cancel_order('75633174','trxbtc'));exit();
            //dump($ex->get_order_state('75633174','TRXBTC'));exit();
        //  dump($ex->get_orders_matchresults($symbol));exit();
        //  dump($ex->get_market_depth($symbol));exit(); 
       ///   dump($ex->get_market_detail($symbol));exit();
         // dump($ex->get_detail_merged($symbol));
         // dump($ex->get_market_trade());exit();
       //   dump($ex->get_history_trade($symbol,$size));exit();
         //dump($ex->get_balance('198800'));exit();
      //  dump($ex->get_market_tickers());exit();
        //dump($ex->get_history_kline($symbol,$period,$size));exit();
         //dump($ex->get_common_symbols());exit();
       // echo 'hello kitty'.$a;
        dump($ex->get_account_accounts());exit();
         $zip=new \ZipExtension();
         $req=new Request();
        var_dump( $req);exit();
      //   $req->abc();
      //   $test=new Test2();
   //      $test->index('1');
        $rs= $zip->createFile('ddd','ssss.tst');
         writefile('','test.zip',$rs);
        // $view=new View(['type'=>'Think']);
         $this->assign('hello','hello world');
         $this->assign('test',[['v'=>'aaaaa'],['v'=>'ddd']]);
         $paginer=[1=>'aaa',2,3,4,5,6,7,8,9,10=>'sssss'];
         $re=new Response($paginer,'json');
       //  $re->send();
         return $re;
         //echo '中国';exit();
      // return $this->display("monitor");
        //echo $content;
    }
    function   hexToStr($hex)
    {
        $string="";
        for   ($i=0;$i<strlen($hex)-1;$i+=2)
            $string.=chr(hexdec($hex[$i].$hex[$i+1]));
            return   $string;
    }
    
    function getpw()
    {
      //  !input("?post.key")&&exit('error');
        /*
        $pw="313166";
        $str="81f44308190fc4154efc777bb7b4a563e0ac9b7fdde614d60938109ffc98d9e6f1bb3eba683fb65dc9d0ab4ec7b8ea6f9fe162762e45267bb414f8888843778779580318ef255c3bd016ab319ba226db17b78c09a6f2c52a279095c4cbceb196b40266fa357bcbbe682375b012741ebdfc4c672e225c073464259e8c0437786b";
        echo strlen($str);
       //$str= hex2bin($str);
       // $str=str_pad($str,128);
       //$str=openssl_get_publickey(file_get_contents('pub.pem'));
       $cipher="aes-128-gcm";
        $ivlen = openssl_cipher_iv_length($cipher);
        $iv = openssl_random_pseudo_bytes($ivlen);
        $str=openssl_encrypt($str,$cipher,$pw,0,$iv);
        dump(base64_decode($str));

        exit;
        */
      // $newkey=input("post.key");
      //  $this->assign('newkey', $newkey);
        return $this->fetch('');
    }
    protected function geturlpath($method)
    {
        $domain=Request::instance()->domain;
        $class = get_called_class();
        $class = get_string("app\\", "\\controller", $class);
        if (! empty($class))
            $module = $class;
        else
            $module = Module::get_module();
       return $domain.'/' . $module.'/'.Module::get_controller().'/'.$method;
    }
     function get_pw()
    {
         dump((1<<16)>>1);
        dump(hexdec("8293"),dechex("43531"));
        $rsa = new RSA();
        $pw="313166";
        $str="81f44308190fc4154efc777bb7b4a563e0ac9b7fdde614d60938109ffc98d9e6f1bb3eba683fb65dc9d0ab4ec7b8ea6f9fe162762e45267bb414f8888843778779580318ef255c3bd016ab319ba226db17b78c09a6f2c52a279095c4cbceb196b40266fa357bcbbe682375b012741ebdfc4c672e225c073464259e8c0437786b";
       $str_rs="06b892ad96d3b9f622ad22ccc6c27fb7e44572501af4804cdbfd6fadeeaf9eaf3dc55d20973963c7d81a53f0ed45e60d9c04debc8e47f03d76647b14adf547c3d11c8817f2c42e3fde5f327a6642b787980a39f3e1fce5dffdaf26a8999a9dab9235f3ee11d3545787a351f5db43530237de01a841fdc53faed8cdbb7dd99fa6";
        echo strlen($str);
        echo strlen($str_rs);
        $m="9DAF37FC7EDFA2AA4DACBBA6EA10FE762F99DC8EE28C3EBBFCD66F03F0D75BEE6F0BAAFA45C9BC9302E669C51531132C04B22D52917E8E24298063790E75DA7A46333B1CD2F8782061DA0AEEBC87F040A587926E880D58E371144C7108927C95A94AB6E8C83DEB41303D4337BEAE0D713A5686228DB28155CFF67572859766FB";
        $rsa=new \rsaEncode($m,'10001','313166');
        dump($rsa->getm());exit;
        
        $rsa->setPublicKeyFormat(3);   
        $rsa->setSignatureMode(2);
        
     //   $rsa->setPrivateKeyFormat(3);       
        $result = $rsa->createKey();
        dump($result);
        $key=$result['publickey'];
        
        $pkey=$result['privatekey'];
        //writefile('','pkey,text',$pkey);
        //echo $pkey;
       // $pkey=$rsa->getPrivateKey(3);
        $e=new \phpseclib\Math\BigInteger("10001",16);
        $str2="c5223c6e62717731632e851e7054847836fead101e0c481afeda0b593ea8fdee436a3ac9ec45632b4c5e1a3cd8034ca978d139c492e4ca051879346a9687aefa8b6b3fda73e07ea570f86f8d48b644c9dfcaf8669b74addfb9374c5b375ae9c10ccdadb19ae58d9041f338e87afecf3d8cb6f471e3b814f96a04fe7814609241";
        
       //$rasen=new \rsaEncode($str2,'10001','313166');
      // dump($rasen);exit;
       // openssl_private_decrypt($encrypt_data, $decrypted, $pkey);
       // dump($decrypted);exit;
        
        $m=new \phpseclib\Math\BigInteger($str2,16);
        $rsa = new RSA();
        $keys=['e'=>$e,'n'=>$m];
      //  dump($keys);
        $rsa->loadKey($keys,3);
        
        $rsa->setEncryptionMode(3);    //选择加密的模式，可选模式参考官方文档
       // dump($rsa->getPublicKey(3));
       $msg= $rsa->encrypt($pw);
                            
         $msg_new=new \phpseclib\Math\BigInteger($msg,256);
         $msg_new=$msg_new->toBytes();
         $msg1=base64_encode($msg);
         $msg=bin2hex($msg);
       //$msg1->copy();
       dump($msg,$msg_new);
    //  exit;
        
        $pkey=file_get_contents('pkey,text');
        $rsa->loadKey($pkey);
        
        $rsa->setEncryptionMode(3);
       // $msg=hex2bin($msg);
        $msg="67f5eb839b3e14eb2db46bb062a172e7920dcb9853d0589fa82b629f04893bb1e31260dded61c73982198ca171d59ccbb4832d3460511e62baeea7ec211746d3b70967e094af909e8cafc425b1e74db06595681a26152d6c544c0b7291a0176e554b8da275656db75f6faac03dd99e7a575062dfa870d9eb194141e04166a2b6";
        $private_key = openssl_pkey_get_private('file://F:/zend/studymvc/public/private_key.pem');
      //  $private_key=file_get_contents('private_key.pem');
        //$msg=pack("H*",$msg);
      // $msg= base64_decode($msg);
     // $msg=hex2bin($msg);
   //   $msg= base64_decode($msg);
      
        $plaintext='';
        openssl_private_decrypt($msg, $plaintext, $private_key, OPENSSL_PKCS1_PADDING);
        dump($plaintext);
        
       // $msg=base64_decode($msg);
        echo $rsa->decrypt($msg);
        
        exit;
        
        /*
   //     $url=$this->geturlpath('getpw');
     //   $ql = QueryList::getInstance();
      //  exit($url);
        // 安装时需要设置PhantomJS二进制文件路径
     //   $ql->use(PhantomJs::class,'f:/phantomjs/bin/phantomjs.exe');
        $url='http://t.cctvlian.cn/test/test/getpw';
        $url2="http://tp6.com/test/test/getpw";
        //dump(\http\Curl::get($url2));exit;
        $client = Client::getInstance();
        //这一步非常重要，务必跟服务器的phantomjs文件路径一致
        $client->getEngine()->setPath('f:/phantomjs/bin/phantomjs.exe');
        $client->isLazy(); // 让客户端等待所有资源加载完毕
        $request  = $client->getMessageFactory()->createRequest();
        $request->setTimeout(1000); // 设置超时时间(超过这个时间停止加载并渲染输出画面)
       // $request->setDelay(5);
        $response = $client->getMessageFactory()->createResponse();
        //设置请求方法
        $request->setMethod('POST');
        $request->setRequestData(['key'=>"ddddd"]);
        //设置请求连接
        $request->setUrl($url2);
        //发送请求获取响应
        $client->send($request, $response);
        dump($request,$response);
            //输出抓取内容
            echo $response->getContent();
            //获取内容后的处理
        
     //   dump($html);
    //    echo $html;
        exit();
        */
    }
    function img()
    {
        //$this->check_session();exit;
        //$this->getAllStock();exit;
        $this->autologin2();exit;
        $this->getcode();exit;
         //$this->getimg(30);exit;
       // $this->cut_img('testimg/cut','testimg'.DS.'1559951056.jpg');
     // $this->get_cut('testimg', 'testimg/new');exit;
       $valite=new \Valiteimg();
    // $valite->setKeys('testimg/new');exit;
     $path='testimg/new';
     $paths=scandir($path);
     foreach($paths as $file){
         if($file=='.'||$file=='..')
             continue;
        $filename=$path.'/'.$file;
        $valite->setImage($filename);
        $valite->getHec();
        $valite->filterInfo();
        $valite->dealwithData();
      //  $valite->DrawDealData();
       // $valite->Draw();
        //echo "\n 结果是：";
        $data = $valite->run();
        $newname=implode("",$data);        
        rename($filename,$path.'/'.$newname.'.jpg');
        echo $newname."\n";
     }
    }
    protected function autologin()
    {
        $cookie_file='cookie.tmp';
        if(is_file($cookie_file))
            $getcookie=\http\Curl::getCookie($cookie_file);
        else{
            $url="http://t.cctvlian.cn/okex/index/checklogin";        
            $info=\http\Curl::post($url,['pw'=>'xuabc,./123456'],false,1);
            $cookie=$info[2];       
            \http\Curl::saveCookie($cookie_file,$cookie);
            $getcookie=\http\Curl::getCookie($cookie_file);
        }
        $url="http://t.cctvlian.cn/okex/dotrade/get_price?symbol=XRP_CQ&type=2&lever=20";     
        $rs=\http\Curl::get($url,$getcookie,1);  
        !empty($rs[2])&&\http\Curl::updateCookie($cookie_file, $rs[2]);
        dump($rs);
    }
    protected function autologin2()
    {
        $start_url="https://jy.ghzq.com.cn/";
        $ref="https://jy.ghzq.com.cn/logout.jsp";
        $file='cookie2.tmp';
        $info=\http\Curl::get($start_url,false,1,$ref);
        dump($info);
        \http\Curl::saveCookie($file, $info[2]);
        $cookie=\http\Curl::getCookie($file);
        dump($cookie);
        $code_url="https://jy.ghzq.com.cn/validatecode?random=".time()."000";
        $info=\http\Curl::get($code_url,$cookie,1,$start_url);
        dump($info);
        $code=$this->getcode($info[0]);
        \http\Curl::updateCookie($file, $info[2]);
        $cookie=\http\Curl::getCookie($file);
        dump($cookie);
        $login_url="https://jy.ghzq.com.cn/loginAction.jsp?random=".time()."380";
        $password="87cfeca9e7e28674e0f56cc83115a2389338a2a0ce45eb6b93b91f43d504ce7c759327050db987841f570a1a510bb9e646a09e65ffd6b4365bbd619c371e55d334d78b0900981f19f5096420bc3291576b31b2a58c3dce824ffaa81e1f95add540584a4c46c02dca49de580e8ad0728b043a569e90a3b51a685d475b856c2250";
        $params=['byAjax'=>1,'logintype'=>1,'password'=>$password,'macAddress'=>'undefined','HDDAddress'=>'undefined','itype'=>'zjzh',
            'loginname'=>'29254216','password2'=>$password,'randomCode'=>$code
        ];
        $info=\http\Curl::post($login_url,$params,$cookie,1,$start_url);
        dump($params,\http\Curl::decondestr($info[0]),$info);
        \http\Curl::updateCookie($file, $info[2]);     
        $cookie=\http\Curl::getCookie($file);
        dump($cookie);
       // $this->getAllStock();
        
    }
    protected function check_login($rs)
    {
        if(stripos($rs,"is_OK':true}")!==false)
            return true;
        return false;
    }
    protected function logout()
    {
        $file='cookie2.tmp';        
        $ref="https://jy.ghzq.com.cn/etrade2/home.jsp";       
        $logout_url="https://jy.ghzq.com.cn/logout.jsp";
        $cookie=\http\Curl::getCookie($file);
        \http\Curl::get($logout_url,$cookie,0,$ref);
    }
    protected function check_session()
    {
        $file='cookie2.tmp';
        $ref="https://jy.ghzq.com.cn/etrade2/home.jsp";
        $url="https://jy.ghzq.com.cn/etrade2/checkSession.jsp?random=".time()."228";
        $cookie=\http\Curl::getCookie($file);
        $rs=\http\Curl::get($url,$cookie,0,$ref);
        $rs=str_replace("'","\"",$rs);
        $rs=json_decode($rs,1);
        dump($rs);
        return $rs;
    }
    protected function getAllStock()
    {
        $file='cookie2.tmp';      
        $url="https://jy.ghzq.com.cn/etrade2/jy_mrb.jsp";
        $ref="https://jy.ghzq.com.cn/etrade2/home.jsp";      
        $cookie=\http\Curl::getCookie($file);
        $info=\http\Curl::get($url,$cookie,1,$ref);
        \http\Curl::updateCookie($file, $info[2]);
        $preg="/<tr id=\"(.+)>(.+)<\/tr>/isU";
        $preg2="/<td(.+)>(.+)<\/td>/isU";
        preg_match_all($preg,$info[0],$matchs);
        dump($matchs);
        $return=[];
        if(!empty($matchs[1])){
            foreach($matchs[1] as $val){
                preg_match_all($preg2,$val[1],$matchs2);
                $array=[];
                foreach($matchs2[1] as $v){
                    $array[]=trim($v[1]);
                }
                $return[]=$array;
            }
        }
        dump($cookie,$info,$return);
    }
    protected function getcode($get)
    {
        $file='temp.jpg';
        writefile('',$file,$get);        
        $img=\Image::open($file);
        $width=$img->width()-2;
        $height=$img->height()-2;
        $img->crop($width,$height,1,1,$width,$height);
        $img->save($file);
        $valite=new \Valiteimg();       
        $valite->setImage($file);
        $valite->getHec();
        $valite->filterInfo();
        $valite->dealwithData();
        $data = $valite->run();
        $newname=implode("",$data);
        return $newname;
    }
    protected function getimg($size=50)
    {
        $url="https://jy.ghzq.com.cn/validatecode?random=".time()."000";       
        for($i=0;$i<=$size;$i++){
            $get=\http\Curl::get($url);
            $file='testimg'.DS.time().$i.'.jpg';
            writefile('',$file,$get);
            sleep(1);
        }
    }
    protected function get_cut($path,$cut_path)
    {
        $files=scandir($path);
        foreach($files as $file){
            if($file=='.'||$file=='..')
                continue;
            $file=$path.'/'.$file;
            if(is_file($file)){
                $this->cut_img2($cut_path, $file);
            }
        }
    }
    protected function cut_img($cut_path,$img_file,$count=4)
    {
        $img=\Image::open($img_file);
        $name=pathinfo($img_file,PATHINFO_BASENAME);
        check_path($cut_path);
        list($name,$type)=explode(".",$name);
        $width=$img->width();
        $height=$img->height()-2;
        $w=floor($width/$count);
        for($i=0;$i<$count;$i++){
            $x=1+$i*$w;
            $y=1;        
            $img=\Image::open($img_file);           
            $img->crop($w,$height,$x,$y,$w,$height);
            $img->save($cut_path.'/'.$name.$i.'.'.$type);
        }
    }
    protected function cut_img2($cut_path,$img_file)
    {
        $img=\Image::open($img_file);
        $name=pathinfo($img_file,PATHINFO_BASENAME);
        check_path($cut_path);
        list($name,$type)=explode(".",$name);
        $width=$img->width()-2;
        $height=$img->height()-2;
        $img->crop($width,$height,1,1,$width,$height);
         $img->save($cut_path.'/'.$name.'.'.$type);
        
    }
}