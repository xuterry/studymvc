<?php

// 应用公共文件

/**
 * 根据start,end获取客串的内容,need=1表示没有搜索到end截取start形如的位置
 *
 * @param string $start            
 * @param string $end            
 * @param string $str            
 * @param number $need            
 * @return string
 */
function get_string($start = '', $end = '', $str = '', $need = 0)
{
    $flag1 = strpos($str, $start);
    $length1 = strlen($start);
    if ($flag1 === FALSE)
        return "";
    else {
        $content = substr($str, $flag1 + $length1);
        if ($end != '') {
            $flag2 = strpos($content, $end);
            if ($need != 0 && $flag2 === FALSE)
                return $content;
            else
                return substr($content, 0, $flag2);
        } else
            return $content;
    }
}

function get_file_name($filename)
{
    $filename = str_replace("\\", "/", $filename);
    $gets = explode("/", $filename);
    return end($gets);
}

/**
 * 根据path,name写入str
 */
function writefile($path = '', $name = '', $str, $mode = 1)
{
    if ($path == '') {
        $paths = explode("/", $name);
        $len = sizeof($paths);
        $name = $paths[$len - 1];
        unset($paths[$len - 1]);
    } else
        $paths = explode("/", $path);
    
    $Path = '';
    if (sizeof($paths) > 0) {
        foreach ($paths as $v) {
            $Path .= $v . "/";
            if (! is_dir($Path))
                mkdir($Path);
        }
    }
    if($mode==3){
        $zp=gzopen($Path.$name,"ab9");
        gzwrite($zp, $str);
        gzclose($zp);
    }else{
    if ($mode == 1)
        $fh = fopen($Path . $name, "w+");
    if ($mode == 2)
        $fh = fopen($Path . $name, "a");
    fwrite($fh, $str);
    fclose($fh);
    }
}
function unzip($src_file, $dest_dir=false, $create_zip_name_dir=false, $overwrite=true){
    if (($zip = zip_open($src_file))==true){
        if ($zip){
            $splitter = ($create_zip_name_dir === true) ? "." : "/";
            if($dest_dir === false){
                $dest_dir = substr($src_file, 0, strrpos($src_file, $splitter))."/";
            }
            check_path($dest_dir);
            while (($zip_entry = zip_read($zip))==true){
                $pos_last_slash = strrpos(zip_entry_name($zip_entry), "/");
                if ($pos_last_slash !== false){
                    check_path($dest_dir.substr(zip_entry_name($zip_entry), 0, $pos_last_slash+1));
                }
                // 打开包
                if (zip_entry_open($zip,$zip_entry,"r")){
                    // 文件名保存在磁盘上
                    $file_name = $dest_dir.zip_entry_name($zip_entry);
                    if ($overwrite === true || $overwrite === false && !is_file($file_name)){
                        // 读取压缩文件的内容
                        $fstream = zip_entry_read($zip_entry, zip_entry_filesize($zip_entry));
                        file_put_contents($file_name, $fstream);
                        // 设置权限
                        @chmod($file_name, 0777);
                    }
                    // 关闭入口
                    zip_entry_close($zip_entry);
                }
            }
            // 关闭压缩包
            zip_close($zip);
        }
    }else{
        return false;
    }
    return true;
}
/**
 * 将filename压缩成zip
 */
function zip_addfile($filename)
{
    $zip_file = $filename . '.zip';
    $zip = new \ZipArchive();
    if ($zip->open($zip_file, \ZipArchive::CREATE)) {
        $zip->addFile(get_file_name($filename));
        $zip->close();
    }
}

/**
 * 将数组转化成文件的形式
 *
 * @param array $datas            
 * @param string $file            
 */
function array_to_file($datas = [], $file = '', $return = 0)
{
    $len = sizeof($datas);
    $i = 0;
    $str = '';
    foreach ($datas as $key => $value) {
        if (is_array($value)) {
            $value = array_to_file($value, $file, 1);
        }
        if ($value === false)
            $value = 0;
        if (is_string($value))
            $str .= "'" . $key . "'=>'" . $value . "',";
        else
            $str .= "'" . $key . "'=>" . $value . ",";
        $i ++;
        if ($i == $len) {
            $str = "\n[" . $str . "]\n";
            if ($return == 1)
                return $str;
        }
    }
    $str = str_replace([
                            "'\n[","]\n'","=>,"
    ], [
            "\n[","]\n","=>0,"
    ], $str);
    $str = "<?php\nreturn" . $str . ";\n?>"; // exit($str);
    $paths = explode("/", $file);
    unset($paths[sizeof($paths) - 1]);
    $Path = '';
    foreach ($paths as $v) {
        $Path .= $v . "/";
        if (! is_dir($Path))
            mkdir($Path);
    }
    $fh = fopen($file, 'w');
    fwrite($fh, $str);
    fclose($fh);
    unset($str, $paths, $datas);
}

/**
 * 将对象转换成数组
 *
 * @param unknown $array            
 * @return array
 */
function object_array($array)
{
    if (is_object($array)) {
        $array = (array) $array;
    }
    if (is_array($array)) {
        foreach ($array as $key => $value) {
            $array[$key] = object_array($value);
        }
    }
    return $array;
}

/**
 * 将数字转换成float
 */
function get_float($str)
{
    $getflag = strpos($str, 'E');
    if ($getflag !== false) {
        $get = substr($str, $getflag);
        $len = str_replace("E-", '', $get) + strlen(substr($str, 0, $getflag)) - 2;
        switch ($get) {
            case 'E-5':
                $need = 0.00001;
                break;
            case 'E-6':
                $need = 0.000001;
                break;
            case 'E-7':
                $need = 0.0000001;
                break;
            case 'E-8':
                $need = 0.00000001;
                break;
            case 'E-9':
                $need = 0.000000001;
                break;
            default:
                $need = 0.0000000001;
        }
        // echo substr($str,0,$getflag);
        return number_format(floatval(substr($str, 0, $getflag)) * $need, $len);
    } else
        return floatval($str);
}

// 返回e-x对应的小数位
function value_precision($num = 0)
{
    $value = 1;
    switch ($num) {
        case 1:
            $value = 0.1;
            break;
        case 2:
            $value = 0.01;
            break;
        case 3:
            $value = 0.001;
            break;
        case 4:
            $value = 0.0001;
            break;
        case 5:
            $value = 0.00001;
            break;
        case 6:
            $value = 0.000001;
            break;
        case 7:
            $value = 0.0000001;
            break;
        case 8:
            $value = 0.00000001;
            break;
        case 9:
            $value = 0.000000001;
            break;
        case 10:
            $value = 0.0000000001;
            break;
    }
    return $value;
}

/**
 * 获取data的最大和最小值
 *
 * @param unknown $data            
 * @param number $period            
 * @return boolean|number[]|unknown[]
 */
function get_max_min($data, $period = 18)
{
    if (! is_array($data))
        return false;
    $size = sizeof($data);
    $temp_min = [];
    $temp_max = [];
    $max_value = 0;
    $min_value = 0;
    $min_key = - 1;
    $max_key = - 1;
    $origin = $data[$size - 1];
    for ($i = $size - 2; $i >= 0; $i --) {
        $value = $data[$i];
        if ($value > $origin)
            $temp_max[] = $i;
        if ($value < $origin)
            $temp_min[] = $i;
        if (sizeof($temp_min) > 1)
            for ($j = 1; $j < sizeof($temp_min); $j ++) {
                if ($data[$temp_min[$j] - 1] > $data[$temp_min[$j]]) {
                    $min_value = $data[$temp_min[$j]];
                    $min_key = $temp_min[$j];
                    break;
                }
            }
        if (sizeof($temp_max) > 1)
            for ($j = 0; $j < sizeof($temp_max); $j ++) {
                if ($data[$temp_max[$j] - 1] < $data[$temp_max[$j]]) {
                    $max_value = $data[$temp_max[$j]];
                    $max_key = $temp_max[$j];
                    break;
                }
            }
        if ($max_key > - 1 && $min_key > - 1)
            break;
        if ($i == $size - $period) {
            if ($max_key == - 1 && $min_key > - 1) {
                if (sizeof($temp_max) > 1)
                    $max_value = $data[$i];
            }
            if ($max_key > - 1 && $min_key == - 1) {
                if (sizeof($temp_min) > 1)
                    $min_value = $data[$i];
            }
            break;
        }
    }
    if (sizeof($temp_min) == 1)
        $min_value = $data[$temp_min[0]];
    if (sizeof($temp_min) == 0)
        $min_value = $origin;
    if (sizeof($temp_max) == 1)
        $max_value = $data[$temp_max[0]];
    if (sizeof($temp_max) == 0)
        $max_value = $origin;
    return [
                'max' => $max_value,'min' => $min_value
    ];
}

// 获取data的ma值
function trade_ma($data, $num)
{
    $return = [];
    $sum = 0;
    for ($i = 0; $i < sizeof($data); $i ++) {
        if ($i < $num) {
            $sum = $sum + $data[$i];
            $return[$i] = $sum / $num;
        } else {
            $sum = $data[$i] + $sum - $data[$i - $num];
            $return[$i] = $sum / $num;
        }
    }
    return $return;
}

// 返回日期
function get_date()
{
    // ini_set('date.timezone','PRC');
    // date_default_timezone_set("Etc/GMT-8");
    return date('Y-m-d H:i:s', time() + 8 * 3600);
}
function nowDate()
{
    return date("Y-m-d H:i:s");
}
// 画线
function drawline($array)
{
    $max = max($array);
    $min = min($array);
    $h1 = ($max - $min) * (1.3);
    foreach ($array as $v)
        $data[] = $v / $h1 * 500;
    // dump($data);exit();
    $im = imagecreatetruecolor(1200, 500);
    $black = imagecolorallocate($im, 0, 0, 0);
    $white = imagecolorallocate($im, 255, 255, 255);
    $red = imagecolorallocate($im, 255, 0, 0);
    imagefill($im, 0, 0, $white);
    $ph = 1100 / sizeof($data);
    // $data=array('9'=>50,'10'=>100,'11'=>130,'12'=>150,'13'=>120,'14'=>200,'15'=>300,'16'=>'280','17'=>'370','18'=>350);
    foreach ($data as $key => $value) { // 取得每个时间点的股市坐标
        $point[] = array(
                        intval($key * $ph),250 - $value
        );
    }
    for ($i = 0, $j = count($point); $i < $j - 1; $i ++) { // 连接前后坐标
                                                           // imagesetpixel($im,$point[$i][0],$point[$i][1],$red);
        imageline($im, $point[$i][0], $point[$i][1], $point[$i + 1][0], $point[$i + 1][1], $black);
        imageline($im, $point[$i][0], 250, $point[$i + 1][0], 250, $red);
    }
    
    header('Content-type:image/jpeg');
    imagejpeg($im);
    imagedestroy($im);
}
function check_path($filename='')
{
    if(strpos($filename,"/")!==false){
        $ds='/';
        $filename=str_replace("\\","/",$filename);
    }
    else{
        $ds='\\';
        $filename=str_replace("/","\\",$filename);       
    }
    if(strpos($filename,'.')!==false)
    $paths=explode($ds,pathinfo($filename,PATHINFO_DIRNAME));
    else 
    $paths=explode($ds,$filename);      
    $Path = '';
    if (sizeof($paths) > 0) {
        foreach ($paths as $v) {
            $Path .= $v . $ds;
            if (! is_dir($Path)){
                mkdir($Path);
                @chmod($Path, 0777);
            }
        }
    }
    return true;
}
function check_file($filename)
{
    if(strpos($filename,"/")!==false){
        return str_replace(["\\","//"],["/","/"],$filename);
    }
    if(strpos($filename,"\\")!==false){
        return str_replace(["/","\\\\"],["\\","\\"],$filename);
    }
}
function del_path($path)
{
    if(empty($path))
        return;
    if(strpos($path,'/')!==false)
        $ds='/';
    else 
        $ds='\\';
    $dirs=scandir($path);
    foreach($dirs as $file){
        if($file=='.'||$file=='..')
            continue;
        $file=$path.$ds.$file;
        if(is_file($file))
            unlink($file);
        else 
        del_path($file);
    }
    rmdir($path);
}
function getPathFile($path,$ext='',&$return=[])
{
    if(empty($path))
        return;
        if(strpos($path,'/')!==false)
            $ds='/';
        else
            $ds='\\';
        $dirs=scandir($path);
         foreach($dirs as $file){
            if($file=='.'||$file=='..')
            continue;
            $file=check_file($path.$ds.$file);
             if(is_file($file)){
                      if(!empty($ext)){
                          $type=pathinfo($file,PATHINFO_EXTENSION);
                          if(strpos($ext,$type)!==false)
                              $return[]=$file;
                      }else 
                          $return[]=$file;
                  }
                else
               getPathFile($file,$ext,$return);
         }
         return $return;
}
/**
 * 获取data的中间值的最小值
 *
 * @param unknown $data            
 * @return number[]
 */
function get_mid_price($data)
{
    return [
                'mid' => (max($data) + min($data)) / 2,'ave' => array_sum($data) / sizeof($data)
    ];
}

// 求数组里每个元素对应前面数之和的平均值
function get_average($data)
{
    if (sizeof($data) > 0) {
        $sum = 0;
        $return = [];
        $i = 1;
        foreach ($data as $v) {
            $sum = $sum + $v;
            $return[] = $sum / $i;
            $i ++;
        }
        return $return;
    } else
        return false;
}

// 获取数组相似的值
function array_like($search, $array)
{
    if (empty($array))
        return null;
    $return = [];
    foreach ($array as $v) {
        $find = strpos($v, $search);
        if ($find > 0 || $find === 0)
            $return[] = $v;
    }
    return $return;
}

// 最后一个值和开始一个值的差距
function get_rise($data)
{
    $len = sizeof($data);
    if ($len < 10)
        return 0;
    $start = $data[0];
    $end = end($data);
    $rise = ($end - $start) / $start;
    return round($rise, 4);
}

// 根据vol的值调整val的值
function value_vol($val, $vol = 8000, $max = 50000, $min = 500)
{
    $base = 30000;
    if ($vol > $max)
        $vol = $max;
    if ($vol < $min)
        $vol = $min;
    $return = (1 - $vol / ($vol + $base)) * 2 * $val;
    return $return;
}

// 获取小数点的个数
function get_precision($value)
{
    $value = get_float($value);
    if (strpos($value, ".") > 0) {
        list ($val, $preci) = explode(".", $value);
        return strlen($preci);
    } else
        return 0;
}

function value_time($value = 0)
{
    $d = $h = $m = $s = 0;
    $getd = $geth = $getm = 0;
    $is_minus = 0;
    if ($value < 0)
        $is_minus = 1;
    $value = abs($value);
    if ($value > (24 * 3600)) {
        $d = floor($value / 24 / 3600);
        $getd = $value % (24 * 3600);
        if ($getd > 3600) {
            $h = floor($getd / 3600);
            $geth = $getd % 3600;
            $m = floor($geth / 60);
        } else {
            $m = floor($getd / 60);
        }
    } elseif ($value > 3600) {
        $h = floor($value / 3600);
        $geth = $value % 3600;
        if ($geth > 60) {
            $m = floor($geth / 60);
            $s = $geth % 60;
        } else
            $s = $geth;
    } else {
        if ($value > 60) {
            $m = floor($value / 60);
            $s = $value % 60;
        } else
            $s = $value;
    }
    $return = '';
    if ($d != 0)
        $return .= $d . "天";
    if ($h != 0)
        $return .= $h . "小时";
    if ($m != 0)
        $return .= $m . "分";
    if ($s != 0)
        $return .= $s . "秒";
    if ($is_minus)
        $return = '-' . $return;
    return $return;
}

/**
 * 值越大，返回值越大 0.5-2之间
 *
 * @param unknown $val            
 * @param number $max            
 * @param unknown $min            
 * @param number $type            
 * @return number
 */
function value_limit($val, $max = 8, $min = -4)
{
    $base = 8;
    if ($val > $max)
        $val = $max;
    if ($val < $min)
        $val = $min;
    $return = ($base + $val) / $base;
    return $return;
}

/**
 * 趋势越好，值越小
 *
 * @param unknown $value            
 * @param unknown $trend            
 * @param number $max            
 * @param unknown $min            
 * @return number
 */
function value_trend($value, $trend, $max = 5, $min = -5)
{
    if ($trend > $max)
        $trend = $max;
    if ($trend < $min)
        $trend = $min;
    $base = 20;
    // 范围在vlaue 的 0.75 - 1.25
    return $value * (1 - $trend / $base);
}

/**
 * 截取数值，最大位数在1-4区间保留2位，4-9保留一位
 *
 * @param string $value            
 * @return number
 */
function get_floor($value = '')
{
    // 0.66 0.33 1.92 9.12 35.67 90.99 120 888 1239 8888 9189
    if ($value >= 1) {
        $floorval = floor($value);
        $len = strlen($floorval);
        if ($value >= 1 * pow(10, $len - 1) && $value < 4 * pow(10, $len - 1))
            $returnval = floor($value * 10 * pow(0.1, $len - 1)) / pow(0.1, $len - 1) / 10;
        else
            $returnval = floor($value * 10 * pow(0.1, $len)) / pow(0.1, $len) / 10;
    } else {
        $m = [];
        if ($value < 0.0001)
            $value = get_float($value);
        preg_match("/0.[0]+/s", $value, $m);
        if (sizeof($m) == 0) {
            if ($value >= 0.1 && $value < 0.4)
                $returnval = floor($value * 100) / 100;
            else
                $returnval = floor($value * 10) / 10;
        } else {
            $len = strlen($m[0]);
            if ($value >= 1 * pow(0.1, $len - 1) && $value < 4 * pow(0.1, $len - 1))
                $returnval = floor($value * pow(10, $len)) / pow(10, $len);
            else
                $returnval = floor($value * pow(10, $len - 1)) / pow(10, $len - 1);
        }
    }
    return $returnval;
}

/**
 * 截取value的小数位数，四舍五入
 *
 * @param number $value            
 * @param number $cut            
 * @return number
 */
function get_round($value, $cut = 3)
{
    $m = [];
    if ($value < 0.0001)
        $value = get_float($value);
    preg_match("/0.0+/", $value, $m);
    if (sizeof($m) == 0||strpos($m[0],".")===false) {
        $returnval = round($value, $cut);
    } else {
        $gets = explode(".", $m[0]);
        $len = strlen(end($gets));
        $returnval = round($value, $len + $cut);
    }
    return $returnval;
}

/**
 * * 截取数值，最大位数在1-4区间保留2位，4-9保留一位，截取处进1位
 *
 * @param string $value            
 * @return number
 */
function get_ceil($value = '')
{
    // 0.66 0.33 1.92 9.12 35.67 90.99 120 888 1239 8888 9189
    if ($value >= 1) {
        $ceilval = ceil($value);
        $len = strlen($ceilval);
        if ($value >= 1 * pow(10, $len - 1) && $value < 4 * pow(10, $len - 1))
            $returnval = ceil($value * 10 * pow(0.1, $len - 1)) / pow(0.1, $len - 1) / 10;
        else
            $returnval = ceil($value * 10 * pow(0.1, $len)) / pow(0.1, $len) / 10;
    } else {
        $m = [];
        if ($value < 0.0001)
            $value = get_float($value);
        preg_match("/0.[0]+/s", $value, $m);
        if (sizeof($m) == 0) {
            if ($value >= 0.1 && $value < 0.4)
                $returnval = ceil($value * 100) / 100;
            else
                $returnval = ceil($value * 10) / 10;
        } else {
            $len = strlen($m[0]);
            if ($value >= 1 * pow(0.1, $len - 1) && $value < 4 * pow(0.1, $len - 1))
                $returnval = ceil($value * pow(10, $len)) / pow(10, $len);
            else
                $returnval = ceil($value * pow(10, $len - 1)) / pow(10, $len - 1);
        }
    }
    return $returnval;
}
?>