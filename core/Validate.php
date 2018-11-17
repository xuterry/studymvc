<?php
namespace core;
/**
 验证类
 */
class Validate
{

    protected $rule;

    protected $data;

    protected $msg;

    // 验证表
    protected $check_table = [];

    function __construct($data = [], $rule = [], $msg = '')
    {
        $this->parseData($data)
            ->parseRule($rule)
            ->parseMsg($msg);
    }

    protected function parseData($data = [])
    {
        if (is_string($data)) {
            $this->data[] = $data;
        } else {
            $this->data= $data;
        }
        return $this;
    }
/**
 * 根据规则生成验证表
 * @param string $rule
 * @return \core\Validate
 */
    protected function parseRule($rule = '')
    {
        if (is_string($rule)) {
            $this->rule[] = $rule;
        } else
            $this->rule = $rule;
       // dump($this->data,$this->rule);
        foreach ($this->rule as $k => $v) {
            if (array_key_exists($k, $this->data)) {
                if (strpos($v, "|") !== false) {
                    $rules = explode("|", $v);
                    foreach ($rules as $val)
                        $this->check_table[$val] = $this->data[$k];
                } else
                    $this->check_table[$v] = $this->data[$k];
            }
        }
        return $this;
    }

    protected function parseMsg($msg = '')
    {
        $this->msg=is_string($msg)?[$msg]:$msg;
        return $this;
    }

    public function check()
    {
        foreach($this->check_table as $rule=>$data){
            if(!$this->match($rule,$data))
                return false;
        }
        return true;
    }
    protected function match($rule,$data)
    {
        $args=0;
        if(strpos($rule,':')!==false){
            list($rule,$args)=explode(':',$rule);
          //  dump($args);
        }
        if(is_callable([$this,$rule])){
            $data['_value']=$args;
           // dump($data);
            return call_user_func_array([$this,$rule],[$data]);
        }
        else{
            is_string($data)&&$data=[$data];
            try{
            foreach($data as $v){
            if( !preg_match($rule,$v))
                return false;
            }
            }catch(\Exception $e){
                dump($e);
            }
        }
        return false;
    }
    protected function requires($data)
    {
        return count($data);
        return false;
    }
    protected function fileSize($data)
    {
        $value=$data['_value'];
        unset($data['_value']);
        foreach($data as $key=>$val){
            if(strpos($key,'size')!==false)
                return $val<=$value;
        }
        return false;
    }
    protected function fileType($data)
    {
        $value=$data['_value'];
        unset($data['_value']);
        foreach($data as $key=>$val){
            if(strpos($key,'type')!==false){
                if(empty($val))
                    return false;
                if(strpos($value,',')!==false)
                    $values=explode(',', $value);
                else 
                    $values[]=$value;
                list($type1,$type2)=explode("/",$val);
                return in_array($type2,$values);
                }
        }
        return false;
    }
}