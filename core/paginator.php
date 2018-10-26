<?php
namespace core;
/**
分页器类
*/
class Paginator implements \ArrayAccess,\Countable,\IteratorAggregate,\JsonSerializable
{
    /**
     * @var Collection
     */
    protected $items;
    protected $simple=false;
    protected $currentPage;
    protected $total;
    protected $listRows;
    protected $hasMore;
    protected $options = [
        'var_page' => 'page',
        'path'     => '/',
        'query'    => [],
        'fragment' => '',
    ];
    public function getIterator()
    {
        return new \ArrayIterator($this->items->all());
    }
    public function count()
    {
        return $this->items->count();
    }
    public function offsetExists($offset)
    {
        return $this->items->offsetExists($offset);
    }
    

    public function offsetGet($offset)
    {
        return $this->items->offsetGet($offset);
    }

    public function offsetSet($offset, $value)
    {
        $this->items->offsetSet($offset, $value);
    }
 
    public function offsetUnset($offset)
    {
        $this->items->offsetUnset($offset);
    }
    public function jsonSerialize()
    {
        return $this->toArray();
    }
    
    function __construct($items,$listRows,$currentPage=null,$total=null,$simple=false,$options=[])
    {
        $this->options=array_merge($this->options,$options);
        $this->options['path']=$this->options['path']!='/'?rtrim($this->options['path'],'/'):$this->options['path'];
        $this->simple=$simple;
        $this->listRows=$listRows;
        $items  instanceof Collection?:$items=Collection::make($items);
        if($simple){
            $this->currentPage=$this->setCurrentPage($currentPage);
            $this->hasMore=count($items)>$this->listRows;
            if($this->hasMore){
                $this->nextItem=$items->slice($this->listRows,1);
            }
            $items=$items->slice(0,$this->listRows);
        }else{
            $this->total=$total;
            $this->lastPage=(int)ceil($total/$listRows);
            $this->currentPage=$this->setCurrentPage($currentPage);
            $this->hasMore=$this->currentPage<$this->lastPage;
        }
        $this->items=$items;
    }
    
    public static function make($items,$listRows,$currentPage=null,$total=null,$simple=false,$options=[])
    {
        return new self($items,$listRows,$currentPage,$total,$simple,$options);
    }
    protected function setCurrentPage($currentPage)
    {
        return (!$this->simple&&$currentPage>$this->lastPage)?($this->lastPage>0?$this->lastPage:1):$currentPage;
    }
    public function url($page)
    {
        if($page<=0)
            $page=1;
        if(strpos($this->options['path'],'[PAGE]')===false){
            $parameters=[$this->options['var_page']=>$page];
            $path=$this->options['path'];
        }else{
            $parameters=[];
            $path=str_replace('[PAGE]',$page,$this->options['path']);      
        }
        if(count($this->options['query'])>0)
            $parameters=array_merge($this->options['query'],$parameters);
        $url=$path;
        if(!empty($parameters))
            $url.='?'.http_build_query($parameters,null,'&');
        return $url.$this->buildFragment();
    }
    
    public static function getCurrentPage($varPage='page',$default=1)
    {
        $page=(int)Request::init()->params($varPage);
         if(filter_var($page,FILTER_VALIDATE_INT)!==false&&$page>1)
             return $page;
         return  $default;
    }
    public function getCurrentPath()
    {
        return '/'.Request::init()->urlPath;
    }
    public function total()
    {
        if($this->simple)
            throw new \Exception('simple ');
        return $this->total;
    }
    
    public function currentPage()
    {
        return $this->currentPage;
    }
    public function lastPage()
    {
        if($this->simple)
            throw new \Exception('simple ');
        return $this->lastPage();
    }
    public function hasPages()
    {
        return !(1 == $this->currentPage && !$this->hasMore);
    }
    public function getUrlRange($start,$end)
    {
        $url=[];
        for($page=$start;$page<=$end;$page++)
            $url[$page]=$this->url($page);
        return $url;
    }
    public function fragment($fragment)
    {
        $this->options['fragment']=$fragment;
        return $this;
    }
    public function appends($key,$value=null)
    {
        if(is_string($key))
            $queries=[$key=>$value];
        else 
            $queries=$key;
        foreach($queries as $k=>$v){
            if($this->options['var_page']!==$k)
                $this->options['query'][$k]=$v;
        }
        return $this;
    }
    protected function buildFragment()
    {
        return $this->options['fragment'] ? '#' . $this->options['fragment'] : '';
    }
    public function items()
    {
        return $this->items->all();
    }
    
    public function getCollection()
    {
        return $this->items;
    }
    
    function each(callable $callback)
    {
        foreach($this->items as $k=>$v){
            $result=$callback($v,$k);
            if($result===false)
                break;
            elseif(!is_object($v)){
                $this->items[$k]=$result;
            }
        }
        return $this;
    }
    public function isEmpty()
    {
        return $this->items->isEmpty();
    }
    
    function __toString()
    {
        return (string) $this->render();
    }

    public function toArray()
    {
        if ($this->simple) {
            return [
                'per_page'     => $this->listRows,
                'current_page' => $this->currentPage,
                'has_more'     => $this->hasMore,
                'next_item'    => $this->nextItem,
                'data'         => $this->items->toArray(),
            ];
        } else {
            return [
                'total'        => $this->total,
                'per_page'     => $this->listRows,
                'current_page' => $this->currentPage,
                'last_page'    => $this->lastPage,
                'data'         => $this->items->toArray(),
            ];
        }
    }
    function __call($name,$args)
    {
        $collection=$this->getCollection();
        $rs=call_user_func_array([$collection,$name],$args);
        if($rs===$collection)
            return $this;
        return $rs;
    }
    
    protected function getPreviousButton($text="&laquo;")
    {
        if($this->currentPage()<=1){
            return $this->getDisabledTextWrapper($text);
        }
        $url=$this->url($this->currentPage()-1);
        return $this->getPageLinkWrapper($url,$text);
    }
    protected function getNextButton($text="&raquo;")
    {
        if(!$this->hasMore){
            return $this->getDisabledTextWrapper($text);
        }
        $url=$this->url($this->currentPage()+1);
        return $this->getPageLinkWrapper($url,$text);
    }
 
    protected function getLinks()
    {
        if ($this->simple)
            return '';
            
            $block = [
                'first'  => null,
                'slider' => null,
                'last'   => null
            ];
            
            $side   = 3;
            $window = $side * 2;
            
            if ($this->lastPage < $window + 6) {
                $block['first'] = $this->getUrlRange(1, $this->lastPage);
            } elseif ($this->currentPage <= $window) {
                $block['first'] = $this->getUrlRange(1, $window + 2);
                $block['last']  = $this->getUrlRange($this->lastPage - 1, $this->lastPage);
            } elseif ($this->currentPage > ($this->lastPage - $window)) {
                $block['first'] = $this->getUrlRange(1, 2);
                $block['last']  = $this->getUrlRange($this->lastPage - ($window + 2), $this->lastPage);
            } else {
                $block['first']  = $this->getUrlRange(1, 2);
                $block['slider'] = $this->getUrlRange($this->currentPage - $side, $this->currentPage + $side);
                $block['last']   = $this->getUrlRange($this->lastPage - 1, $this->lastPage);
            }
            
            $html = '';
            
            if (is_array($block['first'])) {
                $html .= $this->getUrlLinks($block['first']);
            }
            
            if (is_array($block['slider'])) {
                $html .= $this->getDots();
                $html .= $this->getUrlLinks($block['slider']);
            }
            
            if (is_array($block['last'])) {
                $html .= $this->getDots();
                $html .= $this->getUrlLinks($block['last']);
            }
            
            return $html;
    }
    
    public function render()
    {
        if ($this->hasPages()) {
            if ($this->simple) {
                return sprintf(
                    '<ul class="pager">%s %s</ul>',
                    $this->getPreviousButton(),
                    $this->getNextButton()
                    );
            } else {
                return sprintf(
                    '<ul class="pagination">%s %s %s</ul>',
                    $this->getPreviousButton(),
                    $this->getLinks(),
                    $this->getNextButton()
                    );
            }
        }
    }
    
    
    /**
     * 生成一个可点击的按钮
     *
     * @param  string $url
     * @param  int    $page
     * @return string
     */
    protected function getAvailablePageWrapper($url, $page)
    {
        return '<li><a href="' . htmlentities($url) . '">' . $page . '</a></li>';
    }
    
    /**
     * 生成一个禁用的按钮
     *
     * @param  string $text
     * @return string
     */
    protected function getDisabledTextWrapper($text)
    {
        return '<li class="disabled"><span>' . $text . '</span></li>';
    }
    
    /**
     * 生成一个激活的按钮
     *
     * @param  string $text
     * @return string
     */
    protected function getActivePageWrapper($text)
    {
        return '<li class="active"><span>' . $text . '</span></li>';
    }
    
    /**
     * 生成省略号按钮
     *
     * @return string
     */
    protected function getDots()
    {
        return $this->getDisabledTextWrapper('...');
    }
    
    /**
     * 批量生成页码按钮.
     *
     * @param  array $urls
     * @return string
     */
    protected function getUrlLinks(array $urls)
    {
        $html = '';
        
        foreach ($urls as $page => $url) {
            $html .= $this->getPageLinkWrapper($url, $page);
        }
        
        return $html;
    }
    
    /**
     * 生成普通页码按钮
     *
     * @param  string $url
     * @param  int    $page
     * @return string
     */
    protected function getPageLinkWrapper($url, $page)
    {
        if ($page == $this->currentPage()) {
            return $this->getActivePageWrapper($page);
        }
        
        return $this->getAvailablePageWrapper($url, $page);
    }
}