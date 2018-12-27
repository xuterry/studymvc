<?php
namespace app\admin\controller;

use core\Controller;
use core\Db;

class Set extends Controller
{

    function index()
    {
        // $this->copyFile();
       // $this->editView();
        // $this->updateDb();
      // $this->createFile();
     // $this->createModel();
   $this->editController();
  // $this->addMenu();
    }

    protected function copyFile()
    {
        $path = "F:\\zend\\app\\LKT\\webapp\\modules";
        $path2 = "F:\\zend\\studymvc\\app\\admin\\view" . DS;
        $paths = scandir($path);
        foreach ($paths as $dir) {
            if ($dir != '.' && $dir != '..' && is_dir($path . DS . $dir . DS . 'templates')) {
                $paths2 = scandir($path . DS . $dir . DS . 'templates');
                foreach ($paths2 as $val) {
                    if ($val != '.' && $val != '..' && is_file($path . DS . $dir . DS . 'templates' . DS . $val)) {
                        $namefile = $path2 . strtolower($dir) . DS . $val;
                        $namefile = str_replace(".tpl", '.html', $namefile);
                        \core\Loader::checkPath($namefile);
                        
                        if (! is_file($namefile))
                            copy($path . DS . $dir . DS . 'templates' . DS . $val, $namefile);
                        // exit($namefile);
                    }
                }
            }
        }
    }

    protected function rep($str)
    {
        $str = str_replace([
                                "\$db=DBAction::getInstance();","\$request = \$this->getContext()->getRequest();","\$db = DBAction::getInstance();","return \$this->getDefaultView();"
        ], [
                ''
        ], $str);
        return str_replace([
                                "\$this->getContext()->getStorage()->read","getParameter","LKT_VERSION","View :: INPUT;","\$this->getContext()->getStorage()->write","\$request->setAttribute"
        ], [
                "Session::get","param","\$this->version","\$this->fetch('',[],['__moduleurl__'=>\$this->module_url]);","Session::set","\$this->assign"
        ], $str);
    }

    protected function getFun($str)
    {
        preg_match_all("/(private|protected|public|function)+(.+){(.+)\\n    }/isU", $str, $matchs);
        $return = [];
        foreach ($matchs[2] as $k => $v) {
            if (strpos($v, "getDefaultView") === false && strpos($v, "execute") === false && strpos($v, "getRequestMethods") === false) {
                $return[] = $matchs[0][$k];
            }
        }
        return $return;
        // dump($return);exit();
    }

    protected function createFile()
    {
        $path = "F:\\zend\\app\\LKT\\webapp\\modules";
        $path2 = "F:\\zend\\studymvc\\app\\admin\\controller" . DS;
        $paths = scandir($path);
        shuffle($paths);
        foreach ($paths as $dir) {
            if ($dir != '.' && $dir != '..' && is_dir($path . DS . $dir . DS . 'actions')) {
                $paths2 = scandir($path . DS . $dir . DS . 'actions');
                $str1 = '';
                foreach ($paths2 as $val) {
                    if ($val != '.' && $val != '..' && is_file($path . DS . $dir . DS . 'actions' . DS . $val)) {
                        $name = str_replace([
                                                "Action.class.php",".php",'config'
                        ], [
                                '','','configs'
                        ], $val);
                        $getstr = file_get_contents($path . DS . $dir . DS . 'actions' . DS . $val);
                        // $this->getFun($getstr);
                        $indexstr = $this->rep(get_string("function getDefaultView() {", "public function", $getstr));
                        $dostr = $this->rep(get_string("function execute(){", "public function", $getstr));
                        
                        if (strlen($indexstr) > 20) {
                            $str1 .= "    public function " . $name . "(Request \$request)\n    {" . $indexstr . "\n";
                            
                            if (strlen(trim(str_replace("\n", '', $dostr))) > 10)
                                $str1 .= "    private function do_" . $name . "(\$request)\n    {" . $dostr . "\n";
                        }
                    }
                }
                if (! empty($str1)) {
                    $funs=$this->getFun($getstr);
                    foreach($funs as $v){
                        if(strpos($v,'private')===false&&strpos($v,'protected')===false)
                        $str1.="\n    private ".str_replace("public function", "private function", $v)."\n";
                        else 
                            $str1.="\n    ".$v."\n";
                            
                    }
                    $filename = $path2 . ucfirst(str_replace("return", "returns", $dir)) . '.php';
                    if (! is_file($filename)) {
                        $str = "<?php\nnamespace app\\admin\\controller;\nuse core\\Request;\nuse core\\Session;\n\nclass " . ucfirst(str_replace("return", "returns", $dir)) . " extends Index\n{\n\n    function __construct()\n    {\n        parent::__construct();\n    }\n" . $str1 . "    function changePassword(Request \$request)\n    {\n        \$this->redirect(\$this->module_url.'/index/changePassword');\n    }\n    function maskContent(Request \$request)\n    {\n       \$this->redirect(\$this->module_url.'/index/maskContent');\n    }\n}";
                       writefile('', $filename, $str);
                    }
                }
                // echo $filename;
              //   exit($str);
            }
        }
    }

    protected function createModel()
    {
        $path = "F:\\zend\\studymvc\\app\\admin\\model" . DS;
        $conn = Db::connect(include ("F:\\zend\\studymvc\\app\\admin\\config.php"));
        $tables = $conn->getTables('l');
        shuffle($tables);
        foreach ($tables as $val) {
            $name0 = str_replace("lkt_", '', $val);
            $name=ucfirst($name0);
            if (strpos($name0, '_') !== false) {
                $names = explode("_", $name0);
                $name = '';
                foreach ($names as $v)
                    $name .= ucfirst($v);
            }
            $str='';
            if (! is_file($path . $name . '.php')) {
                        $str = "<?php\nnamespace app\\admin\\model;\nuse core\\Model;\nclass "
                            . $name
                        . " extends Model\n{\n\n    function __construct(\$options=[],\$module='')\n    {\n        \$this->db_table='".$name0."';\n        \$this->db_options=empty(\$options)?include(APP_PATH.DS.\$module.DS.'config.php'):\$options;\n        parent::__construct();\n    }\n" 
                         ."}";
                        writefile('',$path.$name.'.php',$str);
                    
                
            }
            //exit($str);
        }
    }
    
    protected function addcontroller($str)
    {
        return preg_replace_callback("/public function (.+)\((.+){(.+)}/isU", function($m) use ($str){
            //dump($m);exit();
            if(strpos($str,"do_".$m[1])!==false&&strpos($str,"\$request->method()==")===false)
                return "public function ".$m[1]."(".$m[2]."{\n       \$request->method()=='post'&&\$this->do_".$m[1]."(\$request);\n".$m[3]."}";
            else 
                return $m[0];
        },$str);
    }
    
    protected function  rep_controller1($str)
    {
        $str = str_replace(  ["\"index.php?module=","\$request->setAttribute","\$request -> setAttribute","\n\n\n","== -1","'index.php?module=","&action="], 
            ["\$this->module_url.\"/","\$this->assign","\$this->assign","\n","==false","'\".\$this->module_url.\"/","/"], $str);
      // $str=preg_replace_callback("/\$url = \"index.php\?module=\/isU", $callback, $str);
        return $str;
    }
    protected function rep_controller2($str)
    {
        return preg_replace_callback("/header\((.+)<\/script>\"\)*;/isU", function($m){
            dump($m);
            $msg=get_string("alert('","')",$m[0]);
            $href='';
            if(strpos($m[0],'href')!==false){
                $href=get_string("location.href='","'",$m[0]);
                if(strpos($href,"module_url")!==false){
                $href=str_replace(["\"","\$this->module_url",".","'"," "],[''], $href);
                $href="\$this->module_url.\"".$href."\"";
                }else{
                    $href=str_replace("index.php?module=", "\$this->module_url", $href);
                    if(strpos($href,"&action=")!==false)
                        $href=str_replace("&action=",".\"/", $href);
                    if(strpos($href,"&")!==false)
                        $href=preg_replace("/&/","?",$href,1);
                }
            }
            empty($href)&&$href="''";
            if(strpos($m[0],'成功')!==false)
            return "\$this->success('".$msg."',".$href.");";
            else 
            return "\$this->error('".$msg."',".$href.");";               
            //dump($m);
        }, $str);
        
    }
    protected function rep_controller3($str)
    {
        return preg_replace_callback("/echo \"<script(.+)>(.+)<\/script>\";/isU", function($m){
            //dump($m);
            $msg=get_string("alert('","')",$m[0]);
            $href='';
            if(strpos($m[0],'href')!==false){
                $href=get_string("location.href='","'",$m[0]);
                if(strpos($href,"module_url")!==false){
                    $href=str_replace(["\"","\$this->module_url",".","'"," "],[''], $href);
                    $href="\$this->module_url.\"".$href."\"";
                }else{
                    $href=str_replace("index.php?module=", "\$this->module_url", $href);
                    if(strpos($href,"&action=")!==false)
                        $href=str_replace("&action=",".\"/", $href);
                        if(strpos($href,"&")!==false)
                            $href=preg_replace("/&/","?",$href,1);
                }
            }
            empty($href)&&$href="''";
            if(strpos($m[0],'成功')!==false)
                return "\$this->success('".$msg."',".$href.");";
                else
                    return "\$this->error('".$msg."',".$href.");";
                    //dump($m);
        }, $str);
    }
    protected function parseM($name)
    {
        $return='';
        $name=str_replace(["'","\""],[''],trim($name));
        if(strpos($name,"_")!==false){
            $names=explode("_",$name);
            foreach($names as $v)
                $return.=ucfirst($v);
            return $return;
        }else 
            return ucfirst($name);
    }
    protected function rep_select1($str)
    {
        return preg_replace_callback("/\\\$sql = (\"|')select \* from lkt_([a-z-A-Z0-9_]+) (\"|');(.+)\\\$([a-zA-Z0-9_]+) =(.+);/isU", function($m){
            //dump($m);exit();
            if(strpos($m[6],"select")!==false)
            return "\$".$m[5]."=\$this->getModel('".$this->parseM($m[2])."')->fetchAll();";
            elseif(strpos($m[6],"row")!==false)
            return "\$".$m[5]."=\$this->getModel('".$this->parseM($m[2])."')->getCount();";
            else 
                return $m[0];
        },$str);
    }
    protected function rep_select2($str)
    {
        return preg_replace_callback("/\\\$sql = (\"|')select \* from lkt_([a-z-A-Z0-9_]+) where ([a-zA-Z_ ]+)=(.+);(.+)\\\$([a-zA-Z0-9_]+) =(.+);/isU", function($m){
            //dump($m);
            if(strpos($m[7],"select")!==false){
                if(trim($m[2])=='config')
                    return "\$".$m[6]."=\$this->getConfig();";
               else  {
                   if(strpos($m[4],"$")!==false)
                       $m4=str_replace(["'","\""," ","."],[""], $m[4]);
                   else 
                       $m4="'".str_replace(["'","\""," ","."],[""], $m[4])."'";
                       
                return "\$".$m[6]."=\$this->getModel('".$this->parseM($m[2])."')->get(".trim($m4).",'".$m[3]."');";
               }
                
            }
                elseif(strpos($m[7],"row")!==false)
                return "\$".$m[5]."=\$this->getModel('".$this->parseM($m[2])."')->getCount();";
                else
                    return $m[0];
        },$str);
    }
    protected function editController()
    {
        $path = "F:\\zend\\studymvc\\app\\admin\\controller";
        $paths = scandir($path);
        shuffle($paths);
        foreach ($paths as $dir) {
            if ($dir != '.' && $dir != '..'&&$dir!='set.php' && is_file($path . DS . $dir)) {
                   //  if($dir!='Product.php') continue;
                        $namefile = $path . DS . $dir;
                        $str = file_get_contents($namefile);
                      // $str=$this->addcontroller($str);
                    //   $str=$this->rep_controller1($str);
                     //  $str=$this->rep_controller2($str);
                       //$str=$this->rep_controller3($str);
                     //  $str=$this->rep_select1($str);
                        $str=$this->rep_select2($str);
                        // $str=str_replace(["{\$module_url}"],["__moduleurl__"],$str);
                        // $str=str_replace(["__moduleurl__&"],["__moduleurl__?"],$str);
                        
                        // $str=preg_replace_callback("/\/([0-9a-zA-Z]+)&([0-9a-zA-Z_]+)=/is", function ($m){
                        // dump($m);
                        // return "/".$m[1].'?'.$m[2];
                        // return str_replace('&','?',$m[0]);
                        // }, $str);
                        //exit($str);
                        writefile('', $namefile, $str);
                        unset($str);
                    }                           
        }
    }
    protected function editView()
    {
        $path = "F:\\zend\\studymvc\\app\\admin\\view";
        $paths = scandir($path);
        shuffle($paths);
        foreach ($paths as $dir) {
            if ($dir != '.' && $dir != '..' && is_dir($path . DS . $dir)) {
                $paths2 = scandir($path . DS . $dir);
                foreach ($paths2 as $val) {
                    if ($val != '.' && $val != '..' && is_file($path . DS . $dir . DS . $val)) {
                        $namefile = $path . DS . $dir . DS . $val;
                        $str = file_get_contents($namefile);
                       // $str = str_replace([
                       //                         "../LKT/",'./static/images','../index.php'
                       // ], [
                     //          "/",'/style/static/images',"__moduleurl__/main"
                      //  ], $str);
                        // $str=str_replace(["{\$module_url}"],["__moduleurl__"],$str);
                         $str=str_replace(["fileManagerJson : 'kindeditor/php/"],["fileManagerJson : '/style/kindeditor/php/"],$str);
                        
                       // $str=preg_replace_callback("/\/([0-9a-zA-Z]+)&([0-9a-zA-Z_]+)=/is", function ($m){
                     //  $str=preg_replace_callback("/<nav class=\"breadcrumb\">(.+)<\/nav>/is", function ($m) use($dir){
                                 
                        // dump($m);
                       //  return "/".$m[1].'?'.$m[2];
                      // if(strpos($m[1],'href')===false&&substr_count($m[1],"c-gray en")==2){
                           
                    //       return "<nav class=\"breadcrumb\">".$m[1]."<a class=\"btn btn-success radius r mr-20\" style=\"line-height:1.6em;margin-top:3px\" href=\"#\" onclick=\"location.href='__moduleurl__/".
                   ///        $dir."';\" title=\"关闭\" ><i class=\"Hui-iconfont\">&#xe6a6;</i></a></nav>";
                  //    }else{
                //          return $m[0];
                 //      }
                //        }, $str);
                        
                       //  exit($str);
                        writefile('', $namefile, $str);
                        unset($str);
                    }
                }
            }
        }
    }

    protected function updateDb()
    {
        $menu = new \app\admin\model\Menu('', 'admin');
        $rs = $menu->fetchAll([
                                    'id','image','image1','url'
        ], 0);
        // dump($rs);
        foreach ($rs as $v) {
            $image = str_replace("../LKT", '', $v->image);
            $image1 = str_replace("../LKT", '', $v->image1);
            $id = $v->id;
            $url = str_replace([
                                    "index.php?module=","/"
            ], [
                    '/admin/','/'
            ], $v->url);
            $menu->save([
                            'image' => $image,'image1' => $image1,'url' => $url
            ], $id, 'id');
        }
    }
    protected function addMenu($type='')
    {
        $config=include(APP_PATH.DS.'admin'.DS.'config.php');
        $menu=new \app\admin\model\Menu($config);
        if($type=='add'){
        $data=['s_id'=>86,'title'=>'功能模块','name'=>'','image'=>'','image1'=>'','module'=>'module','action'=>'index','url'=>'module/index',
            'sort'=>'100','is_core'=>1,'recycle'=>1,'level'=>2,'add_time'=>nowDate(),
        ];
        dump($menu->insert($data));
        }
        if($type=='del'){
            $id='';
            $menu->delete($id,'id');
        }
    }
}