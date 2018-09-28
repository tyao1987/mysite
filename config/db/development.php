<?php
$dbProduction = require ROOT_PATH . '/config/db/production.php';
$dbDevelopment = array(
    "cmsdb" => array(
        "host"        => "127.0.0.1", 
        "dbname"      => "cms", 
        "charset"     => "utf8", 
        "username"    => "root", 
        "password"    => "123456"
    ), 
	"userdb" => array(
		"host"        => "127.0.0.1", 
        "dbname"      => "user", 
        "charset"     => "utf8", 
        "username"    => "root", 
        "password"    => "123456"
	),
	
);
return array_merge($dbProduction,$dbDevelopment);
