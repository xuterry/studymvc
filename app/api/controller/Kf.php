<?php
namespace app\api\controller;
use core\Request;
use core\Session;

class Kf extends Api
{

    function __construct()
    {
        parent::__construct();
    }
    public function valid (Request $request)
    {
        // 1.读取echostr字段的值
        $echoStr = $request["echostr"];
        // 2.调用私有方法, 方法返回值验证成功还是失败
        if ($this->checkSignature($request)) {
            echo $echoStr;
            exit();
        }
    }

    private function checkSignature($request)
    {
        // 1）将token、timestamp、nonce三个参数进行字典序排序
        $signature = $request["signature"];
        $timestamp = $request["timestamp"];
        $nonce = $request["nonce"];
        $token = Session::get('TOKEN');
        
        $tmpArray = array(
                        $token,$timestamp,$nonce
        );
        sort($tmpArray);
        
        // 2）将三个参数字符串拼接成一个字符串进行sha1加密
        $tmpStr = implode($tmpArray);
        $tmpStr = sha1($tmpStr);
        
        // 3）开发者获得加密后的字符串可与signature对比，标识该请求来源于微信
        if ($tmpStr == $signature) {
            return true;
        } else {
            return false;
        }
    }

    public function responseMsg (Request $request)
    {
        /**
         * 1.接收微信服务器发送的XML字符串 PHP7.0+ $postStr = file_get_contents("php://input"); PHP7.0以下:
         */
        $postStr = file_get_contents("php://input");
        // 2.将字符串转成对象
        if (! empty($postStr)) {
            $postObj = simplexml_load_string($postStr, "SimpleXMLElement", LIBXML_NOCDATA);
            // 判断消息类型
            $msgType = $postObj->MsgType;
            switch ($msgType) {
                case "text": // 文本类型
                    logger("debug", "TR0010", "[index.php: responseMsg]; 接收到文本消息, 内容是:" . $postObj->Content);
                    $result = $this->receiveText($postObj);
                    break;
                case "image": // 图片类型
                    $result = $this->receiveImage($postObj);
                    break;
                case "event": // 事件类型
                    $result = $this->receiveEvent($postObj);
                    break;
                default: // 其他类型
                    $content = "不是要求的消息类型";
                    $result = $this->transmitText($postObj, $content);
                    break;
            }
            echo $result;
        }
    }

    private function receiveEvent($postObj)
    {
        $eventType = $postObj->Event;
        switch ($eventType) {
            case "subscribe": // 关注事件
                $content = $this->handleSubscribe($postObj);
                break;
            
            case "SCAN": // 针对关注用户, 扫描带参数二维码
                $content = "针对关注用户, 扫描带参数二维码";
                break;
            
            case "CLICK": // 自定义菜单点击事件
                          // 判断点击哪个按钮
                switch ($postObj->EventKey) {
                    case "TRKEY_01_02": // 第一级菜单第二个二级菜单按钮
                        $content[] = array(
                                        "Title" => "接入公众号支付","Description" => "描述1","PicUrl" => "https://df","Url" => "http://php.dajiangsai.org/wechat/deal.php"
                        );
                        $content[] = array(
                                        "Title" => "第二条图文消息","Description" => "描述2","PicUrl" => "https://df","Url" => "http://m.baidu.com"
                        );
                        break;
                    
                    default:
                        $content = "点击其他click按钮";
                        break;
                }
                break;
            
            default:
                break;
        }
        
        if (is_array($content)) {
            $result = $this->transimtNews($postObj, $content);
        } else {
            $result = $this->transmitText($postObj, $content);
        }
        return $result;
    }

    private function handleSubscribe($postObj)
    {
        if (! empty($postObj->EventKey)) {
            // 针对没有关注用户, 扫描带参数二维码
            // 判断场景参数id // qrscene_123123
            $sceneID = str_replace("qrscene_", "", $postObj->EventKey);
            switch ($sceneID) {
                case "123123":
                    $content = "针对没有关注用户, 扫描带参数二维码: 123123";
                    break;
                
                default:
                    $content = "针对没有关注用户, 扫描带参数二维码";
                    break;
            }
        } else { // 一般关注事件(扫描公众号二维码)
            $content = getUserInfo($postObj, getAccessToken(APP_ID, APP_SECRECT));
        }
        return $content;
    }

    private function receiveText($postObj)
    {
        // 1.如果用户输入的关键词是"图文"
        $keyword = trim($postObj->Content);
        if ($keyword == "图文") {
            // 2.拼接图文XML, 返回
            $contentArray = array();
            $contentArray[] = array(
                                    "Title" => "标题1","Description" => "描述1","PicUrl" => "http://1.shirleytest.applinzi.com/images/596c7157N852de046.jpg","Url" => "https://m.baidu.com"
            );
            $contentArray[] = array(
                                    "Title" => "标题2","Description" => "描述2","PicUrl" => "http://1.shirleytest.applinzi.com/images/CW-t-fypceiq6378139.jpg","Url" => "https://www.github.com"
            );
            $contentArray[] = array(
                                    "Title" => "标题3","Description" => "描述3","PicUrl" => "http://1.shirleytest.applinzi.com/images/596c7157N852de046.jpg","Url" => "https://www.apple.com.cn"
            );
            $contentArray[] = array(
                                    "Title" => "标题4","Description" => "描述4","PicUrl" => "http://1.shirleytest.applinzi.com/images/59bf3c47N91d65c73.jpg","Url" => "https://m.dianping.com"
            );
            $result = $this->transimtNews($postObj, $contentArray);
        } else {
            $content = "你发送的是文本消息, 返回你输入内容: " . $postObj->Content;
            $result = $this->transmitText($postObj, $content);
        }
        return $result;
    }

    private function receiveImage($postObj)
    {
        $content = "你发送的是图片消息, 返回图片url: " . $postObj->PicUrl;
        $result = $this->transmitText($postObj, $content);
        return $result;
    }

    private function transmitText($postObj, $content)
    {
        // 1.解析XML数据
        $toUserName = $postObj->FromUserName;
        $fromUserName = $postObj->ToUserName;
        $createTime = time();
        // 2.拼接JSON数据(文本消息)
        $MsgId = $createTime . '123456';
        $arr = array(
                    'ToUserName' => $toUserName,'FromUserName' => $fromUserName,'CreateTime' => $createTime,'MsgType' => "text",'Content' => $content,'MsgId' => $MsgId
        );
        $textStr = json_encode($arr);
        // 调用logger方法
        logger("info", "TR0001", "返回文本消息字符串: " . $textStr);
        // 3.返回
        return $textStr;
    }

    private function transimtNews($postObj, $content)
    {
        if (! is_array($content)) {
            return;
        }
        
        // 1.拼接第二部分
        $itemStr = "<item>
                <Title><![CDATA[%s]]></Title>
                <Description><![CDATA[%s]]></Description>
                <PicUrl><![CDATA[%s]]></PicUrl>
                <Url><![CDATA[%s]]></Url>
                </item>";
        $tmpStr = "";
        foreach ($content as $item) {
            $tmpStr .= sprintf($itemStr, $item["Title"], $item["Description"], $item["PicUrl"], $item["Url"]);
        }
        // 2.拼接完整
        $newsStr = "<xml>
                <ToUserName><![CDATA[%s]]></ToUserName>
                <FromUserName><![CDATA[%s]]></FromUserName>
                <CreateTime>%s</CreateTime>
                <MsgType><![CDATA[news]]></MsgType>
                <ArticleCount>%s</ArticleCount>
                <Articles>$tmpStr</Articles>
                </xml>";
        $result = sprintf($newsStr, $postObj->FromUserName, $postObj->ToUserName, time(), count($content));
        return $result;
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
        $result = httpsRequest($url);
        $jsonArray = json_decode($result, true);
        // 写入文件
        $accessToken = $jsonArray['access_token'];
        file_put_contents($fileName, $accessToken);
        return $accessToken;
    }

    function getUserInfo($postObj, $accessToken)
    {
        // 1.openID
        $openID = $postObj->FromUserName;
        // 2.url
        $url = "https://api.weixin.qq.com/cgi-bin/user/info?access_token=" . $accessToken . "&openid=" . $openID . "&lang=zh_CN";
        // 3.发送请求
        $jsonStr = httpsRequest($url);
        // 4.json->array
        $userInfoArray = json_decode($jsonStr, true);
        // 5.拼接字符串
        $nameTmpStr = "您好, " . $userInfoArray['nickname'];
        $sexTmpStr = "性别: " . (($userInfoArray['sex'] == 1) ? "男" : (($userInfoArray['sex'] == 2) ? "女" : "未知"));
        $locationTmpStr = "地区: " . $userInfoArray['country'] . " " . $userInfoArray['province'] . " " . $userInfoArray['city'];
        $languageTmpStr = "语言: " . (($userInfoArray['language'] == "zh_CN") ? "简体中文" : "未知");
        $dateTmpStr = "关注: " . date("Y年m月d日", $userInfoArray['subscribe_time']);
        $finalTmpStr = $nameTmpStr . "\n" . $sexTmpStr . "\n" . $locationTmpStr . "\n" . $languageTmpStr . "\n" . $dateTmpStr;
        
        file_put_contents("saestor://1708test/finalTmpStr.txt", $finalTmpStr);
        
        // 6.返回
        return $finalTmpStr;
    }

    function logger($level, $errorCode, $content)
    {
        // 不允许超过5M
        $maxBytes = 5 * 1024 * 1024; // bytes
        $logFileName = "error.log";
        // 判断文件大小
        if (file_exists($logFileName) && (filesize($logFileName) > $maxBytes)) {
            unlink($logFileName);
        }
        $content = "[" . date("Y/m/d H:i:s", time()) . "] [:" . $level . "] " . $errorCode . ": " . $content . "\n";
        file_put_contents($logFileName, $content, FILE_APPEND);
        // 注意: 新浪云SAE上述代码修改成下面的代码, 并且不支持FILE_APPEND追加形式
    }

    function createQRcode($sceneType, $sceneID, $accessToken)
    {
        // 1. 根据二维码类型, 给定不同的post数据
        switch ($sceneType) {
            case "QR_SCENE": // 临时
                $postData = '{"expire_seconds": 604800, "action_name": "QR_SCENE", "action_info": {"scene": {"scene_id": ' . $sceneID . '}}';
                break;
            case "QR_limit_SCENE": // 永久
                $postData = '{"action_name": "QR_limit_SCENE", "action_info": {"scene": {"scene_id": ' . $sceneID . '}}}';
                break;
        }
        
        // 2. 拼接url, 发送post请求(ticket)
        $url = "https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token=" . $accessToken;
        $result = httpsRequest($url, $postData);
        
        // 3. JSON解析, 获取ticket字符串
        $ticketArray = json_decode($result, true);
        $ticketStr = $ticketArray['ticket'];
        
        // 4. 返回拼接url(二维码图片)
        $qrCodeUrl = "https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=" . urlencode($ticketStr);
        
        return $qrCodeUrl;
    }

}