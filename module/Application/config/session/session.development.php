<?php
$sessionProduction = require_once ROOT_PATH .'/module/Application/config/session/session.production.php';
$sessionDevelopment = array(
	'handler' => 'memcache',
	'memcache' => array(
		'config' => array(
			'name' => 'memcached',
			'options' => array(
				'ttl' => 604800,
				'servers' => array(
					array(
						'host' => '127.0.0.1',
						'port' => 11211
					),
				),
			),
		),
	),
);

return array_merge($sessionProduction,$sessionDevelopment);