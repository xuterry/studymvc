<?php
namespace core;

/**
 * /xtw 2018 输出类
 */
class Response
{

    // 输出数据
    protected $data;

    // 文件头
    protected $head = [];

    // 状态码
    protected $code = 200;

    // 编码
    protected $charset = 'utf-8';

    // 类型 暂时支持html/text json
    protected $type = 'text';

    function __construct($data = '', $type = '', $code = 0, $head = [])
    {
        $this->code = ! empty($code) ? $code : $this->code;
        $this->type = empty($type) ? $this->type : $type;
        $this->head=array_merge($this->head,$head);
        $this->data = $data;

    }

    /**
     * 输出内容
     * 
     * @param string $content            
     * @param string $type            
     * @param number $code            
     * @param array $head            
     * @return \core\Response
     */
    public static function instance($data = '', $type = '', $code = 0, $head = [])
    {    
        return (new self($data, $type, $code, $head));
    }

    /**
     * 发送头部信息
     */
    protected function headsend()
    {
        if (! headers_sent() && ! empty($this->head)) {
            // 发送状态码
            http_response_code($this->code);
            // 发送头部信息
            foreach ($this->head as $name => $val) {
                if (is_null($val)) {
                    header($name);
                } else {
                    header($name . ':' . $val);
                }
            }
        }
    }
/**
 * 重新初始数据
 */
   protected function init($obj)
   {
       if($obj instanceof $this){
            $this->head = $obj->head;
            $this->charset = $obj->charset;
            $this->data = $obj->data;
            $this->code = $obj->code;
            $this->type = $obj->type;
        }else
           $this->data=$obj;
   }
    /**
     * 发送数据
     */
    function send($obj=[])
    {
        if(!empty($obj))
            $this->init($obj);
        $this->setcontenttype();
        $this->headsend();
        if($this->type=='json')
            $this->setjson($this->data);
        echo $this->data;
     //   exit();
    }
    /**
     * 转化为json
     */
    protected function setjson()
    {
        $options = JSON_UNESCAPED_UNICODE;
        $this->data=is_array($this->data)?json_encode($this->data,$options):json_encode($this->toArray($this->data),$options);
    }
    protected function toArray($data)
    {
       if(is_string($data))
           return (array)($data);
       if(is_object($data))
           $return=(array)$data;
       if(is_array($return)){
           foreach($return as $key=>$value)
               $return[$key]=$this->toArray($value);
       }
       return $return;
    }
    /**
     * 设置返回类型的头部
     */
   protected function setcontenttype()
   {
       if($this->type=='text')
           $this->head['Content-Type'] ='text/html; charset=' . $this->charset;
       if($this->type=='json')
           $this->head['Content-Type']='application/json';
       return $this;
       
   }
    function __destruct()
    {
        unset($this->data);
    }
}