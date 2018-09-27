<?php
$cacheProduction = require ROOT_PATH . '/config/cache.production.php';
$cacheDevelopment =  array(
    "cache" => array(
        "name" => "memcached", 
        "options" => array(
            "ttl" => 3600, 
        	"lib_options" => array(
        		"libketama_compatible" => true,
        		"connect_timeout" => 10,
        		"server_failure_limit" => 1,
        		"retry_timeout" => 1,
        	),
            "servers" => array(
                array("host" => "127.0.0.1", "port" => 11211),
            ),
        ),
    ), 
    "constantCache" => array(
        "name" => "memcached", 
        "options" => array(
            "ttl" => 43200,
        	"lib_options" => array( 
        		"libketama_compatible" => true,
        		"connect_timeout" => 10,
        		"server_failure_limit" => 1,
        		"retry_timeout" => 1,
        	),
            "servers" => array(
                array("host" => "127.0.0.1", "port" => 11212),
            ),
        ),
    ), 
    "dynamicCache" => array(
        "name" => "memcached", 
        "options" => array(
            "ttl" => 259200,
        	"lib_options" => array(
        		"libketama_compatible" => true,
        		"connect_timeout" => 10,
        		"server_failure_limit" => 1,
        		"retry_timeout" => 1,
        	),
            "servers" => array(
                array("host" => "127.0.0.1", "port" => 11213)
            ),
        ),
    ), 
);
return array_merge($cacheProduction,$cacheDevelopment);