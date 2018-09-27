<?php
return array(
    "cache" => array(
        "name" => "memcached", 
        "options" => array(
            "ttl" => 3600, 
            "servers" => array(
                array("host" => "127.0.0.1", "port" => 11211),
            ),
        ),
    ), 
    "constantCache" => array(
        "name" => "memcached", 
        "options" => array(
            "ttl" => 43200, 
            "servers" => array(
                array("host" => "127.0.0.1", "port" => 11212),
            ),
        ),
    ), 
    "dynamicCache" => array(
        "name" => "memcached", 
        "options" => array(
            "ttl" => 259200, 
            "servers" => array(
                array("host" => "127.0.0.1", "port" => 11213)
            ),
        ),
    ), 
);