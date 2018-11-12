<?php
namespace captcha;
use core\Response;
use core\Session;
class Picture
{
        
        protected $options = [
            'width' => 120,'height' => 40,'bg_color' => [
                223,225,230
            ],'length' => 4,'font_size' => 20,
            'zh' => false,'mix'=>false,'bg'=>false,'expire'=>1800,'prefix'=>'captcha_code',
            'codeSet' => 'qwertyuiopasdfghjklzxcvbnmQWERTYUIOPASDFGHJKLZXCVBNM23456789',
            'zhSet' => '我人有的和主产不为这工要在地一上是中国经以发了民同多从朋折笔方立水炎之式林大寺五止昌吕男比双妇子忆册鑫众月白禾言立'
        ];
        
        protected $image;
        
        protected $font_color;
        
        function __construct($options = [])
        {
            $this->options = array_merge($this->options, $options);
        }
        public function create($name='')
        {
            $this->image = imagecreate($this->width, $this->height);
            imagecolorallocate($this->image, $this->bg_color[0], $this->bg_color[1], $this->bg_color[2]);
            $this->font_color = imagecolorallocate($this->image, mt_rand(1, 120), mt_rand(1, 120), mt_rand(1, 120));
            $getcode = [];
            $code_x = $this->width / $this->length;
            $code_y = $this->font_size;
            $asset_path = __DIR__ . DS . 'assets' . DS;
            
            $this->bg&&$this->drawBg($this->getAssetFile($asset_path.'bgs'.DS,'*.jpg'));
            
            $this->drawNoise();
            
            $this->drawCurve();
            
            for ($i = 0; $i < $this->length; $i ++) {
                if ($this->zh == false) {
                    $getcode[$i] = $this->codeSet[mt_rand(0, strlen($this->codeSet) - 1)];
                    $code=$getcode[$i];
                    $fontfile = $this->getAssetFile($asset_path . 'ttfs' . DS);
                } else {
                    $this->mix&&$this->zhSet.=$this->codeSet;//混合
                    $getcode[$i] = iconv_substr($this->zhSet, mt_rand(0, mb_strlen($this->zhSet, 'utf-8') - 1), 1, 'utf-8');
                    $fontfile = $this->getAssetFile($asset_path . 'zhttfs' . DS);
                    $code=$this->toUnicode($getcode[$i]);
                }
                $x = $code_x * $i + ($code_x - $this->font_size) / 2 * mt_rand(2, 9) / 20;
                $x <= 0 && $x = 2;
                $y = $code_y * (1 + mt_rand(6, 10) / 40);
                imagettftext($this->image, $this->font_size, mt_rand(- 30, 30), $x, $y, $this->font_color, $fontfile, $code);
            }
            ob_start();
            imagepng($this->image);
            $data=ob_get_clean();
            imagedestroy($this->image);
            $name=$this->prefix.$name;
            $this->setCode(implode('',$getcode), $name);
            return Response::instance($data, 'jpeg', 200)->contentType('image/png');
        }
        /**
         * 验证验证码
         * @param string$code
         * @param string $name
         * @return boolean
         */
        public function check($code,$name='')
        {
            $name=$this->prefix.$name;
            $time=time()-Session::get($name.'_time');
            if($time>$this->expire){
                Session::delete($name);
                return false;
            }
            if(md5(strtolower(trim($code)))==Session::get($name)){
                return true;
            }else{
                Session::delete($name);
            }
            return false;
        }
        /**
         * 添加背景
         * @param  $file
         */
        private function drawBg($file)
        {
            list($src_w,$src_h)=getimagesize($file);
            $src_w>$this->width&&$src_w=$this->width;
            $src_h>$this->height&&$src_h=$this->height;
            $bg=imagecreatefromjpeg($file);
            imagecopyresampled($this->image, $bg, 0, 0, 0, 0, $this->width, $this->height, $src_w, $src_h);
            imagedestroy($bg);
        }
        /**
         * 画干扰噪点
         */
        private function drawNoise()
        {
            $len1=5;
            $len2=10;
            for($i=0;$i<$len1;$i++){
                $font_size=floor(($this->height/$len1)*0.6);
                $y=$this->height/$len1*mt_rand(1,10)/10+$this->height/$len1*$i;
                $color=imagecolorallocate($this->image,  mt_rand(120,225),  mt_rand(120,225),  mt_rand(120,225));
                for($j=0;$j<$len2;$j++){
                    $x=$this->width/$len2*$j+$font_size*mt_rand(1,20)/20;
                    $str=$this->codeSet[mt_rand(0,strlen($this->codeSet)-1)];
                    imagestring($this->image, $font_size, $x, $y, $str,$color);
                }
            }
        }
        /**
         * 画干扰曲线
         */
        private function drawCurve()
        {
            $b=mt_rand(0,$this->width);
            for($i=0;$i<$this->width;$i++){
                $x_width=floor($this->height/12)+rand(1,2);
                $x=$i;
                while($x_width){
                    $y=$this->height/3*sin(2*M_PI/$this->width*$i+$b)+$this->height/2+$x_width;
                    imagesetpixel($this->image, $x, $y, $this->font_color);
                    $x_width--;
                }
            }
        }
        function __set($name, $value)
        {
            isset($this->options[$name]) && $this->options[$name] = $value;
        }
        
        function __get($name)
        {
            return isset($this->options[$name]) ? $this->options[$name] : null;
        }
        
        private function getAssetFile($path,$pattern='*.ttf')
        {
            $files = glob($path . $pattern);
            return empty($files) ? null : $files[mt_rand(0, sizeof($files) - 1)];
        }
        
        private function toUnicode($string,$from='UTF-8',$to='UCS-2')
        {
            $str = mb_convert_encoding($string,$to, $from);
            $arrstr = str_split($str, 2);
            $unistr = '';
            foreach ($arrstr as $n) {
                $dec = hexdec(bin2hex($n));
                $unistr .= '&#' . $dec . ';';
            }
            return $unistr;
        }
        private function setCode($code,$name)
        {
            //echo strtolower($code);exit();
            Session::set($name,md5($code));
            Session::set($name.'_time',time());
        }
        function __destruct()
        {
            unset($this->image);
        }
}