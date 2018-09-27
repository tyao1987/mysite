<?php

$configProduction = require ROOT_PATH . '/module/Admin/config/config.production.php';

$configDevelopment =  array (
		'cmsHost' => 'admin.mysite.com',
		'cacheHosts' => array (
				array (
					'ip' => '127.0.0.1',
					'domain' => 'www.mysite.com'
				)
		),
		
);

return array_merge($configProduction,$configDevelopment);
