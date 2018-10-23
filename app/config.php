<?php
/**
/xtw
2018
*/
return [
    'debug'=>1,
    'moudle'=>'',
    'route'=>[
        '__pattern__' => [
            'name' => '\w+',
            'id'=>'\d+',
            'year'=>'\d{4}',
            'month'=>'\d{2}',
        ],
        '[hello]'     => [
            ':id'   => ['index/hello', ['method' => 'get'], ['id' => '\d+']],
            ':name' => ['index/hello', ['method' => 'post']],
        ],
        'hello/[:name]'=>['index/hello',['method'=>'get','ext'=>'html']],
        'base/:name'=>['base/index',['method'=>'get','ext'=>'html']],
        //'hello/[:name]'=>function ($name){
        //	return 'hello '.$name.'  ';},
        'blog/:year/:id'=>['blog/archive',['method'=>'get'],['year'=>'\d{4}','id'=>'\d{2}']],
        'blog/:year/:month'=>['blog/archive',['method'=>'get']],
        '12'=>['blog/get',['method'=>'get']],
        'blog/:name'=>['blog/read',['method'=>'get']],
        'http://tp6.com/test/index/[:name]'=>['/domain/test/index',['method'=>'get'],'name'=>'.*']
        
    ],
    'cache'      => [
        // 驱动方式
        'type'   => 'File',
        // 缓存保存目录
        'path'   => CACHE_PATH,
        // 缓存前缀
        'prefix' => '',
        // 缓存有效期 0表示永久缓存
        'expire' => 1,
    ],
    'template'=>['type'=>'Think'],
    'cookie'                 => [
        // cookie 名称前缀
        'prefix'    => '',
        // cookie 保存时间
        'expire'    => 0,
        // cookie 保存路径
        'path'      => '/',
        // cookie 有效域名
        'domain'    => '',
        //  cookie 启用安全传输
        'secure'    => false,
        // httponly设置
        'httponly'  => '',
        // 是否使用 setcookie
        'setcookie' => true,
    ],
];