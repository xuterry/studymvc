<?php
namespace app\api\controller;
use core\Request;

class Getcode extends Api
{

    function __construct()
    {
        parent::__construct();
    }
    public function code (Request $request)
    {     
        $r=$this->getModel('Config')->where(['id'=>['=','1']])->fetchAll('*');
        if ($r) {
            $appid = $r[0]->appid; // 小程序唯一标识
            $appsecret = $r[0]->appsecret; // 小程序的 app secret
        }
        $AccessToken = $this->getAccessToken($appid, $appsecret);
        $res = $this->get_qrcode($AccessToken);
        return $res;
    }

    public function get_qrcode($AccessToken)
    {
        // header('content-type:image/jpeg'); 测试时可打开此项 直接显示图片
        
        $request=new Request();
        $path = $request->param('path');
        $width = $request->param('width');
        $id = trim($request->param('id'));
        // 查询系统参数
        $r_1=$this->getModel('Config')->where(['id'=>['=','1']])->fetchAll('*');
        $uploadImg_domain = $r_1[0]->uploadImg_domain; // 图片上传域名
        $uploadImg = $r_1[0]->uploadImg; // 图片上传位置
        if (strpos($uploadImg, '../') === false) { // 判断字符串是否存在 ../
            $img = $uploadImg_domain . $uploadImg; // 图片路径
        } else { // 不存在
            $img = $uploadImg_domain . substr($uploadImg, 2); // 图片路径
        }
        $filename = "ewm" . $id . ".jpg"; // /
        $imgDir = './images/';
        // 要生成的图片名字
        $newFilePath = $imgDir . $filename;
        if (is_file($newFilePath)) {
            return $filename;
        } else {
            // 获取三个重要参数 页面路径 图片宽度 文章ID
            $arr = [
                        "path" => $path,"width" => $width
            ];
            $data = json_encode($arr);
            // 把数据转化JSON 并发送
            $url = 'https://api.weixin.qq.com/cgi-bin/wxaapp/createwxaqrcode?access_token=' . $AccessToken;
            // 获取二维码API地址
            $da = $this->Curl($url, $data);
            // 发送post带参数请求
            
            $newFile = fopen($newFilePath, "w"); // 打开文件准备写入
            fwrite($newFile, $da); // 写入二进制流到文件
            fclose($newFile); // 关闭文件
                              // 拼接服务器URL 返回
            $url = $img . $filename;
            return $filename;
        }
    }

    public function product_share (Request $request)
    {
            
        $product_img = $request->param('product_img_path');
        $str_r = trim(strrchr($product_img, '/'), '/');
        if ($str_r) {
            $product_img = $str_r;
        }
        $type = $request->param('type');
        $product_title = $request->param('product_title');
        if (strlen($product_title) > 18) {
            $product_title = mb_substr($product_title, 0, 18, 'utf-8') . '...';
        }
        $pid = $request->param('pid');
        $price = $request->param('price');
        $yprice = $request->param('yprice');
        $nickname = $request->param('nickname');
        $head = $request->param('head');
        $regenerate = trim($request->param('regenerate'));       
        // 默认底图和logo
        $logo = '/images/ditu/logo.png';
        
        $path = $request->param('path');
        $id = $request->param('id');      
        // 生成密钥
        $utoken = '';
        $str = 'QWERTYUIOPASDFGHJKLZXCVBNMqwertyuiopasdfghjklzxcvbnm1234567890';
        for ($i = 0; $i < 32; $i ++) {
            $utoken .= $str[rand(0, 61)];
        }        
        $uur=$this->getModel('User')->where(['user_id'=>['=',$id]])->fetchAll('img_token');
        $lu_token = isset($uur[0]) ? md5($uur[0]->img_token) : md5($id);
        $img_token = isset($uur[0]) ? $uur[0]->img_token : false;       
        // 定义固定分享图片储存路径 以便删除
        $imgDir="product_share_img/";
        $r=$this->getModel('Config')->where(['id'=>['=','1']])->fetchAll('*');
        if ($r) {
            $appid = $r[0]->appid; // 小程序唯一标识
            $appsecret = $r[0]->appsecret; // 小程序的 app secret
            $uploadImg_domain = $r[0]->uploadImg_domain; // 图片上传域名
            $uploadImg = $r[0]->uploadImg; // 图片上传位置
            $logn=$r[0]->logo;
            if (strpos($uploadImg, '../') === false) { // 判断字符串是否存在 ../
                $img = $uploadImg_domain . $uploadImg; // 图片路径
            } else {
                // 不存在
                $img = $uploadImg_domain . substr($uploadImg, 2); // 图片路径
            }
            $product_img = check_file(PUBLIC_PATH.DS.$uploadImg . $product_img);
            $font_file = PUBLIC_PATH."/font/font.ttf";
            $sharePath =check_file(PUBLIC_PATH.DS.$uploadImg.DS. $imgDir);      
            check_path($sharePath);
        }       
        $tkt_r=$this->getModel('Extension')->where(['type'=>['=',$type],'isdefault'=>['=','1']])->fetchAll('*');        
        $pic = $lu_token . '-' . $type . '-' . $pid . '-ewm.jpg';
        if ($regenerate || ! $img_token) {
            // 通过控制access_token 来校验不同二维码
            if(is_file($sharePath . $pic))
            @unlink($sharePath . $pic);
            $lu_token = md5($utoken);
            $update_rs=$this->getModel('User')->saveAll(['img_token'=>$utoken],['user_id'=>['=',$id]]);
            $pic = $lu_token . '-' . $type . '-ewm.jpg';
        }
        
        if (is_file($sharePath . $pic)) {
            $url = $img . $imgDir . $pic;
            $waittext = isset($tkt_r[0]->waittext) ? $tkt_r[0]->waittext : '#fff';
            echo json_encode(array(
                                    'status' => true,'url' => $url,'waittext' => $waittext
            ));
            exit();
        }       
        $waittext = isset($tkt_r[0]->waittext) ? $tkt_r[0]->waittext : '#fff';       
        if (empty($tkt_r)) {
            $tkt_r=$this->getModel('Extension')->where(['type'=>['=',$type]])->fetchAll('*');
            if (empty($tkt_r)) {
                $url = $img . $imgDir . 'img.jpg';
                echo json_encode(array(
                                        'status' => true,'url' => $url
                ));
                exit();
            }
        }     
        if ($type == '1') {
            // 文章
            if (! empty($r)) {
                $bottom_img = check_file(PUBLIC_PATH.DS.$uploadImg . $tkt_r[0]->bg);
                $data = $tkt_r[0]->data;
            }
        } else if ($type == '2') {
            // 红包
            if (! empty($r)) {
                $bottom_img = check_file(PUBLIC_PATH.DS.$uploadImg . $tkt_r[0]->bg);
                $data = $tkt_r[0]->data;
            }
        } else if ($type == '3') {
            // 商品
            if (! empty($r)) {
                $bottom_img = check_file(PUBLIC_PATH.DS.$uploadImg . $tkt_r[0]->bg);
                $data = $tkt_r[0]->data;
            }
            // var_dump($bottom_img);exit;
        } else {
            // 分销
            if (! empty($r)) {
                $bottom_img = check_file(PUBLIC_PATH.DS.$uploadImg . $tkt_r[0]->bg);
                $data = $tkt_r[0]->data;
            }
        }
        // head' style="margin-bottom: 4px">头像
        // nickname' style="margin-bottom: 4px">昵称
        // qr' style="margin-bottom: 4px">二维码
        // img' style="margin-bottom: 4px">图片
        // title' >商品名称
        // thumb' >商品图片
        // marketprice' >商品现价
        // productprice' >商品原价       
        // 创建底图
       // $dest = $this->create_imagecreatefromjpeg($bottom_img);
        $im=\Image::create(320,500,$sharePath . $pic);
        $im->water($im->resize($bottom_img,320,500),1,50);
        $datas = json_decode($data);
        $qr=[];
        foreach ($datas as $key => $value) {
            if($value->type=='qr'){
                unset($datas[$key]);
                $qr=$value;
            }
        }
        !empty($qr)&&array_push($datas,$qr);
        foreach ($datas as $key => $value) {
            $data = [];
            // $data =$this->getRealData((array)$value);
            foreach ($value as $k => $v) {
                if ($k == 'left' || $k == 'top' || $k == 'width' || $k == 'height' || $k == 'size') {
                    $v = intval(str_replace('px', '', $v)) ;
                }
                if($k=='color'){
                    if(strlen($v)!=7)
                        $v="#000000";
                }
                $data[$k] = $v;
            }
            if ($value->type == 'head') {
              //  $im->write_img($head, $data['left'],$data['top']);
                $im->water($im->resize($head,$data['width'],$data['height']),[$data['left'],$data['top']]);             
            } else if ($value->type == 'nickname') {
                $im->text($nickname,$font_file,$data['size'],$data['color'],[$data['left'],$data['top']],0);
                //$dest = $this->write_text($dest, $data, $product_title, $font_file);
            } else if ($value->type == 'qr') {
                $AccessToken = $this->getAccessToken($appid, $appsecret);
                $share_qrcode = $this->get_share_qrcode($path, $value->width, $id, $AccessToken);
                // var_dump($dest,$data,$share_qrcode);exit;
                //$dest = $this->write_img($dest, $data, $share_qrcode);
                $im->water($im->resize($share_qrcode,$data['width'],$data['height']),[$data['left'],$data['top']]);               
            } else if ($value->type == 'img') {
                if ($value->src) {
                    $imgs =  check_file(PUBLIC_PATH.DS.$uploadImg . $value->src);
                   // $dest = $this->write_img($dest, $data, $imgs);
                    //$im->write_img($imgs, $data['left'],$data['top'],$data['width'],$data['height']); 
                    $imgs=$im->resize($product_img, $data['width'], $data['height']);
                    $im->water($imgs,[$data['left'],$data['top']]);
                }
            } else if ($value->type == 'title') {
                // 标题
              //  $dest = $this->write_text($dest, $data, $product_title, $font_file);
                $im->text($product_title,$font_file,$data['size'],$data['color'],[$data['left'],$data['top']],0);
                
            } else if ($value->type == 'thumb') {
                // 商品图合成
               // $dest = $this->write_img($dest, $data, $product_img);
            } else if ($value->type == 'marketprice') {
                // 价格
                $product_title = '￥' . $price;
                //$dest = $this->write_text($dest, $data, $product_title, $font_file);
                $im->text($product_title,$font_file,$data['size'],$data['color'],[$data['left'],$data['top']],0);
            } else if ($value->type == 'productprice') {
                // 原价
                $product_title = '￥' . $yprice;
              //  $dest = $this->write_text($dest, $data, $product_title, $font_file);
                $im->text($product_title,$font_file,$data['size'],$data['color'],[$data['left'],$data['top']],0);
                $shanchuxian = '—';
                for ($i = 0; $i < (strlen($product_title) - 3) / 4; $i ++) {
                    $shanchuxian .= $shanchuxian;
                }
               // $dest = $this->write_text($dest, $data, $shanchuxian, $font_file);
                $im->text($shanchuxian,$font_file,$data['size'],$data['color'],[$data['left'],$data['top']],0);               
                
            }
        }    
        // header("content-type:image/jpeg");
       // imagejpeg($dest, $sharePath . $pic);
       $im->save($sharePath.$pic);
        $url = $img . $imgDir . $pic;
        echo json_encode(array(
                                'status' => true,'url' => $url,'waittext' => $waittext
        ));
        exit();
    }

    public function getRealData($data)
    {
        $data['left'] = intval(str_replace('px', '', $data['left'])) * 2;
        $data['top'] = intval(str_replace('px', '', $data['top'])) * 2;
        $data['width'] = intval(str_replace('px', '', $data['width'])) * 2;
        $data['height'] = intval(str_replace('px', '', $data['height'])) * 2;
        
        if ($data['size']) {
            $data['size'] = intval(str_replace('px', '', $data['size'])) * 2;
        }
        if ($data['src']) {
            $data['src'] = tomedia($data['src']);
        }
        
        return $data;
    }


    function autowrap($fontsize, $angle, $fontface, $string, $width)
    {
        // 参数分别是 字体大小, 角度, 字体名称, 字符串, 预设宽度
        $content = "";
        // 将字符串拆分成一个个单字 保存到数组 letter 中
        preg_match_all("/./u", $string, $arr);
        $letter = $arr[0];
        foreach ($letter as $l) {
            $teststr = $content . $l;
            // var_dump($fontsize, $angle, $fontface, $teststr);
            $testbox = imagettfbbox($fontsize, $angle, $fontface, $teststr);
            if (($testbox[2] > $width) && ($content !== "")) {
                $content .= PHP_EOL;
            }
            $content .= $l;
        }
        return $content;
    }

    public function write_text($dest, $data, $string, $fontfile)
    {
        if ($data['type'] == 'title') {
            $width = imagesx($dest) - $data['left'] * 2;
        } else {
            $width = imagesx($dest) * 2;
        }           
        // var_dump($font_file);
        $colors = $this->hex2rgb($data['color']);
        $color = imagecolorallocate($dest, $colors['red'], $colors['green'], $colors['blue']); // 背景色
        $string = $this->autowrap($data['size'], 0, $fontfile, $data['text'], $width);
        $fontsize = $data['size'];
        imagettftext($dest, $fontsize, 0, $data['left'], $data['top'], $color, $fontfile, $string);
        return $dest;
    }

    function hex2rgb($colour)
    {
        if ($colour[0] == '#') {
            $colour = substr($colour, 1);
        }
        if (strlen($colour) == 6) {
            list ($r, $g, $b) = array(
                                    $colour[0] . $colour[1],$colour[2] . $colour[3],$colour[4] . $colour[5]
            );
        } elseif (strlen($colour) == 3) {
            list ($r, $g, $b) = array(
                                    $colour[0] . $colour[0],$colour[1] . $colour[1],$colour[2] . $colour[2]
            );
        } else {
            return false;
        }
        $r = hexdec($r);
        $g = hexdec($g);
        $b = hexdec($b);
        return array(
                    'red' => $r,'green' => $g,'blue' => $b
        );
    }

    function wpjam_hex2rgb($hex)
    {
        $hex = str_replace("#", "", $hex);       
        if (strlen($hex) == 3) {
            $r = hexdec(substr($hex, 0, 1) . substr($hex, 0, 1));
            $g = hexdec(substr($hex, 1, 1) . substr($hex, 1, 1));
            $b = hexdec(substr($hex, 2, 1) . substr($hex, 2, 1));
        } else {
            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));
        }      
        return array(
                    $r,$g,$b
        );
    }

    public function get_share_qrcode($path, $width, $id, $AccessToken)
    {
        // header('content-type:image/jpeg'); 测试时可打开此项 直接显示图片
        
         $request= new Request();
        // 查询系统参数
        $r_1=$this->getModel('Config')->where(['id'=>['=','1']])->fetchAll('*');
        $uploadImg_domain = $r_1[0]->uploadImg_domain; // 图片上传域名
        $uploadImg = $r_1[0]->uploadImg; // 图片上传位置
        if (strpos($uploadImg, '../') === false) { // 判断字符串是否存在 ../
            $img = $uploadImg_domain . $uploadImg; // 图片路径
        } else { // 不存在
            $img = $uploadImg_domain . substr($uploadImg, 2); // 图片路径
        }
        $pid = $request->param('pid');
        $path_name = str_replace('/', '_', $path);
        $filename = $path_name . '_share_' . $id . '_' . $pid . '.jpeg'; // /
        $imgDir = 'product_share_img/';
        $width = 430;
        // 要生成的图片名字
        $newFilePath = check_file(PUBLIC_PATH.DS.$uploadImg.DS.$imgDir . $filename);
        if (is_file($newFilePath)) {
            return $newFilePath;
        } else {
            $scene = $request->param('scene');
            // 获取三个重要参数 页面路径 图片宽度 文章ID
            // --B $arr = ["page"=> $path, "width"=>$width,'scene'=>$scene];
            // --A
            $arr = [
                        "path" => $path . '?' . $scene,"width" => $width
            ];
            $data = json_encode($arr);
            // 把数据转化JSON 并发送
            // 接口A: 适用于需要的码数量较少的业务场景 接口地址：
            $url = 'https://api.weixin.qq.com/wxa/getwxacode?access_token=' . $AccessToken;
            // 接口B：适用于需要的码数量极多的业务场景
            // $url = 'https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token=' . $AccessToken;
            // 接口C：适用于需要的码数量较少的业务场景
            // $url = 'https://api.weixin.qq.com/cgi-bin/wxaapp/createwxaqrcode?access_token=' . $AccessToken;
            // 获取二维码API地址
            
            $da = $this->Curl($url, $data);
            // 发送post带参数请求
            // var_dump($da);exit;
            // header('content-type:image/jpeg');
            // echo $da;exit;
            $newFile = fopen($newFilePath, "w"); // 打开文件准备写入
            fwrite($newFile, $da); // 写入二进制流到文件
            fclose($newFile); // 关闭文件
            return $newFilePath;
        }
    }
    public function Send_success($rew)
    {   
        $r=$this->getModel('Config')->where(['id'=>['=','1']])->fetchAll('*');
        if ($r) {
            $appid = $r[0]->appid; // 小程序唯一标识
            $appsecret = $r[0]->appsecret; // 小程序的 app secret
            $AccessToken = $this->getAccessToken($appid, $appsecret);
            $url = 'https://api.weixin.qq.com/cgi-bin/message/wxopen/template/send?access_token=' . $AccessToken;
        }
        
        foreach ($rew as $k => $v) {
            
            foreach ($v as $key => $value) {
                $lottery_status = $value[''];
                $product_title = $value['product_title'];
                $name = $value['name'];
                $time = $value['time'];
                $openid = $value['openid'];
                $fromid = $value['fromid'];
                $lottery_status = $value['lottery_status'];
                $data = array();
                $data['access_token'] = $AccessToken;
                $data['touser'] = $openid;
                $r=$this->getModel('Notice')->where(['id'=>['=','1']])->fetchAll('*');
                $template_id = $r[0]->lottery_res;
                $data['template_id'] = $template_id;
                $data['form_id'] = $fromid;
                $minidata = array(
                                'keyword1' => array(
                                                    'value' => $name,'color' => "#173177"
                                ),'keyword2' => array(
                                                    'value' => $product_title,'color' => "#173177"
                                ),'keyword3' => array(
                                                    'value' => $time,'color' => "#173177"
                                ),'keyword4' => array(
                                                    'value' => $lottery_status,'color' => "#173177"
                                )
                );
                $data['data'] = $minidata;
                $data = json_encode($data);
                
                $da = $this->Curl($url, $data);
                $delete_rs=$this->getModel('DrawUserFromid')->delete($fromid,'fromid');
                var_dump(json_encode($da));
            }
        }
    }

    public function getToken (Request $request)
    {
        
        
        $r=$this->getModel('Config')->where(['id'=>['=','1']])->fetchAll('*');
        if ($r) {
            $appid = $r[0]->appid; // 小程序唯一标识
            $appsecret = $r[0]->appsecret; // 小程序的 app secret
            $company = $r[0]->company; // 公司名称
            $AccessToken = $this->getAccessToken($appid, $appsecret);
            echo json_encode(array(
                                    'access_token' => $AccessToken,'company' => $company
            ));
            exit();
        }
    }

    public function Send_Prompt (Request $request)
    {
               
        $openid = trim($request->param('user_id')); // --
        $form_id = trim($request->param('form_id')); // --
        $page = trim($request->param('page')); // --
                                                      // $oid = trim($request->param('oid'));
        $f_price = trim($request->param('price'));
        $f_sNo = trim($request->param('order_sn'));
        $f_pname = trim($request->param('f_pname'));
        $time = date("Y-m-d h:i:s", time());
        $r=$this->getModel('Config')->where(['id'=>['=','1']])->fetchAll('*');
        if ($r) {
            $appid = $r[0]->appid; // 小程序唯一标识
            $appsecret = $r[0]->appsecret; // 小程序的 app secret
            $address = $r[0]->company;
            
            $company = array(
                            'value' => $address,"color" => "#173177"
            );
            $time = array(
                        'value' => $time,"color" => "#173177"
            );
            $f_pname = array(
                            'value' => $f_pname,"color" => "#173177"
            );
            $f_sNo = array(
                        'value' => $f_sNo,"color" => "#173177"
            );
            $f_price = array(
                            'value' => $f_price,"color" => "#173177"
            );
            $o_data = array(
                            'keyword1' => $company,'keyword2' => $time,'keyword3' => $f_pname,'keyword4' => $f_sNo,'keyword5' => $f_price
            );
            
            $AccessToken = $this->getAccessToken($appid, $appsecret);
            $url = 'https://api.weixin.qq.com/cgi-bin/message/wxopen/template/send?access_token=' . $AccessToken;
            $r=$this->getModel('Notice')->where(['id'=>['=','1']])->fetchAll('*');
            $template_id = $r[0]->pay_success;
            $data = json_encode(array(
                                    'access_token' => $AccessToken,'touser' => $openid,'template_id' => $template_id,'form_id' => $form_id,'page' => $page,'data' => $o_data
            ));
            $da = $this->Curl($url, $data);
            echo json_encode($da);
            
            exit();
        }
    }

    function getAccessToken($appID, $appSerect)
    {
        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=" . $appID . "&secret=" . $appSerect;
        // 时效性7200秒实现
        // 1.当前时间戳
        $currentTime = time();
        // 2.修改文件时间
        $fileName = "accessToken"; // 文件名
                                   // var_dump(is_file($fileName),$fileName);
        if (is_file($fileName)) {
            $modifyTime = filemtime($fileName);
            if (($currentTime - $modifyTime) < 7200) {
                // 可用, 直接读取文件的内容
                $accessToken = file_get_contents($fileName);
                return $accessToken;
            }
        }
        // 重新发送请求
        $result = $this->Curl($url);
        $jsonArray = json_decode($result, true);
        // 写入文件
        $accessToken = $jsonArray['access_token'];
        file_put_contents($fileName, $accessToken);
        return $accessToken;
    }

    function madeCode (Request $request)
    {
        
        
        $id = trim($request->param('id'));
        $wx_id = $request->param('openid');
        // 查询公司名称
        $r=$this->getModel('Config')->where(['id'=>['=','1']])->fetchAll('*');
        $company = $r[0]->company;
        $instring = $company . '给你发红包啦';
        
        echo json_encode(array(
                                'status' => 1,'text' => $instring
        ));
        exit();
        return;
    }

    function getPromotion($name, $ditu, $x, $y, $wx_id, $kuan = 300)
    {
        
        $r_w=$this->getModel('User')->where(['wx_id'=>['=',$wx_id]])->fetchAll('user_id');
        // 信息准备
        $userid = $r_w[0]->user_id;
        // $dest = imagecreatefromjpeg('../LKT/images/bottom/img01.jpg'); //底图1 http://127.0.0.1:8080/LKT/images/1523861937693.jpeg
        $dest = imagecreatefromjpeg($ditu); // 底图1
        $dirName = '/images/';
        $headfilename = 'logo.jpg';
        $filename = '';
        // 取得二维码图片文件名称
        $erweima = $this->code();
        
        /* 带参数二维码图片是否已经存在 */
        if (file_exists($dirName . $erweima)) {
            $filename = $erweima;
        } else {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_URL, $filename);
            ob_start();
            curl_exec($ch);
            $headfile = ob_get_contents();
            ob_end_clean();
            if (! file_exists($dirName)) {
                mkdir($dirName, 0777, true);
            }
            // 保存文件
            $res = fopen($dirName . $erweima, 'a');
            fwrite($res, $headfile);
            fclose($res);
            $filename = $erweima;
        }
        // exit;
        /* 二维码组合底图1 */
        $src = imagecreatefromjpeg($dirName . $filename);
        list ($width, $height, $type, $attr) = getimagesize($dirName . $filename);
        $image = imagecreatetruecolor($kuan, $kuan);
        imagecopyresampled($image, $src, 0, 0, 0, 0, $kuan, $kuan, $width, $height);
        imagecopymerge($dest, $image, $x, $y, 0, 0, $kuan, $kuan, 100);
        // /*end 二维码*/$x, $y,$wx_id 20, 580
        
        // /* 图片组合完成 保存图片 */
        $pic = $userid . $name . 'tui.jpg';
        $res = imagejpeg($dest, $dirName . $pic);
        $url = 'http://' . $_SERVER['HTTP_HOST'] . '/duan/LKT/images/' . $pic; /* end 保存 */
        return $url;
    }

    function createPromotion (Request $request)
    {
        $url = [];
        
        
        $wx_id = $request->param('openid');
        
        $r=$this->getModel('Extension')->fetchAll('image,x,y,kuan');
        if ($r) {
            foreach ($r as $key => $value) {
                $str = $value->image;
                $img = str_replace("/duan/", "../", $str);
                $img_url = $this->getPromotion($key + 1, $img, $value->x, $value->y, $wx_id, $value->kuan);
                $url[$key] = array(
                                'hpcontent_id' => $key + 1,'hp_img_url' => $img_url
                );
            }
        }
        echo json_encode(array(
                                'status' => 1,'pictures' => $url
        ));
        exit();
    }

}