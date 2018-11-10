<?php
use core\Session;
use core\Response;

/**
 * 验证码类
 * 
 * @author xtw
 */
class Captcha
{

    protected $options = [
                            'width' => 120,'height' => 40,'bg_color' => [
                                                                            223,225,230
                            ],'length' => 4,'font_size' => 20,
        'zh' => false,'codeSet' => 'qwertyuiopasdfghjklzxcvbnmQWERTYUIOPASDFGHJKLZXCVBNM23456789',
        'zhSet' => '我人有的和主产不为这工要在地一上是中国经以发了民同多从朋折笔方立水炎之式林大寺五止昌吕男比双妇子忆册鑫众月白禾言立'
    ];

    protected $image;

    protected $font_color;

    function __construct($options = [])
    {
        $this->options = array_merge($this->options, $options);
    }

    public function create()
    {
        $this->image = imagecreate($this->width, $this->height);
        imagecolorallocate($this->image, $this->bg_color[0], $this->bg_color[1], $this->bg_color[2]);
        $this->font_color = imagecolorallocate($this->image, mt_rand(1, 120), mt_rand(1, 120), mt_rand(1, 120));
        $getcode = [];
        $code_x = $this->width / $this->length;
        $code_y = $this->font_size;
        $asset_path = __DIR__ . DS . 'captcha' . DS . 'assets' . DS;
        
        $this->drawBg($this->getAssetFile($asset_path.'bgs'.DS,'*.jpg'));
        
        for ($i = 0; $i < $this->length; $i ++) {
            if ($this->zh == false) {
                $getcode[$i] = $this->codeSet[mt_rand(0, strlen($this->codeSet) - 1)];
                $fontfile = $this->getAssetFile($asset_path . 'ttfs' . DS);
            } else {
                $getcode[$i] = $this->toUnicode(iconv_substr($this->zhSet, mt_rand(0, mb_strlen($this->zhSet, 'utf-8') - 1), 1, 'utf-8'));
                $fontfile = $this->getAssetFile($asset_path . 'zhttfs' . DS);
            }
            $x = $code_x * $i + ($code_x - $this->font_size) / 2 * mt_rand(2, 9) / 20;
            $x <= 0 && $x = 2;
            $y = $code_y * (1 + mt_rand(6, 10) / 40);
            imagettftext($this->image, $this->font_size, mt_rand(- 30, 30), $x, $y, $this->font_color, $fontfile, $getcode[$i]);
        }
        $data = imagepng($this->image);
        imagedestroy($this->image);
        return Response::instance($data, 'jpeg', 200)->contentType('image/png');
    }
    /**
     * 添加背景
     * @param  $file
     */
   private function drawBg($file)
   {
       list($src_w,$src_h)=getimagesize($file);
       $src_w>$this->width&&$src_w=$this->width;
       $src_h>$this->height&&$src_w=$this->height;      
       $bg=imagecreatefromjpeg($file);
       imagecopyresampled($this->image, $bg, 0, 0, 0, 0, $this->width, $this->height, $src_w, $src_h);
       imagedestroy($bg);
   }
   /**
    * 画干扰
    */
   private function drawNoise()
   {
       
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

    private function toUnicode($string)
    {
        $str = mb_convert_encoding($string, 'UCS-2', 'UTF-8');
        $arrstr = str_split($str, 2);
        $unistr = '';
        foreach ($arrstr as $n) {
            $dec = hexdec(bin2hex($n));
            $unistr .= '&#' . $dec . ';';
        }
        return $unistr;
    }
}