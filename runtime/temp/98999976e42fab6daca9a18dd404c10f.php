<?php if (!defined('CORE_PATH')) exit(); /*a:3:{s:48:"F:\zend\studymvc\app\test\view\test\monitor.html";i:1539864248;s:47:"F:\zend\studymvc\app\test\view\test\header.html";i:1533967749;s:47:"F:\zend\studymvc\app\test\view\test\footer.html";i:1539869137;}*/ ?>
<!DOCTYPE html>
<html lang="zh">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>虚拟货币综合走势，趋势分析</title>
    <style type="text/css">
    body {
        font-size:13px; margin:0px; padding:0px;background-color: rgb(33, 33, 45);
    }
	a{color:#FFF}
	 span { color:#FFF} .activity{ margin: 0 auto;background-color: rgb(33, 33, 45); padding-top:0px;}
	div {border: 0px; padding:0px; margin: 0 auto;}
	#bg {text-align:center;}
	#footer , #top {width: 1000px; text-align:center;}
	#chart{ } #showtrade{height:80px; color:#FFF}
	.info {text-align:left} #buy_sell{color:#FFF}
	select{height:20px;}
	#part , #symbollist {}
	#footer a , #admin a { color:#FFF}#admin {float:right; width:80px;}#s li{padding:0px;list-style-type: none; color:#FFF; font-size:15px; height:22px}
    </style>
    </head>
    <div id="bg">
      <div id="top">
     
      <div class='info' id='buy_sell'></div>
  
    
 <div class='info' style="color: #FFF"><h1>虚拟货币综合走势，趋势分析</h1></div>

<dvi class='info'><?php if(is_array($test) || $test instanceof \think\Collection || $test instanceof \think\Paginator): $i = 0; $__LIST__ = $test;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$a): $mod = ($i % 2 );++$i;?><span><?php echo $a['v']; ?><br></span><?php endforeach; endif; else: echo "" ;endif; ?>
</div>

      
      <div  id='footer'>foot</div>
      </body>
</html>
