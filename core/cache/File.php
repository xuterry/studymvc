<?php
namespace core\cache;

use core\cache\Driver;
/**
 * 文件类型缓存
 * @author Administrator
 *
 */
class File extends Driver
{

    protected $options = [
                            'expire' => 0,'cache_subdir' => true,'prefix' => '','path' => CACHE_PATH,'data_compress' => false
    ];

    protected $expire;

    function __construct($options = [])
    {
        if (! empty($options)) {
            $this->options = array_merge($this->options, $options);
        }
        if (substr($this->options['path'], - 1) != DS) {
            $this->options['path'] .= DS;
        }
        if (! is_dir($this->options['path'])) {
            mkdir($this->options['path'], 0755, true);
        }
    }
/**
 * 清除缓存
 * {@inheritDoc}
 * @see \core\cache\Driver::clear()
 */
    public function clear($tag = '')
    {
        if ($tag) {
            $tags = $this->getTag($tag);
            foreach ($tags as $file)
                $this->unlink($file);
            return true;
        }
        $files = (array) glob($this->options['path'] . ($this->options['prefix'] ? $this->options['prefix'] . DS : '') . '*');
        foreach ($files as $path) {
            if (is_dir($path)) {
                $matches = glob($path . '/*.php');
                if (is_array($matches)) {
                    array_map('unlink', $matches);
                }
                rmdir($path);
            } else {
                unlink($path);
            }
        }
        return true;
    }
/**
 * 删除缓存
 * {@inheritDoc}
 * @see \core\cache\Driver::del()
 */
    public function del($name = '')
    {
        $name = $this->getPreKey($name);
        return $this->unlink($name);
    }
/**
 * 获取缓存
 * {@inheritDoc}
 * @see \core\cache\Driver::get()
 */
    public function get($name = '', $default = false)
    {
        $filename = $this->getPreKey($name);
        if (! is_file($filename)) {
            return $default;
        }
        $content = file_get_contents($filename);
        $this->expire = null;
        if (false !== $content) {
            $expire = (int) substr($content, 8, 12);
            if (0 != $expire && time() > filemtime($filename) + $expire) {
                return $default;
            }
            $this->expire = $expire;
            $content = substr($content, 31);
            if ($this->options['data_compress'] && function_exists('gzcompress')) {
                // 启用数据压缩
                $content = gzuncompress($content);
            }
            $content = unserialize($content);
            return $content;
        } else {
            return $default;
        }
    }

    public function set($name, $value, $expire = null)
    {
        $expire = is_null($expire) ? $this->options['expire'] : $expire;
        if ($expire instanceof \DateTime) {
            $expire = $expire->getTimestamp() - time();
        }
        $filename = $this->getPreKey($name);
        if ($this->tag && ! is_file($filename))
            $tag = 1;
        $data = serialize($value);
        if ($this->options['data_compress'] && function_exists('gzcompress'))
            $data = gzcompress($data, 5);
        $data = "<?php\n//" . sprintf('%012d', $expire) . "\nexit();?>\n" . $data;
        if (file_put_contents($filename, $data)) {
            isset($tag) && $this->setTag($filename);
            clearstatcache();
            return true;
        } else
            return false;
    }
    public function has($name='')
    {
        return $this->get($name)?true:false;
    }
/**
 * 删除缓存，删除文件
 * @param string $path
 * @return boolean
 */
    protected function unlink($path)
    {
        return is_file($path) && unlink($path);
    }
  /**
   * 获取文件名
   * {@inheritDoc}
   * @see \core\cache\Driver::getPreKey()
   */
     function getPreKey($name)
    {
        $name = md5($name);
        $name = $this->options['cache_subdir'] ? substr($name, 0, 2) . DS . substr($name, 2) : $name;
        $name = $this->options['prefix'] ? $this->options['prefix'] . DS . $name : $name;
        $name = $this->options['path']  . $name.'.php';
        $dir = dirname($name);
        if (!is_dir( $dir))
            mkdir($dir, 0755, true);
        return $name;
    }
}
?>