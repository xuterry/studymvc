<?php
namespace captcha;
use core\Response;
use core\Session;
class Drag
{
    protected $options=[
        'width'=>240,'height'=>150,'width_mark'=>50,'height_mark'=>50,'prefix'=>'captcha_code','expire'=>1800,'value'=>3,
    ];
    protected $x;
    protected $y;
    protected $image;
    
    function __construct($options=[])
    {
        $this->options=array_merge($this->options,$options);
    }
    function create($name='')
    {
        $asset_path = __DIR__ . DS . 'assets' . DS;
        
        $this->x=mt_rand(50,$this->width-$this->width_mark);
        $this->y=mt_rand(0,$this->height-$this->height_mark);  
        $this->image=imagecreatetruecolor($this->width, $this->height*3);   
        $image_bg=imagecreatefrompng($this->getAssetFile($asset_path.'drag_bg'.DS));                  
        $image_mark=imagecreatefrompng($asset_path.'drag_mark'.DS.'mark.png');
        $image_mark2=imagecreatefrompng($asset_path.'drag_mark'.DS.'mark2.png');
        
        imagecopy($this->image, $image_bg, 0, 0, 0, 0, $this->width, $this->height);
        
        imagecopy($this->image, $image_bg, 0, $this->height*2, 0, 0, $this->width, $this->height);
   
        imagecopy($this->image, $image_mark,$this->x,$this->y,0,0,$this->width_mark,$this->height_mark);
        
        $image=imagecreatetruecolor($this->width_mark, $this->height_mark);
        imagecopy($image,$image_bg,0,0,$this->x,$this->y,$this->width_mark,$this->height_mark);
        imagecopy($image,$image_mark2,0,0,0,0,$this->width_mark,$this->height_mark);
        imagecolortransparent($image,0);
        
        imagecopy($this->image, $image,0,$this->height+$this->y,0,0,$this->width_mark,$this->height_mark);
        
        ob_start();
        imagepng($this->image);
        $data=ob_get_clean();
        imagedestroy($this->image);
        imagedestroy($image_bg);
        imagedestroy($image_mark);
        imagedestroy($image_mark2);
        imagedestroy($image);
        $name=$this->prefix.$name;
        $this->setCode($this->x, $name);
        return Response::instance($data, 'jpeg', 200)->contentType('image/png');
        
    }
    function __set($name, $value)
    {
        isset($this->options[$name]) && $this->options[$name] = $value;
    }
    
    function __get($name)
    {
        return isset($this->options[$name]) ? $this->options[$name] : null;
    }
    /**
     * 验证验证码
     * @param string$code
     * @param string $name
     * @return boolean
     */
    public function check($value,$name='')
    {
        $name=$this->prefix.$name;
        $time=time()-Session::get($name.'_time');
        if($time>$this->expire){
            Session::delete($name);
            return false;
        }
        if(abs($value-Session::get($name))<=$this->value){
            return true;
        }else{
            //Session::delete($name);
        }
        return false;
    }
    private function setCode($value,$name)
    {
        Session::set($name,$value);
        Session::set($name.'_time',time());
    }
    private function getAssetFile($path,$pattern='*.png')
    {
        $files = glob($path . $pattern);
        return empty($files) ? null : $files[mt_rand(0, sizeof($files) - 1)];
    }
}