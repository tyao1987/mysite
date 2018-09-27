<?php

return array(
	'languageMapping' => array(
		'zh_CN' => 'CHN',
		'en_GB' => 'GBR',
	),
	'countryShortname' => array(
		'CHN' => 'cn',
		'GBR' => 'uk',
		
	),
    'writableDir' => array(
        'base'            => '',
		'dataCache'       => ROOT_PATH . '/data/data-cache/',
    	'articles'        => '/var/www/articles/',
		'captcha'         => ROOT_PATH . '/public/images/captcha/',
		'log'             => '/var/www/log/mysite/',
		'styles'          => ROOT_PATH . '/public/styles/',
    ), 
    'errorReport'            => 'tyao1987@163.com',
    'imageserver'            => 'http://mysite', 
    'productImageServer'     => 'http://mysite', 
    'styleServer'            => '',
    'jsfile'                 => '/scripts/core.js',  
    
    'log' => array(
        'enabled'    => true,
        'file'       => 'error',
        'email'      => true,
    	'emailTimeZone' => 'Asia/Shanghai',
        'slowConnection' => 6,
    ),
    
);

