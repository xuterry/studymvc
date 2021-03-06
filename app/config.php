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
            'all'=>'.+',
            'path'=>'.+',//保持全路径
        ],
        '[api]'=>[
            'detail/[:all]'=>['index/api/detail',['method'=>'get']],
            'file'=>['index/api/file',['method'=>'post']],
            'getinfo/[:path]'=>['index/api/getinfo',['method'=>'get']],
            'auth/[:path]'=>['index/api/auth',['method'=>'get']],
            
            'getbalance/[:path]'=>['index/api/getbalance',['method'=>'get']],
            'trade'=>['index/api/trade',['method'=>'post']],        
            'balances/[:path]'=>['index/api/balances',['method'=>'get']], 
            ],
        'app/[:path]'=>['/index/app',['method'=>['get','post']]],
        '[hello]'     => [
            ':id'   => ['index/hello', ['method' => 'get'], ['id' => '\d{2}+']],
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
    
    
    'session'      => [
        'id'             => '',       
        'session_id' => '',
        // SESSION 前缀
        'prefix'         => 'session',
        // 驱动方式 支持redis memcache
        'type'           => '',
        // 是否自动开启 SESSION
        'auto_start'     => true,
    ],
    'database'=>[
        'type'            => 'mysql',
        // 服务器地址
        'hostname'        => '127.0.0.1',
        // 数据库名
        'database'        => 'demo',
        // 用户名
        'username'        => 'root',
        // 密码
        'password'        => '',
        // 端口
        'hostport'        => '',
        // 连接dsn
        'dsn'             => '',
        // 数据库连接参数
        'params'          => [],
        // 数据库编码默认采用utf8
        'charset'         => 'utf8',
        // 数据库表前缀
        'prefix'          => 'think_'
        
    ],
    'paginate'=>[
        'list_rows'=>10,
        'var_page'  => 'page',
        ],
    
];