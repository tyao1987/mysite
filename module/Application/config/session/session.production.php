<?php
return array(
	'handler' => 'memcache',
	'memcache' => array(
		'config' => array(
			'name' => 'memcached',
			'options' => array(
				'ttl' => 3600,
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