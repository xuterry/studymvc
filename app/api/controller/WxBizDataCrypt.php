<?php
namespace app\api\controller;

class WxBizDataCrypt extends Api
{

    private $appid;

    private $sessionKey;
    
    public static $OK = 0;
    
    public static $IllegalAesKey = - 41001;
    
    public static $IllegalIv = - 41002;
    
    public static $IllegalBuffer = - 41003;
    
    public static $DecodeBase64Error = - 41004;
    /**
     * 构造函数
     * 
     * @param $sessionKey string
     *            用户在小程序登录后获取的会话密钥
     * @param $appid string
     *            小程序的appid
     */
    public function __construct($appid, $sessionKey)
    {
        $this->sessionKey = \core\Session::get('session_key');
        $this->appid = $appid;
    }

    public function decryptData($encryptedData, $iv, &$data)
    {
        if (strlen($this->sessionKey) != 24) {
            return self::$IllegalAesKey;
        }
        $aesKey = base64_decode($this->sessionKey);
        
        if (strlen($iv) != 24) {
            return self::$IllegalIv;
        }
        $aesIV = base64_decode($iv);
        
        $aesCipher = base64_decode($encryptedData);
        
        $result = openssl_decrypt($aesCipher, "AES-128-CBC", $aesKey, 1, $aesIV);
        
        $dataObj = json_decode($result);
        if ($dataObj == NULL) {
            return self::$IllegalBuffer;
        }
        if ($dataObj->watermark->appid != $this->appid) {
            return self::$IllegalBuffer;
        }
        $data = $result;
        return self::$OK;
    }

}