<?php
namespace core\session;

/**
 * redis session handler
 */
class Redis extends \SessionHandler
{

    protected $handler;

    protected $options = [
                            'host' => '127.0.0.1', // redis主机
'port' => 6379, // redis端口
'password' => '', // 密码
'select' => 0, // 操作库
'expire' => 3600, // 有效期(秒)
'timeout' => 0, // 超时时间(秒)
'persistent' => true, // 是否长连接
'prefix' => '' // sessionkey前缀
    ];

    function __construct($options = [])
    {
        $this->options = array_merge($this->options, $options);
       // echo __FUNCTION__;
    }

    function destroy($id)
    {
        return $this->handler->delete($this->options['prefix'].$id)?true:false;
    }

    function gc($maxlifetime)
    {
        return true;
    }

    function open($path, $name)
    {
        if (! extension_loaded('redis'))
            throw new \Exception('redis can not load');
        $this->handler = new \Redis();
        
        $this->options['persistent'] ? $this->handler->pconnect($this->options['host'], $this->options['port'], $this->options['timeout'], 'persistent_id_' . $this->options['select']) : $this->handler->connect($this->options['host'], $this->options['port'], $this->options['timeout']);
        if (! empty($this->options['password']))
            $this->handler->auth($this->options['password']);
        if ($this->options['select'])
            $this->handler->select($this->options['select']);
        return true;
    }

    function close()
    {
       $this->handler->close();
       $this->handler=null;
      // echo __FUNCTION__;
        return true;
    }

    function read($id)
    {
        return (string)$this->handler->get($this->options['prefix'].$id);
    }

    function write($session_id, $session_data)
    {
       // echo __FUNCTION__;
      // var_dump($this->options);
        return $this->options['expire']>0?$this->handler->setex($this->options['prefix'].$session_id,$this->options['expire'],$session_data):
        $this->handler->set($this->options['prefix'].$session_id,$session_data);     
    }
}