<?php

class SimpleSp
{
    protected $links=[];
    protected $url='';
    protected $host='';
    protected $content='';
    protected $config=[
        'depth'=>2,'mul'=>false,'pattern'=>[],'content'=>'article','local'=>false
    ];
    protected $pattern=[
        'a'=> [
            'link'=>"/<a href=(.+)>(.+)<\/a>/isU",'title'=>"/title=(.+)>/isU",'image'=>"/<ima(.*)>/isU",'replace'=>['',''],
            'url'=>"/< href=\"|'(.*)\"|'/isU"
        ],
        'json'=>[
            'link'=>"/{\"(.*)\"}/isU",'title'=>"/title\":\"(.+)\"/isU",'image'=>"/\"image\":\"(.*)\"/isU",'replace'=>[['\u002F'],['/']],
            'url'=>"/id\":(.+),\"/isU"
        ]
    ];
    protected static $local_prefix='tmp_sp';
    protected  static $cookie='';
    public $urlarr = array() ;

    public $useurl = array();

    public $str_arr = array();

    public $strlen_arr = array();

    public $count = 0;

    public $gtitle = '';

    public $getdiv_count;
    
    function __construct($url='',$config=[])
    {
        $this->url=empty($url)?'https://www.baidu.com':$url;
        $this->config=array_merge($this->config,(array)$config);
        empty($this->config['pattern'])&&$this->config['pattern']=$this->pattern['a'];
        if(is_string($this->config['pattern']))
            array_key_exists($this->config['pattern'],$this->pattern)&&$this->config['pattern']=$this->pattern[$this->config['pattern']];       
        $this->host=$this->parseHost($this->url);
        $this->host==false&&exit('host err');
        if($this->config['local']==true&&is_file(self::$local_prefix.'links')){
            $this->links=unserialize(file_get_contents(self::$local_prefix.'links'));
        }
        else
           $this->init();
    }
    /**
     * 单线
     * @param unknown $count
     */
    public function startRun($count=5,$type=2)
    {
        while(($links=current($this->links))==true){
            if($links['isdo']||$links['type']!=2){
                next($this->links);
                continue;
            }
         $this->count++;
         if($this->count>$count)
                break;
          $content=self::Curl($links['url']);
          $content=$this->getContent($content);
          if(!empty($content))
          $this->updateLink($links['url'],['content'=>$content,'isdo'=>1]);
          else{
              //exit('get content err url:'.$links['url']);
          }
          sleep(2);
          next($this->links);
        }
        dump($this->links);
        $this->writeLocal();
    }
    
   public function getLink()
   {
       return $this->links;
   }
   
   
   private function getContent($content,$pattern="/<div class=\"bbt-html\"(.*)>(.+)<\/div>/isU")
   {
       preg_match($pattern,$content,$getcontent);
       if(!empty($getcontent[2]))
           return preg_replace(["/<a(.+)>(.*)<\/a>/isU","/<img(.*)>/isU","/<pre(.*)>(.*)<\/pre>/isU"],'',$getcontent[2]);
       return null;
   }
   private function init()
   {
       $content=self::Curl($this->url);
       $header=$this->getHeader($content);
       $status=$this->getHeaderItem('status',$header);
       if($status==521){
           self::$cookie=self::parseCookie($content);
           $content=self::Curl($this->url,self::$cookie);
       }
       $link_match=$this->parseLink($content,$this->config['pattern']['link']);
       if(empty($link_match[1])){
           dump($header,$content);
           exit('get link err');
       }
       else 
           $link_match=$link_match[1];
      // dump($link_match);
       $this->parseTitle($link_match);
       $this->parseImage($link_match);
       $this->addLinkAll($link_match,$this->url);
   }
    private function parseHost($url)
    {
        if(($find=strrpos($url,'.'))!==false){
            $substr=substr($url,$find);
            if(($find2=strpos($substr,'/'))!==false)
                $com=substr($substr,0,$find2);
            else 
                $com=$substr;
            return substr($url,0,$find).$com;
        }
        return false;
    }
    private function addLink($url,$parent='',$type=0,$isdo=0,$title='',$image='',$content='')
    {
        $name=substr(md5($url),0,16);
        if(!array_key_exists($name, $this->links)){
        !empty($parent)&&$parent=substr(md5($parent),0,16);
        $this->links[$name]=['url'=>$url,'parent'=>$parent,'type'=>$type,'isdo'=>$isdo,'title'=>$title,'image'=>$image,'content'=>$content];
        return true;
        }
        return false;
    }
    private function updateLink($url,$name,$value='')
    {
        $key=substr(md5($url),0,16);
        if(is_array($name)){
            foreach($name as $k=>$v){
                if(array_key_exists($key, $this->links)){
                    $this->links[$key][$k]=$v;
                }
            }
            return true;
        }
        if(array_key_exists($key, $this->links)){
            $this->links[$key][$name]=$value;
            return true;
        }
        return false;
    }
    private function hasLink($url)
    {
        $name=substr(md5($url),0,16);
         return array_key_exists($name, $this->links);
    }
    private function isDo($url)
    {
        $name=substr(md5($url),0,16);
        if(!empty($this->links[$name]['isdo']))
            return true;
        return false;
    }
    private function getHeader($content)
    {
        preg_match("/(.+)\\r\\n\\r\\n/isU",$content,$header);
        if(empty($header[1]))
            return ;
        else 
            return $header[1];
    }
    private function getHeaderItem($key='status',$header)
    {
        $headers=explode("\n",$header);
        if($key=='status'){
            return intval(str_replace([" ","HTTP/1.1",'OK'],[''],$headers[0]));
        } 
        $return=[];
        foreach($headers as $v){
            if(strpos(strtolower($v),':')!==false){
                list($key,$value)=explode(":",$v);
                $return[]=$value;
            }
        }
        return $return;
    }
  private function writeLocal()
  {
      foreach($this->links as $k=>$link){
          if($link['type']!=2||$k>$this->count||$link['isdo'])
              continue;
          $image=$link['image'];
          $explode=explode("/",$image);
          $name=self::$local_prefix.end($explode);
          if(!is_file($name)){
          $imagestr=file_get_contents($image);
          $fh=fopen($name,'w');
          fwrite($fh,$imagestr);
          fclose($fh);
          }
          $this->links[$k]['localimage']=$name;
      }
      $linkstr=serialize($this->links);
      $fh=fopen(self::$local_prefix.'links','w');
      fwrite($fh,$linkstr);
      fclose($fh);
  }
  private function parseCookie($content)
  {
      preg_match("/<script(.*)>(.+)\(\"(.+)\", 200\);(.*)<\/script>/isU",$content,$getjs);
      $js=new V8Js();
      $JS = <<< EOT
      $getjs[4]
     $getjs[3]
EOT;
      try {
          $js->executeString($JS, 'basic.js');
      } catch (V8JsException $e) {
          preg_match("/.cookie='(.+)'/isU",$e->getJsSourceLine(),$cookie);
          return $cookie[1];
          //var_dump($cookie);
         // var_dump($e->getJsSourceLine());
      }
     exit();
  }
  public static  function Curl($url)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL,$url);
       // curl_setopt($curl,CURLOPT_HTTPHEADER,[ 
       //    'accept:text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8',
           // 'accept-language: zh-CN,zh;q=0.9',
          //  'cookie: eCM1_5408_smile=2D1; UM_distinctid=163cf084a1b233-0c3b281f7e8e3-3b4e5362-fa000-163cf084a1cf7; eCM1_5408_saltkey=i22HB66q; eCM1_5408_lastvisit=1542548658; eCM1_5408_atarget=1; eCM1_5408_viewid=tid_245462; eCM1_5408_sid=OZGi1D; eCM1_5408_forum_lastvisit=D_118_1542552344D_61_1542642570; eCM1_5408_visitedfid=61D118; eCM1_5408_lastact=1542642581%09question_bestanswer.php%09; QINGCLOUDELB=ee6ea876d53355c236672bc7be0c17a04034e6907c8908535728585aa2508462|W/Qft|W/Qe6',
        //   'user-agent: Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/67.0.3396.87 Safari/537.36])']);
        if(!empty(self::$cookie))
        curl_setopt($curl,CURLOPT_HTTPHEADER,["cookie:".self::$cookie]);
        curl_setopt($curl, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/67.0.3396.87 Safari/537.36");
        curl_setopt($curl, CURLOPT_HEADER, 1);
        curl_setopt($curl, CURLOPT_REFERER, 'https://www.baidu.com');
        if (strpos($url, 'https:') !== false) {
            //curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
        $str = curl_exec($curl);
        self::parseCharset($str);
        curl_close($curl);
        return $str;
    }
 public static function mulCurl($urls)
 {
     $mh = curl_multi_init();
     $curl=[];
     foreach($urls as $k=>$url){
         $curl[$k] = curl_init();
         curl_setopt($curl[$k] , CURLOPT_URL, $url);
         curl_setopt($curl[$k] , CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/67.0.3396.87 Safari/537.36");
         curl_setopt($curl[$k] , CURLOPT_HEADER, 0);
         if (strpos($url, 'https:') !== false) {
             curl_setopt($curl[$k] , CURLOPT_SSL_VERIFYHOST, 2);
             curl_setopt($curl[$k] , CURLOPT_SSL_VERIFYPEER, false);
         }
         curl_setopt($curl[$k] , CURLOPT_RETURNTRANSFER, 1);
         curl_setopt($curl[$k] , CURLOPT_FOLLOWLOCATION, 1);
         curl_multi_add_handle ($mh,$curl[$k]);
     }
     $active = null; 
     do {
         $mrc = curl_multi_exec($mh,$active);
     } while ($mrc == CURLM_CALL_MULTI_PERFORM);
     while ($active and $mrc == CURLM_OK) {
         if (curl_multi_select($mh) != -1) {
             do {
                 $mrc = curl_multi_exec($mh, $active);
             } while ($mrc == CURLM_CALL_MULTI_PERFORM);
         }
     }
     $return=[];
     foreach ($curl as $i => $url) {
         $return[]=curl_multi_getcontent($curl[$i]);
         curl_multi_remove_handle($mh,$curl[$i]);
         curl_close($curl[$i]);
     } // 结束清理
     
     curl_multi_close($mh);
     return $return;
 }
   static function post($url, $poststr, $ref = '')
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        if ($ref != "")
            curl_setopt($ch, CURLOPT_REFERER, $ref);
        if(strpos($url,'https')!==false){
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        }
        // curl_setopt($ch, CURLOPT_TIMEOUT, 120);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        if ($poststr != "") {
            if(isset($poststr['file']))
            curl_setopt($ch,CURLOPT_SAFE_UPLOAD,true);
            curl_setopt($ch, CURLOPT_POST, 1);
          //  dump($poststr);exit();
            curl_setopt($ch, CURLOPT_POSTFIELDS, $poststr);
        }
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        $result = curl_exec($ch);
        return $result;
    }
    static function postLocal($url)
    {
        $file=self::$local_prefix.'links';
        if(is_file($file)){
            $linkinfo=unserialize(file_get_contents($file));
            //dump($linkinfo);
            $rs=[];
            foreach($linkinfo as $link){                
                isset($link['localimage'])&&$localimage=$link['localimage'];
                $content=@$link['content'];
                if(!empty($content)&&is_file($localimage)){
                 $title=$link['title'];
                 $rs[]=self::post($url,['file'=>new CURLFile(realpath($localimage),'image/jpeg',$localimage),'content'=>$content,'title'=>$title,'key'=>'abc123654']);
                }
            }
            dump($rs);
            return count($rs)>0;
        }
        return false;
    }
    public static function clearLocal()
    {
        $dir=scandir(getcwd());
        dump($dir);
        foreach($dir as $file){
            strpos($file,self::$local_prefix)!==false&&@unlink($file);
        }
    }
  /**
   * 登录
   * @param unknown $url
   * @param string $poststr
   * @param number $cookie
   * @param number $out
   * @param number $cookie2
   * @param string $ref
   * @return mixed
   */
    function postlogin($url, $poststr = '', $cookie = 0, $out = 0, $cookie2 = 0, $ref = '')
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        if ($ref != "")
            curl_setopt($ch, CURLOPT_REFERER, $ref);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        // curl_setopt($ch, CURLOPT_TIMEOUT, 120);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $arr = explode("&", $poststr);
        $l = sizeof($arr);
        if ($l == 0)
            $l = 1;
        if ($poststr != "") {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $poststr);
        }
        // curl_setopt($ch,NOBODY,1);
        // curl_setopt($ch,CURLOPT_COOKIESESSION,true);
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.0)");
        $b = $_SERVER['HTTP_USER_AGENT'];
        if (strpos($b, "MSIE") !== FALSE)
            $cookie_file = dirname(__FILE__) . "/cookie.txt";
        elseif (strpos($b, "Chrome") !== FALSE)
            $cookie_file = dirname(__FILE__) . "/cookie1.txt";
        else
            $cookie_file = dirname(__FILE__) . "/cookie2.txt";
        if ($cookie2 == 1)
            curl_setopt($ch, CURLOPT_COOKIEJAR, dirname(__FILE__) . "/x.txt");
        if ($cookie == 1) {
            curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file);
            $logincount = 1;
        } else
            curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        $result = curl_exec($ch);
        // $info=curl_getinfo($ch);
        // print_r($info);
        curl_close($ch); // exit();
        if ($out == 1)
            echo $result;
        // echo $result;
        if ($cookie2 == 1) {
            copy("x.txt", strtr($cookie_file, array(
                                                    dirname(__FILE__) . "/" => ""
            )));
        }
        return $result;
    }
    /**
     * 获取连接
     * @param unknown $content
     * @param string $pattern
     */
  private function parseLink($content,$pattern='')
  {
      preg_match_all($pattern,$content,$links);
      return $links;
  }
  private function parseTitle(&$link_match)
  {
      foreach($link_match as $k=>$v){
          preg_match($this->config['pattern']['title'],$v,$gettitle);
         if(empty($gettitle[1])||mb_strlen($gettitle[1])<3)
              unset($link_match[$k]);
      }
  }
  private function parseImage(&$link_match)
  {
      foreach($link_match as $k=>$v){
          preg_match($this->config['pattern']['image'],$v,$getimage);
          if(empty($getimage[1]))
              unset($link_match[$k]);
      }
  }
  private function addLinkAll($link_match,$parent)
  {
      foreach($link_match as $links){
          $url=$this->getLinkItem($links, 'url');
          if(empty($url))
              continue;
          $title=$this->getLinkItem($links, 'title');
          $image=$this->getLinkItem($links, 'image');
          $this->addLink($url,$parent,2,0,$title,$image);
      }
  }
  private function getLinkItem($links,$name)
  {
          preg_match($this->config['pattern'][$name],$links,$getitem);
          if(empty($getitem[1]))
              return null;
           $get=$getitem[1];
           if($name=='url'){ 
           strpos($get,$this->config['content'])===false&&$get=$this->config['content'].'/'.$get;
           strpos($get,'http')===false&&$get=$this->host.'/'.$get;
           }
           return str_replace($this->config['pattern']['replace'][0],$this->config['pattern']['replace'][1],$get);
  }
  /**
   * 多线程执行
   */
  private function threadDo($thread=1)
  {
      
  }
  /**
   * 解析内容
   * @param unknown $pattern
   * @param number $min_len
   */
  private function parseContent($pattern,$min_len=50)
  {
      
  }
  private static  function parseCharset(&$str,$from='gbk',$to='utf')
  {
      $charset = strtolower(get_string("charset=", "\"", $str));
      $charset == ""&&$charset = strtolower(get_string("charset=", ">", $str));
      if($to=='utf')
          $to=$to.'-8';
      if ($charset != "" && strpos(strtolower($charset), $to) === FALSE)
              $str = iconv($from, $to."//IGNORE", $str);
  }
  /**
   * 获取div标签内容
   * @param unknown $str
   * @param number $first
   */
    function getdiv($str, $first = 0)
    {
        if ($first == 1) {
            $str = strtolower($str);
       $this->parseCharset($str);
            $title = trim(get_string("<title>", "</title>", $str));
            if (strpos($title, "_") !== FALSE)
                list ($title, $t) = explode("_", $title);
            if (strpos($title, "-") !== FALSE)
                list ($title, $t) = explode("-", $title);
            if (strpos($title, "|") !== FALSE)
                list ($title, $t) = explode("|", $title);
            $this->gtitle = trim($title);
        }
        $len = strpos($str, "</div>");
        $start = $len;
        if ($start !== FALSE) {
            $str1 = substr($str, 0, $len + 6); // 截取第一个div的内容
            $nstr = substr($str, $len + 6); // 获取第一个div之后的内容           
            $str2 = substr($str1, 0);
            $preg = array(
                        "/<a (.)*>(.)*<\/a>/isU","/<script(.)*>(.)+<\/script>/isU","/<style(.)*>(.)*<\/style>/isU","/<\!\-\-(.)*\-\->/isU","/\n/sU","/\r/sU","/ /sU","/\{(.)*\};/su","/\$\((.)*\)/sU",'/\s(?=\s)/'
            );
            $str2 = preg_replace($preg, "", $str2);
            $str2 = trim(strip_tags($str2, "<p><br><\/p>"));
            $strlen = 0;
            $strlen = strlen($str2);
            if ($strlen > 200) {
                $this->str_arr[] = $str2;
                $this->strlen_arr[] = $strlen;
            }
        }
        unset($str, $str1, $str2);
        $len3 = strpos($nstr, "</div>");
        if ($len3 !== FALSE) {
            $this->getdiv_count ++;
            $this->getdiv($nstr);
        } else {
            if (! empty($this->str_arr)) {
                arsort($this->strlen_arr);
                $key = key($this->strlen_arr);
                $con = $this->str_arr[$key];
                if (strlen($con) > 1) {
                    $fh = fopen("tmp.txt", "a");
                    fwrite($fh, $con);
                    fclose($fh);
                }
            }
            $strlen_arr = array();
            $str_arr = array();
            unset($con);
            return file_get_contents('tmp.txt');
        }
    }
/**
 * 根据百度获取内容
 * @param unknown $url
 * @param unknown $pflag
 * @param unknown $preg
 * @param number $limit
 * @param string $charset
 */
    function getFromBaidu($url, $pflag, $preg, $limit = 10, $charset = '')
    {
        $this->useurl[] = $url;
        $this->count ++;
        $kw_tmp = get_string("wd=", "&", $url);
        if (strpos($url, $pflag) !== false) {
            list ($p1, $p2) = explode($pflag, $url);
            if (strpos($p1, "baidu") !== FALSE)
                $p1 = strtr($p1, array(
                                        "https://www.baidu.com/" => ""
                ));
            $p1 = strtr($p1, array(
                                    "?" => "\\?","/" => "\\/","." => "\\."
            ));
        } else
            $p1 = '';
        $p_preg = "/((href=\")|(href='))\/" . $p1 . $pflag . "=(.)+((\")|('))/sU";
        $str = $this->Curl($url);
        echo $str;
        exit();
        if ($charset != 'utf-8') {
            $str = iconv($charset, "utf-8//IGNORE", $str);
            $kw_tmp = iconv($charset, "utf-8//IGNORE", $kw_tmp);
        }
        preg_match_all($preg, $str, $arrtmp);
        preg_match_all($p_preg, $str, $pagearr);
        $this->alltitle = '';
        $this->allcon = '';
        $i = 0;
        foreach ($arrtmp[0] as $v) {
            $i ++;
            $v = strtr($v, array(
                                "\"" => "'"
            ));
            $geturl0 = get_string("href='", "'", $v);
            if (strpos($geturl0, "link?url=") !== FALSE) {
                $this->gtitle = '';
                $gstr = $this->Curl($geturl0);

                $this->getdiv($gstr, 1);
                unset($str);
                $x = file_get_contents("tmp.txt"); // 读取临时文本文件的内容。;
                if (strlen($x) > 10) {

                    $x = strtr($x, array(
                                        "&" => "","nbsp;" => ""
                    ));
                    $this->alltitle .= "|||" . $this->gtitle;
                    $this->allcon .= "|||" . $x;
                    // 清理文件内容
                    $fh = fopen("tmp.txt", "w");
                    fwrite($fh, "");
                    fclose($fh);
                    
                    sleep(1);
                }
            }
            unset($x);
        }
        $con3 = "";
        $str2 = [];
        $con3 = $str2['trans_result'][0]['dst'];
        $con3 = strtr($con3, array(
                                    "&" => "","< / p >" => "</p>","< P >" => "<p>","< p >" => "<p>","< BR >" => "<br>","< BR / >" => "<br />","< BR" => "","< br / >" => "<br />","< br >" => "<br />","< / >" => "","；；；" => "","nbsp；" => "","< / P" => "","< >" => "","< p" => "","/ >" => "","我是在我；" => "","这是我在；" => "","这是我在|；" => "","在；" => "","；；" => "","我；" => ""
        ));

        if (sizeof($pagearr[0]) == 0) {
            $pagearr = array();
            if (substr_count($url, "com/") > 0) {
                list ($p3, $realurl) = explode("com/", $url);
                $host = $p3 . 'com/';
            }
            if (substr_count($url, "info/") > 0) {
                list ($p3, $realurl) = explode("info/", $url);
                $host = $p3 . 'info/';
            }
            if (substr_count($url, "cn/") > 0) {
                list ($p3, $realurl) = explode("cn/", $url);
                $host = $p3 . 'cn/';
            }
            if (substr_count($url, "org/") > 0) {
                list ($p3, $realurl) = explode("org/", $url);
                $host = $p3 . 'org/';
            }
            list ($p1, $p2) = explode($pflag, $realurl);
            $p1 = strtr($p1, array(
                                    "?" => "\\?","/" => "\\/","." => "\\."
            ));
            $p_preg = "/((href=\")|(href='))" . $p1 . $pflag . "=(.)+((\")|('))/sU";
            preg_match_all($p_preg, $str, $pagearr);
        }
        $this->urlarr = array();
        foreach ($pagearr[0] as $v) {
            $useflag = 0;
            $v = strtr($v, array(
                                "\"" => "","'" => "","href=" => ""
            ));
            $v = $host . $v;
            if (strpos($url, "baidu") !== FALSE)
                $v = strtr("https://www.baidu.com/" . $v, array(
                                                                "com//" => "com/"
                ));
            if (sizeof($this->useurl) > 0) {
                
                foreach ($this->useurl as $v1) {
                    if ($v1 == $v) {
                        $useflag = 1;
                        break;
                    }
                }
            }
            if ($useflag == 0)
                $this->urlarr[] = $v;
        }
        unset($str, $this->allcons, $str1, $str2, $con1, $con3, $alltitles);
        $geturl = $this->urlarr[0];
        if ($geturl != '' && $this->count <= $limit) {
            echo $this->count . "  " . $geturl . "<br/>"; // 输出页数和分页地址
            sleep(1);
            $this->getcon($geturl, $pflag, $preg, $limit, $charset);
        }
    }
}
?>

