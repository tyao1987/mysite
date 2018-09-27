<?php
$configProduction = require ROOT_PATH . '/module/Application/config/config.production.php';

$configDevelopment = array(
	'writableDir' => array(
		'base'            => '',
		'dataCache'       => ROOT_PATH . '/data/data-cache/',
		'articles'        => '/var/www/articles/',
		'captcha'         => ROOT_PATH . '/public/images/captcha/',
		'log'             => '/var/www/log/mysite/',
		'styles'          => ROOT_PATH . '/public/styles/',
	),
	'errorReport'            => 'tyao1987@163.com',
	'imageServer'            => 'http://www.mysite.com/images/',
	'productImageServer'     => 'http://mysite',
);
return array_merge($configProduction,$configDevelopment);

