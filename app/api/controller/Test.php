<?php
namespace app\api\controller;
use core\Request;
use core\Session;

class Test extends Api
{

    function __construct()
    {
        parent::__construct();
    }
    public function Send_fail($uid, $fromid, $sNo, $p_name, $price, $template_id, $page)
    {
        
        
        $r=$this->getModel('Config')->where(['id'=>['=','1']])->fetchAll('*');
        if ($r) {
            $appid = $r[0]->appid; // 小程序唯一标识
            $appsecret = $r[0]->appsecret; // 小程序的 app secret
            $AccessToken = $this->getAccessToken($appid, $appsecret);
            $url = 'https://api.weixin.qq.com/cgi-bin/message/wxopen/template/send?access_token=' . $AccessToken;
        }
        
        $data = array();
        $data['access_token'] = $AccessToken;
        $data['touser'] = $uid;
        $data['template_id'] = $template_id;
        $data['form_id'] = $fromid;
        $data['page'] = $page;
        $price = $price . '元';
        $minidata = array(
                        'keyword1' => array(
                                            'value' => $sNo,'color' => "#173177"
                        ),'keyword2' => array(
                                            'value' => $p_name,'color' => "#173177"
                        ),'keyword3' => array(
                                            'value' => $price,'color' => "#173177"
                        ),'keyword4' => array(
                                            'value' => '退回到钱包','color' => "#FF4500"
                        ),'keyword5' => array(
                                            'value' => '拼团失败--退款','color' => "#FF4500"
                        )
        );
        $data['data'] = $minidata;
        
        $data = json_encode($data);
        
        $da = $this->httpsRequest($url, $data);
        $delete_rs=$this->getModel('UserFromid')->delete($fromid,'fromid');
        var_dump(json_encode($da));
    }

    private function wxrefundapi($ordersNo, $refund, $total_fee, $price)
    {
        // 通过微信api进行退款流程
        
        $r=$this->getModel('Config')->where(['id'=>['=','1']])->fetchAll('*');
        if ($r) {
            $appid = $r[0]->appid;
            // 小程序唯一标识
            $appsecret = $r[0]->appsecret;
            // 小程序的 app secret
            $company = $r[0]->company;
            $mch_key = $r[0]->mch_key; // 商户key
            $mch_id = $r[0]->mch_id; // 商户mch_id
        }
        
        $parma = array(
                    'appid' => $appid,'mch_id' => $mch_id,'nonce_str' => $this->createNoncestr(),'out_refund_no' => $refund,'out_trade_no' => $ordersNo,'total_fee' => $total_fee,'refund_fee' => $price,'op_user_id' => $appid
        );
        $parma['sign'] = $this->getSign($parma, $mch_key);
        $xmldata = $this->arrayToXml($parma);
        $xmlresult = $this->postXmlSSLCurl($xmldata, 'https://api.mch.weixin.qq.com/secapi/pay/refund');
        $result = $this->xmlToArray($xmlresult);
        return $result;
    }

    protected function createNoncestr($length = 32)
    {
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i ++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }

    protected function getSign($Obj, $mch_key)
    {
        foreach ($Obj as $k => $v) {
            $Parameters[$k] = $v;
        }
        // 签名步骤一：按字典序排序参数
        ksort($Parameters);
        $String = $this->formatBizQueryParaMap($Parameters, false);
        // 签名步骤二：在string后加入KEY
        $String = $String . "&key=" . $mch_key;
        // 签名步骤三：MD5加密
        $String = md5($String);
        // 签名步骤四：所有字符转为大写
        $result_ = strtoupper($String);
        return $result_;
    }

    protected function formatBizQueryParaMap($paraMap, $urlencode)
    {
        $buff = "";
        ksort($paraMap);
        foreach ($paraMap as $k => $v) {
            if ($urlencode) {
                $v = urlencode($v);
            }
            // $buff .= strtolower($k) . "=" . $v . "&";
            $buff .= $k . "=" . $v . "&";
        }
        $reqPar;
        if (strlen($buff) > 0) {
            $reqPar = substr($buff, 0, strlen($buff) - 1);
        }
        return $reqPar;
    }

    protected function arrayToXml($arr)
    {
        $xml = "<xml>";
        foreach ($arr as $key => $val) {
            if (is_numeric($val)) {
                $xml .= "<" . $key . ">" . $val . "</" . $key . ">";
            } else {
                $xml .= "<" . $key . "><![CDATA[" . $val . "]]></" . $key . ">";
            }
        }
        $xml .= "</xml>";
        return $xml;
    }

    protected function xmlToArray($xml)
    {
        $array_data = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        return $array_data;
    }

    private function postXmlSSLCurl($xml, $url, $second = 30)
    {
        $ch = curl_init();
        // 超时时间
        curl_setopt($ch, CURLOPT_TIMEOUT, $second);
        // 这里设置代理，如果有的话
        // curl_setopt($ch,CURLOPT_PROXY, '8.8.8.8');
        // curl_setopt($ch,CURLOPT_PROXYPORT, 8080);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        // 设置header
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        // 要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        // 设置证书
        // 使用证书：cert 与 key 分别属于两个.pem文件
        // 默认格式为PEM，可以注释
        $cert = str_replace('lib', 'filter', MO_LIB_DIR) . '/apiclient_cert.pem';
        $key = str_replace('lib', 'filter', MO_LIB_DIR) . '/apiclient_key.pem';
        curl_setopt($ch, CURLOPT_SSLCERTTYPE, 'PEM');
        curl_setopt($ch, CURLOPT_SSLCERT, $cert);
        // 默认格式为PEM，可以注释
        curl_setopt($ch, CURLOPT_SSLKEYTYPE, 'PEM');
        curl_setopt($ch, CURLOPT_SSLKEY, $key);
        // post提交方式
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        $data = curl_exec($ch);
        // 返回结果
        if ($data) {
            curl_close($ch);
            return $data;
        } else {
            $error = curl_errno($ch);
            echo "curl出错，错误码:$error" . "<br>";
            curl_close($ch);
            return false;
        }
    }

    public function Send_success($arr, $template_id)
    {
        
        
        $r=$this->getModel('Config')->where(['id'=>['=','1']])->fetchAll('*');
        if ($r) {
            $appid = $r[0]->appid; // 小程序唯一标识
            $appsecret = $r[0]->appsecret; // 小程序的 app secret
            
            $AccessToken = $this->getAccessToken($appid, $appsecret);
            $url = 'https://api.weixin.qq.com/cgi-bin/message/wxopen/template/send?access_token=' . $AccessToken;
        }
        foreach ($arr as $k => $v) {
            $data = array();
            $data['access_token'] = $AccessToken;
            $data['touser'] = $v->uid;
            $data['template_id'] = $template_id;
            $data['form_id'] = $v->fromid;
            $data['page'] = "pages/order/detail?orderId=$v->id";
            $p_price = $v->p_price . '元';
            $z_price = $v->z_price . '元';
            $minidata = array(
                            'keyword1' => array(
                                                'value' => $v->p_name,'color' => "#173177"
                            ),'keyword2' => array(
                                                'value' => $p_price,'color' => "#173177"
                            ),'keyword3' => array(
                                                'value' => $z_price,'color' => "#173177"
                            ),'keyword4' => array(
                                                'value' => $v->sNo,'color' => "#173177"
                            ),'keyword5' => array(
                                                'value' => '拼团失败','color' => "#FF4500"
                            ),'keyword6' => array(
                                                'value' => $v->add_time,'color' => "#173177"
                            )
            );
            $data['data'] = $minidata;
            
            $data = json_encode($data);
            
            $da = $this->httpsRequest($url, $data);
            $re2=$this->getModel('UserFromid')->delete($v->fromid,'fromid');
            
            var_dump(json_encode($da));
        }
    }

    public function get_fromid($openid, $type = '')
    {
        
        
        if (empty($type)) {
            $fromidres=$this->getModel('UserFromid')->where('open_id','=',function($query) use($openid) {
             //   select max(id) from lkt_user_fromid where open_id='$openid'
                    $query->name('user_fromid')->where("open_id='".$openid."'")->max('id');
            })->fetchAll('fromid,open_id');
            if ($fromidres) {
                $fromid = $fromidres[0]->fromid;
                $arrayName = array(
                                'openid' => $openid,'fromid' => $fromid
                );
                return $arrayName;
            } else {
                return array(
                            'openid' => $openid,'fromid' => '123456'
                );
            }
        } else {
            $re2=$this->getModel('UserFromid')->delete($type,'fromid');
            $fromidres=$this->getModel('UserFromid')->where('open_id','=',function($query) use($openid) {
                $query->name('user_fromid')->where("open_id='".$openid."'")->max('id');
            })->fetchAll('fromid,open_id');
            if ($fromidres) {
                $fromid = $fromidres[0]->fromid;
                $arrayName = array(
                                'openid' => $openid,'fromid' => $fromid
                );
                return $arrayName;
            } else {
                return array(
                            'openid' => $openid,'fromid' => '123456'
                );
            }
        }
    }

    function httpsRequest($url, $data = null)
    {
        // 1.初始化会话
        $ch = curl_init();
        // 2.设置参数: url + header + 选项
        // 设置请求的url
        curl_setopt($ch, CURLOPT_URL, $url);
        // 保证返回成功的结果是服务器的结果
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if (! empty($data)) {
            // 发送post请求
            curl_setopt($ch, CURLOPT_POST, 1);
            // 设置发送post请求参数数据
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }
        // 3.执行会话; $result是微信服务器返回的JSON字符串
        $result = curl_exec($ch);
        // 4.关闭会话
        curl_close($ch);
        return $result;
    }

    function getAccessToken($appID, $appSerect)
    {
        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=" . $appID . "&secret=" . $appSerect;
        // 时效性7200秒实现
        // 1.当前时间戳
        $currentTime = time();
        // 2.修改文件时间
        $fileName = "accessToken"; // 文件名
        if (is_file($fileName)) {
            $modifyTime = filemtime($fileName);
            if (($currentTime - $modifyTime) < 7200) {
                // 可用, 直接读取文件的内容
                $accessToken = file_get_contents($fileName);
                return $accessToken;
            }
        }
        // 重新发送请求
        $result = $this->httpsRequest($url);
        $jsonArray = json_decode($result, true);
        // 写入文件
        $accessToken = $jsonArray['access_token'];
        file_put_contents($fileName, $accessToken);
        return $accessToken;
    }

}