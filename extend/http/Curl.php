<?php
namespace http;
class Curl
{
    /**
 * Power: Mikkle
 * Email：776329498@qq.com
 * @param $url
 * @return bool|mixed
 */
    static public function get($url,$cookie=false,$return_type=0,$ref=''){
        $oCurl = curl_init();
        if(stripos($url,"https://")!==FALSE){
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, FALSE);
            curl_setopt($oCurl, CURLOPT_SSLVERSION, 1); //CURL_SSLVERSION_TLSv1
        }
        curl_setopt($oCurl , CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/74.0.3729.169 Safari/537.36");
      //  curl_setopt($oCurl , CURLOPT_HEADER, 0);
        curl_setopt($oCurl, CURLOPT_URL, $url);
        !empty($cookie)&&curl_setopt($oCurl, CURLOPT_COOKIE, $cookie);        
        $return_type>0&&curl_setopt($oCurl , CURLOPT_HEADER, 1);       
        !empty($ref)&&curl_setopt($oCurl, CURLOPT_REFERER,$ref);
        
        curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1 );
        $sContent = curl_exec($oCurl);
        if($return_type>0){
            $headerSize = curl_getinfo($oCurl, CURLINFO_HEADER_SIZE);
            $header=substr($sContent,0,$headerSize);
            $body=substr($sContent,$headerSize);
            preg_match_all("/set\-cookie:([^\r\n]*)/i", $header, $matches);
            $cookie = $matches[1];
            curl_close($oCurl);          
            return [$body,$header,$cookie];
        }else{
            curl_close($oCurl);          
            return $sContent;
        }
    }

    /**
     * Power: Mikkle
     * Email：776329498@qq.com
     * @param $url
     * @return bool|mixed
     */
    static public function getXml($url){
        $oCurl = curl_init();
        if(stripos($url,"https://")!==FALSE){
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, FALSE);
            curl_setopt($oCurl, CURLOPT_SSLVERSION, 1); //CURL_SSLVERSION_TLSv1
        }
        curl_setopt($oCurl, CURLOPT_URL, $url);
        curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1 );
        $sContent = curl_exec($oCurl);
        $aStatus = curl_getinfo($oCurl);
        curl_close($oCurl);
        $xml = simplexml_load_string($sContent);
        if($xml){
            return $xml;
        }else{
            return false;
        }


    }

    /**
     * POST 请求
     * @param string $url
     * @param array $param
     * @param boolean $post_file 是否文件上传
     * @return string content
     */
    static public function post($url,$param,$cookie=false,$return_type=0,$ref=''){
        $oCurl = curl_init();
        if(stripos($url,"https://")!==FALSE){
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($oCurl, CURLOPT_SSLVERSION, 1); //CURL_SSLVERSION_TLSv1
        }
        if (is_string($param)) {
            $strPOST = $param;
        } else{
            $aPOST = array();
            foreach($param as $key=>$val){
                $aPOST[] = $key."=".urlencode($val);
            }
            $strPOST =  join("&", $aPOST);
        }
        curl_setopt($oCurl , CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/74.0.3729.169 Safari/537.36");      
        curl_setopt($oCurl, CURLOPT_URL, $url);
        curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt($oCurl, CURLOPT_POST,true);
        curl_setopt($oCurl, CURLOPT_POSTFIELDS,$strPOST);
        !empty($cookie)&&curl_setopt($oCurl, CURLOPT_COOKIE, $cookie);
        !empty($ref)&&curl_setopt($oCurl, CURLOPT_REFERER,$ref);       
        $return_type>0&&curl_setopt($oCurl , CURLOPT_HEADER, 1);     
        $sContent = curl_exec($oCurl);
        //$aStatus = curl_getinfo($oCurl);
        if($return_type>0){
            $headerSize = curl_getinfo($oCurl, CURLINFO_HEADER_SIZE);
            $header=substr($sContent,0,$headerSize);
            $body=substr($sContent,$headerSize);
            preg_match_all("/set\-cookie:([^\r\n]*)/i", $header, $matches);
            $cookie = $matches[1];
            curl_close($oCurl);
            return [$body,$header,$cookie];
        }else{
            curl_close($oCurl);
            return $sContent;
        }
    }


    static public function getCurlFileMedia($file_path){
        if (class_exists('\CURLFile')) {// 这里用特性检测判断php版本
            $data =  new \CURLFile($file_path,"","");//>=5.5
        } else {
            $data =  '@' . $file_path;//<=5.5
        }
        return $data;

    }
    static public function  curlFile($url,$data){
// 兼容性写法参考示例
        $curl = curl_init();
        if (class_exists('\CURLFile')) {// 这里用特性检测判断php版本
            curl_setopt($curl, CURLOPT_SAFE_UPLOAD, true);

        } else {
            if (defined('CURLOPT_SAFE_UPLOAD')) {
                curl_setopt($curl, CURLOPT_SAFE_UPLOAD, false);
            }
        }
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, 1 );
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_USERAGENT,"TEST");
        $result = curl_exec($curl);
    //    $error = curl_error($curl);
        $status = curl_getinfo($curl);
        curl_close($curl);
        if(intval($status["http_code"])==200){
            return $result;
        }else{
            return false;
        }
    }
  static public function parseCookie($cookie)
  {
      !is_array($cookie)&&$cookie=[$cookie];
      $cookies=[];
      foreach($cookie as $v){
          list($value)=explode(";",$v);
          list($key,$val)=explode("=",$value);
          $cookies[trim($key)]=trim($val);
      }
      return $cookies;
  }
  static public function getCookie($file)
  {
     $return='';
     if(is_file($file)){
         $info=unserialize(file_get_contents($file));
         foreach($info as $key=>$val)
             $return.=$key.'='.$val.';';
         return substr($return,0,-1);
     }
     return $return;
  }
  static public function saveCookie($file,$cookies)
  {
      $cookies=self::parseCookie($cookies);  
      if(!empty($cookies))
      writefile('',$file,serialize($cookies));
  }
  static public function updateCookie($file,$cookies)
  {
      $cookies=self::parseCookie($cookies);
      if(!empty($cookies)){
      if(is_file($file)){
          $info=unserialize(file_get_contents($file));
          foreach($cookies as $k=>$v)
          $info[$k]=$v;
      }else 
          $info=$cookies;
      writefile('',$file,serialize($cookies)); 
      }
  }
  static public function decondestr($value,$to='UTF-8',$from='GBK')
  {
      if(is_string($value)){
          return mb_convert_encoding($value,$to,$from);
      }else{
          foreach($value as $k=>$v){
              $return[$k]=mb_convert_encoding($v, $to,$from);
          }
          return $return;
      }
  }
    /**
     * 生成安全JSON数据
     * @param array $array
     * @return string
     */
    static public function jsonEncode($array)
    {
        return preg_replace_callback('/\\\\u([0-9a-f]{4})/i', create_function('$matches', 'return mb_convert_encoding(pack("H*", $matches[1]), "UTF-8", "UCS-2BE");'), json_encode($array));
    }

    static public function  curlDownload($url, $dir)
    {
        $ch = curl_init($url);
        $fp = fopen($dir, "wb");
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        $res = curl_exec($ch);
        curl_close($ch);
        fclose($fp);
        return $res;
    }
}
?>