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
        
    ],
    'cache'      => [
        // 驱动方式
        'type'   => 'File',
        // 缓存保存目录
        'path'   => 'runtime',
        // 缓存前缀
        'prefix' => '',
        // 缓存有效期 0表示永久缓存
        'expire' => 1,
    ],
    'template'=>['type'=>'Think']
];